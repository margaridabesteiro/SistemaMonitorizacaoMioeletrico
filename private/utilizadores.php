<?php
require_once __DIR__.'/../config/app.php';
requireLogin();
// Redireciona para a gestão de utilizadores do perfil correto
switch($_SESSION['perfil']){
    case 'admin': redirect(APP_URL.'/private/admin/utilizadores/lista_utilizadores.php');
    default: redirect(APP_URL.'/private/login/login.php');
}
