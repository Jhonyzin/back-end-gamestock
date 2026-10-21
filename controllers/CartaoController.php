<?php

/**
 * CONTROLLER de cartões: identificar o banco pelo BIN e
 * gerenciar os cartões salvos (formas de pagamento).
 * O número completo é usado só para validar e NUNCA é salvo nem devolvido.
 */
class CartaoController extends Controller
{
    // Banco padrão quando o prefixo não está na tabela
    private const BANCO_GENERICO = [
        'encontrado' => false,
        'banco'      => 'Banco não identificado',
        'banco_slug' => 'generico',
        'cor_hex'    => '#4b5563',
        'bandeira'   => null,
    ];

    // GET /cartoes/identificar?bin=424242
    public function identificar(Request $req): void
    {
        $bin = substr(Cartao::somenteDigitos((string) $req->query('bin', '')), 0, 6);
        if (strlen($bin) < 6) {
            throw new ErroApi('Informe os 6 primeiros dígitos do cartão.', 422);
        }
        Response::sucesso($this->bancoDoBin($bin));
    }

    // GET /formas-pagamento
    public function listar(Request $req): void
    {
        $linhas = (new FormaPagamentoDAO($this->pdo))->listar($req->usuario->id);
        Response::sucesso(array_map(fn ($l) => $this->formatar($l), $linhas));
    }

    // POST /formas-pagamento {numero, titular, validade, tipo, apelido?, padrao?}
    public function adicionar(Request $req): void
    {
        // valida e já descarta o número completo (sobra só bin6 e últimos 4)
        $cartao = Cartao::validar($req->todos(), false);

        $tipo = $req->input('tipo', 'CARTAO_CREDITO');
        if (!in_array($tipo, ['CARTAO_CREDITO', 'CARTAO_DEBITO'], true)) {
            throw new ErroApi('Tipo de cartão inválido.', 422);
        }

        $dao = new FormaPagamentoDAO($this->pdo);
        $primeiro = $dao->contar($req->usuario->id) === 0;
        $padrao = $primeiro || Validador::booleano($req->input('padrao'));

        $id = $dao->criar($req->usuario->id, $cartao + [
            'tipo'    => $tipo,
            'apelido' => Validador::texto($req->input('apelido'), 60, 'apelido'),
            'emissor' => $this->bancoDoBin($cartao['bin6'])['banco'],
            'padrao'  => 0,
        ]);
        if ($padrao) {
            $dao->tornarPadrao($id, $req->usuario->id);
        }

        Response::sucesso($this->formatar($dao->buscar($id, $req->usuario->id)), 201);
    }

    // PUT /formas-pagamento/{id}/padrao
    public function tornarPadrao(Request $req): void
    {
        $id = $this->idDaRota($req);
        $dao = new FormaPagamentoDAO($this->pdo);
        if (!$dao->buscar($id, $req->usuario->id)) {
            throw new ErroApi('Cartão não encontrado.', 404);
        }
        $dao->tornarPadrao($id, $req->usuario->id);
        Response::sucesso(['mensagem' => 'Cartão definido como padrão.']);
    }

    // DELETE /formas-pagamento/{id}
    public function remover(Request $req): void
    {
        $id = $this->idDaRota($req);
        $dao = new FormaPagamentoDAO($this->pdo);
        if (!$dao->buscar($id, $req->usuario->id)) {
            throw new ErroApi('Cartão não encontrado.', 404);
        }
        $dao->remover($id, $req->usuario->id);
        $dao->garantirPadrao($req->usuario->id);
        Response::sucesso(['mensagem' => 'Cartão removido.']);
    }

    // Busca o banco na tabela bins_cartao (ou devolve o genérico)
    private function bancoDoBin(string $bin): array
    {
        $banco = (new BinDAO($this->pdo))->identificar($bin);
        if (!$banco) {
            return self::BANCO_GENERICO;
        }
        return ['encontrado' => true] + $banco;
    }

    private function formatar(array $linha): array
    {
        $forma = FormaPagamento::deLinha($linha);
        $banco = $this->bancoDoBin($linha['bin6']);
        $forma->banco = ['nome' => $banco['banco'], 'slug' => $banco['banco_slug'], 'cor_hex' => $banco['cor_hex']];
        return $forma->toArray();
    }
}
