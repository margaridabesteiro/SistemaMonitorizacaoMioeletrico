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

$sess = $utid ? $db->query("SELECT data_hora, categoria, estado, duracao_min, notas FROM sessoes WHERE utente_id=$utid ORDER BY data_hora DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
$met  = $utid ? $db->query("SELECT s.data_hora, m.rms_uv, m.mav_uv, m.frequencia_hz, m.score_jogo, m.precisao_pct FROM metricas_sessao m JOIN sessoes s ON s.id=m.sessao_id WHERE s.utente_id=$utid ORDER BY s.data_hora DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
$logs = $db->query("SELECT acao, ip, criado_em FROM logs_acesso WHERE utilizador_id=$id ORDER BY criado_em DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

$export = [
    'dados_pessoais'   => $pessoais,
    'sessoes_treino'   => $sess,
    'metricas_emg'     => $met,
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
