<?php

require_once __DIR__ . '/Ambiente.php';

class Bancodados
{
    private string $driver;
    private string $host;
    private string $port;
    private string $database;
    private string $username;
    private string $password;

    private static ?PDO $conexaoAtual = null;

    public function __construct()
    {
        $this->driver   = Ambiente::ler('DB_DRIVER', 'pgsql');
        $this->host     = Ambiente::ler('DB_HOST', 'localhost');
        $this->port     = Ambiente::ler('DB_PORT', $this->driver === 'pgsql' ? '5432' : '3306');
        $this->database = Ambiente::ler('DB_NAME');
        $this->username = Ambiente::ler('DB_USER');
        $this->password = Ambiente::ler('DB_PASS');

        $url = trim(Ambiente::ler('DATABASE_URL'), " 	
\"'");
        if ($url !== '') {
            $partes = parse_url($url);
            if (!isset($partes['host'])) {
                throw new ErroApi('DATABASE_URL invalida. Use postgresql://usuario:senha@host:5432/banco', 500);
            }
            $this->driver   = 'pgsql';
            $this->host     = $partes['host'] ?? $this->host;
            $this->port     = (string) ($partes['port'] ?? '5432');
            $this->database = ltrim($partes['path'] ?? '', '/');
            $this->username = urldecode($partes['user'] ?? '');
            $this->password = urldecode($partes['pass'] ?? '');
        }
    }

    // conexao com o banco
    public function conectar(): PDO
    {
        $opcoes = [];

        if ($this->driver === 'pgsql') {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";

            // url externa do render precisa de ssl
            if (Ambiente::booleano('DB_SSL') || str_contains($this->host, '.render.com')) {
                $dsn .= ';sslmode=require';
            }
        } else {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
            if (Ambiente::booleano('DB_SSL')) {
                $opcoes[PDO::MYSQL_ATTR_SSL_CA] = Ambiente::ler('DB_SSL_CA', '/etc/ssl/certs/ca-certificates.crt');
                $opcoes[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        try {
            $pdo = new PDO($dsn, $this->username, $this->password, $opcoes);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            if ($this->driver === 'pgsql') {
                $pdo->exec("SET TIME ZONE 'America/Sao_Paulo'");
            } else {
                $pdo->exec("SET time_zone = '-03:00'");
            }

            return $pdo;
        } catch (PDOException $e) {
            error_log('Erro ao conectar no banco: ' . $e->getMessage());
            $mensagem = 'Não foi possível conectar ao banco.';
            if (Ambiente::booleano('APP_DEBUG')) {
                $mensagem .= ' Detalhe: ' . $e->getMessage();
            }
            throw new ErroApi($mensagem, 500);
        }
    }

    public static function conexao(): PDO
    {
        if (self::$conexaoAtual === null) {
            self::$conexaoAtual = (new Bancodados())->conectar();
        }
        return self::$conexaoAtual;
    }
}
