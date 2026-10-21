<?php

/**
 * MODEL Promocao: desconto em % aplicado a uma lista de jogos
 * durante um período (data_inicio até data_fim).
 */
class Promocao
{
    public int $id;
    public string $nome;
    public ?string $descricao;
    public float $percentual;
    public string $dataInicio;
    public string $dataFim;
    public bool $ativo;
    public array $jogos = [];   // ids dos jogos

    public static function deLinha(array $l): self
    {
        $p = new self();
        $p->id         = (int) $l['id'];
        $p->nome       = $l['nome'];
        $p->descricao  = $l['descricao'];
        $p->percentual = (float) $l['percentual_desconto'];
        $p->dataInicio = $l['data_inicio'];
        $p->dataFim    = $l['data_fim'];
        $p->ativo      = (bool) $l['ativo'];
        $p->jogos      = !empty($l['jogos']) ? array_map('intval', explode(',', $l['jogos'])) : [];
        return $p;
    }

    // Situação para mostrar no painel
    public function situacao(): string
    {
        $agora = date('Y-m-d H:i:s');
        if (!$this->ativo) {
            return 'Desligada';
        }
        if ($agora < $this->dataInicio) {
            return 'Agendada';
        }
        if ($agora > $this->dataFim) {
            return 'Encerrada';
        }
        return 'Ativa agora';
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'nome'        => $this->nome,
            'descricao'   => $this->descricao,
            'percentual'  => $this->percentual,
            'data_inicio' => $this->dataInicio,
            'data_fim'    => $this->dataFim,
            'ativo'       => $this->ativo,
            'situacao'    => $this->situacao(),
            'jogos'       => $this->jogos,
        ];
    }
}
