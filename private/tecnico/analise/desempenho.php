<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Análise de Desempenho'; $pagina_ativa = 'analise';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$st  = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$st->execute([$uid]); $pid = (int)($st->fetchColumn() ?: 0);

$pacientes = [];
if ($pid) {
    $sp = $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.tecnico_id=? ORDER BY u.nome");
    $sp->execute([$pid]); $pacientes = $sp->fetchAll();
}

$sel   = (int)($_GET['utente_id'] ?? ($pacientes[0]['id'] ?? 0));
$dados = [];
if ($sel) {
    $sd = $db->prepare("
        SELECT s.data_hora, j.nome AS jogo, m.percentagem_final, m.score_jogo, m.passou_nivel, m.tendencia
        FROM metricas_sessao m
        JOIN sessoes s ON s.id = m.sessao_id
        LEFT JOIN jogos j ON j.id = s.jogo_id
        WHERE s.utente_id = ? AND m.percentagem_final IS NOT NULL
        ORDER BY s.data_hora ASC
        LIMIT 30
    ");
    $sd->execute([$sel]); $dados = $sd->fetchAll();
}
?>
        <main class="content">
            <h1 class="mb-4">Análise de Desempenho</h1>

            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Paciente</label>
                    <select name="utente_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($pacientes as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['id'] === $sel ? 'selected' : '' ?>><?= h($p['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if (!empty($dados)): ?>

            <div class="card p-3 mb-4">
                <canvas id="chartDesempenho" height="80"></canvas>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;">
                            <tr><th>Data</th><th>Jogo</th><th>Precisão</th><th>Score</th><th>Passou</th><th>Tendência</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dados as $d): ?>
                            <tr>
                                <td><?= h(substr($d['data_hora'], 0, 10)) ?></td>
                                <td><?= h($d['jogo'] ?? '—') ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px;">
                                            <div class="progress-bar" style="width:<?= (float)$d['percentagem_final'] ?>%;background:linear-gradient(90deg,#667eea,#764ba2);"></div>
                                        </div>
                                        <span class="fw-bold"><?= number_format((float)$d['percentagem_final'], 1) ?>%</span>
                                    </div>
                                </td>
                                <td><?= $d['score_jogo'] ?? '—' ?></td>
                                <td>
                                    <?php if ($d['passou_nivel']): ?>
                                        <span class="badge bg-success">✔ Sim</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">✘ Não</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $tend = ['melhoria' => '<i class="fa-solid fa-arrow-trend-up text-success"></i> Melhoria',
                                             'estavel'  => '<i class="fa-solid fa-minus text-secondary"></i> Estável',
                                             'regressao'=> '<i class="fa-solid fa-arrow-trend-down text-danger"></i> Regressão'];
                                    echo $tend[$d['tendencia']] ?? '—';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
            new Chart(document.getElementById('chartDesempenho'), {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_map(fn($d) => substr($d['data_hora'], 0, 10), $dados)) ?>,
                    datasets: [{
                        label: 'Precisão (%)',
                        data: <?= json_encode(array_map(fn($d) => round((float)$d['percentagem_final'], 1), $dados)) ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102,126,234,0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#764ba2',
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: { min: 0, max: 100, title: { display: true, text: 'Precisão (%)' } }
                    }
                }
            });
            </script>

            <?php elseif ($sel): ?>
                <div class="alert alert-info">Sem dados de desempenho para este paciente.</div>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
