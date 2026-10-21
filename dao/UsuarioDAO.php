<?php

class UsuarioDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function emailEmUso(string $email, int $ignorarId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ? AND id <> ?');
        $stmt->execute([$email, $ignorarId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(array $d): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, nome_exibicao, email, telefone, senha_hash, tipo_usuario, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['nome'], $d['nome_exibicao'] ?? $d['nome'], $d['email'], $d['telefone'] ?? null,
            $d['senha_hash'], $d['tipo_usuario'] ?? 'CLIENTE', $d['ativo'] ?? 1,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizarPerfil(int $id, string $nomeExibicao, ?string $telefone): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET nome_exibicao = ?, telefone = ? WHERE id = ?');
        $stmt->execute([$nomeExibicao, $telefone, $id]);
    }

    public function atualizarEmail(int $id, string $email): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET email = ? WHERE id = ?');
        $stmt->execute([$email, $id]);
    }

    public function atualizarSenha(int $id, string $senhaHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
        $stmt->execute([$senhaHash, $id]);
    }

    public function atualizarFoto(int $id, ?string $caminho): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET foto_perfil = ? WHERE id = ?');
        $stmt->execute([$caminho, $id]);
    }

    public function listar(string $busca = ''): array
    {
        $sql = 'SELECT u.*,
                       (SELECT COUNT(*) FROM pedidos p WHERE p.usuario_id = u.id AND p.status = \'CONCLUIDO\') AS total_pedidos
                  FROM usuarios u
                 WHERE u.deleted_at IS NULL';
        $params = [];
        if ($busca !== '') {
            $sql .= ' AND (LOWER(u.nome) LIKE ? OR LOWER(u.email) LIKE ?)';
            $termo = '%' . mb_strtolower($busca) . '%';
            $params = [$termo, $termo];
        }
        $stmt = $this->pdo->prepare($sql . ' ORDER BY u.id');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function atualizarAdmin(int $id, array $d): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET nome = ?, nome_exibicao = ?, email = ?, telefone = ?, tipo_usuario = ?, ativo = ? WHERE id = ?'
        );
        $stmt->execute([$d['nome'], $d['nome_exibicao'], $d['email'], $d['telefone'], $d['tipo_usuario'], $d['ativo'], $id]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET deleted_at = NOW(), ativo = 0 WHERE id = ?');
        $stmt->execute([$id]);
    }
}
