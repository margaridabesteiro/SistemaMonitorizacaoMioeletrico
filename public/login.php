<?php
// Login público — alternativa à rota privada (mesmo form, redireciona para login principal)
require_once __DIR__ . '/../config/app.php';
if (isset($_SESSION['utilizador_id'])) {
    $destinos = ['admin'=>'/private/admin/index_admin.php','medico'=>'/private/medico/index_M.php','tecnico'=>'/private/tecnico/index_F.php','utente'=>'/private/utente/index_utente.php'];
    redirect(APP_URL . ($destinos[$_SESSION['perfil']] ?? '/private/login/login.php'));
}
redirect(APP_URL . '/private/login/login.php');
