<?php

/**
 * DAO de plataformas (PC, PS5, Xbox...). Toda a lógica está em CadastroSimplesDAO.
 */
class PlataformaDAO extends CadastroSimplesDAO
{
    protected string $tabela = 'plataformas';
    protected string $tabelaLigacao = 'produto_plataforma';
    protected string $colunaLigacao = 'plataforma_id';
}
