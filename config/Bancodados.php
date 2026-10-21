<?php

require_once __DIR__ . '/Ambiente.php';

/**
 * Conexão com o MySQL usando PDO.
 * As credenciais NÃO ficam no código: vêm do .env (XAMPP)
 * ou das variáveis de ambiente (Render). Ver .env.example.
 */
class Bancodados
{
    private string $host;
    private string $port;
    private string $database;
    private string $username;
    private string $password;

    // Uma única conexão por requisição (todos os DAOs usam a mesma)
    private static ?PDO $conexaoAtual = null;

    public function __construct()
    {
        $this->host     = Ambiente::ler('DB_HOST', 'localhost');
        $this->port     = Ambiente::ler('DB_PORT', '3306');
        $this->database = Ambiente::ler('DB_NAME');
        $this->username = Ambiente::ler('DB_USER');
        $this->password = Ambiente::ler('DB_PASS');
    }

    public function conectar(): PDO
    {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";

        $opcoes = [];
        // Alguns MySQL externos (nuvem) exigem conexão segura
        if (Ambiente::booleano('DB_SSL')) {
            $opcoes[PDO::MYSQL_ATTR_SSL_CA] = Ambiente::ler('DB_SSL_CA', '/etc/ssl/certs/ca-certificates.crt');
            $opcoes[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $pdo = new PDO($dsn, $this->username, $this->password, $opcoes);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // NOW() do MySQL no horário de Brasília (igual ao PHP)
            $pdo->exec("SET time_zone = '-03:00'");

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
