<?php

class BinDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function identificar(string $bin): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT banco, banco_slug, cor_hex, bandeira FROM bins_cartao
              WHERE ? LIKE CONCAT(prefixo, '%')
              ORDER BY LENGTH(prefixo) DESC
              LIMIT 1"
        );
        $stmt->execute([$bin]);
        return $stmt->fetch() ?: null;
    }
}
