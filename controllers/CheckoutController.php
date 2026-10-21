<?php

class CheckoutController extends Controller
{
    private const METODOS = ['PIX', 'CARTAO_CREDITO', 'CARTAO_DEBITO', 'BOLETO'];

    private const ALFABETO_CODIGO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function finalizar(Request $req): void
    {
        $usuario = $req->usuario;
        $ids = Validador::listaIds($req->input('itens', []));
        if (!$ids) {
            throw new ErroApi('Seu carrinho está vazio.', 422);
        }
        sort($ids);

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
            $itens = [];
            // confere os jogos
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

            // total com cupom
            $subtotal = round(array_sum(array_column($itens, 'preco')), 2);
            $percentual = $cupom ? (float) $cupom['percentual_desconto'] : 0;
            $desconto = round($subtotal * $percentual / 100, 2);
            $total = round($subtotal - $desconto, 2);

            // pagamento
            $pagamento = $total > 0 ? $this->simularPagamento($req, $usuario, $total) : null;

            // cria o pedido
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
                if ($pagamento['salvar_cartao']) {
                    $formaDAO = new FormaPagamentoDAO($this->pdo);
                    $primeiro = $formaDAO->contar($usuario->id) === 0;
                    $pagamento['forma_pagamento_id'] = $formaDAO->criar($usuario->id, $pagamento['salvar_cartao'] + ['padrao' => $primeiro ? 1 : 0]);
                }
                $pedidoDAO->registrarPagamento($pagamento + ['pedido_id' => $pedidoId]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
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

        if ($metodo === 'CARTAO_CREDITO') {
            $pagamento['parcelas'] = Validador::inteiro($req->input('parcelas', 1), 'parcelas', 1, 12);
        }

        $formaId = (int) $req->input('forma_pagamento_id', 0);
        if ($formaId > 0) {
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

        // cartao final 0000 e recusado
        if ($pagamento['ultimos4'] === '0000') {
            throw new ErroApi('Pagamento recusado (simulação). Tente outro cartão.', 422);
        }
        return $pagamento;
    }

    // codigo GS-XXXX-XXXX-XXXX
    private function gerarCodigoUnico(PedidoDAO $dao): string
    {
        do {
            $blocos = [];
            for ($b = 0; $b < 3; $b++) {
                $bloco = '';
                foreach (str_split(random_bytes(4)) as $byte) {
                    $bloco .= self::ALFABETO_CODIGO[ord($byte) % 32];
                }
                $blocos[] = $bloco;
            }
            $codigo = 'GS-' . implode('-', $blocos);
        } while ($dao->codigoExiste($codigo));

        return $codigo;
    }
}
