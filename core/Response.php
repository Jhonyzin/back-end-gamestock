<?php

class Response
{
    public static function json(array $corpo, int $codigoHttp = 200): void
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function sucesso(mixed $dados = null, int $codigoHttp = 200): void
    {
        self::json(['sucesso' => true, 'dados' => $dados], $codigoHttp);
    }

    public static function erro(string $mensagem, int $codigoHttp = 400): void
    {
        self::json(['sucesso' => false, 'erro' => $mensagem], $codigoHttp);
    }
}
