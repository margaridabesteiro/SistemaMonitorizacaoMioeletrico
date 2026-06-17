<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Análise de Desempenho'; $pagina_ativa = 'progresso';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?");
$stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();

// KPIs
$n_total    = 0; $n_conc   = 0;
$media_esf  = null; $ultima_prog = null; $n_melhoria = 0; $n_regressao = 0;

if ($utid) {
    $n_total = (int)$db->prepare("SELECT COUNT(*) FROM sessoes WHERE utente_id=?")->execute([$utid]) ? (int)$db->query("SELECT COUNT(*) FROM sessoes WHERE utente_id=$utid")->fetchColumn() : 0;
    $n_conc  = (int)$db->query("SELECT COUNT(*) FROM sessoes WHERE utente_id=$utid AND estado='concluida'")->fetchColumn();
    try {
        $s = $db->prepare("SELECT AVG(esforco_score) FROM sessoes WHERE utente_id=? AND esforco_score IS NOT NULL AND estado='concluida'");
        $s->execute([$utid]); $media_esf = $s->fetchColumn();
        $s = $db->prepare("SELECT progressao FROM sessoes WHERE utente_id=? AND progressao IS NOT NULL AND estado='concluida' ORDER BY data_hora DESC LIMIT 1");
        $s->execute([$utid]); $ultima_prog = $s->fetchColumn() ?: null;
        $n_melhoria  = (int)$db->query("SELECT COUNT(*) FROM sessoes WHERE utente_id=$utid AND progressao='melhoria' AND estado='concluida'")->fetchColumn();
        $n_regressao = (int)$db->query("SELECT COUNT(*) FROM sessoes WHERE utente_id=$utid AND progressao='regressao' AND estado='concluida'")->fetchColumn();
    } catch (\Throwable $e) {}
}

