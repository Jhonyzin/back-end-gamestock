<?php

/**
 * CONTROLLER de status: responde sem usar o banco.
 * Serve para conferir se a API está no ar (abrir /api no navegador).
 */
class StatusController
{
    // GET /
    public function status(Request $req): void
    {
        Response::sucesso([
            'api'    => 'GameStock 2025',
            'status' => 'online',
            'hora'   => date('d/m/Y H:i:s'),
        ]);
    }
}
