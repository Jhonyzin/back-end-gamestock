<?php

class AdminConfiguracaoController extends Controller
{
    public function listar(Request $req): void
    {
        Response::sucesso((new ConfiguracaoDAO($this->pdo))->listar());
    }

    public function atualizar(Request $req): void
    {
        $dao = new ConfiguracaoDAO($this->pdo);
        foreach ($req->todos() as $chave => $valor) {
            if (!$dao->existe((string) $chave)) {
                throw new ErroApi("Configuração desconhecida: {$chave}.", 422);
            }
            $dao->atualizar((string) $chave, Validador::texto($valor, 500, (string) $chave));
        }
        Response::sucesso($dao->listar());
    }
}
