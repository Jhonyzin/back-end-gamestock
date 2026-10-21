<?php

class PlataformaDAO extends CadastroSimplesDAO
{
    protected string $tabela = 'plataformas';
    protected string $tabelaLigacao = 'produto_plataforma';
    protected string $colunaLigacao = 'plataforma_id';
}
