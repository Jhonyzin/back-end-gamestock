<?php

/**
 * DAO de cupons de desconto.
 */
class CupomDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    // Cupom ativo e dentro da validade (validade NULL = sem prazo)
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
