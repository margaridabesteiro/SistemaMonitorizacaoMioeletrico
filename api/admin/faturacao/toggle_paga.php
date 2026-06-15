<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$num = trim($_GET['num'] ?? '');
if ($num) {
    $db = getDB();
    $s = $db->prepare('SELECT paga FROM faturas WHERE numero=?');
    $s->execute([$num]);
    $paga_atual = (bool)$s->fetchColumn();
    if ($paga_atual) {
        $db->prepare('UPDATE faturas SET paga=0, metodo_pagamento=NULL, data_pagamento=NULL WHERE numero=?')
           ->execute([$num]);
        registarAuditoria('ATUALIZAR', 'Fatura', null, 'Fatura ' . $num . ' revertida para pendente');
        $_SESSION['flash'] = ['tipo'=>'warning','mensagem'=>'Fatura revertida para pendente.'];
    } else {
        $db->prepare('UPDATE faturas SET paga=1 WHERE numero=?')->execute([$num]);
        registarAuditoria('ATUALIZAR', 'Fatura', null, 'Fatura ' . $num . ' marcada como paga');
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Fatura marcada como paga.'];
    }
}
redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
