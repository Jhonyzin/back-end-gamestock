<?php

class AdminCategoriaController extends Controller
{
    protected function dao(): CadastroSimplesDAO
    {
        return new CategoriaDAO($this->pdo);
    }

    protected function rotulo(): string
    {
        return 'categoria';
    }

    public function listar(Request $req): void
    {
        Response::sucesso($this->dao()->listar(false));
    }

    public function criar(Request $req): void
    {
        [$nome, $ativo] = $this->lerFormulario($req);
        $dao = $this->dao();
        if ($dao->nomeEmUso($nome)) {
            throw new ErroApi("Já existe uma {$this->rotulo()} com esse nome.", 409);
        }
        $id = $dao->criar($nome, $ativo);
        Response::sucesso(['id' => $id, 'nome' => $nome, 'ativo' => (bool) $ativo], 201);
    }

    public function atualizar(Request $req): void
    {
        $id = $this->idDaRota($req);
        $dao = $this->dao();
        if (!$dao->buscar($id)) {
            throw new ErroApi(ucfirst($this->rotulo()) . ' não encontrada.', 404);
        }
        [$nome, $ativo] = $this->lerFormulario($req);
        if ($dao->nomeEmUso($nome, $id)) {
            throw new ErroApi("Já existe uma {$this->rotulo()} com esse nome.", 409);
        }
        $dao->atualizar($id, $nome, $ativo);
        Response::sucesso(['id' => $id, 'nome' => $nome, 'ativo' => (bool) $ativo]);
    }

    public function excluir(Request $req): void
    {
        $id = $this->idDaRota($req);
        $dao = $this->dao();
        if (!$dao->buscar($id)) {
            throw new ErroApi(ucfirst($this->rotulo()) . ' não encontrada.', 404);
        }
        $emUso = $dao->emUso($id);
        if ($emUso > 0) {
            throw new ErroApi("Não é possível excluir: {$emUso} jogo(s) usam esta {$this->rotulo()}. Desative-a em vez de excluir.", 409);
        }
        $dao->excluir($id);
        Response::sucesso(['mensagem' => ucfirst($this->rotulo()) . ' excluída.']);
    }

    private function lerFormulario(Request $req): array
    {
        $nome = Validador::texto($req->input('nome'), 100, 'nome');
        if ($nome === null) {
            throw new ErroApi('Preencha o campo: nome.', 422);
        }
        return [$nome, Validador::booleano($req->input('ativo', 1))];
    }
}
