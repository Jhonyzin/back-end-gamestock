<?php

class ConfiguracaoDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar(): array
    {
        return $this->pdo->query('SELECT chave, valor, descricao FROM configuracoes_site ORDER BY chave')->fetchAll();
    }

    public function mapa(): array
    {
        $mapa = [];
        foreach ($this->listar() as $l) {
            $mapa[$l['chave']] = $l['valor'];
        }
        return $mapa;
    }

    public function atualizar(string $chave, ?string $valor): bool
    {
        $stmt = $this->pdo->prepare('UPDATE configuracoes_site SET valor = ? WHERE chave = ?');
        $stmt->execute([$valor, $chave]);
        return $stmt->rowCount() > 0;
    }

    public function existe(string $chave): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM configuracoes_site WHERE chave = ?');
        $stmt->execute([$chave]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
