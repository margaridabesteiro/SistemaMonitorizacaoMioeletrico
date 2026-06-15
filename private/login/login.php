<?php
// private/login/login.php
// Página de autenticação — suporta login por email + password

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

// Se já autenticado, redirecionar para a área correta
if (!empty($_SESSION['utilizador_id'])) {
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
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $erro = 'Preencha todos os campos.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, nome, password_hash, perfil, ativo FROM utilizadores WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['ativo'] && password_verify($password, $user['password_hash'])) {
            // Regenerar ID de sessão para prevenir session fixation
            session_regenerate_id(true);

            $_SESSION['utilizador_id'] = $user['id'];
            $_SESSION['nome']          = $user['nome'];
            $_SESSION['perfil']        = $user['perfil'];

            // Registar último login e log de acesso
            $db->prepare('UPDATE utilizadores SET ultimo_login = NOW() WHERE id = ?')
               ->execute([$user['id']]);
            $db->prepare('INSERT INTO logs_acesso (utilizador_id, acao, ip, user_agent) VALUES (?,?,?,?)')
               ->execute([$user['id'], 'login', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
            registarAuditoria('LOGIN', 'Utilizador', $user['id'], 'Login de ' . $user['nome'] . ' (' . $user['perfil'] . ')');

            // Forçar alteração de password no primeiro acesso (requer migration_deve_alterar_password.sql)
            try {
                $dap = $db->prepare('SELECT deve_alterar_password FROM utilizadores WHERE id=?');
                $dap->execute([$user['id']]);
                if ((bool)$dap->fetchColumn()) {
                    $_SESSION['deve_alterar_password'] = 1;
                    redirect(APP_URL . '/private/login/alterar_password_obrigatoria.php');
                }
            } catch (\Throwable $e) { /* coluna ainda não existe — ignorar até migração ser executada */ }

            $destinos = [
                'admin'   => APP_URL . '/private/admin/index_admin.php',
                'medico'  => APP_URL . '/private/medico/index_M.php',
                'tecnico' => APP_URL . '/private/tecnico/index_F.php',
                'utente'  => APP_URL . '/private/utente/index_utente.php',
            ];
            redirect($destinos[$user['perfil']] ?? APP_URL);
        } else {
            $erro = 'Credenciais inválidas ou conta desativada.';
            // Registar tentativa falhada
            $db->prepare('INSERT INTO logs_acesso (utilizador_id, acao, ip, user_agent, detalhes) VALUES (NULL,?,?,?,?)')
               ->execute(['login_falhou', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '', $email]);
            registarAuditoria('LOGIN_FALHOU', 'Utilizador', null, 'Tentativa de login falhada com email: ' . $email);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink | Entrar</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/public/assets/img/logo.jpg" type="image/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:ital,wght@0,300;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/common.css">
    <style>
        .login-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f4f6f9; }
        .login-card { background: #fff; border-radius: 12px; padding: 2.5rem; box-shadow: 0 4px 24px rgba(0,0,0,.08); width: 100%; max-width: 420px; }
        .login-logo { text-align: center; margin-bottom: 2rem; }
        .login-logo img { height: 56px; }
        .login-logo h1 { font-size: 1.4rem; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-top: .5rem; }
        .btn-login { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: #fff; width: 100%; padding: .75rem; border-radius: 8px; font-weight: 600; }
        .btn-login:hover { background: linear-gradient(135deg, #5a6fd6 0%, #6a3d94 100%); color: #fff; }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <img src="<?= APP_URL ?>/public/assets/img/logo.jpg" alt="RehabLink" onerror="this.style.display='none'">
            <h1>RehabLink</h1>
            <p class="text-muted small">Área Privada</p>
        </div>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger py-2 small"><?= h($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= h($_POST['email'] ?? '') ?>"
                       placeholder="utilizador@rehablink.pt" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="pwd" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="var p=document.getElementById('pwd'); p.type=p.type==='password'?'text':'password'">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Entrar
            </button>
        </form>
        <div class="text-center mt-3">
            <a href="esqueci_password.php" class="text-muted small">Esqueci a minha password</a>
        </div>
    </div>
</div>
<script src="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
