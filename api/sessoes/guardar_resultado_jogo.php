<?php
// api/sessoes/guardar_resultado_jogo.php
// Regista o resultado de um jogo na BD (sessao + metricas_sessao)
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['utilizador_id'])) {
    echo json_encode(['ok' => false, 'erro' => 'Não autenticado.']); exit;
}

$db          = getDB();
$jogo_id     = (int)($_POST['jogo_id']     ?? 0);
$percentagem = (float)($_POST['percentagem'] ?? 0);
$score       = (int)($_POST['score']       ?? 0);
$perfil      = $_SESSION['perfil'] ?? '';

if (!$jogo_id) {
    echo json_encode(['ok' => false, 'erro' => 'Jogo inválido.']); exit;
}

// Garante registos base e atualiza níveis: claw=1(minimo), catch=2(medio), flappy=3(maximo)
$db->exec("INSERT INTO jogos (id, nome, nivel, descricao) VALUES
    (1, 'catch_game',         'medio',  'Apanhar objetos em queda — controlo on/off'),
    (2, 'claw_game',          'minimo', 'Garra arcade com dois thresholds de força'),
    (3, 'flappy_trainer',     'maximo', 'Controlo proporcional de altitude por força'),
    (4, 'prosthesis_trainer', 'maximo', 'Simulação de tarefas reais de prótese mioelétrica')
ON DUPLICATE KEY UPDATE nivel=VALUES(nivel)");

if ($perfil === 'utente') {
    $s = $db->prepare("SELECT ut.id, ut.tecnico_id FROM utentes ut WHERE ut.utilizador_id = ?");
    $s->execute([$_SESSION['utilizador_id']]);
    $row = $s->fetch();
    if (!$row) { echo json_encode(['ok' => false, 'erro' => 'Utente não encontrado.']); exit; }
    $utente_id  = $row['id'];
    $tecnico_id = $row['tecnico_id'] ?: (int)$db->query("SELECT id FROM profissionais LIMIT 1")->fetchColumn();

} elseif ($perfil === 'tecnico') {
    $utente_id  = (int)($_POST['utente_id'] ?? 0);
    $s = $db->prepare("SELECT id FROM profissionais WHERE utilizador_id = ?");
    $s->execute([$_SESSION['utilizador_id']]);
    $tecnico_id = (int)$s->fetchColumn();
    if (!$utente_id || !$tecnico_id) {
        echo json_encode(['ok' => false, 'erro' => 'Utente ou técnico inválido.']); exit;
    }
} else {
    echo json_encode(['ok' => false, 'erro' => 'Perfil não autorizado.']); exit;
}

try {
    $db->prepare("INSERT INTO sessoes (utente_id, tecnico_id, jogo_id, data_hora, duracao_min, categoria, estado)
                  VALUES (?,?,?,NOW(),1,'jogo','concluida')")
       ->execute([$utente_id, $tecnico_id, $jogo_id]);
    $sessao_id = (int)$db->lastInsertId();

    $db->prepare("INSERT INTO metricas_sessao (sessao_id, percentagem_final, score_jogo) VALUES (?,?,?)")
       ->execute([$sessao_id, $percentagem, $score]);

    echo json_encode(['ok' => true, 'sessao_id' => $sessao_id]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()]);
}
