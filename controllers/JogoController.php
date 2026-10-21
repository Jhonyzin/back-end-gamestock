<?php

class JogoController extends Controller
{
    public function listar(Request $req): void
    {
        $filtros = [
            'busca'      => (string) $req->query('busca', ''),
            'categoria'  => (int) $req->query('categoria', 0),
            'plataforma' => (int) $req->query('plataforma', 0),
            'preco'      => (string) $req->query('preco', ''),
            'promocao'   => $req->query('promocao') === '1',
            'nota_min'   => (float) str_replace(',', '.', (string) $req->query('nota_min', '0')),
            'ordem'      => (string) $req->query('ordem', 'populares'),
            'ids'        => Validador::listaIds($req->query('ids', '')),
        ];
        $pagina = max(1, (int) $req->query('pagina', 1));
        $limite = min(100, max(1, (int) $req->query('limite', 12)));

        if ($req->query('ids') !== null && !$filtros['ids']) {
            Response::sucesso(['itens' => [], 'total' => 0, 'pagina' => 1, 'limite' => $limite]);
        }

        [$jogos, $total] = (new JogoDAO($this->pdo))->listar($filtros, $pagina, $limite);

        Response::sucesso([
            'itens'  => $this->paraArray($jogos, $req->usuario),
            'total'  => $total,
            'pagina' => $pagina,
            'limite' => $limite,
        ]);
    }

    public function destaques(Request $req): void
    {
        $jogos = (new JogoDAO($this->pdo))->destaques();
        $midias = new MidiaDAO($this->pdo);

        $lista = $this->paraArray($jogos, $req->usuario);
        foreach ($lista as &$jogo) {
            $jogo['galeria'] = $midias->imagens($jogo['id'], ['GALERIA', 'SCREENSHOT'], 4);
        }
        unset($jogo);
        Response::sucesso($lista);
    }

    public function promocoes(Request $req): void
    {
        $limite = min(50, max(1, (int) $req->query('limite', 12)));
        $jogos = (new JogoDAO($this->pdo))->emPromocao($limite);
        Response::sucesso($this->paraArray($jogos, $req->usuario));
    }

    public function maisVendidos(Request $req): void
    {
        $limite = min(50, max(1, (int) $req->query('limite', 10)));
        $jogos = (new JogoDAO($this->pdo))->maisVendidos($limite);
        Response::sucesso($this->paraArray($jogos, $req->usuario));
    }

    public function lancamentos(Request $req): void
    {
        $limite = min(50, max(1, (int) $req->query('limite', 8)));
        $jogos = (new JogoDAO($this->pdo))->lancamentos($limite);
        Response::sucesso($this->paraArray($jogos, $req->usuario));
    }

    public function detalhe(Request $req): void
    {
        $id = $this->idDaRota($req);
        $linha = (new JogoDAO($this->pdo))->buscarLinha($id);
        if (!$linha) {
            throw new ErroApi('Jogo não encontrado.', 404);
        }

        $midias = new MidiaDAO($this->pdo);
        $jogo = Jogo::deLinha($linha);
        $jogo->adicionarDetalhes($linha, $midias->imagens($id), $midias->videos($id));

        Response::sucesso($this->paraArray([$jogo], $req->usuario)[0]);
    }

    public function relacionados(Request $req): void
    {
        $jogos = (new JogoDAO($this->pdo))->relacionados($this->idDaRota($req));
        Response::sucesso($this->paraArray($jogos, $req->usuario));
    }

    private function paraArray(array $jogos, ?Usuario $usuario): array
    {
        if ($usuario) {
            $possuidos = array_flip((new BibliotecaDAO($this->pdo))->idsDoUsuario($usuario->id));
            $desejados = array_flip((new DesejoDAO($this->pdo))->idsDoUsuario($usuario->id));
            foreach ($jogos as $jogo) {
                $jogo->possui = isset($possuidos[$jogo->id]);
                $jogo->desejado = isset($desejados[$jogo->id]);
            }
        }
        return array_map(fn (Jogo $j) => $j->toArray(), $jogos);
    }
}
