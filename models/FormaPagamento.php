<?php

/**
 * MODEL FormaPagamento: cartão salvo do comprador.
 * Só existe o que é seguro guardar: 4 últimos dígitos, bandeira,
 * banco e validade. Nada de número completo ou CVV.
 */
class FormaPagamento
{
    public int $id;
    public string $tipo;
    public ?string $apelido;
    public string $nomeTitular;
    public string $ultimos4;
    public ?string $bandeira;
    public ?string $emissor;
    public int $validadeMes;
    public int $validadeAno;
    public bool $padrao;
    public ?array $banco = null;   // logo e cor do banco (tabela bins_cartao)

    public static function deLinha(array $l): self
    {
        $f = new self();
        $f->id          = (int) $l['id'];
        $f->tipo        = $l['tipo'];
        $f->apelido     = $l['apelido'];
        $f->nomeTitular = $l['nome_titular'];
        $f->ultimos4    = $l['ultimos_4_digitos'];
        $f->bandeira    = $l['bandeira'];
        $f->emissor     = $l['emissor'];
        $f->validadeMes = (int) $l['validade_mes'];
        $f->validadeAno = (int) $l['validade_ano'];
        $f->padrao      = (bool) $l['padrao'];
        return $f;
    }

    // "MM/AA"
    public function validadeTexto(): string
    {
        return sprintf('%02d/%02d', $this->validadeMes, $this->validadeAno % 100);
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'tipo'              => $this->tipo,
            'apelido'           => $this->apelido,
            'nome_titular'      => $this->nomeTitular,
            'ultimos_4_digitos' => $this->ultimos4,
            'bandeira'          => $this->bandeira,
            'emissor'           => $this->emissor,
            'validade'          => $this->validadeTexto(),
            'vencido'           => !Cartao::validadeOk($this->validadeMes, $this->validadeAno),
            'padrao'            => $this->padrao,
            'banco'             => $this->banco,
        ];
    }
}
