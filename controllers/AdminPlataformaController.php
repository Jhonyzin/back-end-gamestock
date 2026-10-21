<?php

/**
 * CONTROLLER do CRUD de plataformas: reaproveita tudo de
 * AdminCategoriaController (HERANÇA), trocando só o DAO e o rótulo.
 */
class AdminPlataformaController extends AdminCategoriaController
{
    protected function dao(): CadastroSimplesDAO
    {
        return new PlataformaDAO($this->pdo);
    }

    protected function rotulo(): string
    {
        return 'plataforma';
    }
}
