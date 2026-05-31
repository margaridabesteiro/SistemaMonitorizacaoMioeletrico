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

// Processar alteração de password (POST do modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_acao'] ?? '') === 'alterar_password') {
    $pw_atual  = $_POST['pw_atual']  ?? '';
    $pw_nova   = $_POST['pw_nova']   ?? '';
    $pw_conf   = $_POST['pw_conf']   ?? '';
    $_pw_erro  = null;
    $_pw_ok    = false;
    if (strlen($pw_nova) < 8) {
        $_pw_erro = 'A nova password deve ter pelo menos 8 caracteres.';
    } elseif ($pw_nova !== $pw_conf) {
        $_pw_erro = 'A confirmação não coincide com a nova password.';
    } else {
        $hash_atual = $_db_header->prepare("SELECT password_hash FROM utilizadores WHERE id=?");
        $hash_atual->execute([$_SESSION['utilizador_id']]);
        $hash = $hash_atual->fetchColumn();
        if (!password_verify($pw_atual, $hash)) {
            $_pw_erro = 'A password atual está incorreta.';
        } else {
            $_db_header->prepare("UPDATE utilizadores SET password_hash=? WHERE id=?")
                       ->execute([password_hash($pw_nova, PASSWORD_BCRYPT, ['cost'=>12]), $_SESSION['utilizador_id']]);
            $_pw_ok = true;
        }
    }
}
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
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalPerfilAdmin">
                    <i class="fa-solid fa-circle-user me-2"></i>Perfil</a></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/private/admin/seguranca/logs_acesso.php">
                    <i class="fa-solid fa-shield me-2"></i>Segurança</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/api/auth/logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sair</a></li>
            </ul>
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
                    <?php if (!empty($_pw_ok)): ?>
                        <div class="alert alert-success py-2"><i class="fa-solid fa-circle-check me-2"></i>Password alterada com sucesso.</div>
                    <?php endif; ?>
                    <?php if (!empty($_pw_erro)): ?>
                        <div class="alert alert-danger py-2"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= h($_pw_erro) ?></div>
                    <?php endif; ?>

                    <!-- Dados do perfil -->
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div style="width:60px;height:60px;border-radius:50%;background:#8B0000;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-user-gear fa-xl text-white"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-6"><?= h($_admin_row['nome'] ?? '') ?></div>
                            <div class="text-muted small"><?= h($_admin_row['email'] ?? '') ?></div>
                            <span class="badge mt-1" style="background:#8B0000;">Administrador</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-4"><i class="fa-regular fa-calendar me-1"></i>Conta criada em <?= $_admin_row ? date('d/m/Y', strtotime($_admin_row['criado_em'])) : '—' ?></p>

                    <hr>

                    <!-- Alterar password -->
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-lock me-2" style="color:#8B0000;"></i>Alterar Password</h6>
                    <form method="POST">
                        <input type="hidden" name="_acao" value="alterar_password">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password atual</label>
                            <input type="password" name="pw_atual" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nova password <span class="text-muted fw-normal">(mín. 8 caracteres)</span></label>
                            <input type="password" name="pw_nova" class="form-control form-control-sm" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Confirmar nova password</label>
                            <input type="password" name="pw_conf" class="form-control form-control-sm" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-sm w-100" style="background:#8B0000;color:#fff;">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Guardar nova password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($_pw_ok) || !empty($_pw_erro)): ?>
    <script>document.addEventListener('DOMContentLoaded', () => { new bootstrap.Modal(document.getElementById('modalPerfilAdmin')).show(); });</script>
    <?php endif; ?>

    <div class="wrapper">
