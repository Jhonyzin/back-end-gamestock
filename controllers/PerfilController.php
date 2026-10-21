<?php

/**
 * CONTROLLER do perfil do usuário logado (nome, telefone, e-mail, senha e foto).
 */
class PerfilController extends Controller
{
    // GET /perfil
    public function mostrar(Request $req): void
    {
        Response::sucesso($req->usuario->toArray());
    }

    // PUT /perfil {nome_exibicao, telefone}
    public function atualizar(Request $req): void
    {
        $nome = Validador::texto($req->input('nome_exibicao'), 80, 'nome de exibição');
        if ($nome === null || mb_strlen($nome) < 2) {
            throw new ErroApi('Informe um nome de exibição com pelo menos 2 letras.', 422);
        }
        $telefone = Validador::telefone($req->input('telefone'));

        $dao = new UsuarioDAO($this->pdo);
        $dao->atualizarPerfil($req->usuario->id, $nome, $telefone);

        Response::sucesso(Usuario::deLinha($dao->buscarPorId($req->usuario->id))->toArray());
    }

    // PUT /perfil/email {email, senha_atual}
    public function trocarEmail(Request $req): void
    {
        $email = Validador::email($req->input('email'));
        $dao = new UsuarioDAO($this->pdo);
        $this->conferirSenhaAtual($dao, $req->usuario->id, (string) $req->input('senha_atual'));

        if ($dao->emailEmUso($email, $req->usuario->id)) {
            throw new ErroApi('Este e-mail já está em uso por outra conta.', 409);
        }
        $dao->atualizarEmail($req->usuario->id, $email);

        Response::sucesso(Usuario::deLinha($dao->buscarPorId($req->usuario->id))->toArray());
    }

    // PUT /perfil/senha {senha_atual, nova_senha}
    public function trocarSenha(Request $req): void
    {
        $nova = Validador::senha($req->input('nova_senha'));
        $dao = new UsuarioDAO($this->pdo);
        $this->conferirSenhaAtual($dao, $req->usuario->id, (string) $req->input('senha_atual'));

        $dao->atualizarSenha($req->usuario->id, password_hash($nova, PASSWORD_DEFAULT));
        Response::sucesso(['mensagem' => 'Senha alterada com sucesso.']);
    }

    // POST /perfil/foto (multipart, campo "foto")
    public function enviarFoto(Request $req): void
    {
        $caminho = Upload::imagem($req->arquivo('foto'), 'perfis', 2);

        $dao = new UsuarioDAO($this->pdo);
        Upload::apagar($req->usuario->fotoPerfil);   // apaga a foto antiga (se foi enviada)
        $dao->atualizarFoto($req->usuario->id, $caminho);

        Response::sucesso(Usuario::deLinha($dao->buscarPorId($req->usuario->id))->toArray());
    }

    // DELETE /perfil/foto
    public function removerFoto(Request $req): void
    {
        $dao = new UsuarioDAO($this->pdo);
        Upload::apagar($req->usuario->fotoPerfil);
        $dao->atualizarFoto($req->usuario->id, null);

        Response::sucesso(Usuario::deLinha($dao->buscarPorId($req->usuario->id))->toArray());
    }

    // Toda troca sensível pede a senha atual
    private function conferirSenhaAtual(UsuarioDAO $dao, int $usuarioId, string $senha): void
    {
        $linha = $dao->buscarPorId($usuarioId);
        if (!$linha || !password_verify($senha, $linha['senha_hash'])) {
            throw new ErroApi('Senha atual incorreta.', 422);
        }
    }
}
