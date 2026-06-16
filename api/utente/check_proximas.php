<?php
// Verifica sessões/consultas nos próximos 15 min e envia notificação se ainda não enviada.
// Chamado via fetch() do header do utente a cada 2 minutos.
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
header('Content-Type: application/json');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$s   = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?");
$s->execute([$uid]);
$utid = (int)$s->fetchColumn();
$enviadas = 0;

if (!$utid) { echo json_encode(['ok'=>true,'enviadas'=>0]); exit; }

$url_dest = APP_URL . '/private/utente/sessoes_consultas.php';

// Sessões de treino nos próximos 15 min
try {
    $s = $db->prepare("
        SELECT s.id, s.data_hora, COALESCE(j.nome, s.categoria, 'Sessão de Treino') AS titulo
        FROM sessoes s
        LEFT JOIN jogos j ON j.id = s.jogo_id
        WHERE s.utente_id = ?
          AND s.data_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 MINUTE)
          AND s.estado NOT IN ('cancelada','concluida')
    ");
    $s->execute([$utid]);
    foreach ($s->fetchAll() as $item) {
        $marker = 'prox_sessao_' . $item['id'];
        $ex = $db->prepare("SELECT COUNT(*) FROM notificacoes WHERE utilizador_id=? AND corpo LIKE ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 4 HOUR)");
        $ex->execute([$uid, '%' . $marker . '%']);
        if (!(int)$ex->fetchColumn()) {
            $hora = date('H:i', strtotime($item['data_hora']));
            notificar($uid, 'sessao',
                'Sessão em breve — ' . $hora,
                $item['titulo'] . ' começa em menos de 15 minutos. [' . $marker . ']',
                $url_dest);
            $enviadas++;
        }
    }
} catch (\Throwable $e) {}

// Consultas médicas nos próximos 15 min
try {
    $s = $db->prepare("
        SELECT c.id, c.data_hora, COALESCE(CONCAT('Consulta — ', c.tipo), 'Consulta Médica') AS titulo
        FROM consultas c
        WHERE c.utente_id = ?
          AND c.data_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 MINUTE)
          AND c.estado NOT IN ('cancelada','realizada')
    ");
    $s->execute([$utid]);
    foreach ($s->fetchAll() as $item) {
        $marker = 'prox_consulta_' . $item['id'];
        $ex = $db->prepare("SELECT COUNT(*) FROM notificacoes WHERE utilizador_id=? AND corpo LIKE ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 4 HOUR)");
        $ex->execute([$uid, '%' . $marker . '%']);
        if (!(int)$ex->fetchColumn()) {
            $hora = date('H:i', strtotime($item['data_hora']));
            notificar($uid, 'sessao',
                'Consulta em breve — ' . $hora,
                $item['titulo'] . ' começa em menos de 15 minutos. [' . $marker . ']',
                $url_dest);
            $enviadas++;
        }
    }
} catch (\Throwable $e) {}

echo json_encode(['ok' => true, 'enviadas' => $enviadas]);
