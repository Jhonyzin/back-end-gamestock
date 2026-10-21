<?php

/**
 * MODEL Usuario: representa uma linha da tabela "usuarios".
 * O toArray() NUNCA devolve o senha_hash.
 */
class Usuario
{
    public int $id;
    public string $nome;
    public ?string $nomeExibicao;
    public string $email;
    public ?string $telefone;
    public string $tipoUsuario;
    public ?string $fotoPerfil;
    public ?string $bio;
    public ?string $regiao;
    public bool $ativo;
    public ?string $dataCadastro;

    // Cria o objeto a partir da linha vinda do banco
    public static function deLinha(array $linha): self
    {
        $u = new self();
        $u->id           = (int) $linha['id'];
        $u->nome         = $linha['nome'];
        $u->nomeExibicao = $linha['nome_exibicao'] ?? null;
        $u->email        = $linha['email'];
        $u->telefone     = $linha['telefone'] ?? null;
        $u->tipoUsuario  = $linha['tipo_usuario'];
        $u->fotoPerfil   = $linha['foto_perfil'] ?? null;
        $u->bio          = $linha['bio'] ?? null;
        $u->regiao       = $linha['regiao'] ?? null;
        $u->ativo        = (bool) ($linha['ativo'] ?? true);
        $u->dataCadastro = $linha['data_cadastro'] ?? null;
        return $u;
    }

    public function ehAdmin(): bool
    {
        return $this->tipoUsuario === 'ADMINISTRADOR';
    }

    // Nome mostrado na tela (nome de exibição ou o nome completo)
    public function nomeVisivel(): string
    {
        return $this->nomeExibicao ?: $this->nome;
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'nome'          => $this->nome,
            'nome_exibicao' => $this->nomeVisivel(),
            'email'         => $this->email,
            'telefone'      => $this->telefone,
            'tipo_usuario'  => $this->tipoUsuario,
            'foto_perfil'   => $this->fotoPerfil,
            'bio'           => $this->bio,
            'regiao'        => $this->regiao,
            'ativo'         => $this->ativo,
            'data_cadastro' => $this->dataCadastro,
        ];
    }
}
