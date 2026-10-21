<?php

/**
 * DAO de pedidos, itens (com o código de resgate) e pagamentos.
 */
class PedidoDAO
{
    // Pedido + cliente + cupom + pagamento (um pagamento por pedido)
    private const SELECT_PEDIDOS = "
        SELECT pe.*, c.codigo AS cupom_codigo,
               COALESCE(u.nome_exibicao, u.nome) AS cliente_nome, u.email AS cliente_email,
               pg.metodo, pg.status AS pagamento_status, pg.parcelas,
               pg.ultimos_4_digitos, pg.bandeira, pg.emissor
          FROM pedidos pe
          JOIN usuarios u         ON u.id = pe.usuario_id
          LEFT JOIN cupons c      ON c.id = pe.cupom_id
          LEFT JOIN pagamentos pg ON pg.pedido_id = pe.id
    ";

    public function __construct(private PDO $pdo)
    {
    }

    public function criar(int $usuarioId, float $subtotal, float $desconto, float $total, ?int $cupomId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO pedidos (usuario_id, subtotal, desconto, valor_total, cupom_id, status)
             VALUES (?, ?, ?, ?, ?, 'CONCLUIDO')"
        );
        $stmt->execute([$usuarioId, $subtotal, $desconto, $total, $cupomId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function codigoExiste(string $codigo): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM pedido_itens WHERE codigo_resgate = ?');
        $stmt->execute([$codigo]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function adicionarItem(int $pedidoId, int $produtoId, float $preco, string $codigo): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, subtotal, codigo_resgate)
             VALUES (?, ?, 1, ?, ?, ?)'
        );
        $stmt->execute([$pedidoId, $produtoId, $preco, $preco, $codigo]);
        return (int) $this->pdo->lastInsertId();
    }

    public function registrarPagamento(array $p): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO pagamentos (pedido_id, forma_pagamento_id, metodo, status, valor, parcelas,
                                     ultimos_4_digitos, bandeira, emissor, identificador_transacao, data_pagamento)
             VALUES (?, ?, ?, 'APROVADO', ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $p['pedido_id'], $p['forma_pagamento_id'], $p['metodo'], $p['valor'], $p['parcelas'],
            $p['ultimos4'], $p['bandeira'], $p['emissor'], $p['transacao'],
        ]);
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::SELECT_PEDIDOS . ' WHERE pe.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function listarDoUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(self::SELECT_PEDIDOS . ' WHERE pe.usuario_id = ? ORDER BY pe.data_criacao DESC, pe.id DESC');
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public function listarTodos(int $limite = 0): array
    {
        $sql = self::SELECT_PEDIDOS . ' ORDER BY pe.data_criacao DESC, pe.id DESC';
        if ($limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }
        return $this->pdo->query($sql)->fetchAll();
    }

    // Itens de vários pedidos de uma vez (com nome e capa do jogo)
    public function itensDosPedidos(array $pedidoIds): array
    {
        if (!$pedidoIds) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($pedidoIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT it.*, pr.nome,
                    (SELECT i.caminho_url FROM produto_imagens i
                      WHERE i.produto_id = pr.id AND i.tipo = 'CAPA' AND i.ativo = 1
                      ORDER BY i.principal DESC, i.ordem, i.id LIMIT 1) AS capa
               FROM pedido_itens it
               JOIN produtos pr ON pr.id = it.produto_id
              WHERE it.pedido_id IN ({$marcadores})
              ORDER BY it.id"
        );
        $stmt->execute($pedidoIds);
        return $stmt->fetchAll();
    }

    // Item + dono do pedido (para conferir quem pode ver o código)
    public function buscarItem(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT it.id, it.produto_id, it.codigo_resgate, pe.usuario_id, pr.nome
               FROM pedido_itens it
               JOIN pedidos pe  ON pe.id = it.pedido_id
               JOIN produtos pr ON pr.id = it.produto_id
              WHERE it.id = ?'
        );
        $stmt->execute([$itemId]);
        return $stmt->fetch() ?: null;
    }
}
