<?php

/**
 * DAO de jogos (tabela "produtos" + view "vw_precos_jogos").
 * Toda listagem usa a view, então o preço já vem com a promoção ativa.
 */
class JogoDAO
{
    // SELECT usado por todas as listagens: dados do jogo + preço + capa
    // + categorias + plataformas + nota média + vendas
    private const SELECT_BASE = "
        SELECT p.*,
               v.preco_base, v.preco_final, v.percentual,
               v.promocao_id, v.promocao_nome, v.promocao_fim,
               m.nome AS marca_nome, e.nome AS editora_nome,
               (SELECT i.caminho_url FROM produto_imagens i
                 WHERE i.produto_id = p.id AND i.tipo = 'CAPA' AND i.ativo = 1
                 ORDER BY i.principal DESC, i.ordem, i.id LIMIT 1) AS capa,
               (SELECT GROUP_CONCAT(c.nome ORDER BY c.nome SEPARATOR '||')
                  FROM produto_categoria pc JOIN categorias c ON c.id = pc.categoria_id
                 WHERE pc.produto_id = p.id) AS categorias,
               (SELECT GROUP_CONCAT(pl.nome ORDER BY pl.id SEPARATOR '||')
                  FROM produto_plataforma pp JOIN plataformas pl ON pl.id = pp.plataforma_id
                 WHERE pp.produto_id = p.id) AS plataformas,
               (SELECT ROUND(AVG(a.nota), 1) FROM avaliacoes a WHERE a.produto_id = p.id) AS nota_media,
               (SELECT COUNT(*) FROM avaliacoes a WHERE a.produto_id = p.id) AS total_avaliacoes,
               (SELECT COUNT(*) FROM avaliacoes a WHERE a.produto_id = p.id AND a.nota >= 4) AS avaliacoes_positivas,
               (SELECT COUNT(*) FROM pedido_itens it JOIN pedidos pe ON pe.id = it.pedido_id
                 WHERE it.produto_id = p.id AND pe.status = 'CONCLUIDO') AS vendas
          FROM produtos p
          JOIN vw_precos_jogos v ON v.produto_id = p.id
          LEFT JOIN marcas m   ON m.id = p.marca_id
          LEFT JOIN editoras e ON e.id = p.editora_id
    ";

