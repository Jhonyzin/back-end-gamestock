<?php

/**
 * DAO da lista de desejos (tabela "wishlist").
 */
class DesejoDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    // ids dos jogos desejados, do mais recente para o mais antigo
    public function idsDoUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT produto_id FROM wishlist WHERE usuario_id = ? ORDER BY data_adicionado DESC, id DESC');
        $stmt->execute([$usuarioId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // INSERT IGNORE: se já estiver na lista, não faz nada
    public function adicionar(int $usuarioId, int $produtoId): void
    {
        $this->pdo->prepare('INSERT IGNORE INTO wishlist (usuario_id, produto_id) VALUES (?, ?)')->execute([$usuarioId, $produtoId]);
    }

    public function remover(int $usuarioId, int $produtoId): void
    {
        $this->pdo->prepare('DELETE FROM wishlist WHERE usuario_id = ? AND produto_id = ?')->execute([$usuarioId, $produtoId]);
    }
}
