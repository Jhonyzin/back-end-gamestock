<?php

/**
 * CONTROLLER de pedidos no painel admin (somente leitura).
 */
class AdminPedidoController extends Controller
{
    // GET /admin/pedidos
    public function listar(Request $req): void
    {
        $dao = new PedidoDAO($this->pdo);
        $pedidos = [];
        foreach ($dao->listarTodos() as $linha) {
            $pedidos[(int) $linha['id']] = Pedido::deLinha($linha);
        }
        foreach ($dao->itensDosPedidos(array_keys($pedidos)) as $item) {
            $pedidos[(int) $item['pedido_id']]->adicionarItem($item);   // código mascarado
        }
        Response::sucesso(array_values(array_map(fn (Pedido $p) => $p->toArray(), $pedidos)));
    }
}
