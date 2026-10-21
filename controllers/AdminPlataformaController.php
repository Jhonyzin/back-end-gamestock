<?php

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
