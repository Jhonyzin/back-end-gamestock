<?php

class MidiaDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function imagens(int $produtoId, array $tipos = [], int $limite = 0): array
    {
        $sql = 'SELECT id, caminho_url, tipo, ordem, principal FROM produto_imagens WHERE produto_id = ? AND ativo = 1';
        $params = [$produtoId];
        if ($tipos) {
            $sql .= ' AND tipo IN (' . implode(',', array_fill(0, count($tipos), '?')) . ')';
            array_push($params, ...$tipos);
        }
        $sql .= " ORDER BY tipo = 'CAPA' DESC, principal DESC, ordem, id";
        if ($limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map(fn ($i) => [
            'id'          => (int) $i['id'],
            'caminho_url' => $i['caminho_url'],
            'tipo'        => $i['tipo'],
            'ordem'       => (int) $i['ordem'],
            'principal'   => (bool) $i['principal'],
        ], $stmt->fetchAll());
    }

    public function buscarImagem(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM produto_imagens WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function proximaOrdem(int $produtoId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM produto_imagens WHERE produto_id = ?');
        $stmt->execute([$produtoId]);
        return (int) $stmt->fetchColumn();
    }

    public function adicionarImagem(int $produtoId, string $url, string $tipo, int $principal, int $ordem): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO produto_imagens (produto_id, caminho_url, tipo, ordem, principal, ativo) VALUES (?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$produtoId, $url, $tipo, $ordem, $principal]);
        return (int) $this->pdo->lastInsertId();
    }

    public function desmarcarCapasPrincipais(int $produtoId): void
    {
        $this->pdo->prepare("UPDATE produto_imagens SET principal = 0 WHERE produto_id = ? AND tipo = 'CAPA'")
            ->execute([$produtoId]);
    }

    public function atualizarImagem(int $id, int $ordem, int $principal): void
    {
        $this->pdo->prepare('UPDATE produto_imagens SET ordem = ?, principal = ? WHERE id = ?')
            ->execute([$ordem, $principal, $id]);
    }

    public function removerImagem(int $id): void
    {
        $this->pdo->prepare('DELETE FROM produto_imagens WHERE id = ?')->execute([$id]);
    }

    public function videos(int $produtoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, titulo, url, tipo FROM produto_videos WHERE produto_id = ? AND ativo = 1 ORDER BY principal DESC, ordem, id'
        );
        $stmt->execute([$produtoId]);
        return array_map(fn ($v) => ['id' => (int) $v['id'], 'titulo' => $v['titulo'], 'url' => $v['url'], 'tipo' => $v['tipo']],
            $stmt->fetchAll());
    }

    public function adicionarVideo(int $produtoId, ?string $titulo, string $url, string $tipo): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO produto_videos (produto_id, titulo, url, tipo, ordem, principal, ativo) VALUES (?, ?, ?, ?, 0, 1, 1)'
        );
        $stmt->execute([$produtoId, $titulo, $url, $tipo]);
        return (int) $this->pdo->lastInsertId();
    }

    public function removerVideo(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM produto_videos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
