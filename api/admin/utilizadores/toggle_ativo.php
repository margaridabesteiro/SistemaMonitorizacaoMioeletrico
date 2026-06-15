<?php
// api/admin/utilizadores/toggle_ativo.php
// Ativa/desativa um utilizador — chamada via link, requer sessão admin

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';

requirePerfil('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
}

$db = getDB();

// Não permitir desativar a própria conta
if ($id === (int)$_SESSION['utilizador_id']) {
    $_SESSION['flash'] = ['tipo' => 'warning', 'mensagem' => 'Não pode desativar a sua própria conta.'];
    redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
}

$stmt = $db->prepare('SELECT ativo FROM utilizadores WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['flash'] = ['tipo' => 'danger', 'mensagem' => 'Utilizador não encontrado.'];
    redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
}

$novo_estado = $user['ativo'] ? 0 : 1;
$db->prepare('UPDATE utilizadores SET ativo = ? WHERE id = ?')->execute([$novo_estado, $id]);
$acao_txt = $novo_estado ? 'reativado' : 'desativado';
registarAuditoria('ATUALIZAR', 'Utilizador', $id, 'Utilizador ID ' . $id . ' ' . $acao_txt);

$_SESSION['flash'] = [
    'tipo'     => 'success',
    'mensagem' => 'Utilizador ' . ($novo_estado ? 'ativado' : 'desativado') . ' com sucesso.'
];
redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
