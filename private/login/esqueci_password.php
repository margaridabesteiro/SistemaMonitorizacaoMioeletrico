<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

if (!empty($_SESSION['utilizador_id'])) {
    redirect(APP_URL . '/private/login/login.php');
}

$mensagem = ''; $tipo_msg = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $email = trim($_POST['email'] ?? '');
    $db    = getDB();
    $stmt  = $db->prepare("SELECT id, nome, perfil FROM utilizadores WHERE email=? AND ativo=1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['perfil'] === 'utente') {
        // Utente: sistema de token (para reset autónomo)
        $db->prepare("UPDATE password_resets SET usado=1 WHERE utilizador_id=?")->execute([$user['id']]);
        $token     = bin2hex(random_bytes(32));
        $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $db->prepare("INSERT INTO password_resets (utilizador_id, token, expira_em) VALUES (?,?,?)")
           ->execute([$user['id'], $token, $expira_em]);
        // Notificar admins
        $admins = $db->query("SELECT id FROM utilizadores WHERE perfil='admin' AND ativo=1")->fetchAll();
        foreach ($admins as $adm) {
            notificar((int)$adm['id'], 'warning',
                'Recuperação de acesso — ' . $email,
                'O utente ' . $user['nome'] . ' (' . $email . ') não consegue aceder à conta e pediu recuperação de password.',
                APP_URL . '/private/admin/utilizadores/editar_utilizador.php?id=' . $user['id']
            );
        }
        $mensagem = 'Pedido registado. Receberá indicações de recuperação em breve.';

    } elseif ($user && in_array($user['perfil'], ['medico', 'tecnico', 'admin'], true)) {
        // Profissional/admin: notificar todos os admins via notificações internas
        $admins = $db->query("SELECT id FROM utilizadores WHERE perfil='admin' AND ativo=1")->fetchAll();
        foreach ($admins as $adm) {
            if ($adm['id'] == ($user['id'] ?? 0)) continue;
            notificar((int)$adm['id'], 'warning',
                'Recuperação de acesso — ' . $email,
                ucfirst($user['perfil']) . ' ' . $user['nome'] . ' (' . $email . ') não consegue aceder à conta e pediu recuperação de password.',
                APP_URL . '/private/admin/utilizadores/editar_utilizador.php?id=' . $user['id']
            );
        }
        $mensagem = 'Pedido enviado à administração. Será contactado brevemente para repor o acesso.';

    } else {
        // Email não encontrado — resposta genérica (não revelar existência da conta)
        $mensagem = 'Se o email existir na nossa base de dados, receberá indicações em breve.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink | Recuperar Password</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/public/assets/img/logo.jpg">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="card p-4 shadow-sm" style="max-width:420px;width:100%;">
        <div class="text-center mb-4">
            <img src="<?= APP_URL ?>/public/assets/img/logo.jpg" alt="RehabLink" height="44" onerror="this.style.display='none'">
            <h4 class="fw-bold mt-2" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">RehabLink</h4>
            <p class="text-muted small mb-0">Recuperar acesso</p>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-info small"><?= h($mensagem) ?></div>
            <a href="login.php" class="btn btn-outline-secondary w-100">Voltar ao Login</a>
        <?php else: ?>
        <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email da sua conta</label>
                <input type="email" name="email" class="form-control" placeholder="email@exemplo.pt" required autofocus>
                <div class="form-text">Introduza o email associado à sua conta RehabLink.</div>
            </div>
            <button type="submit" class="btn w-100 text-white" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;">
                Pedir recuperação de acesso
            </button>
        </form>
        <div class="text-center mt-3">
            <a href="login.php" class="text-muted small">Voltar ao login</a>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
