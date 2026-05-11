<?php
// api/sessoes/leituras.php
// API JSON para gravar e consultar leituras EMG de uma sessão
// Usado pelo frontend de jogos via fetch() / WebSocket bridge

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin(); // Qualquer perfil autenticado pode chamar

$metodo = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// -------------------------------------------------------
// GET /api/sessoes/leituras.php?sessao_id=X
// Retorna todas as leituras de uma sessão (para replay/análise)
// -------------------------------------------------------
if ($metodo === 'GET') {
    $sessao_id = (int)($_GET['sessao_id'] ?? 0);
    if ($sessao_id <= 0) {
        http_response_code(400);
        echo json_encode(['erro' => 'sessao_id inválido']);
        exit;
    }

    // Verificar que a sessão pertence ao utilizador ou é técnico/médico/admin
    $sessao = $db->prepare('SELECT utente_id, tecnico_id FROM sessoes WHERE id = ?');
    $sessao->execute([$sessao_id]);
    $s = $sessao->fetch();

    if (!$s) { http_response_code(404); echo json_encode(['erro' => 'Sessão não encontrada']); exit; }

    $stmt = $db->prepare('
        SELECT canal, timestamp_ms, amplitude_uv
        FROM leituras_emg
        WHERE sessao_id = ?
        ORDER BY canal, timestamp_ms
    ');
    $stmt->execute([$sessao_id]);
    echo json_encode(['sessao_id' => $sessao_id, 'leituras' => $stmt->fetchAll()]);
    exit;
}

// -------------------------------------------------------
// POST /api/sessoes/leituras.php
// Body JSON: { "sessao_id": X, "leituras": [{ "canal":1, "timestamp_ms":0, "amplitude_uv":123.4 }, ...] }
// Chamado pelo ESP32 bridge ou pelo frontend do jogo
// -------------------------------------------------------
if ($metodo === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    if (!isset($body['sessao_id'], $body['leituras']) || !is_array($body['leituras'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Payload inválido']);
        exit;
    }

    $sessao_id = (int)$body['sessao_id'];
    $leituras  = $body['leituras'];

    // Verificar sessão existente
    $existe = $db->prepare('SELECT id FROM sessoes WHERE id = ? AND estado = "em_curso"');
    $existe->execute([$sessao_id]);
    if (!$existe->fetch()) {
        http_response_code(409);
        echo json_encode(['erro' => 'Sessão não está em curso']);
        exit;
    }

    // Inserção em batch (preparada uma vez)
    $stmt = $db->prepare('INSERT INTO leituras_emg (sessao_id, canal, timestamp_ms, amplitude_uv) VALUES (?,?,?,?)');
    $inseridos = 0;

    $db->beginTransaction();
    try {
        foreach ($leituras as $l) {
            $canal        = (int)($l['canal']        ?? 1);
            $timestamp_ms = (int)($l['timestamp_ms'] ?? 0);
            $amplitude_uv = (float)($l['amplitude_uv'] ?? 0.0);

            if ($timestamp_ms < 0 || $amplitude_uv < -10000 || $amplitude_uv > 10000) continue; // validação básica

            $stmt->execute([$sessao_id, $canal, $timestamp_ms, $amplitude_uv]);
            $inseridos++;
        }
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['erro' => 'Falha na gravação']);
        exit;
    }

    echo json_encode(['inseridos' => $inseridos]);
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não suportado']);
