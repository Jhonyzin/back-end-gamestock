<?php

abstract class CadastroSimplesDAO
{
    protected string $tabela;
    protected string $tabelaLigacao;
    protected string $colunaLigacao;

    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar(bool $somenteAtivos): array
    {
        $sql = "SELECT t.id, t.nome, t.ativo,
                       (SELECT COUNT(*) FROM {$this->tabelaLigacao} l
                          JOIN produtos p ON p.id = l.produto_id
                         WHERE l.{$this->colunaLigacao} = t.id AND p.ativo = 1 AND p.deleted_at IS NULL) AS total_jogos
                  FROM {$this->tabela} t";
        if ($somenteAtivos) {
            $sql .= ' WHERE t.ativo = 1';
        }
        $sql .= ' ORDER BY t.ativo DESC, t.nome';
        return array_map(fn ($l) => [
            'id'          => (int) $l['id'],
            'nome'        => $l['nome'],
            'ativo'       => (bool) $l['ativo'],
            'total_jogos' => (int) $l['total_jogos'],
        ], $this->pdo->query($sql)->fetchAll());
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tabela} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function nomeEmUso(string $nome, int $ignorarId = 0): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->tabela} WHERE nome = ? AND id <> ?");
        $stmt->execute([$nome, $ignorarId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(string $nome, int $ativo): int
    {
        $this->pdo->prepare("INSERT INTO {$this->tabela} (nome, ativo) VALUES (?, ?)")->execute([$nome, $ativo]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, string $nome, int $ativo): void
    {
        $this->pdo->prepare("UPDATE {$this->tabela} SET nome = ?, ativo = ? WHERE id = ?")->execute([$nome, $ativo, $id]);
    }

    public function emUso(int $id): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->tabelaLigacao} WHERE {$this->colunaLigacao} = ?");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    public function excluir(int $id): void
    {
        $this->pdo->prepare("DELETE FROM {$this->tabela} WHERE id = ?")->execute([$id]);
    }

    public function todosExistem(array $ids): bool
    {
        if (!$ids) {
            return true;
        }
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->tabela} WHERE id IN ({$marcadores})");
        $stmt->execute($ids);
        return (int) $stmt->fetchColumn() === count($ids);
    }
}
