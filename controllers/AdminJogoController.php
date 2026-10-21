<?php

/**
 * CONTROLLER do CRUD de jogos no painel admin, com imagens e trailer.
 */
class AdminJogoController extends Controller
{
    // GET /admin/jogos?busca=
    public function listar(Request $req): void
    {
        $jogos = (new JogoDAO($this->pdo))->listarAdmin((string) $req->query('busca', ''));
        Response::sucesso(array_map(fn (Jogo $j) => $j->toArray(), $jogos));
    }

    // GET /admin/jogos/{id} (todos os dados para o formulário)
    public function mostrar(Request $req): void
    {
        Response::sucesso($this->dadosCompletos($this->idDaRota($req)));
    }

    // POST /admin/jogos
    public function criar(Request $req): void
    {
        $dao = new JogoDAO($this->pdo);
        $dados = $this->lerFormulario($req);
        $dados['slug'] = $this->gerarSlug($dao, $dados['nome']);

        $this->pdo->beginTransaction();
        try {
            $id = $dao->criar($dados, $req->usuario->id);
            $dao->definirCategorias($id, $dados['categorias']);
            $dao->definirPlataformas($id, $dados['plataformas']);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        Response::sucesso($this->dadosCompletos($id), 201);
    }

    // PUT /admin/jogos/{id}
    public function atualizar(Request $req): void
    {
        $id = $this->idDaRota($req);
        $dao = new JogoDAO($this->pdo);
        if (!$dao->existe($id)) {
            throw new ErroApi('Jogo não encontrado.', 404);
        }

        $dados = $this->lerFormulario($req);
        $dados['slug'] = $this->gerarSlug($dao, $dados['nome'], $id);

        $this->pdo->beginTransaction();
        try {
            $dao->atualizar($id, $dados);
            $dao->definirCategorias($id, $dados['categorias']);
            $dao->definirPlataformas($id, $dados['plataformas']);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        Response::sucesso($this->dadosCompletos($id));
    }

    // DELETE /admin/jogos/{id} (soft delete)
    public function excluir(Request $req): void
    {
        $id = $this->idDaRota($req);
        $dao = new JogoDAO($this->pdo);
        if (!$dao->existe($id)) {
            throw new ErroApi('Jogo não encontrado.', 404);
        }
        $dao->excluir($id);
        Response::sucesso(['mensagem' => 'Jogo excluído.']);
    }

    // POST /admin/jogos/{id}/imagens (multipart "arquivo" OU campo "url"; "tipo"; "principal")
    public function adicionarImagem(Request $req): void
    {
        $id = $this->idDaRota($req);
        if (!(new JogoDAO($this->pdo))->existe($id)) {
            throw new ErroApi('Jogo não encontrado.', 404);
        }

        $tipo = strtoupper((string) $req->input('tipo', 'GALERIA'));
        if (!in_array($tipo, ['CAPA', 'GALERIA', 'SCREENSHOT'], true)) {
            throw new ErroApi('Tipo de imagem inválido.', 422);
        }

        $arquivo = $req->arquivo('arquivo');
        if ($arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE) {
            $caminho = Upload::imagem($arquivo, 'jogos', 5);
        } else {
            $caminho = $this->validarUrlImagem((string) $req->input('url', ''));
        }

        $midias = new MidiaDAO($this->pdo);
        // capa nova vira a principal (a menos que venha principal=0)
        $principal = $tipo === 'CAPA' ? Validador::booleano($req->input('principal', 1)) : 0;
        if ($principal) {
            $midias->desmarcarCapasPrincipais($id);
        }
        $midias->adicionarImagem($id, $caminho, $tipo, $principal, $midias->proximaOrdem($id));

        Response::sucesso($midias->imagens($id), 201);
    }

    // PUT /admin/imagens/{id} {ordem, principal}
    public function atualizarImagem(Request $req): void
    {
        $midias = new MidiaDAO($this->pdo);
        $imagem = $midias->buscarImagem($this->idDaRota($req));
        if (!$imagem) {
            throw new ErroApi('Imagem não encontrada.', 404);
        }
        $ordem = Validador::inteiro($req->input('ordem', $imagem['ordem']), 'ordem', 0, 9999);
        $principal = $imagem['tipo'] === 'CAPA' ? Validador::booleano($req->input('principal', $imagem['principal'])) : 0;
        if ($principal) {
            $midias->desmarcarCapasPrincipais((int) $imagem['produto_id']);
        }
        $midias->atualizarImagem((int) $imagem['id'], $ordem, $principal);

        Response::sucesso($midias->imagens((int) $imagem['produto_id']));
    }

    // DELETE /admin/imagens/{id}
    public function removerImagem(Request $req): void
    {
        $midias = new MidiaDAO($this->pdo);
        $imagem = $midias->buscarImagem($this->idDaRota($req));
        if (!$imagem) {
            throw new ErroApi('Imagem não encontrada.', 404);
        }
        $midias->removerImagem((int) $imagem['id']);
        Upload::apagar($imagem['caminho_url']);

        Response::sucesso($midias->imagens((int) $imagem['produto_id']));
    }

    // POST /admin/jogos/{id}/videos {url, titulo?}
    public function adicionarVideo(Request $req): void
    {
        $id = $this->idDaRota($req);
        if (!(new JogoDAO($this->pdo))->existe($id)) {
            throw new ErroApi('Jogo não encontrado.', 404);
        }
        $url = (string) $req->input('url', '');
        if (!preg_match('#^https?://#i', $url) || strlen($url) > 500) {
            throw new ErroApi('Informe um link de vídeo válido (ex.: https://www.youtube.com/watch?v=...).', 422);
        }
        $titulo = Validador::texto($req->input('titulo'), 150, 'título') ?? 'Trailer';

        $midias = new MidiaDAO($this->pdo);
        $midias->adicionarVideo($id, $titulo, $url, 'TRAILER');
        Response::sucesso($midias->videos($id), 201);
    }

    // DELETE /admin/videos/{id}
    public function removerVideo(Request $req): void
    {
        if (!(new MidiaDAO($this->pdo))->removerVideo($this->idDaRota($req))) {
            throw new ErroApi('Vídeo não encontrado.', 404);
        }
        Response::sucesso(['mensagem' => 'Vídeo removido.']);
    }

    // GET /admin/marcas e /admin/editoras (selects do formulário)
    public function marcas(Request $req): void
    {
        Response::sucesso((new EmpresaDAO($this->pdo))->listarMarcas());
    }

    public function editoras(Request $req): void
    {
        Response::sucesso((new EmpresaDAO($this->pdo))->listarEditoras());
    }

    // ---------- auxiliares ----------

    // Lê e valida TODOS os campos do formulário de jogo
    private function lerFormulario(Request $req): array
    {
        $d = $req->todos();
        Validador::obrigatorios($d, ['nome' => 'nome', 'preco' => 'preço', 'estoque' => 'estoque']);

        $nome = Validador::texto($d['nome'], 150, 'nome');
        $tipoMidia = $d['tipo_midia'] ?? 'DIGITAL';
        if (!in_array($tipoMidia, ['DIGITAL', 'FISICA'], true)) {
            throw new ErroApi('Tipo de mídia inválido.', 422);
        }

        $ano = trim((string) ($d['ano_lancamento'] ?? ''));
        $marcaId = (int) ($d['marca_id'] ?? 0);
        $editoraId = (int) ($d['editora_id'] ?? 0);
        $empresas = new EmpresaDAO($this->pdo);
        if ($marcaId && !$empresas->marcaExiste($marcaId)) {
            throw new ErroApi('Desenvolvedora inválida.', 422);
        }
        if ($editoraId && !$empresas->editoraExiste($editoraId)) {
            throw new ErroApi('Editora inválida.', 422);
        }

        $categorias = Validador::listaIds($d['categorias'] ?? []);
        $plataformas = Validador::listaIds($d['plataformas'] ?? []);
        if (!$categorias) {
            throw new ErroApi('Escolha pelo menos uma categoria.', 422);
        }
        if (!$plataformas) {
            throw new ErroApi('Escolha pelo menos uma plataforma.', 422);
        }
        if (!(new CategoriaDAO($this->pdo))->todosExistem($categorias) || !(new PlataformaDAO($this->pdo))->todosExistem($plataformas)) {
            throw new ErroApi('Categoria ou plataforma inválida.', 422);
        }

        return [
            'nome'                    => $nome,
            'descricao_curta'         => Validador::texto($d['descricao_curta'] ?? null, 300, 'descrição curta'),
            'descricao'               => Validador::texto($d['descricao'] ?? null, 10000, 'descrição completa'),
            'preco'                   => Validador::decimal($d['preco'], 'preço', 0, 99999),
            'tipo_midia'              => $tipoMidia,
            'edicao'                  => Validador::texto($d['edicao'] ?? null, 80, 'edição'),
            'ano_lancamento'          => $ano === '' ? null : Validador::inteiro($ano, 'ano de lançamento', 1970, 2100),
            'versao_console'          => Validador::texto($d['versao_console'] ?? null, 80, 'versão'),
            'espaco_minimo_requerido' => Validador::texto($d['espaco_minimo_requerido'] ?? null, 40, 'espaço mínimo'),
            'req_so'                  => Validador::texto($d['req_so'] ?? null, 120, 'sistema operacional'),
            'req_cpu'                 => Validador::texto($d['req_cpu'] ?? null, 120, 'processador'),
            'req_gpu'                 => Validador::texto($d['req_gpu'] ?? null, 120, 'placa de vídeo'),
            'req_ram'                 => Validador::texto($d['req_ram'] ?? null, 40, 'memória RAM'),
            'marca_id'                => $marcaId ?: null,
            'editora_id'              => $editoraId ?: null,
            'estoque'                 => Validador::inteiro($d['estoque'], 'estoque', 0, 1000000),
            'destaque'                => Validador::booleano($d['destaque'] ?? 0),
            'popular'                 => Validador::booleano($d['popular'] ?? 0),
            'selo'                    => Validador::texto($d['selo'] ?? null, 40, 'selo'),
            'ativo'                   => Validador::booleano($d['ativo'] ?? 1),
            'categorias'              => $categorias,
            'plataformas'             => $plataformas,
        ];
    }

    // Jogo completo (inclusive inativo) + ids para o formulário
    private function dadosCompletos(int $id): array
    {
        $dao = new JogoDAO($this->pdo);
        $linha = $dao->buscarLinha($id, false);
        if (!$linha) {
            throw new ErroApi('Jogo não encontrado.', 404);
        }
        $midias = new MidiaDAO($this->pdo);
        $jogo = Jogo::deLinha($linha);
        $jogo->adicionarDetalhes($linha, $midias->imagens($id), $midias->videos($id));

        return $jogo->toArray() + [
            'categoria_ids'  => $dao->idsCategorias($id),
            'plataforma_ids' => $dao->idsPlataformas($id),
            'marca_id'       => $linha['marca_id'] ? (int) $linha['marca_id'] : null,
            'editora_id'     => $linha['editora_id'] ? (int) $linha['editora_id'] : null,
            'req_so'         => $linha['req_so'],
            'req_cpu'        => $linha['req_cpu'],
            'req_gpu'        => $linha['req_gpu'],
            'req_ram'        => $linha['req_ram'],
        ];
    }

    // URL externa (https://...) ou caminho de /capas/ do front
    private function validarUrlImagem(string $url): string
    {
        if ($url === '') {
            throw new ErroApi('Envie um arquivo ou informe a URL da imagem.', 422);
        }
        if (strlen($url) > 500 || !(preg_match('#^https?://#i', $url) || preg_match('#^/capas/[\w\-.]+$#', $url))) {
            throw new ErroApi('URL de imagem inválida. Use https://... ou /capas/arquivo.jpg', 422);
        }
        return $url;
    }

    // "Dragon's Dogma 2" -> "dragon-s-dogma-2" (único na tabela)
    private function gerarSlug(JogoDAO $dao, string $nome, int $ignorarId = 0): string
    {
        $acentos = ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
                    'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
        $slug = strtr(mb_strtolower($nome), $acentos);
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $slug), '-') ?: 'jogo';

        $base = $slug;
        $n = 2;
        while ($dao->slugEmUso($slug, $ignorarId)) {
            $slug = "{$base}-{$n}";
            $n++;
        }
        return $slug;
    }
}
