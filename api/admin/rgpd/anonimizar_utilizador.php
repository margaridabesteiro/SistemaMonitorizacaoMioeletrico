<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');

$db = getDB();

if ($id === (int)$_SESSION['utilizador_id']) {
    $_SESSION['flash'] = ['tipo'=>'danger','mensagem'=>'Não pode anonimizar a sua própria conta.'];
    redirect(APP_URL . '/private/admin/utilizadores/editar_utilizador.php?id=' . $id);
}

$db->prepare("UPDATE utilizadores SET nome=?, email=?, password_hash=? WHERE id=?")
   ->execute(['Utilizador Anonimizado', "anonimizado_{$id}@eliminado.rehablink", password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT), $id]);

// dados clínicos mantidos por obrigação legal de retenção
$db->prepare("UPDATE utentes SET nif=NULL, morada=NULL, codigo_postal=NULL, localidade=NULL, data_nascimento=NULL, sexo=NULL WHERE utilizador_id=?")
   ->execute([$id]);

try {
    $db->prepare("INSERT INTO rgpd_consentimentos (utilizador_id, tipo, registado_por, ip, detalhes) VALUES (?,?,?,?,?)")
       ->execute([$id, 'revogacao', $_SESSION['utilizador_id'], $_SERVER['REMOTE_ADDR'] ?? null,
           'Anonimização realizada pelo administrador — RGPD Art.17 (direito a ser esquecido). Dados clínicos mantidos por obrigação legal.']);
    $db->prepare("UPDATE rgpd_pedidos SET estado='processado', resposta=?, processado_em=NOW() WHERE utilizador_id=? AND tipo='eliminacao' AND estado='pendente'")
       ->execute(['Dados pessoais anonimizados. Dados clínicos mantidos por obrigação legal (mínimo 5 anos).', $id]);
} catch (\Throwable $e) {}

$_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Utilizador anonimizado com sucesso (RGPD Art.17).'];
redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
