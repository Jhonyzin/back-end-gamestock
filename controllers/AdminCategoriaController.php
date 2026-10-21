<?php

/**
 * CONTROLLER do CRUD simples de categorias.
 * AdminPlataformaController herda desta classe e só troca o DAO e o nome.
 */
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

    // GET /admin/categorias
    public function listar(Request $req): void
    {
        Response::sucesso($this->dao()->listar(false));
    }

    // POST /admin/categorias {nome, ativo}
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

    // PUT /admin/categorias/{id} {nome, ativo}
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

    // DELETE /admin/categorias/{id} (só se nenhum jogo usar)
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
