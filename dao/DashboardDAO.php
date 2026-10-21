<?php

/**
 * DAO do dashboard do administrador.
 * Tudo calculado por SQL a partir dos pedidos reais (inclusive os simulados).
 */
class DashboardDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    public function contagemJogos(): array
    {
        $l = $this->pdo->query(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(estoque > 5), 0)              AS em_estoque,
                    COALESCE(SUM(estoque BETWEEN 1 AND 5), 0)  AS estoque_baixo,
                    COALESCE(SUM(estoque = 0), 0)              AS esgotados
               FROM produtos
              WHERE deleted_at IS NULL'
        )->fetch();
        return array_map('intval', $l);
    }

    public function totalUsuarios(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM usuarios WHERE deleted_at IS NULL')->fetchColumn();
    }

    // Quantidade de pedidos concluídos e faturamento bruto
    public function vendas(): array
    {
        $l = $this->pdo->query(
            "SELECT COUNT(*) AS total, COALESCE(SUM(valor_total), 0) AS faturamento
               FROM pedidos WHERE status = 'CONCLUIDO'"
        )->fetch();
        return ['total' => (int) $l['total'], 'faturamento' => (float) $l['faturamento']];
    }

    // Últimos 30 dias, incluindo os dias sem venda (com zero)
    public function vendasPorDia(int $dias = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(data_criacao) AS dia, COUNT(*) AS pedidos, SUM(valor_total) AS total
               FROM pedidos
              WHERE status = 'CONCLUIDO' AND data_criacao >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              GROUP BY DATE(data_criacao)"
        );
        $stmt->execute([$dias - 1]);
        $porDia = [];
        foreach ($stmt->fetchAll() as $l) {
            $porDia[$l['dia']] = $l;
        }

        // gera a lista de dias a partir da data do próprio banco
        $hoje = new DateTime($this->pdo->query('SELECT CURDATE()')->fetchColumn());
        $resultado = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia = (clone $hoje)->modify("-{$i} day")->format('Y-m-d');
            $resultado[] = [
                'dia'     => $dia,
                'pedidos' => (int) ($porDia[$dia]['pedidos'] ?? 0),
                'total'   => (float) ($porDia[$dia]['total'] ?? 0),
            ];
        }
        return $resultado;
    }

    public function vendasPorMetodo(): array
    {
        $linhas = $this->pdo->query(
            "SELECT pg.metodo, COUNT(*) AS pedidos, SUM(pg.valor) AS total
               FROM pagamentos pg
               JOIN pedidos pe ON pe.id = pg.pedido_id
              WHERE pe.status = 'CONCLUIDO' AND pg.status = 'APROVADO'
              GROUP BY pg.metodo
              ORDER BY total DESC"
        )->fetchAll();
        return array_map(fn ($l) => ['metodo' => $l['metodo'], 'pedidos' => (int) $l['pedidos'], 'total' => (float) $l['total']], $linhas);
    }

    public function vendasPorCategoria(): array
    {
        $linhas = $this->pdo->query(
            "SELECT c.nome AS categoria, COUNT(*) AS quantidade, SUM(it.preco_unitario) AS receita
               FROM pedido_itens it
               JOIN pedidos pe            ON pe.id = it.pedido_id
               JOIN produto_categoria pc  ON pc.produto_id = it.produto_id
               JOIN categorias c          ON c.id = pc.categoria_id
              WHERE pe.status = 'CONCLUIDO'
              GROUP BY c.id, c.nome
              ORDER BY receita DESC"
        )->fetchAll();
        return array_map(fn ($l) => ['categoria' => $l['categoria'], 'quantidade' => (int) $l['quantidade'], 'receita' => (float) $l['receita']], $linhas);
    }

    public function topJogos(int $limite = 5): array
    {
        $linhas = $this->pdo->query(
            "SELECT pr.id, pr.nome, COUNT(*) AS quantidade, SUM(it.preco_unitario) AS receita,
                    (SELECT i.caminho_url FROM produto_imagens i
                      WHERE i.produto_id = pr.id AND i.tipo = 'CAPA' AND i.ativo = 1
                      ORDER BY i.principal DESC, i.ordem, i.id LIMIT 1) AS capa
               FROM pedido_itens it
               JOIN pedidos pe  ON pe.id = it.pedido_id
               JOIN produtos pr ON pr.id = it.produto_id
              WHERE pe.status = 'CONCLUIDO'
              GROUP BY pr.id, pr.nome
              ORDER BY quantidade DESC, receita DESC
              LIMIT " . (int) $limite
        )->fetchAll();
        return array_map(fn ($l) => [
            'id' => (int) $l['id'], 'nome' => $l['nome'], 'capa' => $l['capa'],
            'quantidade' => (int) $l['quantidade'], 'receita' => (float) $l['receita'],
        ], $linhas);
    }

    public function estoqueCritico(int $limite = 5): array
    {
        $linhas = $this->pdo->query(
            "SELECT pr.id, pr.nome, pr.estoque,
                    (SELECT i.caminho_url FROM produto_imagens i
                      WHERE i.produto_id = pr.id AND i.tipo = 'CAPA' AND i.ativo = 1
                      ORDER BY i.principal DESC, i.ordem, i.id LIMIT 1) AS capa
               FROM produtos pr
              WHERE pr.deleted_at IS NULL AND pr.ativo = 1 AND pr.estoque <= 5
              ORDER BY pr.estoque ASC, pr.nome ASC
              LIMIT " . (int) $limite
        )->fetchAll();
        return array_map(fn ($l) => ['id' => (int) $l['id'], 'nome' => $l['nome'], 'capa' => $l['capa'], 'estoque' => (int) $l['estoque']], $linhas);
    }
}
