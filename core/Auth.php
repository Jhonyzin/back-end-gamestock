<?php

/**
 * Autenticação por token (Bearer).
 * - No login geramos um token aleatório e guardamos só o SHA-256 dele.
 * - O navegador envia o token em "Authorization: Bearer <token>".
 * - A sessão vale 7 dias; o logout apaga a sessão.
 */
class Auth
{
    public const DIAS_VALIDADE = 7;

    // Cria a sessão e devolve o token "puro" (só o navegador o conhece)
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

    // Usuário do token (ou null se não houver login válido)
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
