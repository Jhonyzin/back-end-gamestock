<?php

class DesejoController extends Controller
{
    public function listar(Request $req): void
    {
        $ids = (new DesejoDAO($this->pdo))->idsDoUsuario($req->usuario->id);
        if (!$ids) {
            Response::sucesso([]);
        }

        [$jogos] = (new JogoDAO($this->pdo))->listar(['ids' => $ids], 1, 100);
        $possuidos = array_flip((new BibliotecaDAO($this->pdo))->idsDoUsuario($req->usuario->id));

        $porId = [];
        foreach ($jogos as $jogo) {
            $jogo->desejado = true;
            $jogo->possui = isset($possuidos[$jogo->id]);
            $porId[$jogo->id] = $jogo->toArray();
        }
        $lista = [];
        foreach ($ids as $id) {
            if (isset($porId[$id])) {
                $lista[] = $porId[$id];
            }
        }
        Response::sucesso($lista);
    }

    public function adicionar(Request $req): void
    {
        $jogoId = $this->idDaRota($req, 'jogoId');
        if (!(new JogoDAO($this->pdo))->existe($jogoId)) {
            throw new ErroApi('Jogo não encontrado.', 404);
        }
        (new DesejoDAO($this->pdo))->adicionar($req->usuario->id, $jogoId);
        Response::sucesso(['mensagem' => 'Adicionado à lista de desejos.'], 201);
    }

    public function remover(Request $req): void
    {
        (new DesejoDAO($this->pdo))->remover($req->usuario->id, $this->idDaRota($req, 'jogoId'));
        Response::sucesso(['mensagem' => 'Removido da lista de desejos.']);
    }
}
