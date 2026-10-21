<?php

class Pedido
{
    public int $id;
    public string $dataCriacao;
    public string $status;
    public float $subtotal;
    public float $desconto;
    public float $valorTotal;
    public ?string $cupom;
    public ?array $cliente = null;
    public ?array $pagamento = null;
    public array $itens = [];

    public static function deLinha(array $l): self
    {
        $p = new self();
        $p->id          = (int) $l['id'];
        $p->dataCriacao = $l['data_criacao'];
        $p->status      = $l['status'];
        $p->subtotal    = (float) $l['subtotal'];
        $p->desconto    = (float) $l['desconto'];
        $p->valorTotal  = (float) $l['valor_total'];
        $p->cupom       = $l['cupom_codigo'] ?? null;

        if (isset($l['cliente_nome'])) {
            $p->cliente = ['id' => (int) $l['usuario_id'], 'nome' => $l['cliente_nome'], 'email' => $l['cliente_email']];
        }
        if (!empty($l['metodo'])) {
            $p->pagamento = [
                'metodo'            => $l['metodo'],
                'status'            => $l['pagamento_status'],
                'parcelas'          => (int) $l['parcelas'],
                'ultimos_4_digitos' => $l['ultimos_4_digitos'],
                'bandeira'          => $l['bandeira'],
                'emissor'           => $l['emissor'],
            ];
        }
        return $p;
    }

    public static function mascararCodigo(string $codigo): string
    {
        return 'GS-****-****-' . substr($codigo, -4);
    }

    public function adicionarItem(array $l, ?array $avaliacao = null, bool $mostrarCodigo = false): void
    {
        $this->itens[] = [
            'item_id'        => (int) $l['id'],
            'produto_id'     => (int) $l['produto_id'],
            'nome'           => $l['nome'],
            'capa'           => $l['capa'] ?? null,
            'preco_unitario' => (float) $l['preco_unitario'],
            'codigo_resgate' => $mostrarCodigo ? $l['codigo_resgate'] : self::mascararCodigo($l['codigo_resgate']),
            'avaliacao'      => $avaliacao,
        ];
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'data_criacao' => $this->dataCriacao,
            'status'       => $this->status,
            'subtotal'     => $this->subtotal,
            'desconto'     => $this->desconto,
            'valor_total'  => $this->valorTotal,
            'cupom'        => $this->cupom,
            'cliente'      => $this->cliente,
            'pagamento'    => $this->pagamento,
            'itens'        => $this->itens,
        ];
    }
}
