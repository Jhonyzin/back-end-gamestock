<?php

/**
 * DAO de categorias (gêneros). Toda a lógica está em CadastroSimplesDAO.
 */
class CategoriaDAO extends CadastroSimplesDAO
{
    protected string $tabela = 'categorias';
    protected string $tabelaLigacao = 'produto_categoria';
    protected string $colunaLigacao = 'categoria_id';
}
