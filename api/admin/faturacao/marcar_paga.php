<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('admin');
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $db = getDB();
    $db->prepare('UPDATE faturas SET paga=1 WHERE id=?')->execute([$id]);
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Fatura marcada como paga.'];
}
redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
