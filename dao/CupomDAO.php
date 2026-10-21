<?php

class CupomDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarValido(string $codigo): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, codigo, percentual_desconto FROM cupons
              WHERE codigo = ? AND ativo = 1 AND (validade IS NULL OR validade >= CURRENT_DATE)'
        );
        $stmt->execute([strtoupper(trim($codigo))]);
        return $stmt->fetch() ?: null;
    }
}
