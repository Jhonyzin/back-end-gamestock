<?php

/**
 * CONTROLLER de moderação de avaliações.
 */
class AdminAvaliacaoController extends Controller
{
    // GET /admin/avaliacoes
    public function listar(Request $req): void
    {
        $lista = (new AvaliacaoDAO($this->pdo))->listarTodas();
        Response::sucesso(array_map(fn (Avaliacao $a) => $a->toArray(), $lista));
    }

    // DELETE /admin/avaliacoes/{id}
    public function excluir(Request $req): void
    {
        if (!(new AvaliacaoDAO($this->pdo))->excluir($this->idDaRota($req))) {
            throw new ErroApi('Avaliação não encontrada.', 404);
        }
        Response::sucesso(['mensagem' => 'Avaliação removida.']);
    }
}
