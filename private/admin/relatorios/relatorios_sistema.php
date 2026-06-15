<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Relatórios do Sistema'; $pagina_ativa = 'relatorios';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
$periodo = (int)($_GET['periodo'] ?? 30);

$s = $db->prepare("SELECT COUNT(*) FROM sessoes WHERE data_hora >= DATE_SUB(NOW(), INTERVAL ? DAY)");
$s->execute([$periodo]); $sessoes_periodo = (int)$s->fetchColumn();
$s = $db->prepare("SELECT COUNT(*) FROM utilizadores WHERE perfil='utente' AND criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)");
$s->execute([$periodo]); $utentes_novos = (int)$s->fetchColumn();
$s = $db->prepare("SELECT COALESCE(SUM(valor_eur),0) FROM faturas WHERE data_emissao >= DATE_SUB(NOW(), INTERVAL ? DAY)");
$s->execute([$periodo]); $fat_periodo = $s->fetchColumn();

// Gráfico 1 — Sessões por dia
$s = $db->prepare("SELECT DATE(data_hora) as dia, COUNT(*) as total FROM sessoes WHERE data_hora >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY dia ORDER BY dia");
$s->execute([$periodo]); $sessoes_por_dia = $s->fetchAll();

// Gráfico 2 — Logins por perfil
$s = $db->prepare("
    SELECT u.perfil, COUNT(*) as total
    FROM logs_acesso l
    JOIN utilizadores u ON u.id = l.utilizador_id
    WHERE l.acao = 'login' AND l.criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY u.perfil
");
$s->execute([$periodo]); $logins_perfil_raw = $s->fetchAll(\PDO::FETCH_KEY_PAIR);

$perfil_labels = ['admin' => 'Administrador', 'medico' => 'Médico', 'tecnico' => 'Técnico', 'utente' => 'Utente'];
$logins_labels = []; $logins_data = [];
foreach ($perfil_labels as $key => $label) {
    $logins_labels[] = $label;
    $logins_data[]   = (int)($logins_perfil_raw[$key] ?? 0);
}

// Gráfico 3 — Pacientes atendidos por médico (inclui médicos com 0 consultas)
$s = $db->prepare("
    SELECT u.nome, COUNT(DISTINCT c.utente_id) as total
    FROM profissionais p
    JOIN utilizadores u ON u.id = p.utilizador_id AND u.perfil = 'medico' AND u.ativo = 1
    LEFT JOIN consultas c ON c.medico_id = p.id AND c.data_hora >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY p.id, u.nome
    ORDER BY total DESC, u.nome ASC
");
$s->execute([$periodo]); $pacientes_medico = $s->fetchAll();

// Tabela — Utentes atribuídos por médico (sempre atualizado, sem filtro de período)
$s = $db->query("
    SELECT u.nome AS medico_nome,
           COUNT(ut.id) AS total_utentes,
           GROUP_CONCAT(um.nome ORDER BY um.nome SEPARATOR '|') AS utentes_nomes
    FROM profissionais p
    JOIN utilizadores u ON u.id = p.utilizador_id AND u.perfil = 'medico' AND u.ativo = 1
    LEFT JOIN utentes ut ON ut.medico_id = p.id
    LEFT JOIN utilizadores um ON um.id = ut.utilizador_id AND um.ativo = 1
    GROUP BY p.id, u.nome
    ORDER BY total_utentes DESC, u.nome ASC
");
$utentes_por_medico = $s->fetchAll();

// Gráfico 4 — Faturação (agrupada por dia ou mês consoante o período)
$fat_formato  = $periodo <= 30 ? '%d/%m' : '%m/%Y';
$s = $db->prepare("
    SELECT DATE_FORMAT(data_emissao, '$fat_formato') as label, COALESCE(SUM(valor_eur),0) as total
    FROM faturas
    WHERE data_emissao >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY label
    ORDER BY MIN(data_emissao)
");
$s->execute([$periodo]); $faturacao_mensal = $s->fetchAll();
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Relatórios do Sistema</h1>
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 text-muted small fw-semibold">Período:</label>
                    <select name="periodo" class="form-select" style="width:140px;" onchange="this.form.submit()">
                        <option value="7"   <?= $periodo==7  ?'selected':'' ?>>7 dias</option>
                        <option value="30"  <?= $periodo==30 ?'selected':'' ?>>30 dias</option>
                        <option value="90"  <?= $periodo==90 ?'selected':'' ?>>90 dias</option>
                        <option value="365" <?= $periodo==365?'selected':'' ?>>1 ano</option>
                    </select>
                </form>
            </div>

            <!-- Métricas rápidas -->
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-primary"><?= $sessoes_periodo ?></div><div class="text-muted small">Sessões (<?= $periodo ?>d)</div></div></div>
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success"><?= $utentes_novos ?></div><div class="text-muted small">Novos Utentes</div></div></div>
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-danger"><?= number_format((float)$fat_periodo,2,',','.') ?>€</div><div class="text-muted small">Faturação</div></div></div>
            </div>

            <!-- Gráfico sessões por dia -->
            <div class="card p-3 mb-4">
                <h5 class="mb-3">Sessões por Dia (últimos <?= $periodo ?> dias)</h5>
                <canvas id="chartSessoes" height="80"></canvas>
            </div>

            <!-- Logins por perfil + Pacientes por médico -->
            <div class="row g-4 mb-4">
                <div class="col-md-5">
                    <div class="card p-3 h-100">
                        <h5 class="mb-3">Logins por Perfil (últimos <?= $periodo ?> dias)</h5>
                        <canvas id="chartLogins"></canvas>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card p-3 h-100">
                        <h5 class="mb-3">Pacientes Atendidos por Médico (últimos <?= $periodo ?> dias)</h5>
                        <canvas id="chartMedicos" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Faturação mensal -->
            <div class="card p-3 mb-4">
                <h5 class="mb-3">Faturação <?= $periodo <= 30 ? 'Diária' : 'Mensal' ?> (últimos <?= $periodo ?> dias)</h5>
                <canvas id="chartFaturacao" height="80"></canvas>
            </div>

            <!-- Utentes por médico -->
            <div class="card p-3 mb-4">
                <h5 class="mb-1">Utentes Atribuídos por Médico</h5>
                <p class="small text-muted mb-3">Estado atual — atualizado automaticamente quando um utente é adicionado ou reatribuído.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Médico</th>
                                <th class="text-center" style="width:80px;">Total</th>
                                <th>Utentes</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($utentes_por_medico)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Sem médicos registados.</td></tr>
                        <?php else: foreach ($utentes_por_medico as $row): ?>
                            <tr>
                                <td class="fw-semibold"><?= h($row['medico_nome']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $row['total_utentes'] > 0 ? 'primary' : 'secondary' ?>">
                                        <?= $row['total_utentes'] ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?php if ($row['utentes_nomes']): ?>
                                        <?= h(implode(', ', explode('|', $row['utentes_nomes']))) ?>
                                    <?php else: ?>
                                        <em>Sem utentes atribuídos</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        // Sessões por dia
        new Chart(document.getElementById('chartSessoes'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($sessoes_por_dia, 'dia')) ?>,
                datasets: [{ label: 'Sessões', data: <?= json_encode(array_column($sessoes_por_dia, 'total')) ?>, backgroundColor: 'rgba(102,126,234,0.7)' }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Logins por perfil (doughnut) — clicável
        const loginsPerfisKeys = ['admin','medico','tecnico','utente'];
        const loginsBaseUrl    = '<?= APP_URL ?>/private/admin/utilizadores/lista_utilizadores.php?perfil=';
        const loginsChart = new Chart(document.getElementById('chartLogins'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($logins_labels) ?>,
                datasets: [{
                    data: <?= json_encode($logins_data) ?>,
                    backgroundColor: ['#e53e3e','#ed8936','#48bb78','#764ba2']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                onClick(evt) {
                    const pts = loginsChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, false);
                    if (pts.length) window.location.href = loginsBaseUrl + loginsPerfisKeys[pts[0].index];
                }
            }
        });
        document.getElementById('chartLogins').style.cursor = 'pointer';

        // Pacientes por médico
        new Chart(document.getElementById('chartMedicos'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($pacientes_medico, 'nome')) ?>,
                datasets: [{ label: 'Pacientes', data: <?= json_encode(array_column($pacientes_medico, 'total')) ?>, backgroundColor: 'rgba(237,137,54,0.8)' }]
            },
            options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } } }
        });

        // Faturação mensal
        new Chart(document.getElementById('chartFaturacao'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($faturacao_mensal, 'label')) ?>,
                datasets: [{
                    label: 'Receita (€)',
                    data: <?= json_encode(array_column($faturacao_mensal, 'total')) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102,126,234,0.15)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
        </script>
<?php require_once __DIR__ . "/../../../includes/footer.php"; ?>
