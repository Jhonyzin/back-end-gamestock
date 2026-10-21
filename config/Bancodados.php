<?php

require_once __DIR__ . '/Ambiente.php';

/**
 * Conexão com o banco usando PDO.
 * Funciona com PostgreSQL (Render / pgAdmin) e com MySQL (XAMPP):
 * escolha com DB_DRIVER=pgsql ou DB_DRIVER=mysql.
 * As credenciais NÃO ficam no código: vêm do .env (XAMPP)
 * ou das variáveis de ambiente (Render). Ver .env.example.
 */
class Bancodados
{
    private string $driver;
    private string $host;
    private string $port;
    private string $database;
    private string $username;
    private string $password;

    // Uma única conexão por requisição (todos os DAOs usam a mesma)
    private static ?PDO $conexaoAtual = null;

    public function __construct()
    {
        $this->driver   = Ambiente::ler('DB_DRIVER', 'mysql');
        $this->host     = Ambiente::ler('DB_HOST', 'localhost');
        $this->port     = Ambiente::ler('DB_PORT', $this->driver === 'pgsql' ? '5432' : '3306');
        $this->database = Ambiente::ler('DB_NAME');
        $this->username = Ambiente::ler('DB_USER');
        $this->password = Ambiente::ler('DB_PASS');

        // No Render dá para colar só a "Internal Database URL":
        // postgresql://usuario:senha@host:5432/banco
        $url = Ambiente::ler('DATABASE_URL');
        if ($url !== '') {
            $partes = parse_url($url);
            $this->driver   = 'pgsql';
            $this->host     = $partes['host'] ?? $this->host;
            $this->port     = (string) ($partes['port'] ?? '5432');
            $this->database = ltrim($partes['path'] ?? '', '/');
            $this->username = urldecode($partes['user'] ?? '');
            $this->password = urldecode($partes['pass'] ?? '');
        }
    }

    public function conectar(): PDO
    {
        $opcoes = [];

        if ($this->driver === 'pgsql') {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
            // A URL externa do Render exige SSL (a interna não)
            if (Ambiente::booleano('DB_SSL')) {
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

            // NOW() do banco no horário de Brasília (igual ao PHP)
            if ($this->driver === 'pgsql') {
                $pdo->exec("SET TIME ZONE 'America/Sao_Paulo'");
            } else {
                $pdo->exec("SET time_zone = '-03:00'");
            }

            return $pdo;

        } catch (PDOException $e) {
            // A mensagem técnica só aparece com APP_DEBUG=true
            $mensagem = 'Não foi possível conectar ao banco.';
            if (Ambiente::booleano('APP_DEBUG')) {
                $mensagem .= ' Detalhe: ' . $e->getMessage();
            }
            throw new ErroApi($mensagem, 500);
        }
    }

    // Atalho usado pelos controllers: conecta só na primeira vez
    public static function conexao(): PDO
    {
        if (self::$conexaoAtual === null) {
            self::$conexaoAtual = (new Bancodados())->conectar();
        }
        return self::$conexaoAtual;
    }
}
