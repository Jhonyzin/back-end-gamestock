<?php

class Jogo
{
    public int $id;
    public string $nome;
    public string $slug;
    public ?string $descricaoCurta;
    public ?string $capa;
    public array $categorias = [];
    public array $plataformas = [];
    public float $precoBase;
    public float $precoFinal;
    public float $percentual;
    public ?array $promocao = null;
    public ?float $notaMedia;
    public int $totalAvaliacoes;
    public int $avaliacoesPositivas;
    public int $vendas;
    public int $estoque;
    public bool $destaque;
    public bool $popular;
    public ?string $selo;
    public ?int $anoLancamento;
    public bool $ativo;
    public bool $possui = false;
    public bool $desejado = false;
    public array $extras = [];

    public static function deLinha(array $l): self
    {
        $j = new self();
        $j->id                  = (int) $l['id'];
        $j->nome                = $l['nome'];
        $j->slug                = $l['slug'];
        $j->descricaoCurta      = $l['descricao_curta'];
        $j->capa                = $l['capa'] ?? null;
        $j->categorias          = $l['categorias'] ?? [];
        $j->plataformas         = $l['plataformas'] ?? [];
        $j->precoBase           = (float) $l['preco_base'];
        $j->precoFinal          = (float) $l['preco_final'];
        $j->percentual          = (float) $l['percentual'];
        $j->notaMedia           = $l['nota_media'] !== null ? (float) $l['nota_media'] : null;
        $j->totalAvaliacoes     = (int) $l['total_avaliacoes'];
        $j->avaliacoesPositivas = (int) $l['avaliacoes_positivas'];
        $j->vendas              = (int) $l['vendas'];
        $j->estoque             = (int) $l['estoque'];
        $j->destaque            = (bool) $l['destaque'];
        $j->popular             = (bool) $l['popular'];
        $j->selo                = $l['selo'];
        $j->anoLancamento       = $l['ano_lancamento'] !== null ? (int) $l['ano_lancamento'] : null;
        $j->ativo               = (bool) $l['ativo'];

        if (!empty($l['promocao_id'])) {
            $j->promocao = [
                'id'   => (int) $l['promocao_id'],
                'nome' => $l['promocao_nome'],
                'fim'  => $l['promocao_fim'],
            ];
        }
        return $j;
    }

    public function adicionarDetalhes(array $l, array $imagens, array $videos): void
    {
        $this->extras = [
            'descricao'               => $l['descricao'],
            'tipo_midia'              => $l['tipo_midia'],
            'edicao'                  => $l['edicao'],
            'versao_console'          => $l['versao_console'],
            'espaco_minimo_requerido' => $l['espaco_minimo_requerido'],
            'requisitos' => [
                'so'     => $l['req_so'],
                'cpu'    => $l['req_cpu'],
                'gpu'    => $l['req_gpu'],
                'ram'    => $l['req_ram'],
                'espaco' => $l['espaco_minimo_requerido'],
            ],
            'marca'   => $l['marca_id'] ? ['id' => (int) $l['marca_id'], 'nome' => $l['marca_nome']] : null,
            'editora' => $l['editora_id'] ? ['id' => (int) $l['editora_id'], 'nome' => $l['editora_nome']] : null,
            'imagens' => $imagens,
            'videos'  => $videos,
        ];
    }

    public function toArray(): array
    {
        return array_merge([
            'id'                   => $this->id,
            'nome'                 => $this->nome,
            'slug'                 => $this->slug,
            'descricao_curta'      => $this->descricaoCurta,
            'capa'                 => $this->capa,
            'categorias'           => $this->categorias,
            'plataformas'          => $this->plataformas,
            'preco_base'           => $this->precoBase,
            'preco_final'          => $this->precoFinal,
            'percentual'           => $this->percentual,
            'promocao'             => $this->promocao,
            'nota_media'           => $this->notaMedia,
            'total_avaliacoes'     => $this->totalAvaliacoes,
            'avaliacoes_positivas' => $this->avaliacoesPositivas,
            'vendas'               => $this->vendas,
            'estoque'              => $this->estoque,
            'destaque'             => $this->destaque,
            'popular'              => $this->popular,
            'selo'                 => $this->selo,
            'ano_lancamento'       => $this->anoLancamento,
            'ativo'                => $this->ativo,
            'possui'               => $this->possui,
            'desejado'             => $this->desejado,
        ], $this->extras);
    }
}
