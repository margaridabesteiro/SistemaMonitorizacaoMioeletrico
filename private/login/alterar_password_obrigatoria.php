<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

// Tem de estar autenticado
if (empty($_SESSION['utilizador_id'])) {
    redirect(APP_URL . '/private/login/login.php');
}

// Se já não precisa de alterar, redirecionar para o dashboard
if (empty($_SESSION['deve_alterar_password'])) {
    $destinos = [
        'admin'   => APP_URL . '/private/admin/index_admin.php',
        'medico'  => APP_URL . '/private/medico/index_M.php',
        'tecnico' => APP_URL . '/private/tecnico/index_F.php',
        'utente'  => APP_URL . '/private/utente/index_utente.php',
    ];
    redirect($destinos[$_SESSION['perfil']] ?? APP_URL);
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $nova     = $_POST['nova_password']      ?? '';
    $confirma = $_POST['confirma_password']  ?? '';

    if (strlen($nova) < 8) {
        $erro = 'A nova password deve ter pelo menos 8 caracteres.';
    } elseif ($nova !== $confirma) {
        $erro = 'As passwords não coincidem.';
    } else {
        $db   = getDB();
        $hash = password_hash($nova, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare('UPDATE utilizadores SET password_hash=?, deve_alterar_password=0 WHERE id=?')
           ->execute([$hash, $_SESSION['utilizador_id']]);

        unset($_SESSION['deve_alterar_password']);
        registarAuditoria('ATUALIZAR', 'Utilizador', $_SESSION['utilizador_id'],
            'Password alterada no primeiro acesso por ' . ($_SESSION['nome'] ?? ''));
        $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Password alterada com sucesso. Bem-vindo ao RehabLink!'];

        $destinos = [
            'admin'   => APP_URL . '/private/admin/index_admin.php',
            'medico'  => APP_URL . '/private/medico/index_M.php',
            'tecnico' => APP_URL . '/private/tecnico/index_F.php',
            'utente'  => APP_URL . '/private/utente/index_utente.php',
        ];
        redirect($destinos[$_SESSION['perfil']] ?? APP_URL);
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink | Definir Nova Password</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/public/assets/img/logo.jpg" type="image/jpg">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/common.css">
    <style>
        .wrapper { min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f4f6f9; }
        .card-change { background:#fff; border-radius:12px; padding:2.5rem; box-shadow:0 4px 24px rgba(0,0,0,.08); width:100%; max-width:440px; }
        .brand { text-align:center; margin-bottom:1.8rem; }
        .brand h1 { font-size:1.4rem; font-weight:700; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .btn-guardar { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); border:none; color:#fff; width:100%; padding:.75rem; border-radius:8px; font-weight:600; }
        .btn-guardar:hover { opacity:.9; color:#fff; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card-change">
        <div class="brand">
            <img src="<?= APP_URL ?>/public/assets/img/logo.jpg" alt="RehabLink" height="52" onerror="this.style.display='none'">
            <h1>RehabLink</h1>
        </div>

        <div class="alert alert-warning py-2 small mb-3">
            <i class="fa-solid fa-key me-2"></i>
            <strong>Primeiro acesso detectado.</strong><br>
            Por segurança, defina uma password pessoal antes de continuar.
        </div>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger py-2 small"><?= h($erro) ?></div>
        <?php endif; ?>

        <p class="small text-muted">Olá, <strong><?= h($_SESSION['nome']) ?></strong>. Escolha uma password segura com pelo menos 8 caracteres.</p>

        <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nova Password</label>
                <div class="input-group">
                    <input type="password" name="nova_password" id="p1" class="form-control" placeholder="Mín. 8 caracteres" required autofocus>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggle('p1',this)">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Confirmar Password</label>
                <div class="input-group">
                    <input type="password" name="confirma_password" id="p2" class="form-control" placeholder="Repetir password" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggle('p2',this)">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-guardar">
                <i class="fa-solid fa-shield-check me-2"></i>Definir Password e Entrar
            </button>
        </form>
    </div>
</div>
<script src="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.bundle.min.js"></script>
<script>
function toggle(id, btn) {
    var el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').classList.toggle('fa-eye');
    btn.querySelector('i').classList.toggle('fa-eye-slash');
}
</script>
</body>
</html>
