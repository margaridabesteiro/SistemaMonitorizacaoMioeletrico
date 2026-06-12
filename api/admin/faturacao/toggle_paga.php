<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$num = trim($_GET['num'] ?? '');
if ($num) {
    $db = getDB();
    $db->prepare('UPDATE faturas SET paga = NOT paga WHERE numero=?')->execute([$num]);
    $s = $db->prepare('SELECT paga FROM faturas WHERE numero=?');
    $s->execute([$num]);
    $paga = (bool)$s->fetchColumn();
    $_SESSION['flash'] = $paga
        ? ['tipo'=>'success', 'mensagem'=>'Fatura marcada como paga.']
        : ['tipo'=>'warning', 'mensagem'=>'Fatura marcada como pendente.'];
}
redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
