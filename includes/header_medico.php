<?php
// includes/header_medico.php
// Cabeçalho reutilizável para área do médico

requirePerfil('medico');
$nome_medico = h($_SESSION['nome'] ?? 'Médico');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink | <?= h($pagina_titulo ?? 'Médico') ?></title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/public/assets/img/logo.jpg" type="image/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/medico.css">
    <?php if (!empty($css_extra)): ?>
        <?php foreach ($css_extra as $css): ?>
            <link rel="stylesheet" href="<?= h($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($js_head)): ?>
        <?php foreach ($js_head as $js): ?>
            <script src="<?= h($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class="topbar d-flex justify-content-between align-items-center px-4 py-3">
        <div class="sistema-nome">
            <a href="<?= APP_URL ?>/private/medico/index_M.php" class="text-decoration-none d-flex align-items-center gap-3">
                <i class="fa-solid fa-stethoscope logo-icon"></i>
                <span class="fw-bold">RehabLink · Médico</span>
            </a>
        </div>
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-user-doctor"></i>
                <span><?= $nome_medico ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#"><i class="fa-regular fa-user me-2"></i>Meu Perfil</a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-regular fa-calendar me-2"></i>Minha Agenda</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/api/auth/logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sair</a></li>
            </ul>
        </div>
    </header>
    <div class="wrapper">
