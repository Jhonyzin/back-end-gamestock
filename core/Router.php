<?php

/**
 * Roteador: procura na tabela de rotas (routes/api.php) a linha que
 * combina com o método + caminho, confere o nível de acesso e chama
 * o método do controller.
 */
class Router
{
    private array $rotas;

    public function __construct(array $rotas)
    {
        $this->rotas = $rotas;
    }

    public function despachar(Request $req): void
    {
        foreach ($this->rotas as [$metodo, $caminho, $controller, $acao, $acesso]) {
            if ($metodo !== $req->metodo) {
                continue;
            }

            // "/jogos/{id}" vira a expressão regular "#^/jogos/(?P<id>[^/]+)$#"
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $caminho) . '$#';
            if (!preg_match($regex, $req->caminho, $encontrados)) {
                continue;
            }

            // guarda só os parâmetros com nome (ex.: id)
            $req->params = array_filter($encontrados, 'is_string', ARRAY_FILTER_USE_KEY);

            // nível de acesso: publico | login | admin
            if ($acesso === 'admin') {
                $req->usuario = Auth::exigirAdmin($req);
            } elseif ($acesso === 'login') {
                $req->usuario = Auth::exigirLogin($req);
            } else {
                $req->usuario = Auth::usuarioOpcional($req);
            }

            $objeto = new $controller();
            $objeto->$acao($req);
            return;
        }

        throw new ErroApi('Rota não encontrada.', 404);
    }
}
