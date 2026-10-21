<?php

/**
 * CONTROLLER público das configurações do site (nome da loja, descrição...).
 */
class ConfiguracaoController extends Controller
{
    // GET /configuracoes
    public function publicas(Request $req): void
    {
        Response::sucesso((new ConfiguracaoDAO($this->pdo))->mapa());
    }
}
