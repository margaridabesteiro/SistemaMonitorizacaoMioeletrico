<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Consultas'; $pagina_ativa = 'consultas';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
$db = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);
$consultas = [];
if ($pid) {
    $stmt = $db->prepare("SELECT c.*, u.nome AS paciente FROM consultas c JOIN utentes ut ON ut.id=c.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE c.medico_id=? ORDER BY c.data_hora DESC");
    $stmt->execute([$pid]); $consultas = $stmt->fetchAll();
}
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Consultas</h1>
                <div class="d-flex gap-2">
                    <a href="nova_consulta.php" class="btn btn-sm" style="background:#8B0000;color:#fff;"><i class="fa-regular fa-calendar-plus me-1"></i>Nova Consulta</a>
                    <a href="agenda.php" class="btn btn-sm btn-outline-secondary"><i class="fa-regular fa-calendar me-1"></i>Minha Agenda</a>
                </div>
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
                            <td><?= h($c['motivo'] ?? '') ?></td>
                            <td><span class="badge bg-<?= ['agendada'=>'primary','realizada'=>'success','cancelada'=>'danger'][$c['estado']] ?? 'secondary' ?>"><?= h($c['estado']) ?></span></td>
                            <td>
                                <a href="detalhe_consulta.php?id=<?= $c['id'] ?>" class="btn btn-xs btn-outline-primary" title="Ver detalhe">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>

        <!-- Modal: Ver Consulta -->
        <div class="modal fade" id="modalVerConsulta" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background:#198754;">
                        <h5 class="modal-title text-white">
                            <i class="fa-regular fa-calendar-check me-2"></i>Detalhes da Consulta
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted fw-semibold" style="width:35%"><i class="fa-solid fa-user me-2"></i>Paciente</td>
                                <td id="mc-paciente"></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold"><i class="fa-regular fa-clock me-2"></i>Data/Hora</td>
                                <td id="mc-datahora"></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold"><i class="fa-solid fa-notes-medical me-2"></i>Motivo</td>
                                <td id="mc-motivo"></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold"><i class="fa-solid fa-circle-info me-2"></i>Estado</td>
                                <td id="mc-estado"></td>
                            </tr>
                            <tr id="mc-notas-row">
                                <td class="text-muted fw-semibold"><i class="fa-regular fa-file-lines me-2"></i>Notas</td>
                                <td id="mc-notas" class="fst-italic text-muted"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        const badges = {agendada:'primary', realizada:'success', cancelada:'danger'};
        document.querySelectorAll('.btn-ver-consulta').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('mc-paciente').textContent  = this.dataset.paciente;
                document.getElementById('mc-datahora').textContent  = this.dataset.datahora;
                document.getElementById('mc-motivo').textContent    = this.dataset.motivo || '—';
                const estado = this.dataset.estado;
                document.getElementById('mc-estado').innerHTML = `<span class="badge bg-${badges[estado]||'secondary'}">${estado}</span>`;
                const notas = this.dataset.notas;
                document.getElementById('mc-notas').textContent = notas || 'Sem notas registadas.';
            });
        });
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
