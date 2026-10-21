<?php

abstract class Controller
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Bancodados::conexao();
    }

    protected function idDaRota(Request $req, string $nome = 'id'): int
    {
        $id = (int) $req->param($nome);
        if ($id <= 0) {
            throw new ErroApi('Identificador inválido.', 400);
        }
        return $id;
    }
}
