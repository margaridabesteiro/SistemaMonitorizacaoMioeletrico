<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $db = getDB();
    $db->prepare('UPDATE faturas SET paga = NOT paga WHERE id=?')->execute([$id]);
    $s = $db->prepare('SELECT paga FROM faturas WHERE id=?');
    $s->execute([$id]);
    $paga = (bool)$s->fetchColumn();
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=> $paga ? 'Fatura marcada como paga.' : 'Fatura marcada como pendente.'];
}
redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
