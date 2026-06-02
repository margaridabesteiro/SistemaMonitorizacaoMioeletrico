<?php
// RGPD Art. 17 — Direito a ser esquecido: utente submete pedido de eliminação
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/private/utente/perfil.php');
}

$uid = (int)$_SESSION['utilizador_id'];
$db  = getDB();
$msg = trim($_POST['mensagem'] ?? '');

try {
    // Verificar se já há um pedido pendente
    $existe = $db->prepare("SELECT id FROM rgpd_pedidos WHERE utilizador_id=? AND tipo='eliminacao' AND estado='pendente'");
    $existe->execute([$uid]);
    if ($existe->fetch()) {
        $_SESSION['flash_rgpd'] = ['tipo'=>'warning','msg'=>'Já tens um pedido de eliminação pendente. O administrador irá processá-lo em breve.'];
    } else {
        $db->prepare("INSERT INTO rgpd_pedidos (utilizador_id, tipo, mensagem) VALUES (?,?,?)")
           ->execute([$uid, 'eliminacao', $msg ?: null]);
        $db->prepare("INSERT INTO rgpd_consentimentos (utilizador_id, tipo, ip, detalhes) VALUES (?,?,?,?)")
           ->execute([$uid, 'eliminacao_pedido', $_SERVER['REMOTE_ADDR'] ?? null, 'Pedido de eliminação submetido pelo titular (Art.17 RGPD)']);
        $_SESSION['flash_rgpd'] = ['tipo'=>'success','msg'=>'Pedido de eliminação registado. O administrador irá processá-lo no prazo de 30 dias (RGPD Art.12).'];
    }
} catch (\Throwable $e) {
    $_SESSION['flash_rgpd'] = ['tipo'=>'danger','msg'=>'Erro ao submeter pedido. Contacte privacidade@rehablink.pt'];
}

redirect(APP_URL . '/private/utente/perfil.php#rgpd');
