<?php

/**
 * DAO de marcas (desenvolvedoras) e editoras — usadas nos selects do formulário de jogos.
 */
class EmpresaDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listarMarcas(): array
    {
        return $this->pdo->query('SELECT id, nome FROM marcas WHERE ativo = 1 ORDER BY nome')->fetchAll();
    }

    public function listarEditoras(): array
    {
        return $this->pdo->query('SELECT id, nome FROM editoras WHERE ativo = 1 ORDER BY nome')->fetchAll();
    }

    public function marcaExiste(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM marcas WHERE id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function editoraExiste(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM editoras WHERE id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
