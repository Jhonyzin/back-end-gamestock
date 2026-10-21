<?php

class AdminPromocaoController extends Controller
{
    public function listar(Request $req): void
    {
        $lista = (new PromocaoDAO($this->pdo))->listar();
        Response::sucesso(array_map(fn (Promocao $p) => $p->toArray(), $lista));
    }

    public function mostrar(Request $req): void
    {
        Response::sucesso($this->buscarOuFalhar($this->idDaRota($req))->toArray());
    }

    public function criar(Request $req): void
    {
        $dados = $this->lerFormulario($req);
        $dao = new PromocaoDAO($this->pdo);

        $this->pdo->beginTransaction();
        try {
            $id = $dao->criar($dados, $req->usuario->id);
            $dao->definirJogos($id, $dados['jogos']);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        Response::sucesso($dao->buscar($id)->toArray(), 201);
    }

    public function atualizar(Request $req): void
    {
        $id = $this->idDaRota($req);
        $this->buscarOuFalhar($id);
        $dados = $this->lerFormulario($req);
        $dao = new PromocaoDAO($this->pdo);

        $this->pdo->beginTransaction();
        try {
            $dao->atualizar($id, $dados);
            $dao->definirJogos($id, $dados['jogos']);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        Response::sucesso($dao->buscar($id)->toArray());
    }

    public function excluir(Request $req): void
    {
        $id = $this->idDaRota($req);
        $this->buscarOuFalhar($id);
        (new PromocaoDAO($this->pdo))->excluir($id);
        Response::sucesso(['mensagem' => 'Promoção excluída.']);
    }

    private function buscarOuFalhar(int $id): Promocao
    {
        $promocao = (new PromocaoDAO($this->pdo))->buscar($id);
        if (!$promocao) {
            throw new ErroApi('Promoção não encontrada.', 404);
        }
        return $promocao;
    }

    private function lerFormulario(Request $req): array
    {
        $d = $req->todos();
        Validador::obrigatorios($d, ['nome' => 'nome', 'percentual' => 'percentual', 'data_inicio' => 'início', 'data_fim' => 'fim']);

        $inicio = Validador::dataHora($d['data_inicio'], 'início');
        $fim = Validador::dataHora($d['data_fim'], 'fim');
        if ($inicio > $fim) {
            throw new ErroApi('A data de início deve ser antes da data de fim.', 422);
        }

        $jogos = Validador::listaIds($d['jogos'] ?? []);
        $jogoDAO = new JogoDAO($this->pdo);
        foreach ($jogos as $jogoId) {
            if (!$jogoDAO->existe($jogoId)) {
                throw new ErroApi("Jogo {$jogoId} não encontrado.", 422);
            }
        }

        return [
            'nome'        => Validador::texto($d['nome'], 120, 'nome'),
            'descricao'   => Validador::texto($d['descricao'] ?? null, 300, 'descrição'),
            'percentual'  => Validador::decimal($d['percentual'], 'percentual', 1, 100),
            'data_inicio' => $inicio,
            'data_fim'    => $fim,
            'ativo'       => Validador::booleano($d['ativo'] ?? 1),
            'jogos'       => $jogos,
        ];
    }
}
