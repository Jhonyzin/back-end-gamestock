<?php

class Avaliacao
{
    public int $id;
    public int $produtoId;
    public ?string $produtoNome;
    public int $usuarioId;
    public string $autor;
    public ?string $fotoAutor;
    public int $nota;
    public ?string $comentario;
    public string $dataCriacao;

    public static function deLinha(array $l): self
    {
        $a = new self();
        $a->id          = (int) $l['id'];
        $a->produtoId   = (int) $l['produto_id'];
        $a->produtoNome = $l['produto_nome'] ?? null;
        $a->usuarioId   = (int) $l['usuario_id'];
        $a->autor       = $l['autor'] ?? '';
        $a->fotoAutor   = $l['foto_autor'] ?? null;
        $a->nota        = (int) $l['nota'];
        $a->comentario  = $l['comentario'];
        $a->dataCriacao = $l['data_atualizacao'] ?? $l['data_criacao'];
        return $a;
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'produto_id'   => $this->produtoId,
            'produto_nome' => $this->produtoNome,
            'usuario_id'   => $this->usuarioId,
            'autor'        => $this->autor,
            'foto_autor'   => $this->fotoAutor,
            'nota'         => $this->nota,
            'comentario'   => $this->comentario,
            'data'         => $this->dataCriacao,
        ];
    }
}
