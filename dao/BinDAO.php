<?php

/**
 * DAO da tabela bins_cartao (FICTÍCIA, só para a demonstração):
 * descobre o banco pelos primeiros dígitos do cartão.
 */
class BinDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    // Procura o prefixo MAIS LONGO que combina com o começo do número
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
