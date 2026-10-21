<?php

class FormaPagamentoDAO
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM formas_pagamento WHERE usuario_id = ? AND ativo = 1 ORDER BY padrao DESC, id DESC'
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public function buscar(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM formas_pagamento WHERE id = ? AND usuario_id = ? AND ativo = 1');
        $stmt->execute([$id, $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public function contar(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM formas_pagamento WHERE usuario_id = ? AND ativo = 1');
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public function criar(int $usuarioId, array $d): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO formas_pagamento
                (usuario_id, tipo, apelido, nome_titular, bin6, ultimos_4_digitos, bandeira, emissor, validade_mes, validade_ano, padrao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $usuarioId, $d['tipo'], $d['apelido'], $d['titular'], $d['bin6'], $d['ultimos4'],
            $d['bandeira'], $d['emissor'], $d['mes'], $d['ano'], $d['padrao'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function tornarPadrao(int $id, int $usuarioId): void
    {
        $this->pdo->prepare('UPDATE formas_pagamento SET padrao = 0 WHERE usuario_id = ?')->execute([$usuarioId]);
        $this->pdo->prepare('UPDATE formas_pagamento SET padrao = 1 WHERE id = ? AND usuario_id = ?')->execute([$id, $usuarioId]);
    }

    public function remover(int $id, int $usuarioId): void
    {
        $this->pdo->prepare('DELETE FROM formas_pagamento WHERE id = ? AND usuario_id = ?')->execute([$id, $usuarioId]);
    }

    public function garantirPadrao(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM formas_pagamento WHERE usuario_id = ? AND ativo = 1 AND padrao = 1');
        $stmt->execute([$usuarioId]);
        if ((int) $stmt->fetchColumn() === 0) {
            $stmt = $this->pdo->prepare('SELECT id FROM formas_pagamento WHERE usuario_id = ? AND ativo = 1 ORDER BY id DESC LIMIT 1');
            $stmt->execute([$usuarioId]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $this->pdo->prepare('UPDATE formas_pagamento SET padrao = 1 WHERE id = ?')->execute([$id]);
            }
        }
    }
}
