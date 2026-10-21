<?php

// carrega as classes
spl_autoload_register(function (string $classe): void {
    foreach (['controllers', 'models', 'dao', 'core', 'config'] as $pasta) {
        $arquivo = __DIR__ . "/{$pasta}/{$classe}.php";
        if (is_file($arquivo)) {
            require_once $arquivo;
            return;
        }
    }
});

date_default_timezone_set('America/Sao_Paulo');
$modoDebug = Ambiente::booleano('APP_DEBUG');
ini_set('display_errors', '0');

// cors
$origensPermitidas = array_filter(array_map(
    fn ($url) => rtrim(trim($url), '/'),
    explode(',', Ambiente::ler('FRONTEND_URL'))
));

if ($modoDebug || !$origensPermitidas) {
    array_push($origensPermitidas, 'http://localhost:3000', 'http://localhost:5173', 'http://127.0.0.1:3000', 'http://127.0.0.1:5173');
}

$origem = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origem !== '' && in_array($origem, $origensPermitidas, true)) {
    header("Access-Control-Allow-Origin: {$origem}");
    header('Vary: Origin');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 86400');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// rotas
try {
    $rotas = require __DIR__ . '/routes/api.php';
    $roteador = new Router($rotas);
    $roteador->despachar(new Request());
} catch (ErroApi $e) {
    Response::erro($e->getMessage(), $e->getCode() ?: 400);
} catch (Throwable $e) {
    error_log('[GameStock] ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    $mensagem = 'Erro interno no servidor. Tente novamente.';
    if ($modoDebug) {
        $mensagem .= ' Detalhe: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
    }
    Response::erro($mensagem, 500);
}
