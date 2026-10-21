<?php

/**
 * CONTROLLER do CRUD de usuários no painel admin.
 * Regra: o administrador NÃO pode excluir nem desativar a si mesmo.
 */
class AdminUsuarioController extends Controller
{
    // GET /admin/usuarios?busca=
    public function listar(Request $req): void
    {
        $linhas = (new UsuarioDAO($this->pdo))->listar((string) $req->query('busca', ''));
        Response::sucesso(array_map(
            fn ($l) => Usuario::deLinha($l)->toArray() + ['total_pedidos' => (int) $l['total_pedidos']],
            $linhas
        ));
    }

    // GET /admin/usuarios/{id}
    public function mostrar(Request $req): void
    {
        Response::sucesso(Usuario::deLinha($this->buscarOuFalhar($this->idDaRota($req)))->toArray());
    }

    // POST /admin/usuarios {nome, email, senha, telefone?, tipo_usuario, ativo}
    public function criar(Request $req): void
    {
        Validador::obrigatorios($req->todos(), ['nome' => 'nome', 'email' => 'e-mail', 'senha' => 'senha']);
        $dados = $this->lerFormulario($req);
        $dao = new UsuarioDAO($this->pdo);
        if ($dao->emailEmUso($dados['email'])) {
            throw new ErroApi('Este e-mail já está cadastrado.', 409);
        }
        $dados['senha_hash'] = password_hash(Validador::senha($req->input('senha')), PASSWORD_DEFAULT);

        $id = $dao->criar($dados);
        Response::sucesso(Usuario::deLinha($dao->buscarPorId($id))->toArray(), 201);
    }

    // PUT /admin/usuarios/{id} (senha opcional: só troca se vier preenchida)
    public function atualizar(Request $req): void
    {
        $id = $this->idDaRota($req);
        $this->buscarOuFalhar($id);
        $dados = $this->lerFormulario($req);

        if ($id === $req->usuario->id && !$dados['ativo']) {
            throw new ErroApi('Você não pode desativar a sua própria conta.', 422);
        }
        if ($id === $req->usuario->id && $dados['tipo_usuario'] !== 'ADMINISTRADOR') {
            throw new ErroApi('Você não pode remover o seu próprio acesso de administrador.', 422);
        }

        $dao = new UsuarioDAO($this->pdo);
        if ($dao->emailEmUso($dados['email'], $id)) {
            throw new ErroApi('Este e-mail já está em uso por outra conta.', 409);
        }
        $dao->atualizarAdmin($id, $dados);

        $senha = (string) $req->input('senha', '');
        if ($senha !== '') {
            $dao->atualizarSenha($id, password_hash(Validador::senha($senha), PASSWORD_DEFAULT));
        }
        // conta desativada perde o login na hora
        if (!$dados['ativo']) {
            (new SessaoDAO($this->pdo))->apagarDoUsuario($id);
        }

        Response::sucesso(Usuario::deLinha($dao->buscarPorId($id))->toArray());
    }

    // DELETE /admin/usuarios/{id} (exclusão lógica)
    public function excluir(Request $req): void
    {
        $id = $this->idDaRota($req);
        if ($id === $req->usuario->id) {
            throw new ErroApi('Você não pode excluir a sua própria conta.', 422);
        }
        $this->buscarOuFalhar($id);

        (new UsuarioDAO($this->pdo))->excluir($id);
        (new SessaoDAO($this->pdo))->apagarDoUsuario($id);
        Response::sucesso(['mensagem' => 'Usuário excluído.']);
    }

    private function buscarOuFalhar(int $id): array
    {
        $linha = (new UsuarioDAO($this->pdo))->buscarPorId($id);
        if (!$linha) {
            throw new ErroApi('Usuário não encontrado.', 404);
        }
        return $linha;
    }

    private function lerFormulario(Request $req): array
    {
        Validador::obrigatorios($req->todos(), ['nome' => 'nome', 'email' => 'e-mail']);
        $tipo = $req->input('tipo_usuario', 'CLIENTE');
        if (!in_array($tipo, ['CLIENTE', 'ADMINISTRADOR'], true)) {
            throw new ErroApi('Tipo de usuário inválido.', 422);
        }
        $nome = Validador::texto($req->input('nome'), 150, 'nome');
        return [
            'nome'          => $nome,
            'nome_exibicao' => Validador::texto($req->input('nome_exibicao'), 80, 'nome de exibição') ?? $nome,
            'email'         => Validador::email($req->input('email')),
            'telefone'      => Validador::telefone($req->input('telefone')),
            'tipo_usuario'  => $tipo,
            'ativo'         => Validador::booleano($req->input('ativo', 1)),
        ];
    }
}
