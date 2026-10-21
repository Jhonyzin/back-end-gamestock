<?php

require_once 'core/ErroApi.php';
require_once 'config/Bancodados.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $banco = new Bancodados();

    $pdo = $banco->conectar();

    echo "Conexão realizada com sucesso!";
} catch (ErroApi $e) {
    http_response_code(500);
    echo $e->getMessage();
}
