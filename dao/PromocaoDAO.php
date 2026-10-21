<?php

class PromocaoDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private function jogosPorPromocao(): array
    {
        $mapa = [];
        foreach ($this->pdo->query('SELECT promocao_id, produto_id FROM promocao_produto ORDER BY produto_id')->fetchAll() as $l) {
            $mapa[(int) $l['promocao_id']][] = (int) $l['produto_id'];
        }
        return $mapa;
    }

    public function listar(): array
    {
        $jogos = $this->jogosPorPromocao();
        $stmt = $this->pdo->query('SELECT * FROM promocoes ORDER BY ativo DESC, data_fim DESC, id DESC');
        return array_map(fn ($l) => Promocao::deLinha($l + ['jogos' => $jogos[(int) $l['id']] ?? []]), $stmt->fetchAll());
    }

    public function buscar(int $id): ?Promocao
    {
        $stmt = $this->pdo->prepare('SELECT * FROM promocoes WHERE id = ?');
        $stmt->execute([$id]);
        $linha = $stmt->fetch();
        if (!$linha) {
            return null;
        }
        return Promocao::deLinha($linha + ['jogos' => $this->jogosPorPromocao()[$id] ?? []]);
    }

    public function criar(array $d, int $criadoPor): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO promocoes (nome, descricao, percentual_desconto, data_inicio, data_fim, ativo, criado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$d['nome'], $d['descricao'], $d['percentual'], $d['data_inicio'], $d['data_fim'], $d['ativo'], $criadoPor]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $d): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE promocoes SET nome = ?, descricao = ?, percentual_desconto = ?, data_inicio = ?, data_fim = ?, ativo = ?
              WHERE id = ?'
        );
        $stmt->execute([$d['nome'], $d['descricao'], $d['percentual'], $d['data_inicio'], $d['data_fim'], $d['ativo'], $id]);
    }

    public function definirJogos(int $id, array $produtoIds): void
    {
        $this->pdo->prepare('DELETE FROM promocao_produto WHERE promocao_id = ?')->execute([$id]);
        $stmt = $this->pdo->prepare('INSERT INTO promocao_produto (promocao_id, produto_id) VALUES (?, ?)');
        foreach ($produtoIds as $produtoId) {
            $stmt->execute([$id, $produtoId]);
        }
    }

    public function excluir(int $id): void
    {
        $this->pdo->prepare('DELETE FROM promocoes WHERE id = ?')->execute([$id]);
    }
}
