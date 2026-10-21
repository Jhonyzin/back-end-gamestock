<?php

/**
 * DAO de avaliações (nota 1 a 5 + comentário). Uma por usuário e jogo.
 */
class AvaliacaoDAO
{
    private const SELECT_BASE = "
        SELECT a.*, COALESCE(u.nome_exibicao, u.nome) AS autor, u.foto_perfil AS foto_autor, pr.nome AS produto_nome
          FROM avaliacoes a
          JOIN usuarios u  ON u.id = a.usuario_id
          JOIN produtos pr ON pr.id = a.produto_id
    ";

    public function __construct(private PDO $pdo)
    {
    }

    public function listarDoJogo(int $produtoId): array
    {
        $stmt = $this->pdo->prepare(self::SELECT_BASE . ' WHERE a.produto_id = ? ORDER BY a.data_atualizacao DESC, a.id DESC');
        $stmt->execute([$produtoId]);
        return array_map(fn ($l) => Avaliacao::deLinha($l), $stmt->fetchAll());
    }

    public function listarTodas(): array
    {
        $stmt = $this->pdo->query(self::SELECT_BASE . ' ORDER BY a.data_atualizacao DESC, a.id DESC');
        return array_map(fn ($l) => Avaliacao::deLinha($l), $stmt->fetchAll());
    }

    public function buscarDoUsuario(int $usuarioId, int $produtoId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM avaliacoes WHERE usuario_id = ? AND produto_id = ?');
        $stmt->execute([$usuarioId, $produtoId]);
        return $stmt->fetch() ?: null;
    }

    // Se já existe, atualiza; senão, cria (1 avaliação por jogo)
    public function salvar(int $usuarioId, int $produtoId, int $nota, ?string $comentario): void
    {
        if ($this->buscarDoUsuario($usuarioId, $produtoId)) {
            $this->pdo->prepare('UPDATE avaliacoes SET nota = ?, comentario = ? WHERE usuario_id = ? AND produto_id = ?')
                ->execute([$nota, $comentario, $usuarioId, $produtoId]);
        } else {
            $this->pdo->prepare('INSERT INTO avaliacoes (usuario_id, produto_id, nota, comentario) VALUES (?, ?, ?, ?)')
                ->execute([$usuarioId, $produtoId, $nota, $comentario]);
        }
    }

    // Mapa produto_id => {nota, comentario} das avaliações do usuário
    public function mapaDoUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT produto_id, nota, comentario FROM avaliacoes WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $mapa = [];
        foreach ($stmt->fetchAll() as $l) {
            $mapa[(int) $l['produto_id']] = ['nota' => (int) $l['nota'], 'comentario' => $l['comentario']];
        }
        return $mapa;
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM avaliacoes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
