<?php

/**
 * CONTROLLER do dashboard do administrador (números reais do banco).
 */
class AdminDashboardController extends Controller
{
    // GET /admin/dashboard
    public function resumo(Request $req): void
    {
        $dao = new DashboardDAO($this->pdo);
        $jogos = $dao->contagemJogos();
        $vendas = $dao->vendas();

        // últimos 10 pedidos com itens
        $pedidoDAO = new PedidoDAO($this->pdo);
        $pedidos = [];
        foreach ($pedidoDAO->listarTodos(10) as $linha) {
            $pedidos[(int) $linha['id']] = Pedido::deLinha($linha);
        }
        foreach ($pedidoDAO->itensDosPedidos(array_keys($pedidos)) as $item) {
            $pedidos[(int) $item['pedido_id']]->adicionarItem($item);
        }

        Response::sucesso([
            'total_jogos'          => $jogos['total'],
            'jogos_em_estoque'     => $jogos['em_estoque'],
            'jogos_estoque_baixo'  => $jogos['estoque_baixo'],
            'jogos_esgotados'      => $jogos['esgotados'],
            'total_usuarios'       => $dao->totalUsuarios(),
            'total_vendas'         => $vendas['total'],
            'faturamento'          => $vendas['faturamento'],
            'ticket_medio'         => $vendas['total'] ? round($vendas['faturamento'] / $vendas['total'], 2) : 0,
            'vendas_por_dia'       => $dao->vendasPorDia(30),
            'vendas_por_metodo'    => $dao->vendasPorMetodo(),
            'vendas_por_categoria' => $dao->vendasPorCategoria(),
            'top_jogos'            => $dao->topJogos(5),
            'estoque_critico'      => $dao->estoqueCritico(5),
            'ultimos_pedidos'      => array_values(array_map(fn (Pedido $p) => $p->toArray(), $pedidos)),
        ]);
    }
}
