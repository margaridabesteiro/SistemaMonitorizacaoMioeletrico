<?php
// includes/header_admin.php
// Cabeçalho (topbar) reutilizável para área administrativa
// Variáveis esperadas no contexto que inclui este ficheiro:
//   $pagina_titulo  — título da aba do browser (ex: 'Utilizadores')
//   $pagina_ativa   — link ativo na sidebar (ex: 'utilizadores')

requirePerfil('admin');
$nome_admin = h($_SESSION['nome'] ?? 'Admin');

// Carregar dados do admin para o modal de perfil
$_db_header = getDB();
$_admin_dados = $_db_header->prepare("SELECT nome, email, criado_em FROM utilizadores WHERE id = ?");
$_admin_dados->execute([$_SESSION['utilizador_id']]);
$_admin_row = $_admin_dados->fetch();
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
        <div class="d-flex align-items-center gap-2">
            <?php require_once __DIR__ . '/notificacoes_bell.php'; ?>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-user-gear"></i> <?= $nome_admin ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalPerfilAdmin">
                        <i class="fa-solid fa-circle-user me-2"></i>Perfil</a></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/private/admin/seguranca/logs_acesso.php">
                        <i class="fa-solid fa-shield me-2"></i>Segurança</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/api/auth/logout.php">
                        <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sair</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Modal Perfil Administrador -->
    <div class="modal fade" id="modalPerfilAdmin" tabindex="-1" aria-labelledby="modalPerfilAdminLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background:#8B0000;">
                    <h5 class="modal-title text-white" id="modalPerfilAdminLabel">
                        <i class="fa-solid fa-circle-user me-2"></i>Perfil do Administrador
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div style="width:64px;height:64px;border-radius:50%;background:#8B0000;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-user-gear fa-xl text-white"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-6"><?= h($_admin_row['nome'] ?? '') ?></div>
                            <div class="text-muted small"><?= h($_admin_row['email'] ?? '') ?></div>
                            <span class="badge mt-1" style="background:#8B0000;">Administrador</span>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted small mb-1"><i class="fa-regular fa-calendar me-1"></i>Conta criada em <?= $_admin_row ? date('d/m/Y', strtotime($_admin_row['criado_em'])) : '—' ?></p>
                    <p class="text-muted small mb-0"><i class="fa-solid fa-circle-info me-1"></i>Para alterar a password, aceda a <a href="<?= APP_URL ?>/private/admin/utilizadores/editar_utilizador.php?id=<?= (int)$_SESSION['utilizador_id'] ?>">Utilizadores → Editar</a>.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="wrapper">
