<?php

class AdminPedidoController extends Controller
{
    public function listar(Request $req): void
    {
        $dao = new PedidoDAO($this->pdo);
        $pedidos = [];
        foreach ($dao->listarTodos() as $linha) {
            $pedidos[(int) $linha['id']] = Pedido::deLinha($linha);
        }
        foreach ($dao->itensDosPedidos(array_keys($pedidos)) as $item) {
            $pedidos[(int) $item['pedido_id']]->adicionarItem($item);
        }
        Response::sucesso(array_values(array_map(fn (Pedido $p) => $p->toArray(), $pedidos)));
    }
}
