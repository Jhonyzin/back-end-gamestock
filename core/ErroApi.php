<?php

/**
 * Erro "esperado" da API: mensagem em português + código HTTP.
 * Ex.: throw new ErroApi('Jogo não encontrado.', 404);
 * O index.php captura e devolve {"sucesso":false,"erro":"..."}.
 */
class ErroApi extends Exception
{
    public function __construct(string $mensagem, int $codigoHttp = 400)
    {
        parent::__construct($mensagem, $codigoHttp);
    }
}
