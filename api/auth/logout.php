<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

if (!empty($_SESSION['utilizador_id'])) {
    $db = getDB();
    $db->prepare('INSERT INTO logs_acesso (utilizador_id, acao, ip, user_agent) VALUES (?,?,?,?)')
       ->execute([
           $_SESSION['utilizador_id'],
           'logout',
           $_SERVER['REMOTE_ADDR'],
           $_SERVER['HTTP_USER_AGENT'] ?? ''
       ]);
    registarAuditoria('LOGOUT', 'Utilizador', $_SESSION['utilizador_id'],
        'Logout de ' . ($_SESSION['nome'] ?? '') . ' (' . ($_SESSION['perfil'] ?? '') . ')');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

redirect(APP_URL . '/private/login/login.php');