    // Ordenações permitidas na loja (nunca colocamos texto do usuário no ORDER BY)
    private const ORDENS = [
        'populares'   => 'vendas DESC, p.popular DESC, p.destaque DESC, p.nome ASC',
        'preco-asc'   => 'v.preco_final ASC, p.nome ASC',
        'preco-desc'  => 'v.preco_final DESC, p.nome ASC',
        'avaliacao'   => 'nota_media DESC, total_avaliacoes DESC, p.nome ASC',
        'lancamentos' => 'p.ano_lancamento DESC, p.data_cadastro DESC, p.id DESC',
        'nome'        => 'p.nome ASC',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    // Executa o SELECT_BASE com filtros e devolve objetos Jogo
    private function consultar(string $where, array $params, string $ordem, int $limite = 0, int $offset = 0): array
    {
        $sql = self::SELECT_BASE . " WHERE {$where} ORDER BY {$ordem}";
        if ($limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite . ' OFFSET ' . (int) $offset;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map(fn ($linha) => Jogo::deLinha($linha), $stmt->fetchAll());
    }

    // Monta o WHERE da loja a partir dos filtros da URL
    private function montarFiltros(array $f): array
    {
        $where = ['p.deleted_at IS NULL', 'p.ativo = 1'];
        $params = [];

        if (!empty($f['busca'])) {
            $where[] = 'p.nome LIKE ?';
            $params[] = '%' . $f['busca'] . '%';
        }
        if (!empty($f['categoria'])) {
            $where[] = 'EXISTS (SELECT 1 FROM produto_categoria pc WHERE pc.produto_id = p.id AND pc.categoria_id = ?)';
            $params[] = (int) $f['categoria'];
        }
        if (!empty($f['plataforma'])) {
            $where[] = 'EXISTS (SELECT 1 FROM produto_plataforma pp WHERE pp.produto_id = p.id AND pp.plataforma_id = ?)';
            $params[] = (int) $f['plataforma'];
        }
        $faixas = ['ate-50' => 'v.preco_final <= 50', 'ate-100' => 'v.preco_final <= 100',
                   'ate-150' => 'v.preco_final <= 150', 'acima-150' => 'v.preco_final > 150'];
        if (!empty($f['preco']) && isset($faixas[$f['preco']])) {
            $where[] = $faixas[$f['preco']];
        }
        if (!empty($f['promocao'])) {
            $where[] = 'v.percentual > 0';
        }
        if (!empty($f['nota_min'])) {
            $where[] = '(SELECT AVG(a.nota) FROM avaliacoes a WHERE a.produto_id = p.id) >= ?';
            $params[] = (float) $f['nota_min'];
        }
        if (!empty($f['ids'])) {
            $marcadores = implode(',', array_fill(0, count($f['ids']), '?'));
            $where[] = "p.id IN ({$marcadores})";
            array_push($params, ...$f['ids']);
        }
        return [implode(' AND ', $where), $params];
    }

    // Catálogo da loja com paginação: devolve [jogos, total]
    public function listar(array $filtros, int $pagina, int $limite): array
    {
        [$where, $params] = $this->montarFiltros($filtros);
        $ordem = self::ORDENS[$filtros['ordem'] ?? ''] ?? self::ORDENS['populares'];

        $jogos = $this->consultar($where, $params, $ordem, $limite, ($pagina - 1) * $limite);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produtos p JOIN vw_precos_jogos v ON v.produto_id = p.id WHERE {$where}");
        $stmt->execute($params);

        return [$jogos, (int) $stmt->fetchColumn()];
    }

    public function destaques(): array
    {
        return $this->consultar('p.deleted_at IS NULL AND p.ativo = 1 AND p.destaque = 1', [], 'p.id', 10);
    }

    public function emPromocao(int $limite): array
    {
        return $this->consultar('p.deleted_at IS NULL AND p.ativo = 1 AND v.percentual > 0', [],
            'v.percentual DESC, v.promocao_fim ASC, p.nome ASC', $limite);
    }

    public function maisVendidos(int $limite): array
    {
        return $this->consultar('p.deleted_at IS NULL AND p.ativo = 1', [], self::ORDENS['populares'], $limite);
    }

    public function lancamentos(int $limite): array
    {
        return $this->consultar('p.deleted_at IS NULL AND p.ativo = 1', [], self::ORDENS['lancamentos'], $limite);
    }

    // Linha completa do jogo (para o detalhe). Admin pode ver inativos.
    public function buscarLinha(int $id, bool $somenteAtivos = true): ?array
    {
        $where = 'p.id = ? AND p.deleted_at IS NULL' . ($somenteAtivos ? ' AND p.ativo = 1' : '');
        $stmt = $this->pdo->prepare(self::SELECT_BASE . " WHERE {$where}");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // 3 jogos da mesma categoria ou da mesma editora
    public function relacionados(int $id): array
    {
        $where = "p.deleted_at IS NULL AND p.ativo = 1 AND p.id <> ? AND (
                    EXISTS (SELECT 1 FROM produto_categoria pc
                             WHERE pc.produto_id = p.id
                               AND pc.categoria_id IN (SELECT categoria_id FROM produto_categoria WHERE produto_id = ?))
                    OR (p.editora_id IS NOT NULL AND p.editora_id = (SELECT editora_id FROM produtos WHERE id = ?))
                  )";
        return $this->consultar($where, [$id, $id, $id], 'vendas DESC, p.popular DESC, p.id ASC', 3);
    }

    // ---------- Admin ----------

    public function listarAdmin(string $busca = ''): array
    {
        $where = 'p.deleted_at IS NULL';
        $params = [];
        if ($busca !== '') {
            $where .= ' AND p.nome LIKE ?';
            $params[] = "%{$busca}%";
        }
        return $this->consultar($where, $params, 'p.nome ASC');
    }

    public function idsCategorias(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT categoria_id FROM produto_categoria WHERE produto_id = ?');
        $stmt->execute([$id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function idsPlataformas(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT plataforma_id FROM produto_plataforma WHERE produto_id = ?');
        $stmt->execute([$id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function slugEmUso(string $slug, int $ignorarId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM produtos WHERE slug = ? AND id <> ?');
        $stmt->execute([$slug, $ignorarId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Colunas que o formulário do admin pode gravar
    private const COLUNAS = [
        'nome', 'slug', 'descricao_curta', 'descricao', 'preco', 'tipo_midia', 'edicao', 'ano_lancamento',
        'versao_console', 'espaco_minimo_requerido', 'req_so', 'req_cpu', 'req_gpu', 'req_ram',
        'marca_id', 'editora_id', 'estoque', 'destaque', 'popular', 'selo', 'ativo',
    ];

    public function criar(array $d, int $criadoPor): int
    {
        $colunas = implode(', ', self::COLUNAS);
        $marcadores = implode(', ', array_fill(0, count(self::COLUNAS), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO produtos ({$colunas}, criado_por) VALUES ({$marcadores}, ?)");
        $valores = array_map(fn ($c) => $d[$c], self::COLUNAS);
        $valores[] = $criadoPor;
        $stmt->execute($valores);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $d): void
    {
        $sets = implode(', ', array_map(fn ($c) => "{$c} = ?", self::COLUNAS));
        $stmt = $this->pdo->prepare("UPDATE produtos SET {$sets} WHERE id = ?");
        $valores = array_map(fn ($c) => $d[$c], self::COLUNAS);
        $valores[] = $id;
        $stmt->execute($valores);
    }

    public function definirCategorias(int $id, array $ids): void
    {
        $this->pdo->prepare('DELETE FROM produto_categoria WHERE produto_id = ?')->execute([$id]);
        $stmt = $this->pdo->prepare('INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)');
        foreach ($ids as $categoriaId) {
            $stmt->execute([$id, $categoriaId]);
        }
    }

    public function definirPlataformas(int $id, array $ids): void
    {
        $this->pdo->prepare('DELETE FROM produto_plataforma WHERE produto_id = ?')->execute([$id]);
        $stmt = $this->pdo->prepare('INSERT INTO produto_plataforma (produto_id, plataforma_id) VALUES (?, ?)');
        foreach ($ids as $plataformaId) {
            $stmt->execute([$id, $plataformaId]);
        }
    }

    // Exclusão lógica: o jogo some da loja, mas os pedidos antigos continuam
    public function excluir(int $id): void
    {
        $this->pdo->prepare('UPDATE produtos SET deleted_at = NOW(), ativo = 0, destaque = 0 WHERE id = ?')->execute([$id]);
    }

    public function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM produtos WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ---------- Checkout ----------

    // Lê o jogo TRAVANDO a linha até o fim da transação (evita vender a mesma chave 2x)
    public function travarParaCompra(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, estoque, ativo, deleted_at FROM produtos WHERE id = ? FOR UPDATE'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Preço atual vindo da view (o preço NUNCA vem do navegador)
    public function precoAtual(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT preco_base, preco_final, percentual FROM vw_precos_jogos WHERE produto_id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function baixarEstoque(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE produtos SET estoque = estoque - 1 WHERE id = ? AND estoque > 0');
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }
}
