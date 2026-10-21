<?php

class SessaoDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function criar(int $usuarioId, string $tokenHash, int $dias): void
    {
        $expira = date('Y-m-d H:i:s', strtotime("+{$dias} days"));
        $stmt = $this->pdo->prepare('INSERT INTO sessoes (usuario_id, token_hash, expira_em) VALUES (?, ?, ?)');
        $stmt->execute([$usuarioId, $tokenHash, $expira]);
    }

    public function buscarUsuario(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*
               FROM sessoes s
               JOIN usuarios u ON u.id = s.usuario_id
              WHERE s.token_hash = ?
                AND s.expira_em > NOW()
                AND u.ativo = 1
                AND u.deleted_at IS NULL'
        );
        $stmt->execute([$tokenHash]);
        return $stmt->fetch() ?: null;
    }

    public function apagar(string $tokenHash): void
    {
        $this->pdo->prepare('DELETE FROM sessoes WHERE token_hash = ?')->execute([$tokenHash]);
    }

    public function apagarDoUsuario(int $usuarioId): void
    {
        $this->pdo->prepare('DELETE FROM sessoes WHERE usuario_id = ?')->execute([$usuarioId]);
    }

    public function limparExpiradas(): void
    {
        $this->pdo->exec('DELETE FROM sessoes WHERE expira_em <= NOW()');
    }
}
