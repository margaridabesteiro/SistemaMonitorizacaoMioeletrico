<?php
// config/app.php
// Configurações globais da aplicação

define('APP_NAME', 'RehabLink');
define('APP_URL', 'http://localhost/rehablink');
define('APP_ROOT', __DIR__ . '/..');

// Configuração de sessão segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se existe sessão ativa.
 * Redireciona para login se não existir.
 */
function requireLogin(): void {
    if (empty($_SESSION['utilizador_id'])) {
        header('Location: ' . APP_URL . '/private/login/login.php');
        exit;
    }
}

/**
 * Verifica se o utilizador tem o perfil (role) necessário.
 * Perfis válidos: 'admin', 'medico', 'tecnico', 'utente'
 */
function requirePerfil(string ...$perfis): void {
    requireLogin();
    if (!in_array($_SESSION['perfil'] ?? '', $perfis, true)) {
        http_response_code(403);
        include APP_ROOT . '/includes/403.php';
        exit;
    }
}

/**
 * Escapa output HTML para prevenir XSS.
 */
function h(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redireciona para URL e termina execução.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
