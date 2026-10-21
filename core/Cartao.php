<?php

class Cartao
{
    public static function somenteDigitos(?string $texto): string
    {
        return preg_replace('/\D/', '', (string) $texto);
    }

    // confere se o numero do cartao e valido
    public static function luhn(string $numero): bool
    {
        $soma = 0;
        $dobrar = false;
        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $digito = (int) $numero[$i];
            if ($dobrar) {
                $digito *= 2;
                if ($digito > 9) {
                    $digito -= 9;
                }
            }
            $soma += $digito;
            $dobrar = !$dobrar;
        }
        return $numero !== '' && $soma % 10 === 0;
    }

    // descobre a bandeira
    public static function bandeira(string $numero): ?array
    {
        $prefixosElo = ['636368', '438935', '504175', '451416', '636297', '5067', '4576', '4011', '6277'];
        foreach ($prefixosElo as $prefixo) {
            if (str_starts_with($numero, $prefixo)) {
                return ['nome' => 'Elo', 'slug' => 'elo'];
            }
        }
        if (str_starts_with($numero, '606282') || str_starts_with($numero, '3841')) {
            return ['nome' => 'Hipercard', 'slug' => 'hipercard'];
        }
        if (preg_match('/^3[47]/', $numero)) {
            return ['nome' => 'American Express', 'slug' => 'amex'];
        }
        if (preg_match('/^4/', $numero)) {
            return ['nome' => 'Visa', 'slug' => 'visa'];
        }
        if (preg_match('/^5[1-5]/', $numero) || preg_match('/^2(22[1-9]|2[3-9]\d|[3-6]\d\d|7[01]\d|720)/', $numero)) {
            return ['nome' => 'Mastercard', 'slug' => 'mastercard'];
        }
        if (preg_match('/^3(0[0-5]|[68])/', $numero)) {
            return ['nome' => 'Diners', 'slug' => 'diners'];
        }
        if (preg_match('/^(6011|65|64[4-9])/', $numero)) {
            return ['nome' => 'Discover', 'slug' => 'discover'];
        }
        return null;
    }

    public static function lerValidade(?string $texto): array
    {
        if (!preg_match('/^(\d{2})\s*\/\s*(\d{2})$/', trim((string) $texto), $m)) {
            throw new ErroApi('Validade inválida. Use MM/AA.', 422);
        }
        $mes = (int) $m[1];
        $ano = 2000 + (int) $m[2];
        if ($mes < 1 || $mes > 12) {
            throw new ErroApi('Mês da validade inválido.', 422);
        }
        if (!self::validadeOk($mes, $ano)) {
            throw new ErroApi('Cartão vencido.', 422);
        }
        return [$mes, $ano];
    }

    public static function validadeOk(int $mes, int $ano): bool
    {
        $anoAtual = (int) date('Y');
        $mesAtual = (int) date('n');
        return $ano > $anoAtual || ($ano === $anoAtual && $mes >= $mesAtual);
    }

    public static function validar(array $dados, bool $exigirCvv): array
    {
        $numero = self::somenteDigitos($dados['numero'] ?? '');
        $bandeira = self::bandeira($numero);

        if (strlen($numero) < 13 || strlen($numero) > 19) {
            throw new ErroApi('Número do cartão inválido.', 422);
        }
        if (!self::luhn($numero)) {
            throw new ErroApi('Número do cartão inválido (não passou na verificação).', 422);
        }
        if (!$bandeira) {
            throw new ErroApi('Bandeira do cartão não reconhecida.', 422);
        }
        if ($bandeira['slug'] === 'amex' && strlen($numero) !== 15) {
            throw new ErroApi('Cartão American Express deve ter 15 dígitos.', 422);
        }

        $titular = trim((string) ($dados['titular'] ?? ''));
        if (mb_strlen($titular) < 3 || mb_strlen($titular) > 120) {
            throw new ErroApi('Informe o nome impresso no cartão.', 422);
        }

        [$mes, $ano] = self::lerValidade($dados['validade'] ?? '');

        if ($exigirCvv) {
            $cvv = self::somenteDigitos($dados['cvv'] ?? '');
            $tamanho = $bandeira['slug'] === 'amex' ? 4 : 3;
            if (strlen($cvv) !== $tamanho) {
                throw new ErroApi("CVV inválido: use {$tamanho} dígitos.", 422);
            }
        }

        return [
            'bin6'      => substr($numero, 0, 6),
            'ultimos4'  => substr($numero, -4),
            'bandeira'  => $bandeira['nome'],
            'titular'   => mb_strtoupper($titular),
            'mes'       => $mes,
            'ano'       => $ano,
        ];
    }
}
