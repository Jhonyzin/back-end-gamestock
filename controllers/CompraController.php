<?php

/**
 * CONTROLLER da área do comprador: biblioteca, histórico e códigos de resgate.
 */
class CompraController extends Controller
{
    // GET /biblioteca
    public function biblioteca(Request $req): void
    {
        $linhas = (new BibliotecaDAO($this->pdo))->listarDoUsuario($req->usuario->id);
        Response::sucesso(array_map(fn ($l) => [
            'produto_id'     => (int) $l['produto_id'],
            'nome'           => $l['nome'],
            'slug'           => $l['slug'],
            'capa'           => $l['capa'],
            'categorias'     => $l['categorias'],
            'data_aquisicao' => $l['data_aquisicao'],
            'origem'         => $l['origem'],
            'item_id'        => $l['item_id'] ? (int) $l['item_id'] : null,
            'preco_pago'     => $l['preco_pago'] !== null ? (float) $l['preco_pago'] : null,
            'metodo'         => $l['metodo'],
        ], $linhas));
    }

    // GET /compras (códigos mascarados + avaliação de cada jogo)
    public function historico(Request $req): void
    {
        $pedidoDAO = new PedidoDAO($this->pdo);
        $linhas = $pedidoDAO->listarDoUsuario($req->usuario->id);
        $avaliacoes = (new AvaliacaoDAO($this->pdo))->mapaDoUsuario($req->usuario->id);

        $pedidos = [];
        foreach ($linhas as $linha) {
            $pedidos[(int) $linha['id']] = Pedido::deLinha($linha);
        }
        foreach ($pedidoDAO->itensDosPedidos(array_keys($pedidos)) as $item) {
            $pedidos[(int) $item['pedido_id']]->adicionarItem($item, $avaliacoes[(int) $item['produto_id']] ?? null);
        }

        Response::sucesso(array_values(array_map(fn (Pedido $p) => $p->toArray(), $pedidos)));
    }

    // GET /compras/itens/{itemId}/codigo (só o dono vê o código completo)
    public function codigo(Request $req): void
    {
        $item = (new PedidoDAO($this->pdo))->buscarItem($this->idDaRota($req, 'itemId'));
        if (!$item) {
            throw new ErroApi('Item não encontrado.', 404);
        }
        if ((int) $item['usuario_id'] !== $req->usuario->id) {
            throw new ErroApi('Este código pertence a outra conta.', 403);
        }
        Response::sucesso([
            'item_id'        => (int) $item['id'],
            'produto_id'     => (int) $item['produto_id'],
            'nome'           => $item['nome'],
            'codigo_resgate' => $item['codigo_resgate'],
        ]);
    }
}
