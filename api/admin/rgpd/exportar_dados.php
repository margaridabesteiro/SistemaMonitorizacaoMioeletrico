<?php
// Admin: exportar todos os dados de um utilizador (portabilidade / auditoria)
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');

$db = getDB();

$u = $db->prepare("SELECT u.nome, u.email, u.perfil, u.ativo, u.criado_em,
                          ut.data_nascimento, ut.sexo, ut.nif, ut.morada, ut.codigo_postal,
                          ut.localidade, ut.diagnostico, ut.cobertura_saude
                   FROM utilizadores u LEFT JOIN utentes ut ON ut.utilizador_id=u.id WHERE u.id=?");
$u->execute([$id]); $pessoais = $u->fetch(PDO::FETCH_ASSOC);
if (!$pessoais) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');

$utente_row = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?");
$utente_row->execute([$id]); $utid = (int)$utente_row->fetchColumn();

$s1 = $db->prepare("SELECT data_hora, categoria, estado, duracao_min, notas FROM sessoes WHERE utente_id=? ORDER BY data_hora DESC");
$s1->execute([$utid]); $sess = $utid ? $s1->fetchAll(PDO::FETCH_ASSOC) : [];
$s2 = $db->prepare("SELECT s.data_hora, m.score_jogo, m.percentagem_final, m.passou_nivel, m.tendencia FROM metricas_sessao m JOIN sessoes s ON s.id=m.sessao_id WHERE s.utente_id=? ORDER BY s.data_hora DESC");
$s2->execute([$utid]); $met = $utid ? $s2->fetchAll(PDO::FETCH_ASSOC) : [];
$s3 = $db->prepare("SELECT acao, ip, criado_em FROM logs_acesso WHERE utilizador_id=? ORDER BY criado_em DESC LIMIT 100");
$s3->execute([$id]); $logs = $s3->fetchAll(PDO::FETCH_ASSOC);

$export = [
    'dados_pessoais'   => $pessoais,
    'sessoes_treino'   => $sess,
    'metricas_jogo'    => $met,
    'logs_acesso'      => $logs,
    'exportado_por'    => $_SESSION['nome'] ?? 'Admin',
    'gerado_em'        => date('c'),
    'base_legal'       => 'RGPD Art. 20 — Portabilidade / Resposta a pedido do titular',
];

// Registar a exportação admin
try {
    $db->prepare('INSERT INTO rgpd_consentimentos (utilizador_id, tipo, registado_por, ip, detalhes) VALUES (?,?,?,?,?)')
       ->execute([$id, 'exportacao', $_SESSION['utilizador_id'], $_SERVER['REMOTE_ADDR'] ?? null, 'Exportação pelo administrador']);
} catch (\Throwable $e) {}

header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="dados_utilizador_' . $id . '_' . date('Ymd') . '.json"');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
