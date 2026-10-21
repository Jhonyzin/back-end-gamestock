<?php

/**
 * Validações feitas SEMPRE no servidor (o front também valida,
 * mas não podemos confiar só nele). Em caso de erro lança ErroApi 422.
 */
class Validador
{
    public static function obrigatorios(array $dados, array $campos): void
    {
        foreach ($campos as $campo => $rotulo) {
            $valor = $dados[$campo] ?? null;
            if ($valor === null || (is_string($valor) && trim($valor) === '') || $valor === []) {
                throw new ErroApi("Preencha o campo: {$rotulo}.", 422);
            }
        }
    }

    public static function email(?string $email): string
    {
        $email = strtolower(trim((string) $email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            throw new ErroApi('E-mail inválido.', 422);
        }
        return $email;
    }

    public static function senha(?string $senha): string
    {
        $senha = (string) $senha;
        if (strlen($senha) < 6) {
            throw new ErroApi('A senha deve ter pelo menos 6 caracteres.', 422);
        }
        if (strlen($senha) > 72) {
            throw new ErroApi('A senha deve ter no máximo 72 caracteres.', 422);
        }
        return $senha;
    }

    // Texto opcional: devolve null se vazio; erro se passar do limite
    public static function texto(mixed $valor, int $maximo, string $rotulo): ?string
    {
        $valor = trim((string) ($valor ?? ''));
        if ($valor === '') {
            return null;
        }
        if (mb_strlen($valor) > $maximo) {
            throw new ErroApi("O campo {$rotulo} aceita no máximo {$maximo} caracteres.", 422);
        }
        return $valor;
    }

    public static function inteiro(mixed $valor, string $rotulo, int $minimo = PHP_INT_MIN, int $maximo = PHP_INT_MAX): int
    {
        if (filter_var($valor, FILTER_VALIDATE_INT) === false) {
            throw new ErroApi("O campo {$rotulo} deve ser um número inteiro.", 422);
        }
        $numero = (int) $valor;
        if ($numero < $minimo || $numero > $maximo) {
            throw new ErroApi("O campo {$rotulo} está fora do intervalo permitido.", 422);
        }
        return $numero;
    }

    // Aceita "84.50" e "84,50"
    public static function decimal(mixed $valor, string $rotulo, float $minimo = 0, ?float $maximo = null): float
    {
        $texto = str_replace(',', '.', trim((string) $valor));
        if (!is_numeric($texto)) {
            throw new ErroApi("O campo {$rotulo} deve ser um número.", 422);
        }
        $numero = round((float) $texto, 2);
        if ($numero < $minimo || ($maximo !== null && $numero > $maximo)) {
            throw new ErroApi("O campo {$rotulo} está fora do intervalo permitido.", 422);
        }
        return $numero;
    }

    // Lista de ids (ex.: [1, 2, 3]) -> inteiros positivos sem repetição
    public static function listaIds(mixed $valor): array
    {
        if (is_string($valor)) {
            $valor = explode(',', $valor);
        }
        if (!is_array($valor)) {
            return [];
        }
        $ids = [];
        foreach ($valor as $item) {
            $id = (int) $item;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    // Telefone: guarda só números (10 ou 11 dígitos) ou null
    public static function telefone(mixed $valor): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) ($valor ?? ''));
        if ($digitos === '') {
            return null;
        }
        if (strlen($digitos) < 10 || strlen($digitos) > 11) {
            throw new ErroApi('Telefone inválido. Use o formato (99) 99999-9999.', 422);
        }
        return $digitos;
    }

    // Data/hora vinda do formulário ("2025-06-10T14:30" ou "2025-06-10 14:30:00")
    public static function dataHora(mixed $valor, string $rotulo): string
    {
        $texto = str_replace('T', ' ', trim((string) $valor));
        $data = date_create($texto);
        if (!$data) {
            throw new ErroApi("Data inválida no campo {$rotulo}.", 422);
        }
        return $data->format('Y-m-d H:i:s');
    }

    // Converte 1/0, true/false, "on" em 1 ou 0
    public static function booleano(mixed $valor): int
    {
        return in_array($valor, [true, 1, '1', 'true', 'on', 'sim'], true) ? 1 : 0;
    }
}
