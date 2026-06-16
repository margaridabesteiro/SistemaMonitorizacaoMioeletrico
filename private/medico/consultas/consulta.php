<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Consultas'; $pagina_ativa = 'consultas';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

$consultas = [];
if ($pid) {
    $stmt = $db->prepare("
        SELECT c.id, c.data_hora, c.tipo, c.modalidade, c.link_videochamada, c.estado,
               u.nome AS paciente
        FROM consultas c
        JOIN utentes ut ON ut.id = c.utente_id
        JOIN utilizadores u ON u.id = ut.utilizador_id
        WHERE c.medico_id = ?
        ORDER BY c.data_hora DESC
    ");
    $stmt->execute([$pid]);
    $consultas = $stmt->fetchAll();
}

$agora = time();
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Consultas</h1>
                <div class="d-flex gap-2">
                    <a href="nova_consulta.php" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                        <i class="fa-regular fa-calendar-plus me-1"></i>Nova Consulta
                    </a>
                    <a href="agenda.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-regular fa-calendar me-1"></i>Minha Agenda
                    </a>
                </div>
            </div>

            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Paciente</th>
                            <th>Tipo de Consulta</th>
                            <th>Data / Hora</th>
                            <th>Modalidade</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($consultas)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Sem consultas.</td></tr>
                    <?php else: foreach ($consultas as $c):
                        $ts       = strtotime($c['data_hora']);
                        $diff     = $ts - $agora;
                        $e_video  = in_array($c['modalidade'], ['video','remota'], true);
                        $pode_entrar = $e_video && !empty($c['link_videochamada']) && $diff <= 900 && $diff >= -3600;
                    ?>
                        <tr>
                            <td class="fw-semibold"><?= h($c['paciente']) ?></td>
                            <td>
                                <?php
                                $tipos = [
                                    'inicial'  => ['label'=>'Inicial',  'bg'=>'info text-dark'],
                                    'rotina'   => ['label'=>'Rotina',   'bg'=>'secondary'],
                                    'alta'     => ['label'=>'Alta',     'bg'=>'success'],
                                    'urgente'  => ['label'=>'Urgente',  'bg'=>'danger'],
                                ];
                                $t = $tipos[$c['tipo']] ?? ['label'=>ucfirst($c['tipo'] ?? '—'), 'bg'=>'secondary'];
                                ?>
                                <span class="badge bg-<?= $t['bg'] ?>"><?= h($t['label']) ?></span>
                            </td>
                            <td><?= h(date('d/m/Y H:i', $ts)) ?></td>
                            <td>
                                <?php if ($e_video): ?>
                                    <span class="badge bg-primary"><i class="fa-solid fa-video me-1"></i>Videoconsulta</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fa-solid fa-hospital me-1"></i>Presencial</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= ['agendada'=>'primary','realizada'=>'success','cancelada'=>'danger'][$c['estado']] ?? 'secondary' ?>">
                                    <?= h(ucfirst($c['estado'])) ?>
                                </span>
                            </td>
                            <td class="d-flex gap-1 flex-wrap">
                                <a href="detalhe_consulta.php?id=<?= $c['id'] ?>"
                                   class="btn btn-xs btn-outline-primary" title="Ver detalhe">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <?php if ($e_video && !empty($c['link_videochamada'])): ?>
                                <div class="entrar-wrap-med"
                                     data-ts="<?= $ts ?>"
                                     data-link="<?= h($c['link_videochamada']) ?>"
                                     style="<?= $pode_entrar ? '' : 'display:none;' ?>">
                                    <a href="<?= h($c['link_videochamada']) ?>" target="_blank" rel="noopener"
                                       class="btn btn-xs btn-primary" title="Entrar na videoconsulta">
                                        <i class="fa-solid fa-video me-1"></i>Entrar
                                    </a>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>

<script>
function atualizarBotoesMed() {
    var agora = Math.floor(Date.now() / 1000);
    document.querySelectorAll('.entrar-wrap-med').forEach(function(el) {
        var ts   = parseInt(el.dataset.ts, 10);
        var diff = ts - agora;
        el.style.display = (diff <= 900 && diff >= -3600) ? '' : 'none';
    });
}
atualizarBotoesMed();
setInterval(atualizarBotoesMed, 30000);
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
