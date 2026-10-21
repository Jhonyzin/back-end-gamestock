<?php

/**
 * Representa a requisição HTTP: método, caminho, corpo, arquivos e token.
 */
class Request
{
    public string $metodo;
    public string $caminho;
    public array $params = [];         // parâmetros da rota, ex.: {id}
    public ?Usuario $usuario = null;   // preenchido pelo Router quando há login
    private array $corpo;

    public function __construct()
    {
        $this->metodo  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->caminho = $this->descobrirCaminho();
        $this->corpo   = $this->lerCorpo();
    }

    // Remove a pasta do projeto e o prefixo /api do endereço.
    // XAMPP:  /gamestock/backend/api/jogos  -> /jogos
    // Render: /api/jogos                    -> /jogos
    private function descobrirCaminho(): string
    {
        $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $caminho = rawurldecode($caminho);

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $pasta  = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if ($script !== '' && str_starts_with($caminho, $script)) {
            $caminho = substr($caminho, strlen($script));      // .../index.php/api/jogos
        } elseif ($pasta !== '' && str_starts_with($caminho, $pasta)) {
            $caminho = substr($caminho, strlen($pasta));
        }

        if (str_starts_with($caminho, '/api')) {
            $caminho = substr($caminho, 4);
        }

        $caminho = '/' . trim($caminho, '/');
        return $caminho;
    }

    // Corpo em JSON (fetch) ou formulário multipart (upload)
    private function lerCorpo(): array
    {
        $tipo = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($tipo, 'application/json')) {
            $json = json_decode(file_get_contents('php://input'), true);
            return is_array($json) ? $json : [];
        }
        return $_POST;
    }

    // Parâmetro da rota (ex.: id de /jogos/{id})
    public function param(string $nome): string
    {
        return $this->params[$nome] ?? '';
    }

    // Parâmetro da URL (?busca=...)
    public function query(string $nome, mixed $padrao = null): mixed
    {
        $valor = $_GET[$nome] ?? $padrao;
        return is_string($valor) ? trim($valor) : $valor;
    }

    // Campo do corpo da requisição
    public function input(string $nome, mixed $padrao = null): mixed
    {
        $valor = $this->corpo[$nome] ?? $padrao;
        return is_string($valor) ? trim($valor) : $valor;
    }

    public function todos(): array
    {
        return $this->corpo;
    }

    public function arquivo(string $nome): ?array
    {
        return $_FILES[$nome] ?? null;
    }

    // Lê "Authorization: Bearer <token>"
    public function token(): ?string
    {
        $cabecalho = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if ($cabecalho === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $nome => $valor) {
                if (strtolower($nome) === 'authorization') {
                    $cabecalho = $valor;
                }
            }
        }

        if (preg_match('/Bearer\s+([a-f0-9]{64})/i', $cabecalho, $m)) {
            return strtolower($m[1]);
        }
        return null;
    }
}
