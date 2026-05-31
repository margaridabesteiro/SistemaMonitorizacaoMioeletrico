<?php
// index.php — entrada pública (landing page)
// Redireciona utilizadores já autenticados para a sua área
// Caminho: C:\xampp\htdocs\sistema_mioeletrico\SistemaMonitorizacaoMioeletrico\index.php

require_once __DIR__ . '/config/app.php';

if (!empty($_SESSION['utilizador_id'])) {
    $destinos = [
        'admin'   => APP_URL . '/private/admin/index_admin.php',
        'medico'  => APP_URL . '/private/medico/index_M.php',
        'tecnico' => APP_URL . '/private/tecnico/index_F.php',
        'utente'  => APP_URL . '/private/utente/index_utente.php',
    ];
    redirect($destinos[$_SESSION['perfil']] ?? APP_URL . '/private/login/login.php');
}

// Redirecionar para a landing page HTML original
// (o index.html já existe na raiz do projeto)
header('Location: index.html');
exit;
