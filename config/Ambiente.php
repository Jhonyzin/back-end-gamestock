<?php

/**
 * Lê as configurações do sistema.
 * 1º tenta as variáveis de ambiente (Render, Docker);
 * 2º tenta o arquivo backend/.env (XAMPP), no formato CHAVE=VALOR.
 */
class Ambiente
{
    private static ?array $arquivo = null;

    // Carrega o .env uma única vez
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
            // ignora comentários e linhas sem "="
            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }
            [$chave, $valor] = explode('=', $linha, 2);
            self::$arquivo[trim($chave)] = trim(trim($valor), "\"'");
        }
        return self::$arquivo;
    }

    // Devolve o valor de uma configuração (ou o padrão)
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

    // Lê uma configuração do tipo verdadeiro/falso
    public static function booleano(string $chave): bool
    {
        return in_array(strtolower(self::ler($chave, 'false')), ['1', 'true', 'sim', 'yes', 'on'], true);
    }
}
