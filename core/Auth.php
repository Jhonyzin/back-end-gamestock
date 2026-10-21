<?php

class Auth
{
    public const DIAS_VALIDADE = 7;

    // cria o token do login
    public static function criarSessao(int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32));
        $dao = new SessaoDAO(Bancodados::conexao());
        $dao->limparExpiradas();
        $dao->criar($usuarioId, hash('sha256', $token), self::DIAS_VALIDADE);
        return $token;
    }

    public static function encerrarSessao(?string $token): void
    {
        if ($token) {
            (new SessaoDAO(Bancodados::conexao()))->apagar(hash('sha256', $token));
        }
    }

    public static function usuarioOpcional(Request $req): ?Usuario
    {
        $token = $req->token();
        if (!$token) {
            return null;
        }
        $linha = (new SessaoDAO(Bancodados::conexao()))->buscarUsuario(hash('sha256', $token));
        return $linha ? Usuario::deLinha($linha) : null;
    }

    public static function exigirLogin(Request $req): Usuario
    {
        $usuario = self::usuarioOpcional($req);
        if (!$usuario) {
            throw new ErroApi('Faça login para continuar.', 401);
        }
        return $usuario;
    }

    public static function exigirAdmin(Request $req): Usuario
    {
        $usuario = self::exigirLogin($req);
        if (!$usuario->ehAdmin()) {
            throw new ErroApi('Acesso restrito ao administrador.', 403);
        }
        return $usuario;
    }
}
