<?php
// private/medico/index_M.php
// Dashboard do médico

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$pagina_titulo = 'Painel do Médico';
$pagina_ativa  = 'dashboard';
$js_head       = ['https://cdn.jsdelivr.net/npm/chart.js'];

require_once __DIR__ . '/../../includes/header_medico.php';
require_once __DIR__ . '/../../includes/sidebar_medico.php';

$db         = getDB();
$medico_id  = $_SESSION['utilizador_id'];

// Profissional associado ao utilizador
$profissional = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id = ?');
$profissional->execute([$medico_id]);
$prof_id = (int)($profissional->fetchColumn() ?: 0);

// Consultas agendadas para hoje
$consultas_hoje = $prof_id ? $db->prepare('
    SELECT COUNT(*) FROM consultas
    WHERE medico_id = ? AND DATE(data_hora) = CURDATE() AND estado = "agendada"
') : null;
if ($consultas_hoje) { $consultas_hoje->execute([$prof_id]); }
$n_consultas = $consultas_hoje ? (int)$consultas_hoje->fetchColumn() : 0;

// Total de pacientes
$n_pacientes = $prof_id ? (int)$db->prepare('SELECT COUNT(*) FROM utentes WHERE medico_id = ?')
    ->execute([$prof_id]) : 0;
// Re-fetch correctly
if ($prof_id) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM utentes WHERE medico_id = ?');
    $stmt->execute([$prof_id]);
    $n_pacientes = (int)$stmt->fetchColumn();
}

// Prescrições ativas
$n_prescricoes = 0;
if ($prof_id) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM programas_tratamento WHERE medico_id = ? AND ativa = 1');
    $stmt->execute([$prof_id]);
    $n_prescricoes = (int)$stmt->fetchColumn();
}

// Próximas consultas (5)
$proximas = [];
if ($prof_id) {
    $stmt = $db->prepare('
        SELECT c.data_hora, c.motivo, c.estado, u.nome AS paciente
        FROM consultas c
        JOIN utentes ut ON ut.id = c.utente_id
        JOIN utilizadores u ON u.id = ut.utilizador_id
        WHERE c.medico_id = ? AND c.data_hora >= NOW()
        ORDER BY c.data_hora ASC
        LIMIT 5
    ');
    $stmt->execute([$prof_id]);
    $proximas = $stmt->fetchAll();
}
?>
        <main class="content">
            <div class="welcome-section mb-4">
                <div class="welcome-text">
                    <h2>Bem-vindo, <?= h($_SESSION['nome']) ?></h2>
                    <p><?= dataPt() ?> · <?= $n_consultas ?> consulta(s) agendada(s) para hoje</p>
                </div>
                <div class="welcome-icon">
                    <i class="fa-solid fa-stethoscope fa-2x" style="color:#8B0000;"></i>
                </div>
            </div>

            <!-- Métricas rápidas -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-danger"><?= $n_pacientes ?></div>
                        <div class="text-muted small">Pacientes</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-primary"><?= $n_consultas ?></div>
                        <div class="text-muted small">Consultas Hoje</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-success"><?= $n_prescricoes ?></div>
                        <div class="text-muted small">Prescrições Ativas</div>
                    </div>
                </div>
            </div>

            <!-- Próximas consultas -->
            <div class="card p-3 mb-4">
                <h5 class="mb-3">Próximas Consultas</h5>
                <?php if (empty($proximas)): ?>
                    <p class="text-muted">Sem consultas agendadas.</p>
                <?php else: ?>
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Data/Hora</th><th>Paciente</th><th>Motivo</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($proximas as $c): ?>
                            <tr>
                                <td><?= h(substr($c['data_hora'], 0, 16)) ?></td>
                                <td><?= h($c['paciente']) ?></td>
                                <td><?= h($c['motivo'] ?? '—') ?></td>
                                <td><span class="badge bg-info"><?= h($c['estado']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <a href="consultas/consulta.php" class="btn btn-sm btn-outline-secondary mt-2">Ver todas</a>
            </div>
        </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
