<?php

/**
 * Classe base de todos os controllers.
 * Já entrega a conexão PDO pronta para os DAOs.
 */
abstract class Controller
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Bancodados::conexao();
    }

    // Lê um id numérico da rota (ex.: /jogos/{id})
    protected function idDaRota(Request $req, string $nome = 'id'): int
    {
        $id = (int) $req->param($nome);
        if ($id <= 0) {
            throw new ErroApi('Identificador inválido.', 400);
        }
        return $id;
    }
}
