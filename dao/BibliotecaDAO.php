<?php

class BibliotecaDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function possui(int $usuarioId, int $produtoId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM biblioteca WHERE usuario_id = ? AND produto_id = ?');
        $stmt->execute([$usuarioId, $produtoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function idsDoUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT produto_id FROM biblioteca WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function adicionar(int $usuarioId, int $produtoId, string $origem): void
    {
        $this->pdo->prepare('INSERT INTO biblioteca (usuario_id, produto_id, origem) VALUES (?, ?, ?)')
            ->execute([$usuarioId, $produtoId, $origem]);
    }

    public function listarDoUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.produto_id, b.data_aquisicao, b.origem, pr.nome, pr.slug,
                    (SELECT i.caminho_url FROM produto_imagens i
                      WHERE i.produto_id = pr.id AND i.tipo = 'CAPA' AND i.ativo = 1
                      ORDER BY i.principal DESC, i.ordem, i.id LIMIT 1) AS capa,
                    it.id AS item_id, it.preco_unitario AS preco_pago, pg.metodo
               FROM biblioteca b
               JOIN produtos pr ON pr.id = b.produto_id
               LEFT JOIN pedido_itens it ON it.id = (
                    SELECT it2.id FROM pedido_itens it2
                      JOIN pedidos pe2 ON pe2.id = it2.pedido_id
                     WHERE pe2.usuario_id = b.usuario_id AND it2.produto_id = b.produto_id
                     ORDER BY it2.id DESC LIMIT 1)
               LEFT JOIN pagamentos pg ON pg.pedido_id = it.pedido_id
              WHERE b.usuario_id = ?
              ORDER BY b.data_aquisicao DESC, b.id DESC"
        );
        $stmt->execute([$usuarioId]);
        $linhas = $stmt->fetchAll();

        $categorias = [];
        $stmt = $this->pdo->prepare(
            'SELECT pc.produto_id, c.nome FROM produto_categoria pc
               JOIN categorias c ON c.id = pc.categoria_id
               JOIN biblioteca b ON b.produto_id = pc.produto_id
              WHERE b.usuario_id = ? ORDER BY c.nome'
        );
        $stmt->execute([$usuarioId]);
        foreach ($stmt->fetchAll() as $l) {
            $categorias[(int) $l['produto_id']][] = $l['nome'];
        }
        foreach ($linhas as &$linha) {
            $linha['categorias'] = $categorias[(int) $linha['produto_id']] ?? [];
        }
        unset($linha);
        return $linhas;
    }
}
