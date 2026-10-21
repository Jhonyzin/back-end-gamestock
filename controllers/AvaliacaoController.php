<?php

/**
 * CONTROLLER de avaliações na página do jogo.
 * Só quem possui o jogo pode avaliar; 1 avaliação por jogo (editável).
 */
class AvaliacaoController extends Controller
{
    // GET /jogos/{id}/avaliacoes
    public function listar(Request $req): void
    {
        $id = $this->idDaRota($req);
        $avaliacoes = (new AvaliacaoDAO($this->pdo))->listarDoJogo($id);

        $total = count($avaliacoes);
        $soma = 0;
        $positivas = 0;
        foreach ($avaliacoes as $a) {
            $soma += $a->nota;
            if ($a->nota >= 4) {
                $positivas++;
            }
        }

        $podeAvaliar = $req->usuario && (new BibliotecaDAO($this->pdo))->possui($req->usuario->id, $id);

        Response::sucesso([
            'media'        => $total ? round($soma / $total, 1) : null,
            'total'        => $total,
            'positivas'    => $positivas,
            'pode_avaliar' => $podeAvaliar,
            'avaliacoes'   => array_map(fn (Avaliacao $a) => $a->toArray(), $avaliacoes),
        ]);
    }

    // POST /jogos/{id}/avaliacoes {nota, comentario}
    public function salvar(Request $req): void
    {
        $id = $this->idDaRota($req);
        if (!(new JogoDAO($this->pdo))->existe($id)) {
            throw new ErroApi('Jogo não encontrado.', 404);
        }
        if (!(new BibliotecaDAO($this->pdo))->possui($req->usuario->id, $id)) {
            throw new ErroApi('Compre o jogo para poder avaliar.', 403);
        }

        $nota = Validador::inteiro($req->input('nota'), 'nota', 1, 5);
        $comentario = Validador::texto($req->input('comentario'), 500, 'comentário');

        $dao = new AvaliacaoDAO($this->pdo);
        $dao->salvar($req->usuario->id, $id, $nota, $comentario);

        Response::sucesso(['nota' => $nota, 'comentario' => $comentario, 'mensagem' => 'Avaliação salva. Obrigado!']);
    }
}
