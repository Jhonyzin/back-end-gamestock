<?php

class AuthController extends Controller
{
    public function cadastrar(Request $req): void
    {
        Validador::obrigatorios($req->todos(), ['nome' => 'nome', 'email' => 'e-mail', 'senha' => 'senha']);

        $nome     = Validador::texto($req->input('nome'), 150, 'nome');
        $email    = Validador::email($req->input('email'));
        $senha    = Validador::senha($req->input('senha'));
        $telefone = Validador::telefone($req->input('telefone'));

        if (mb_strlen($nome) < 3) {
            throw new ErroApi('Informe seu nome completo.', 422);
        }

        $dao = new UsuarioDAO($this->pdo);
        if ($dao->emailEmUso($email)) {
            throw new ErroApi('Este e-mail já está cadastrado.', 409);
        }

        $id = $dao->criar([
            'nome'       => $nome,
            'email'      => $email,
            'telefone'   => $telefone,
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        ]);

        $usuario = Usuario::deLinha($dao->buscarPorId($id));
        $token = Auth::criarSessao($id);

        Response::sucesso(['token' => $token, 'usuario' => $usuario->toArray()], 201);
    }

    public function login(Request $req): void
    {
        Validador::obrigatorios($req->todos(), ['email' => 'e-mail', 'senha' => 'senha']);

        $email = strtolower((string) $req->input('email'));
        $senha = (string) $req->input('senha');

        $dao = new UsuarioDAO($this->pdo);
        $linha = $dao->buscarPorEmail($email);

        if (!$linha || !password_verify($senha, $linha['senha_hash'])) {
            throw new ErroApi('E-mail ou senha incorretos.', 401);
        }
        if ($linha['deleted_at'] !== null || !(int) $linha['ativo']) {
            throw new ErroApi('Conta desativada. Fale com o suporte da loja.', 403);
        }

        $token = Auth::criarSessao((int) $linha['id']);
        Response::sucesso(['token' => $token, 'usuario' => Usuario::deLinha($linha)->toArray()]);
    }

    public function logout(Request $req): void
    {
        Auth::encerrarSessao($req->token());
        Response::sucesso(['mensagem' => 'Sessão encerrada.']);
    }

    public function eu(Request $req): void
    {
        Response::sucesso($req->usuario->toArray());
    }
}
