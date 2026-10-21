<?php

class DesejoDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function idsDoUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT produto_id FROM wishlist WHERE usuario_id = ? ORDER BY data_adicionado DESC, id DESC');
        $stmt->execute([$usuarioId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function adicionar(int $usuarioId, int $produtoId): void
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM wishlist WHERE usuario_id = ? AND produto_id = ?');
        $stmt->execute([$usuarioId, $produtoId]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->pdo->prepare('INSERT INTO wishlist (usuario_id, produto_id) VALUES (?, ?)')->execute([$usuarioId, $produtoId]);
        }
    }

    public function remover(int $usuarioId, int $produtoId): void
    {
        $this->pdo->prepare('DELETE FROM wishlist WHERE usuario_id = ? AND produto_id = ?')->execute([$usuarioId, $produtoId]);
    }
}
