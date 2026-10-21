<?php

/**
 * CONTROLLER de cupons: confere se o código existe e está válido.
 */
class CupomController extends Controller
{
    public const MENSAGEM_INVALIDO = 'Cupom inválido. Tente GAMESTOCK10 ou CYBER20.';

    // POST /cupons/validar {codigo}
    public function validar(Request $req): void
    {
        $codigo = (string) $req->input('codigo', '');
        $cupom = $codigo !== '' ? (new CupomDAO($this->pdo))->buscarValido($codigo) : null;
        if (!$cupom) {
            throw new ErroApi(self::MENSAGEM_INVALIDO, 422);
        }
        Response::sucesso([
            'codigo'     => $cupom['codigo'],
            'percentual' => (float) $cupom['percentual_desconto'],
        ]);
    }
}
