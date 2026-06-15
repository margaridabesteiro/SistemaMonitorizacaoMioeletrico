<?php
// config/database.php
// Configuração da ligação à base de dados MySQL — XAMPP
// Caminho: C:\xampp\htdocs\sistema_mioeletrico\SistemaMonitorizacaoMioeletrico\config\database.php

define('DB_HOST',    'localhost');
define('DB_USER',    'root');       // Utilizador padrão do XAMPP
define('DB_PASS',    '');           // Password vazia por padrão no XAMPP
define('DB_NAME',    'sistema_mioeletrico');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna uma ligação PDO à base de dados.
 * Usa padrão Singleton para evitar múltiplas ligações por pedido.
 */
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
