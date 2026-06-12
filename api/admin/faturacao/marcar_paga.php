<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$num = trim($_GET['num'] ?? '');
if ($num) {
    $db = getDB();
    $db->prepare('UPDATE faturas SET paga=1 WHERE numero=?')->execute([$num]);
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Fatura marcada como paga.'];
}
redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
