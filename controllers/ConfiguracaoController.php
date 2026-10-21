<?php

class ConfiguracaoController extends Controller
{
    public function publicas(Request $req): void
    {
        Response::sucesso((new ConfiguracaoDAO($this->pdo))->mapa());
    }
}
