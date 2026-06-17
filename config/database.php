<?php
// Em produção: definir DB_HOST, DB_USER, DB_PASS, DB_NAME como variáveis de ambiente
// Ex: no Apache (httpd.conf): SetEnv DB_USER rehablink  SetEnv DB_PASS s3nh@S3gura
define('DB_HOST',    getenv('DB_HOST') ?: 'localhost');
define('DB_USER',    getenv('DB_USER') ?: 'root');
define('DB_PASS',    getenv('DB_PASS') ?: '');
define('DB_NAME',    getenv('DB_NAME') ?: 'sistema_mioeletrico');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $opcoes = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            error_log('Erro de ligação BD: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['erro' => 'Falha na ligação à base de dados. Verifique se o MySQL está ativo no XAMPP.']));
        }
    }
    return $pdo;
}
