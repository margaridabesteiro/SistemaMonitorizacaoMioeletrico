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

// Utentes por Médico
$utentes_por_medico = $db->query("
    SELECT u_med.nome AS medico, u_med.ativo AS medico_ativo,
           COUNT(ut.id) AS total,
           GROUP_CONCAT(u_ut.nome ORDER BY u_ut.nome SEPARATOR '||') AS nomes_utentes
    FROM profissionais p
    JOIN utilizadores u_med ON u_med.id = p.utilizador_id AND u_med.perfil = 'medico'
    LEFT JOIN utentes ut ON ut.medico_id = p.id
    LEFT JOIN utilizadores u_ut ON u_ut.id = ut.utilizador_id AND u_ut.email NOT LIKE 'anonimizado_%'
    GROUP BY p.id, u_med.nome, u_med.ativo
    ORDER BY u_med.nome
")->fetchAll();

// Utentes por Técnico
$utentes_por_tecnico = $db->query("
    SELECT u_tec.nome AS tecnico, u_tec.ativo AS tecnico_ativo,
           COUNT(ut.id) AS total,
           GROUP_CONCAT(u_ut.nome ORDER BY u_ut.nome SEPARATOR '||') AS nomes_utentes
    FROM profissionais p
    JOIN utilizadores u_tec ON u_tec.id = p.utilizador_id AND u_tec.perfil = 'tecnico'
    LEFT JOIN utentes ut ON ut.tecnico_id = p.id
    LEFT JOIN utilizadores u_ut ON u_ut.id = ut.utilizador_id AND u_ut.email NOT LIKE 'anonimizado_%'
    GROUP BY p.id, u_tec.nome, u_tec.ativo
    ORDER BY u_tec.nome
")->fetchAll();

// Gráfico 3 — Faturação (agrupada por dia ou mês consoante o período)
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

            <!-- Logins por perfil -->
            <div class="card p-3 mb-4">
                <h5 class="mb-3">Logins por Perfil (últimos <?= $periodo ?> dias)</h5>
                <div style="max-width:320px;margin:0 auto;">
                    <canvas id="chartLogins"></canvas>
                </div>
            </div>

            <!-- Faturação mensal -->
            <div class="card p-3 mb-4">
                <h5 class="mb-3">Faturação <?= $periodo <= 30 ? 'Diária' : 'Mensal' ?> (últimos <?= $periodo ?> dias)</h5>
                <canvas id="chartFaturacao" height="80"></canvas>
            </div>

            <!-- Tabelas: Utentes por Médico e Técnico -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card p-3">
                        <h5 class="mb-3"><i class="fa-solid fa-user-doctor me-2" style="color:#8B0000;"></i>Utentes por Médico</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr><th>Médico</th><th class="text-center" style="width:60px;">Total</th><th>Utentes</th></tr>
                                </thead>
                                <tbody>
                                <?php if (empty($utentes_por_medico)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">Sem dados.</td></tr>
                                <?php else: foreach ($utentes_por_medico as $row): ?>
                                    <tr>
                                        <td>
                                            <?= h($row['medico']) ?>
                                            <?php if (!$row['medico_ativo']): ?><span class="badge bg-secondary ms-1" style="font-size:.65rem;">inativo</span><?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold"><?= (int)$row['total'] ?></td>
                                        <td class="small text-muted">
                                            <?php
                                            $nomes = $row['nomes_utentes'] ? explode('||', $row['nomes_utentes']) : [];
                                            echo $nomes ? h(implode(', ', $nomes)) : '—';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3">
                        <h5 class="mb-3"><i class="fa-solid fa-user-nurse me-2" style="color:#8B0000;"></i>Utentes por Técnico</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr><th>Técnico</th><th class="text-center" style="width:60px;">Total</th><th>Utentes</th></tr>
                                </thead>
                                <tbody>
                                <?php if (empty($utentes_por_tecnico)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">Sem dados.</td></tr>
                                <?php else: foreach ($utentes_por_tecnico as $row): ?>
                                    <tr>
                                        <td>
                                            <?= h($row['tecnico']) ?>
                                            <?php if (!$row['tecnico_ativo']): ?><span class="badge bg-secondary ms-1" style="font-size:.65rem;">inativo</span><?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold"><?= (int)$row['total'] ?></td>
                                        <td class="small text-muted">
                                            <?php
                                            $nomes = $row['nomes_utentes'] ? explode('||', $row['nomes_utentes']) : [];
                                            echo $nomes ? h(implode(', ', $nomes)) : '—';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
