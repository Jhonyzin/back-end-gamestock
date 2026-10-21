<?php

class StatusController
{
    public function status(Request $req): void
    {
        Response::sucesso([
            'api'    => 'GameStock 2025',
            'status' => 'online',
            'hora'   => date('d/m/Y H:i:s'),
        ]);
    }
}
