<?php

class CategoriaController extends Controller
{
    public function listarCategorias(Request $req): void
    {
        Response::sucesso((new CategoriaDAO($this->pdo))->listar(true));
    }

    public function listarPlataformas(Request $req): void
    {
        Response::sucesso((new PlataformaDAO($this->pdo))->listar(true));
    }
}