// Histórico de sessões para gráfico de progressão (últimas 20)
$historico = [];
if ($utid) {
    try {
        $s = $db->prepare("
            SELECT DATE_FORMAT(data_hora,'%d/%m') AS dt,
                   progressao, esforco_score,
                   COALESCE(j.nome, categoria, 'Sessão') AS tipo
            FROM sessoes s
            LEFT JOIN jogos j ON j.id = s.jogo_id
            WHERE s.utente_id=? AND s.estado='concluida'
            ORDER BY s.data_hora DESC LIMIT 20
        ");
        $s->execute([$utid]);
        $historico = array_reverse($s->fetchAll());
    } catch (\Throwable $e) {}
}

// Evolução percentagem final nos jogos (últimas 15 sessões com métricas)
$evolucao_pct = [];
if ($utid) {
    try {
        $s = $db->prepare("
            SELECT DATE_FORMAT(s.data_hora,'%d/%m') AS dt,
                   ROUND(m.percentagem_final,1) AS pct,
                   COALESCE(j.nome,'Sessão') AS jogo
            FROM sessoes s
            JOIN metricas_sessao m ON m.sessao_id = s.id
            LEFT JOIN jogos j ON j.id = s.jogo_id
            WHERE s.utente_id=? AND s.estado='concluida' AND m.percentagem_final IS NOT NULL
            ORDER BY s.data_hora DESC LIMIT 15
        ");
        $s->execute([$utid]);
        $evolucao_pct = array_reverse($s->fetchAll());
    } catch (\Throwable $e) {}
}

// Análises globais de desempenho registadas pelo técnico
$analises_globais = [];
if ($utid) {
    try {
        $s = $db->prepare("
            SELECT ad.data_analise AS dt, ad.texto AS analise_tecnica,
                   ad.progressao_geral AS progressao, u.nome AS tecnico
            FROM analises_desempenho ad
            JOIN profissionais p ON p.id = ad.tecnico_id
            JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE ad.utente_id = ?
            ORDER BY ad.data_analise DESC, ad.criado_em DESC
            LIMIT 10
        ");
        $s->execute([$utid]); $analises_globais = $s->fetchAll();
    } catch (\Throwable $e) {}
}

// Notas de análise técnica por sessão
$analises = [];
if ($utid) {
    try {
        $s = $db->prepare("
            SELECT DATE_FORMAT(s.data_hora,'%d/%m/%Y') AS dt,
                   progressao, esforco_score, analise_tecnica,
                   COALESCE(j.nome, s.categoria, 'Sessão') AS tipo,
                   u.nome AS tecnico
            FROM sessoes s
            LEFT JOIN jogos j ON j.id = s.jogo_id
            LEFT JOIN profissionais p ON p.id = s.tecnico_id
            LEFT JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE s.utente_id=? AND s.estado='concluida' AND s.analise_tecnica IS NOT NULL
            ORDER BY s.data_hora DESC LIMIT 8
        ");
        $s->execute([$utid]);
        $analises = $s->fetchAll();
    } catch (\Throwable $e) {}
}

$prog_cor = ['melhoria'=>'#198754','estavel'=>'#6c757d','regressao'=>'#dc3545'];
$prog_label = ['melhoria'=>'Melhoria','estavel'=>'Estável','regressao'=>'Regressão'];
$prog_icon  = ['melhoria'=>'fa-arrow-trend-up','estavel'=>'fa-minus','regressao'=>'fa-arrow-trend-down'];

require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1"><i class="fa-solid fa-clipboard-list me-2" style="color:#667eea;"></i>Análise de Desempenho</h1>
                    <p class="text-muted mb-0">Avaliações e notas do seu técnico ao longo das sessões.</p>
                </div>
                <a href="<?= APP_URL ?>/private/utente/jogos_reabilitacao.php" class="btn btn-sm" style="background:#1a5f8a;color:#fff;">
                    <i class="fa-solid fa-gamepad me-1"></i>Ir para os Jogos
                </a>
            </div>

            <!-- Análises globais de desempenho do técnico -->
            <?php if (!empty($analises_globais)): ?>
            <div class="card mb-4 p-3" style="border-left:4px solid #1a5f8a;">
                <h5 class="mb-3"><i class="fa-solid fa-clipboard-list me-2" style="color:#1a5f8a;"></i>Análises de Desempenho do Técnico</h5>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($analises_globais as $a): ?>
                    <?php $p = $a['progressao'] ?? 'estavel'; ?>
                    <div class="p-3 rounded" style="background:#f8f9fa;border-left:3px solid <?= $prog_cor[$p] ?? '#6c757d' ?>;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge" style="background:<?= $prog_cor[$p] ?? '#6c757d' ?>;font-size:.75rem;">
                                    <i class="fa-solid <?= $prog_icon[$p] ?? 'fa-minus' ?> me-1"></i><?= $prog_label[$p] ?? '—' ?>
                                </span>
                                <small class="text-muted ms-2">
                                    <?= h(is_string($a['dt']) && strlen($a['dt']) === 10
                                        ? date('d/m/Y', strtotime($a['dt']))
                                        : $a['dt']) ?>
                                </small>
                            </div>
                            <small class="text-muted"><?= h($a['tecnico'] ?? '—') ?></small>
                        </div>
                        <p class="mb-0 small" style="white-space:pre-wrap;"><?= h($a['analise_tecnica']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notas por sessão -->
            <?php if (!empty($analises)): ?>
            <div class="card mb-4 p-3">
                <h5 class="mb-3"><i class="fa-solid fa-stethoscope me-2" style="color:#1a5f8a;"></i>Notas por Sessão</h5>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($analises as $a): ?>
                    <?php $p = $a['progressao'] ?? 'estavel'; ?>
                    <div class="d-flex gap-3 p-3 rounded" style="background:#f8f9fa;border-left:3px solid <?= $prog_cor[$p] ?? '#6c757d' ?>;">
                        <div style="flex-shrink:0;min-width:90px;">
                            <div class="small text-muted"><?= $a['dt'] ?></div>
                            <span class="badge" style="background:<?= $prog_cor[$p] ?? '#6c757d' ?>;font-size:.7rem;">
                                <i class="fa-solid <?= $prog_icon[$p] ?? 'fa-minus' ?> me-1"></i><?= $prog_label[$p] ?? '—' ?>
                            </span>
                            <?php if ($a['esforco_score']): ?>
                            <div class="mt-1" style="color:#ffc107;font-size:.85rem;">
                                <?= str_repeat('★', (int)$a['esforco_score']) ?><?= str_repeat('☆', 5-(int)$a['esforco_score']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted mb-1"><?= h($a['tipo']) ?> · <?= h($a['tecnico'] ?? '—') ?></div>
                            <p class="mb-0 small"><?= nl2br(h($a['analise_tecnica'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($analises_globais) && empty($analises)): ?>
            <div class="alert alert-light text-center mb-4">
                <i class="fa-solid fa-clipboard fa-2x mb-2 d-block opacity-25"></i>
                O seu técnico ainda não registou análises de desempenho.
            </div>
            <?php endif; ?>

            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card text-center p-3">
                        <div class="fw-bold mb-1" style="font-size:2rem;color:#667eea;"><?= $n_conc ?></div>
                        <div class="text-muted small">Sessões Concluídas</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center p-3">
                        <div class="fw-bold mb-1" style="font-size:2rem;color:#198754;"><?= $n_melhoria ?></div>
                        <div class="text-muted small">Sessões com Melhoria</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center p-3">
                        <?php
                        $esf_str = $media_esf !== null && $media_esf !== false
                            ? number_format((float)$media_esf, 1) . ' / 5'
                            : '—';
                        ?>
                        <div class="fw-bold mb-1" style="font-size:2rem;color:#ffc107;"><?= $esf_str ?></div>
                        <div class="text-muted small">Esforço Médio</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center p-3">
                        <?php if ($ultima_prog): ?>
                        <div class="fw-bold mb-1" style="font-size:2rem;color:<?= $prog_cor[$ultima_prog] ?? '#6c757d' ?>;">
                            <i class="fa-solid <?= $prog_icon[$ultima_prog] ?? 'fa-minus' ?>"></i>
                        </div>
                        <div class="text-muted small">Última Progressão: <?= $prog_label[$ultima_prog] ?></div>
                        <?php else: ?>
                        <div class="fw-bold mb-1" style="font-size:2rem;color:#6c757d;">—</div>
                        <div class="text-muted small">Última Progressão</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Gráfico de desempenho nos jogos -->
                <?php if (!empty($evolucao_pct)): ?>
                <div class="col-md-7">
                    <div class="card p-3 h-100">
                        <h5 class="mb-3"><i class="fa-solid fa-gamepad me-2" style="color:#667eea;"></i>Evolução nos Jogos</h5>
                        <canvas id="chartPct" style="max-height:250px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Gráfico de esforço por sessão -->
                <?php if (!empty($historico) && count(array_filter(array_column($historico,'esforco_score'))) > 0): ?>
                <div class="col-md-<?= !empty($evolucao_pct) ? '5' : '12' ?>">
                    <div class="card p-3 h-100">
                        <h5 class="mb-3"><i class="fa-solid fa-fire me-2" style="color:#ffc107;"></i>Esforço por Sessão</h5>
                        <canvas id="chartEsforco" style="max-height:250px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Progressão por sessão (últimas 20) -->
            <?php if (!empty($historico)): ?>
            <div class="card mt-4 p-3">
                <h5 class="mb-3"><i class="fa-solid fa-list-check me-2" style="color:#667eea;"></i>Histórico de Progressão</h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($historico as $h): ?>
                    <?php $p = $h['progressao'] ?? 'estavel'; ?>
                    <div class="d-flex align-items-center gap-1 px-2 py-1 rounded"
                         style="background:<?= $prog_cor[$p] ?? '#6c757d' ?>18;border:1px solid <?= $prog_cor[$p] ?? '#6c757d' ?>40;">
                        <i class="fa-solid <?= $prog_icon[$p] ?? 'fa-minus' ?>" style="color:<?= $prog_cor[$p] ?? '#6c757d' ?>;font-size:.75rem;"></i>
                        <span style="font-size:.75rem;font-weight:600;"><?= $h['dt'] ?></span>
                        <?php if ($h['esforco_score']): ?>
                        <span style="font-size:.7rem;color:#ffc107;">
                            <?= str_repeat('★', (int)$h['esforco_score']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($historico) && empty($evolucao_pct) && empty($analises) && empty($analises_globais)): ?>
            <div class="alert alert-light text-center mt-4">
                <i class="fa-solid fa-chart-line fa-2x mb-2 d-block" style="color:#667eea;opacity:.4;"></i>
                Ainda não tem sessões concluídas. Os dados de desempenho aparecerão aqui após a primeira sessão.
            </div>
            <?php endif; ?>
        </main>

<?php
// Chart.js data
$lbl_pct  = json_encode(array_column($evolucao_pct, 'dt'));
$data_pct = json_encode(array_map('floatval', array_column($evolucao_pct, 'pct')));
$lbl_esf  = json_encode(array_column($historico, 'dt'));
$data_esf = json_encode(array_map(fn($h) => $h['esforco_score'] !== null ? (int)$h['esforco_score'] : null, $historico));
?>
<script>
<?php if (!empty($evolucao_pct)): ?>
new Chart(document.getElementById('chartPct'), {
    type: 'line',
    data: {
        labels: <?= $lbl_pct ?>,
        datasets: [{
            label: '% Final',
            data: <?= $data_pct ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,.1)',
            tension: 0.3, pointRadius: 5, fill: true,
        }]
    },
    options: {
        responsive: true,
        scales: { y: { min:0, max:100, title:{display:true,text:'%'} } },
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>

<?php if (!empty($historico) && count(array_filter(array_column($historico,'esforco_score'))) > 0): ?>
new Chart(document.getElementById('chartEsforco'), {
    type: 'bar',
    data: {
        labels: <?= $lbl_esf ?>,
        datasets: [{
            label: 'Esforço (1-5)',
            data: <?= $data_esf ?>,
            backgroundColor: 'rgba(255,193,7,.7)',
            borderColor: '#ffc107',
            borderWidth: 1,
        }]
    },
    options: {
        responsive: true,
        scales: { y: { min:0, max:5, ticks:{stepSize:1} } },
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
