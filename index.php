<?php

/**
 * GAMESTOCK 2025 - API REST em PHP 8 (POO + MVC + DAO + PDO)
 * Único ponto de entrada (front controller). Toda requisição passa aqui:
 *   1) CORS  2) autoload  3) rotas  4) tratamento de erros
 */

declare(strict_types=1);

// ---------------------------------------------------------
// 1) AUTOLOAD: carrega a classe procurando nas pastas do MVC
// ---------------------------------------------------------
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
ini_set('display_errors', '0');   // erros nunca aparecem no meio do JSON

// ---------------------------------------------------------
// 2) CORS: quais sites (front-end) podem chamar esta API
// ---------------------------------------------------------
$origensPermitidas = array_filter(array_map(
    fn ($url) => rtrim(trim($url), '/'),
    explode(',', Ambiente::ler('FRONTEND_URL'))
));
// Em desenvolvimento (APP_DEBUG=true ou FRONTEND_URL vazio) libera o Vite local
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

// Pré-verificação do navegador (preflight): responde vazio
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------------------------------------------------------
// 3) ROTAS + 4) TRATAMENTO DE ERROS
// ---------------------------------------------------------
try {
    $rotas = require __DIR__ . '/routes/api.php';
    $roteador = new Router($rotas);
    $roteador->despachar(new Request());

} catch (ErroApi $e) {
    // erro esperado (validação, login, permissão...)
    Response::erro($e->getMessage(), $e->getCode() ?: 400);

} catch (Throwable $e) {
    // erro inesperado: registra no log do servidor e responde 500
    error_log('[GameStock] ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    $mensagem = 'Erro interno no servidor. Tente novamente.';
    if ($modoDebug) {
        $mensagem .= ' Detalhe: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
    }
    Response::erro($mensagem, 500);
}
