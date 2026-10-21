<?php

/**
 * CONTROLLER da compra simulada (POST /checkout).
 * Tudo acontece dentro de UMA transação: se qualquer passo falhar,
 * o ROLLBACK desfaz tudo (nenhum pedido "pela metade").
 */
class CheckoutController extends Controller
{
    private const METODOS = ['PIX', 'CARTAO_CREDITO', 'CARTAO_DEBITO', 'BOLETO'];

    // Letras e números sem 0, O, 1 e I (evita confusão na hora de digitar)
    private const ALFABETO_CODIGO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Entrada: { itens: [jogoId...], cupom?, metodo, parcelas?,
     *            forma_pagamento_id? | cartao?: {numero, titular, validade, cvv, salvar?} }
     */
    public function finalizar(Request $req): void
    {
        $usuario = $req->usuario;
        $ids = Validador::listaIds($req->input('itens', []));
        if (!$ids) {
            throw new ErroApi('Seu carrinho está vazio.', 422);
        }
        sort($ids);   // trava as linhas sempre na mesma ordem (evita deadlock)

        // Cupom (opcional)
        $cupom = null;
        $codigoCupom = (string) $req->input('cupom', '');
        if ($codigoCupom !== '') {
            $cupom = (new CupomDAO($this->pdo))->buscarValido($codigoCupom);
            if (!$cupom) {
                throw new ErroApi(CupomController::MENSAGEM_INVALIDO, 422);
            }
        }

        $jogoDAO       = new JogoDAO($this->pdo);
        $bibliotecaDAO = new BibliotecaDAO($this->pdo);
        $pedidoDAO     = new PedidoDAO($this->pdo);

        $this->pdo->beginTransaction();
        try {
            // 1) e 2) Busca os jogos (preço vem do BANCO, nunca do navegador) e valida
            $itens = [];
            foreach ($ids as $id) {
                $jogo = $jogoDAO->travarParaCompra($id);
                if (!$jogo || $jogo['deleted_at'] !== null || !(int) $jogo['ativo']) {
                    throw new ErroApi('Um dos jogos do carrinho não está mais disponível.', 422);
                }
                if ((int) $jogo['estoque'] <= 0) {
                    throw new ErroApi("{$jogo['nome']} está esgotado.", 409);
                }
                if ($bibliotecaDAO->possui($usuario->id, $id)) {
                    throw new ErroApi("Você já possui {$jogo['nome']}. Remova-o do carrinho.", 409);
                }
                $preco = $jogoDAO->precoAtual($id);
                $itens[] = [
                    'id'       => $id,
                    'nome'     => $jogo['nome'],
                    'preco'    => (float) $preco['preco_final'],
                    'gratuito' => (float) $preco['preco_base'] == 0.0,
                ];
            }

            // 3) Valores: cupom aplicado sobre o total já com promoções
            $subtotal = round(array_sum(array_column($itens, 'preco')), 2);
            $percentual = $cupom ? (float) $cupom['percentual_desconto'] : 0;
            $desconto = round($subtotal * $percentual / 100, 2);
            $total = round($subtotal - $desconto, 2);

            // 4) Simula o pagamento (total R$ 0,00 pula esta etapa)
            $pagamento = $total > 0 ? $this->simularPagamento($req, $usuario, $total) : null;

            // 5) Cria pedido, itens (com código), biblioteca e baixa o estoque
            $pedidoId = $pedidoDAO->criar($usuario->id, $subtotal, $desconto, $total, $cupom ? (int) $cupom['id'] : null);

            $itensResposta = [];
            foreach ($itens as $item) {
                $codigo = $this->gerarCodigoUnico($pedidoDAO);
                $itemId = $pedidoDAO->adicionarItem($pedidoId, $item['id'], $item['preco'], $codigo);
                $bibliotecaDAO->adicionar($usuario->id, $item['id'], $item['gratuito'] ? 'GRATUITO' : 'COMPRA');
                if (!$jogoDAO->baixarEstoque($item['id'])) {
                    throw new ErroApi("{$item['nome']} acabou de esgotar.", 409);
                }
                $itensResposta[] = [
                    'item_id'        => $itemId,
                    'produto_id'     => $item['id'],
                    'nome'           => $item['nome'],
                    'preco_unitario' => $item['preco'],
                    'codigo_resgate' => $codigo,
                ];
            }

            if ($pagamento) {
                // salva o cartão (só bin6, últimos 4, bandeira, banco e validade)
                if ($pagamento['salvar_cartao']) {
                    $formaDAO = new FormaPagamentoDAO($this->pdo);
                    $primeiro = $formaDAO->contar($usuario->id) === 0;
                    $pagamento['forma_pagamento_id'] = $formaDAO->criar($usuario->id, $pagamento['salvar_cartao'] + ['padrao' => $primeiro ? 1 : 0]);
                }
                $pedidoDAO->registrarPagamento($pagamento + ['pedido_id' => $pedidoId]);
            }

            $this->pdo->commit();

        } catch (Throwable $e) {
            // 7) Qualquer erro: desfaz tudo
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        Response::sucesso([
            'pedido' => [
                'id'          => $pedidoId,
                'subtotal'    => $subtotal,
                'desconto'    => $desconto,
                'valor_total' => $total,
                'cupom'       => $cupom['codigo'] ?? null,
                'status'      => 'CONCLUIDO',
            ],
            'itens' => $itensResposta,
            'pagamento' => $pagamento ? [
                'metodo'            => $pagamento['metodo'],
                'parcelas'          => $pagamento['parcelas'],
                'valor_parcela'     => round($total / $pagamento['parcelas'], 2),
                'bandeira'          => $pagamento['bandeira'],
                'emissor'           => $pagamento['emissor'],
                'ultimos_4_digitos' => $pagamento['ultimos4'],
                'transacao'         => $pagamento['transacao'],
            ] : null,
        ], 201);
    }

    /**
     * Pagamento de mentirinha:
     * - PIX e Boleto: aprovados na hora.
     * - Cartão: valida Luhn, validade e CVV; final 0000 é RECUSADO.
     */
    private function simularPagamento(Request $req, Usuario $usuario, float $total): array
    {
        $metodo = strtoupper((string) $req->input('metodo', ''));
        if (!in_array($metodo, self::METODOS, true)) {
            throw new ErroApi('Escolha a forma de pagamento.', 422);
        }

        $pagamento = [
            'metodo'             => $metodo,
            'valor'              => $total,
            'parcelas'           => 1,
            'ultimos4'           => null,
            'bandeira'           => null,
            'emissor'            => null,
            'forma_pagamento_id' => null,
            'salvar_cartao'      => null,
            'transacao'          => 'SIM-' . strtoupper(bin2hex(random_bytes(6))),
        ];

        if ($metodo === 'PIX' || $metodo === 'BOLETO') {
            return $pagamento;
        }

        // Parcelas: 1 a 12x sem juros (só no crédito)
        if ($metodo === 'CARTAO_CREDITO') {
            $pagamento['parcelas'] = Validador::inteiro($req->input('parcelas', 1), 'parcelas', 1, 12);
        }

        $formaId = (int) $req->input('forma_pagamento_id', 0);
        if ($formaId > 0) {
            // Cartão salvo
            $forma = (new FormaPagamentoDAO($this->pdo))->buscar($formaId, $usuario->id);
            if (!$forma) {
                throw new ErroApi('Cartão salvo não encontrado.', 404);
            }
            if (!Cartao::validadeOk((int) $forma['validade_mes'], (int) $forma['validade_ano'])) {
                throw new ErroApi('Este cartão está vencido.', 422);
            }
            $pagamento['forma_pagamento_id'] = $formaId;
            $pagamento['ultimos4'] = $forma['ultimos_4_digitos'];
            $pagamento['bandeira'] = $forma['bandeira'];
            $pagamento['emissor']  = $forma['emissor'];
        } else {
            // Cartão novo digitado agora
            $dados = $req->input('cartao');
            if (!is_array($dados)) {
                throw new ErroApi('Informe os dados do cartão.', 422);
            }
            $cartao = Cartao::validar($dados, true);
            $banco = (new BinDAO($this->pdo))->identificar($cartao['bin6']);

            $pagamento['ultimos4'] = $cartao['ultimos4'];
            $pagamento['bandeira'] = $cartao['bandeira'];
            $pagamento['emissor']  = $banco['banco'] ?? null;

            if (Validador::booleano($dados['salvar'] ?? false)) {
                $pagamento['salvar_cartao'] = $cartao + [
                    'tipo'    => $metodo,
                    'apelido' => null,
                    'emissor' => $pagamento['emissor'],
                ];
            }
        }

        // Simulação de recusa para a apresentação
        if ($pagamento['ultimos4'] === '0000') {
            throw new ErroApi('Pagamento recusado (simulação). Tente outro cartão.', 422);
        }
        return $pagamento;
    }

    // Gera GS-XXXX-XXXX-XXXX com random_bytes e confere se não existe
    private function gerarCodigoUnico(PedidoDAO $dao): string
    {
        do {
            $blocos = [];
            for ($b = 0; $b < 3; $b++) {
                $bloco = '';
                foreach (str_split(random_bytes(4)) as $byte) {
                    $bloco .= self::ALFABETO_CODIGO[ord($byte) % 32];   // 256 / 32 = sem viés
                }
                $blocos[] = $bloco;
            }
            $codigo = 'GS-' . implode('-', $blocos);
        } while ($dao->codigoExiste($codigo));

        return $codigo;
    }
}
