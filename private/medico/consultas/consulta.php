<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Consultas'; $pagina_ativa = 'consultas';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
$db = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);
$tab = $_GET['tab'] ?? 'hoje';
$where_map = [
    'hoje'    => "DATE(c.data_hora)=CURDATE()",
    'amanha'  => "DATE(c.data_hora)=DATE_ADD(CURDATE(),INTERVAL 1 DAY)",
    'semana'  => "YEARWEEK(c.data_hora,1)=YEARWEEK(NOW(),1)",
    'pendente'=> "c.estado='agendada' AND c.data_hora>=NOW()",
    'historico'=>"c.estado='realizada'",
];
$where = $where_map[$tab] ?? $where_map['hoje'];
$consultas = [];
if ($pid) {
    $stmt = $db->prepare("SELECT c.*, u.nome AS paciente FROM consultas c JOIN utentes ut ON ut.id=c.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE c.medico_id=? AND $where ORDER BY c.data_hora");
    $stmt->execute([$pid]); $consultas = $stmt->fetchAll();
}
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Consultas</h1>
                <a href="#" class="btn btn-sm" style="background:#8B0000;color:#fff;"><i class="fa-regular fa-calendar-plus me-1"></i>Nova Consulta</a>
            </div>
            <div class="d-flex gap-2 mb-4">
                <?php foreach(['hoje'=>'Hoje','amanha'=>'Amanhã','semana'=>'Esta semana','pendente'=>'Pendentes','historico'=>'Histórico'] as $k=>$v): ?>
                <a href="?tab=<?= $k ?>" class="btn btn-sm <?= $tab===$k?'btn-danger':'btn-outline-secondary' ?>"><?= $v ?></a>
                <?php endforeach; ?>
            </div>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Data/Hora</th><th>Paciente</th><th>Motivo</th><th>Estado</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php if(empty($consultas)): ?><tr><td colspan="5" class="text-center text-muted py-4">Sem consultas.</td></tr>
                    <?php else: foreach($consultas as $c): ?>
                        <tr>
                            <td><?= h(substr($c['data_hora'],0,16)) ?></td>
                            <td><?= h($c['paciente']) ?></td>
                            <td><?= h($c['motivo'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= ['agendada'=>'primary','realizada'=>'success','cancelada'=>'danger'][$c['estado']] ?? 'secondary' ?>"><?= h($c['estado']) ?></span></td>
                            <td><button class="btn btn-xs btn-outline-primary"><i class="fa-regular fa-eye"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
