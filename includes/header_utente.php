<?php
// includes/header_utente.php

requirePerfil('utente');
$nome_utente = h($_SESSION['nome'] ?? 'Utente');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink | <?= h($pagina_titulo ?? 'Utente') ?></title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/public/assets/img/logo.jpg" type="image/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/utente.css">
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
    <header class="topbar d-flex justify-content-between align-items-center px-4 py-3 d-print-none">
        <div class="sistema-nome">
            <a href="<?= APP_URL ?>/private/utente/index_utente.php" class="text-decoration-none d-flex align-items-center gap-3">
                <i class="fa-solid fa-hand-holding-heart logo-icon"></i>
                <span class="fw-bold">RehabLink</span>
            </a>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php require_once __DIR__ . '/notificacoes_bell.php'; ?>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-user"></i> <?= $nome_utente ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/private/utente/perfil.php">
                        <i class="fa-regular fa-user me-2"></i>O Meu Perfil</a></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/private/utente/agenda.php">
                        <i class="fa-regular fa-calendar me-2"></i>Agenda</a></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/private/utente/meu_progresso.php">
                        <i class="fa-solid fa-chart-line me-2"></i>O Meu Progresso</a></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/private/utente/mensagens_equipa.php">
                        <i class="fa-regular fa-comments me-2"></i>Equipa de Tratamento</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/api/auth/logout.php">
                        <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sair</a></li>
                </ul>
            </div>
        </div>
    </header>
    <div class="wrapper">
<script>
(function(){
    var _cpUrl = '<?= APP_URL ?>/api/utente/check_proximas.php';
    function _checkProx(){ fetch(_cpUrl,{credentials:'same-origin'}).catch(function(){}); }
    _checkProx();
    setInterval(_checkProx, 120000);
})();
</script>
