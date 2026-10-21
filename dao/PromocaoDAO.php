<?php

/**
 * DAO de promoções e dos jogos de cada promoção.
 */
class PromocaoDAO
{
    private const SELECT_BASE = "
        SELECT pr.*,
               (SELECT GROUP_CONCAT(pp.produto_id) FROM promocao_produto pp WHERE pp.promocao_id = pr.id) AS jogos
          FROM promocoes pr
    ";

    public function __construct(private PDO $pdo)
    {
    }

    public function listar(): array
    {
        $stmt = $this->pdo->query(self::SELECT_BASE . ' ORDER BY pr.ativo DESC, pr.data_fim DESC, pr.id DESC');
        return array_map(fn ($l) => Promocao::deLinha($l), $stmt->fetchAll());
    }

    public function buscar(int $id): ?Promocao
    {
        $stmt = $this->pdo->prepare(self::SELECT_BASE . ' WHERE pr.id = ?');
        $stmt->execute([$id]);
        $linha = $stmt->fetch();
        return $linha ? Promocao::deLinha($linha) : null;
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
