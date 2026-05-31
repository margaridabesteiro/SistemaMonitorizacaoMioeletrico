<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

if (!empty($_SESSION['utilizador_id'])) {
    redirect(APP_URL . '/private/utente/index_utente.php');
}

$token = trim($_GET['token'] ?? '');
$erro  = ''; $sucesso = '';

if (!$token) redirect(APP_URL . '/private/login/login.php');

$db = getDB();
$stmt = $db->prepare("SELECT pr.*, u.email FROM password_resets pr JOIN utilizadores u ON u.id=pr.utilizador_id WHERE pr.token=? AND pr.usado=0 AND pr.expira_em > NOW()");
$stmt->execute([$token]); $reset = $stmt->fetch();

if (!$reset) {
    $erro = 'Link inválido ou expirado. Solicite um novo link.';
}

if (!$erro && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova    = $_POST['password_nova']     ?? '';
    $confirma= $_POST['password_confirma'] ?? '';
    if (strlen($nova) < 8) {
        $erro = 'A password deve ter pelo menos 8 caracteres.';
    } elseif ($nova !== $confirma) {
        $erro = 'As passwords não coincidem.';
    } else {
        $hash = password_hash($nova, PASSWORD_BCRYPT, ['cost'=>12]);
        $db->prepare("UPDATE utilizadores SET password_hash=? WHERE id=?")->execute([$hash, $reset['utilizador_id']]);
        $db->prepare("UPDATE password_resets SET usado=1 WHERE token=?")->execute([$token]);
        $sucesso = 'Password alterada com sucesso. Pode agora fazer login.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink | Nova Password</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.min.css">
    <link rel="shortcut icon" href="<?= APP_URL ?>/public/assets/img/logo.jpg">
</head>
<body class="bg-light">
<div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="card p-4 shadow-sm" style="max-width:420px;width:100%;">
        <div class="text-center mb-4">
            <h4 class="fw-bold" style="color:#8B0000;">RehabLink</h4>
            <p class="text-muted">Definir Nova Password</p>
        </div>
        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= h($sucesso) ?></div>
            <a href="login.php" class="btn w-100 text-white" style="background:#8B0000;">Ir para o Login</a>
        <?php elseif ($erro): ?>
            <div class="alert alert-danger"><?= h($erro) ?></div>
            <a href="esqueci_password.php" class="btn btn-outline-secondary w-100">Solicitar novo link</a>
        <?php else: ?>
        <p class="text-muted small mb-3">A definir nova password para <strong><?= h($reset['email']) ?></strong></p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nova Password</label>
                <input type="password" name="password_nova" class="form-control" placeholder="Mínimo 8 caracteres" required minlength="8" autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Confirmar Password</label>
                <input type="password" name="password_confirma" class="form-control" required>
            </div>
            <button type="submit" class="btn w-100 text-white" style="background:#8B0000;">Confirmar Nova Password</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
