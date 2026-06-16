<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');

$num    = trim($_POST['num'] ?? '') ?: trim($_GET['num'] ?? '');
$metodo = $_POST['metodo_pagamento'] ?? null;
$data   = $_POST['data_pagamento']   ?? null;
$origem = $_POST['_origem'] ?? '';

$metodos_validos = ['multibanco','cartão','seguro','numerário','transferência'];
if (!$metodo || !in_array($metodo, $metodos_validos, true)) {
    $_SESSION['flash'] = ['tipo'=>'danger','mensagem'=>'Método de pagamento inválido.'];
    redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
}
if (!$data || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    $data = date('Y-m-d');
}
if ($num) {
    $db = getDB();
    $db->prepare('UPDATE faturas SET paga=1, metodo_pagamento=?, data_pagamento=? WHERE numero=?')
       ->execute([$metodo, $data, $num]);
    registarAuditoria('ATUALIZAR', 'Fatura', null, 'Fatura ' . $num . ' marcada como paga via ' . $metodo . ' em ' . $data);
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Fatura marcada como paga.'];
}
if ($origem === 'fatura' && $num) {
    redirect(APP_URL . '/private/admin/faturacao/fatura.php?num=' . urlencode($num));
}
redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
