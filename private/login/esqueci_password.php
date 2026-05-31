<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

// Redirecionar se já autenticado
if (!empty($_SESSION['utilizador_id'])) {
    redirect(APP_URL . '/private/utente/index_utente.php');
}

$mensagem = ''; $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM utilizadores WHERE email=? AND perfil='utente' AND ativo=1");
    $stmt->execute([$email]); $utente = $stmt->fetch();

    // Resposta genérica para não revelar se o email existe (segurança)
    $mensagem = 'Se o email existir na nossa base de dados, receberá um link de recuperação em breve.';

    if ($utente) {
        // Invalidar tokens anteriores
        $db->prepare("UPDATE password_resets SET usado=1 WHERE utilizador_id=?")->execute([$utente['id']]);
        // Criar novo token
        $token     = bin2hex(random_bytes(32));
        $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $db->prepare("INSERT INTO password_resets (utilizador_id, token, expira_em) VALUES (?,?,?)")
           ->execute([$utente['id'], $token, $expira_em]);

        // Simulação de envio — em produção usar mail() ou PHPMailer
        // mail($email, 'Reset de Password — RehabLink', APP_URL . '/private/login/reset_password.php?token=' . $token);

        // Para desenvolvimento: mostrar o link diretamente
        $mensagem .= '<br><br><strong>Link de desenvolvimento:</strong><br>';
        $mensagem .= '<a href="' . APP_URL . '/private/login/reset_password.php?token=' . $token . '">' . APP_URL . '/private/login/reset_password.php?token=' . $token . '</a>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink | Recuperar Password</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.min.css">
    <link rel="shortcut icon" href="<?= APP_URL ?>/public/assets/img/logo.jpg">
</head>
<body class="bg-light">
<div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="card p-4 shadow-sm" style="max-width:420px;width:100%;">
        <div class="text-center mb-4">
            <h4 class="fw-bold" style="color:#8B0000;">RehabLink</h4>
            <p class="text-muted">Recuperar Password</p>
        </div>
        <?php if ($mensagem): ?>
            <div class="alert alert-info small"><?= $mensagem ?></div>
            <a href="login.php" class="btn btn-outline-secondary w-100">Voltar ao Login</a>
        <?php else: ?>
        <?php if ($erro): ?><div class="alert alert-danger"><?= h($erro) ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email da sua conta</label>
                <input type="email" name="email" class="form-control" placeholder="email@exemplo.pt" required autofocus>
                <div class="form-text">Apenas disponível para utentes.</div>
            </div>
            <button type="submit" class="btn w-100 text-white" style="background:#8B0000;">Enviar Link de Recuperação</button>
        </form>
        <div class="text-center mt-3">
            <a href="login.php" class="text-muted small">Voltar ao login</a>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
