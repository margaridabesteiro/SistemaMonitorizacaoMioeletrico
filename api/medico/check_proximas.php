<?php
// Verifica consultas nos próximos 15 min e envia notificação ao médico se ainda não enviada.
// Chamado via fetch() do header do médico a cada 2 minutos.
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('medico');
header('Content-Type: application/json');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$s   = $db->prepare("SELECT id FROM profissionais WHERE utilizador_id=?");
$s->execute([$uid]);
$pid = (int)$s->fetchColumn();
$enviadas = 0;

if (!$pid) { echo json_encode(['ok'=>true,'enviadas'=>0]); exit; }

try {
    $s = $db->prepare("
        SELECT c.id, c.data_hora, c.tipo, c.modalidade,
               u.nome AS paciente
        FROM consultas c
        JOIN utentes ut ON ut.id = c.utente_id
        JOIN utilizadores u ON u.id = ut.utilizador_id
        WHERE c.medico_id = ?
          AND c.data_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 MINUTE)
          AND c.estado NOT IN ('cancelada','realizada')
    ");
    $s->execute([$pid]);
    foreach ($s->fetchAll() as $item) {
        $marker = 'prox_consulta_med_' . $item['id'];
        $ex = $db->prepare("SELECT COUNT(*) FROM notificacoes WHERE utilizador_id=? AND corpo LIKE ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 4 HOUR)");
        $ex->execute([$uid, '%' . $marker . '%']);
        if (!(int)$ex->fetchColumn()) {
            $hora     = date('H:i', strtotime($item['data_hora']));
            $tipo_txt = ucfirst($item['tipo'] ?? 'médica');
            $modal    = in_array($item['modalidade'], ['video','remota'], true) ? 'Videoconsulta' : 'Presencial';
            notificar(
                $uid,
                'videoconsulta',
                'Consulta em breve — ' . $hora,
                'Consulta ' . $tipo_txt . ' (' . $modal . ') com ' . $item['paciente'] . ' começa em menos de 15 minutos. [' . $marker . ']',
                APP_URL . '/private/medico/consultas/consulta.php'
            );
            $enviadas++;
        }
    }
} catch (\Throwable $e) {}

echo json_encode(['ok' => true, 'enviadas' => $enviadas]);
