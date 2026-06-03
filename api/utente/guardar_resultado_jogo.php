<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');

header('Content-Type: application/json');
$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$jogo_id     = ((int)($body['jogo_id']      ?? 0)) ?: null;
$score       = (int)($body['score']          ?? 0);
$percentagem = round((float)($body['percentagem'] ?? 0), 1);
$passou      = !empty($body['passou']);
$n_tentativas = max(1, (int)($body['n_tentativas'] ?? 1));
$duracao_min  = max(1, (int)($body['duracao_min']  ?? 1));

// Obter utente e técnico
$stmt = $db->prepare("SELECT ut.id, ut.tecnico_id FROM utentes ut WHERE ut.utilizador_id=?");
$stmt->execute([$uid]); $utente = $stmt->fetch();

if (!$utente) {
    echo json_encode(['ok' => false, 'erro' => 'Utente não encontrado.']);
    exit;
}
$utid       = (int)$utente['id'];
$tecnico_id = (int)$utente['tecnico_id'];

if (!$tecnico_id) {
    echo json_encode(['ok' => false, 'erro' => 'Sem técnico associado. Contacta o administrador.']);
    exit;
}

try {
    $db->beginTransaction();

    // Criar sessão
    $db->prepare("
        INSERT INTO sessoes
            (utente_id, tecnico_id, jogo_id, data_hora, duracao_min,
             categoria, modalidade, estado, estado_sync)
        VALUES (?, ?, ?, NOW(), ?, 'jogo', 'presencial', 'concluida', 'sincronizado')
    ")->execute([$utid, $tecnico_id, $jogo_id, $duracao_min]);
    $sessao_id = (int)$db->lastInsertId();

    // Calcular tendência vs. última sessão do mesmo jogo
    $tendencia = null;
    if ($jogo_id) {
        $st = $db->prepare("
            SELECT m.percentagem_final
            FROM metricas_sessao m
            JOIN sessoes s ON s.id = m.sessao_id
            WHERE s.utente_id = ? AND s.jogo_id = ? AND s.id != ?
              AND m.percentagem_final IS NOT NULL
            ORDER BY s.data_hora DESC LIMIT 1
        ");
        $st->execute([$utid, $jogo_id, $sessao_id]);
        $ultima = $st->fetchColumn();
        if ($ultima !== false) {
            $diff      = $percentagem - (float)$ultima;
            $tendencia = $diff > 5 ? 'melhoria' : ($diff < -5 ? 'regressao' : 'estavel');
        }
    }

    // Guardar métricas
    $db->prepare("
        INSERT INTO metricas_sessao
            (sessao_id, percentagem_final, score_jogo, passou_nivel, n_tentativas, tendencia)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([$sessao_id, $percentagem, $score, $passou ? 1 : 0, $n_tentativas, $tendencia]);

    $db->commit();
    echo json_encode(['ok' => true, 'sessao_id' => $sessao_id, 'tendencia' => $tendencia]);

} catch (\Throwable $e) {
    $db->rollBack();
    echo json_encode(['ok' => false, 'erro' => 'Erro ao guardar: ' . $e->getMessage()]);
}
