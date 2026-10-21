<?php

class ErroApi extends Exception
{
    public function __construct(string $mensagem, int $codigoHttp = 400)
    {
        parent::__construct($mensagem, $codigoHttp);
    }
}
