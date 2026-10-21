<?php

class CategoriaDAO extends CadastroSimplesDAO
{
    protected string $tabela = 'categorias';
    protected string $tabelaLigacao = 'produto_categoria';
    protected string $colunaLigacao = 'categoria_id';
}
