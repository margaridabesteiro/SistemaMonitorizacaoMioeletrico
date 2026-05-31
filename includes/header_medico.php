<?php
// includes/header_medico.php
// Cabeçalho reutilizável para área do médico

requirePerfil('medico');
$nome_medico = h($_SESSION['nome'] ?? 'Médico');

$_db_hdr = getDB();
$_stmt_hdr = $_db_hdr->prepare("
    SELECT u.email, p.especialidade, p.instituicao
    FROM utilizadores u
    LEFT JOIN profissionais p ON p.utilizador_id = u.id
    WHERE u.id = ?
");
$_stmt_hdr->execute([$_SESSION['utilizador_id']]);
$_prof_hdr = $_stmt_hdr->fetch() ?: [];
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
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalPerfilMedico">
                    <i class="fa-regular fa-user me-2"></i>Meu Perfil</a></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/private/medico/consultas/agenda.php">
                    <i class="fa-regular fa-calendar me-2"></i>Minha Agenda</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/api/auth/logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sair</a></li>
            </ul>
        </div>
    </header>

    <!-- Modal: Perfil do Médico -->
    <div class="modal fade" id="modalPerfilMedico" tabindex="-1" aria-labelledby="modalPerfilMedicoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background:#198754;">
                    <h5 class="modal-title text-white" id="modalPerfilMedicoLabel">
                        <i class="fa-solid fa-user-doctor me-2"></i>Meu Perfil
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div style="width:64px;height:64px;border-radius:50%;background:#198754;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-user-doctor fa-2x text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold"><?= $nome_medico ?></h5>
                            <span class="badge" style="background:#198754;">Médico</span>
                        </div>
                    </div>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted fw-semibold" style="width:40%"><i class="fa-regular fa-envelope me-2"></i>Email</td>
                            <td><?= h($_prof_hdr['email'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold"><i class="fa-solid fa-stethoscope me-2"></i>Especialidade</td>
                            <td><?= h($_prof_hdr['especialidade'] ?? 'Não definida') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold"><i class="fa-solid fa-hospital me-2"></i>Instituição</td>
                            <td><?= h($_prof_hdr['instituicao'] ?? 'Não definida') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold"><i class="fa-solid fa-id-badge me-2"></i>Perfil</td>
                            <td>Médico</td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="wrapper">
