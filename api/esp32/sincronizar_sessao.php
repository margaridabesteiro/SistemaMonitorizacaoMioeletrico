<?php
// POST Authorization: Bearer <token_api>
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/^Bearer\s+(.+)$/i', $auth_header, $m)) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token ausente.']); exit;
}
$token = $m[1];

$db = getDB();
$stmt = $db->prepare('SELECT id, ativo FROM dispositivos WHERE token_api=?');
$stmt->execute([$token]); $dispositivo = $stmt->fetch();

if (!$dispositivo || !$dispositivo['ativo']) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token inválido ou dispositivo inativo.']); exit;
}
$disp_id = (int)$dispositivo['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['erro' => 'JSON inválido.']); exit;
}

$utente_id  = (int)($body['utente_id']  ?? 0);
$jogo_id    = (int)($body['jogo_id']    ?? 0) ?: null;
$data_hora  = $body['data_hora']         ?? '';
$duracao    = (int)($body['duracao_min'] ?? 0);

if (!$utente_id || !$data_hora) {
    http_response_code(422);
    echo json_encode(['erro' => 'Campos obrigatórios: utente_id, data_hora.']); exit;
}

$u = $db->prepare('SELECT id FROM utentes WHERE id=?'); $u->execute([$utente_id]);
if (!$u->fetch()) {
    http_response_code(422);
    echo json_encode(['erro' => 'utente_id inexistente.']); exit;
}

$percentagem = isset($body['percentagem_final']) ? (float)$body['percentagem_final'] : null;
$tendencia   = calcularTendencia($db, $utente_id, $jogo_id, $percentagem);

$db->prepare('INSERT INTO sessoes (utente_id, dispositivo_id, data_hora, duracao_min, categoria, jogo_id, objetivo_sessao, notas, modalidade, estado, estado_sync, data_sync)
              VALUES (?,?,?,?,\'jogo\',?,?,?,\'presencial\',\'concluida\',\'sincronizado\',NOW())')
   ->execute([
       $utente_id, $disp_id, $data_hora, $duracao, $jogo_id,
       $body['objetivo_sessao'] ?? null,
       $body['notas'] ?? null
   ]);
$sessao_id = (int)$db->lastInsertId();

if ($percentagem !== null || isset($body['score_jogo'])) {
    $passou   = isset($body['passou_nivel']) ? (int)(bool)$body['passou_nivel'] : 0;
    $tentativas = (int)($body['n_tentativas'] ?? 1);
    $score    = isset($body['score_jogo']) ? (int)$body['score_jogo'] : null;

    $db->prepare('INSERT INTO metricas_sessao (sessao_id, percentagem_final, score_jogo, passou_nivel, n_tentativas, tendencia)
                  VALUES (?,?,?,?,?,?)')
       ->execute([$sessao_id, $percentagem, $score, $passou, $tentativas, $tendencia]);
}

$db->prepare('UPDATE dispositivos SET ultimo_sync=NOW() WHERE id=?')->execute([$disp_id]);

echo json_encode([
    'sucesso'    => true,
    'sessao_id'  => $sessao_id,
    'tendencia'  => $tendencia,
    'mensagem'   => 'Sessão sincronizada com sucesso.'
]);
