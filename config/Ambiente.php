<?php

class Ambiente
{
    private static ?array $arquivo = null;

    private static function carregarArquivo(): array
    {
        if (self::$arquivo !== null) {
            return self::$arquivo;
        }

        self::$arquivo = [];
        $caminho = __DIR__ . '/../.env';
        if (!is_file($caminho)) {
            return self::$arquivo;
        }

        foreach (file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);

            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }
            [$chave, $valor] = explode('=', $linha, 2);
            self::$arquivo[trim($chave)] = trim(trim($valor), "\"'");
        }
        return self::$arquivo;
    }

    public static function ler(string $chave, string $padrao = ''): string
    {
        $valor = getenv($chave);
        if ($valor !== false && $valor !== '') {
            return $valor;
        }
        $arquivo = self::carregarArquivo();
        if (isset($arquivo[$chave]) && $arquivo[$chave] !== '') {
            return $arquivo[$chave];
        }
        return $padrao;
    }

    public static function booleano(string $chave): bool
    {
        return in_array(strtolower(self::ler($chave, 'false')), ['1', 'true', 'sim', 'yes', 'on'], true);
    }
}
