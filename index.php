<?php
// index.php — página pública de entrada (landing page)
// Redireciona utilizadores já autenticados para a sua área

require_once __DIR__ . '/config/app.php';

if (!empty($_SESSION['utilizador_id'])) {
    $destinos = [
        'admin'   => APP_URL . '/private/admin/index_admin.php',
        'medico'  => APP_URL . '/private/medico/index_M.php',
        'tecnico' => APP_URL . '/private/tecnico/index_F.php',
        'utente'  => APP_URL . '/private/utente/index_utente.php',
    ];
    redirect($destinos[$_SESSION['perfil']] ?? APP_URL . '/private/login/login.php');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink</title>
    <link rel="shortcut icon" href="public/assets/img/logo.jpg" type="image/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="public/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/common.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar-rehab">
        <div class="navbar-logo">
            <img src="public/assets/img/logo.jpg" alt="RehabLink Logo">
            <span>RehabLink</span>
        </div>
        <div class="navbar-links">
            <a href="#quem-somos">Quem Somos</a>
            <a href="#nossa-equipa">Equipa</a>
            <a href="#servicos">Serviços</a>
            <a href="#unidades">Unidades</a>
            <a href="#seguros">Acordos</a>
            <a href="#contacto">Contactos</a>
            <a href="private/login/login.php" class="navbar-btn">Área Privada</a>
        </div>
    </nav>

    <!-- Conteúdo da landing page mantém-se igual ao index.html original -->
    <!-- (colar aqui o body do index.html, apenas actualizando o link da Área Privada) -->

    <script src="public/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
