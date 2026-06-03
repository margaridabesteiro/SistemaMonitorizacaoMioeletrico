<?php
// RGPD Art. 20 — Direito à Portabilidade: o utente descarrega todos os seus dados
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');

$uid = (int)$_SESSION['utilizador_id'];
$db  = getDB();

// Dados pessoais
$u = $db->prepare("SELECT u.nome, u.email, u.criado_em, ut.data_nascimento, ut.sexo, ut.nif,
                          ut.morada, ut.codigo_postal, ut.localidade, ut.diagnostico
                   FROM utilizadores u LEFT JOIN utentes ut ON ut.utilizador_id=u.id WHERE u.id=?");
$u->execute([$uid]); $pessoais = $u->fetch(PDO::FETCH_ASSOC);

$utente_row = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?");
$utente_row->execute([$uid]); $utid = (int)$utente_row->fetchColumn();

// Sessões
$sess = $db->prepare("SELECT s.data_hora, s.categoria, s.estado, s.duracao_min, s.notas FROM sessoes s WHERE s.utente_id=? ORDER BY s.data_hora DESC");
$sess->execute([$utid]); $sessoes = $sess->fetchAll(PDO::FETCH_ASSOC);

// Métricas
$met = $db->prepare("SELECT s.data_hora, m.score_jogo, m.percentagem_final, m.passou_nivel, m.tendencia
                     FROM metricas_sessao m JOIN sessoes s ON s.id=m.sessao_id WHERE s.utente_id=? ORDER BY s.data_hora DESC");
$met->execute([$utid]); $metricas = $met->fetchAll(PDO::FETCH_ASSOC);

// Mensagens enviadas
$msgs = $db->prepare("SELECT m.assunto, m.corpo, m.enviada_em, u.nome AS destinatario
                      FROM mensagens m JOIN utilizadores u ON u.id=m.destinatario_id WHERE m.remetente_id=? ORDER BY m.enviada_em DESC");
$msgs->execute([$uid]); $mensagens = $msgs->fetchAll(PDO::FETCH_ASSOC);

$export = [
    'titular'        => $pessoais,
    'sessoes_treino' => $sessoes,
    'metricas_emg'   => $metricas,
    'mensagens'      => $mensagens,
    'gerado_em'      => date('c'),
    'base_legal'     => 'RGPD Art. 20 — Direito à Portabilidade dos Dados',
    'responsavel'    => 'RehabLink — privacidade@rehablink.pt',
];

// Registar a exportação
try {
    $db->prepare('INSERT INTO rgpd_consentimentos (utilizador_id, tipo, ip, detalhes) VALUES (?,?,?,?)')
       ->execute([$uid, 'exportacao', $_SERVER['REMOTE_ADDR'] ?? null, 'Exportação de dados pelo titular (Art.20 RGPD)']);
} catch (\Throwable $e) {}

header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="dados_rehablink_' . date('Ymd_His') . '.json"');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
