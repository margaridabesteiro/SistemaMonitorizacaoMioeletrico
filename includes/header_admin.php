<?php
// includes/header_admin.php
// Cabeçalho (topbar) reutilizável para área administrativa
// Variáveis esperadas no contexto que inclui este ficheiro:
//   $pagina_titulo  — título da aba do browser (ex: 'Utilizadores')
//   $pagina_ativa   — link ativo na sidebar (ex: 'utilizadores')

requirePerfil('admin');
$nome_admin = h($_SESSION['nome'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink | <?= h($pagina_titulo ?? 'Admin') ?></title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/public/assets/img/logo.jpg" type="image/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/admin.css">
    <?php if (!empty($css_extra)): ?>
        <?php foreach ($css_extra as $css): ?>
            <link rel="stylesheet" href="<?= h($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class="topbar d-flex justify-content-between align-items-center px-4 py-3">
        <div class="sistema-nome">
            <a href="<?= APP_URL ?>/private/admin/index_admin.php" class="text-decoration-none d-flex align-items-center gap-3">
                <i class="fa-solid fa-shield-halved logo-icon"></i>
                <span class="fw-bold">RehabLink · Administrador</span>
            </a>
        </div>
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fa-solid fa-user-gear"></i> <?= $nome_admin ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= APP_URL ?>/private/admin/configuracao/sistema.php">
                    <i class="fa-solid fa-gear me-2"></i>Configurações</a></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/private/admin/seguranca/logs_acesso.php">
                    <i class="fa-solid fa-shield me-2"></i>Logs</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/api/auth/logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sair</a></li>
            </ul>
        </div>
    </header>
    <div class="wrapper">
