<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');
header('Content-Type: application/json');

$sessao_id = (int)($_POST['sessao_id'] ?? 0);
$link      = trim($_POST['link'] ?? '');

if (!$sessao_id) { echo json_encode(['ok'=>false,'erro'=>'ID inválido']); exit; }

// Validar que o link é uma URL ou string vazia
if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
    echo json_encode(['ok'=>false,'erro'=>'URL inválido']); exit;
}

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];

// Confirmar que a sessão é de um utente do médico
$stmt = $db->prepare("
    SELECT s.id, ut.utilizador_id AS utente_uid FROM sessoes s
    JOIN utentes ut ON ut.id = s.utente_id
    JOIN profissionais p ON p.id = s.medico_id
    WHERE s.id=? AND p.utilizador_id=?
");
$stmt->execute([$sessao_id, $uid]);
$sess = $stmt->fetch();

// Fallback: accept if médico matches via utente→medico
if (!$sess) {
    $stmt2 = $db->prepare("
        SELECT s.id, ut.utilizador_id AS utente_uid FROM sessoes s
        JOIN utentes ut ON ut.id = s.utente_id
        JOIN profissionais pm ON pm.id = ut.medico_id
        WHERE s.id=? AND pm.utilizador_id=?
    ");
    $stmt2->execute([$sessao_id, $uid]);
    $sess = $stmt2->fetch();
}

if (!$sess) { echo json_encode(['ok'=>false,'erro'=>'Acesso negado']); exit; }

try {
    $db->prepare("UPDATE sessoes SET link_videochamada=? WHERE id=?")->execute([$link ?: null, $sessao_id]);

    // Notificar o utente se houver link
    if ($link && $sess['utente_uid']) {
        notificar((int)$sess['utente_uid'], 'videoconsulta',
            'Link de videoconsulta disponível',
            'O seu médico adicionou o link para a videoconsulta. Aceda à sua Agenda.',
            APP_URL . '/private/utente/sessoes_consultas.php'
        );
    }
    registarAuditoria('ATUALIZAR', 'Sessao', $sessao_id, 'Link videoconsulta ' . ($link ? 'adicionado' : 'removido'));
    echo json_encode(['ok'=>true]);
} catch (\Throwable $e) {
    echo json_encode(['ok'=>false,'erro'=>'Erro ao guardar: '.$e->getMessage()]);
}
