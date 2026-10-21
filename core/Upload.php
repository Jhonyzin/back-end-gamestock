<?php

class Upload
{
    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public static function imagem(?array $arquivo, string $pasta, int $maxMegabytes): string
    {
        if (!$arquivo || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ErroApi('Envie um arquivo de imagem.', 422);
        }
        if (in_array($arquivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new ErroApi("A imagem deve ter no máximo {$maxMegabytes} MB.", 422);
        }
        if ($arquivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new ErroApi('Falha no envio do arquivo.', 422);
        }
        if ($arquivo['size'] > $maxMegabytes * 1024 * 1024) {
            throw new ErroApi("A imagem deve ter no máximo {$maxMegabytes} MB.", 422);
        }

        $info = @getimagesize($arquivo['tmp_name']);
        if ($info === false || !isset(self::TIPOS[$info['mime']])) {
            throw new ErroApi('O arquivo enviado não é uma imagem válida (use JPG, PNG ou WEBP).', 422);
        }

        $pastaDestino = __DIR__ . '/../uploads/' . $pasta;
        if (!is_dir($pastaDestino)) {
            mkdir($pastaDestino, 0775, true);
        }

        $nome = bin2hex(random_bytes(12)) . '.' . self::TIPOS[$info['mime']];
        if (!move_uploaded_file($arquivo['tmp_name'], $pastaDestino . '/' . $nome)) {
            throw new ErroApi('Não foi possível salvar a imagem.', 500);
        }

        return "/uploads/{$pasta}/{$nome}";
    }

    public static function apagar(?string $caminho): void
    {
        if (!$caminho || !str_starts_with($caminho, '/uploads/')) {
            return;
        }
        $pastaUploads = realpath(__DIR__ . '/../uploads');
        $arquivo = realpath(__DIR__ . '/..' . $caminho);
        if ($arquivo && $pastaUploads && str_starts_with($arquivo, $pastaUploads) && is_file($arquivo)) {
            unlink($arquivo);
        }
    }
}
