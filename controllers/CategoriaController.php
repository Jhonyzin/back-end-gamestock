<?php

/**
 * CONTROLLER das listas públicas de categorias e plataformas (com contagem de jogos).
 */
class CategoriaController extends Controller
{
    // GET /categorias
    public function listarCategorias(Request $req): void
    {
        Response::sucesso((new CategoriaDAO($this->pdo))->listar(true));
    }

    // GET /plataformas
    public function listarPlataformas(Request $req): void
    {
        Response::sucesso((new PlataformaDAO($this->pdo))->listar(true));
    }
}
