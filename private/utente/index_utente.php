<?php
// private/utente/index_utente.php
// Dashboard do utente (paciente)

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$pagina_titulo = 'Dashboard Utente';
$pagina_ativa  = 'dashboard';
$js_head       = ['https://cdn.jsdelivr.net/npm/chart.js'];

require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';

$db = getDB();
$utilizador_id = (int)$_SESSION['utilizador_id'];

// Obter utente
$stmt = $db->prepare('SELECT id, diagnostico FROM utentes WHERE utilizador_id = ?');
$stmt->execute([$utilizador_id]);
$utente = $stmt->fetch();
$utente_id = $utente ? (int)$utente['id'] : 0;

// Próximas sessões
$proximas_sessoes = [];
if ($utente_id) {
    $stmt = $db->prepare('
        SELECT s.data_hora, s.tipo, s.estado,
               u.nome AS tecnico
        FROM sessoes s
        JOIN profissionais p ON p.id = s.tecnico_id
        JOIN utilizadores u ON u.id = p.utilizador_id
        WHERE s.utente_id = ? AND s.data_hora >= NOW() AND s.estado = "agendada"
        ORDER BY s.data_hora ASC
        LIMIT 3
    ');
    $stmt->execute([$utente_id]);
    $proximas_sessoes = $stmt->fetchAll();
}

// Total de sessões concluídas
$total_sessoes = 0;
if ($utente_id) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM sessoes WHERE utente_id = ? AND estado = "concluida"');
    $stmt->execute([$utente_id]);
    $total_sessoes = (int)$stmt->fetchColumn();
}

// Faturas em aberto
$faturas_abertas = 0;
if ($utente_id) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM faturas WHERE utente_id = ? AND paga = 0');
    $stmt->execute([$utente_id]);
    $faturas_abertas = (int)$stmt->fetchColumn();
}

// Mensagens não lidas
$stmt = $db->prepare('SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lida = 0');
$stmt->execute([$utilizador_id]);
$mensagens_nao_lidas = (int)$stmt->fetchColumn();
?>
        <main class="content">
            <div class="welcome-section mb-4">
                <div class="welcome-text">
                    <h2>Bem-vindo, <?= h($_SESSION['nome']) ?></h2>
                    <p><?= dataPt() ?></p>
                </div>
            </div>

            <!-- Métricas rápidas -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-success"><?= $total_sessoes ?></div>
                        <div class="text-muted small">Sessões Concluídas</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-primary"><?= count($proximas_sessoes) ?></div>
                        <div class="text-muted small">Próximas Sessões</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3 <?= $mensagens_nao_lidas > 0 ? 'border-warning' : '' ?>">
                        <div class="fs-2 fw-bold text-warning"><?= $mensagens_nao_lidas ?></div>
                        <div class="text-muted small">Mensagens Não Lidas</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3 <?= $faturas_abertas > 0 ? 'border-danger' : '' ?>">
                        <div class="fs-2 fw-bold text-danger"><?= $faturas_abertas ?></div>
                        <div class="text-muted small">Faturas em Aberto</div>
                    </div>
                </div>
            </div>

            <!-- Próximas sessões -->
            <div class="card p-3 mb-4">
                <h5 class="mb-3">Próximas Sessões de Treino</h5>
                <?php if (empty($proximas_sessoes)): ?>
                    <p class="text-muted">Não tem sessões agendadas.</p>
                <?php else: ?>
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Data/Hora</th><th>Tipo</th><th>Técnico</th></tr></thead>
                        <tbody>
                        <?php foreach ($proximas_sessoes as $s): ?>
                            <tr>
                                <td><?= h(substr($s['data_hora'],0,16)) ?></td>
                                <td><?= h($s['tipo'] ?? '—') ?></td>
                                <td><?= h($s['tecnico']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <a href="sessoes_agendadas.php" class="btn btn-sm btn-outline-secondary mt-2">Ver todas</a>
            </div>

            <!-- Atalhos rápidos -->
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="jogos_reabilitacao.php" class="card p-3 text-decoration-none text-dark d-block text-center">
                        <i class="fa-solid fa-gamepad fa-2x mb-2" style="color:#8B0000;"></i>
                        <div class="fw-semibold">Jogos de Reabilitação</div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="mensagens.php" class="card p-3 text-decoration-none text-dark d-block text-center">
                        <i class="fa-regular fa-envelope fa-2x mb-2" style="color:#8B0000;"></i>
                        <div class="fw-semibold">Mensagens</div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="pagamentos.php" class="card p-3 text-decoration-none text-dark d-block text-center">
                        <i class="fa-solid fa-credit-card fa-2x mb-2" style="color:#8B0000;"></i>
                        <div class="fw-semibold">Pagamentos</div>
                    </a>
                </div>
            </div>
        </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
