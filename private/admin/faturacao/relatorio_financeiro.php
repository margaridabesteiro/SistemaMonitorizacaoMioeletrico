<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Relatório Financeiro'; $pagina_ativa = 'relatorio_fin';
$db = getDB();

$ano_atual = (int)($_GET['ano'] ?? date('Y'));
$anos_db = $db->query("SELECT DISTINCT YEAR(data_emissao) AS ano FROM faturas")->fetchAll(PDO::FETCH_COLUMN);
$anos = array_values(array_unique(array_merge([2026, 2025, 2024], array_map('intval', $anos_db))));
rsort($anos);

// 1. Receita mensal (pagas)
$mensal = $db->prepare("
    SELECT MONTH(data_emissao) AS mes, COALESCE(SUM(valor_eur),0) AS total
    FROM faturas
    WHERE paga=1 AND YEAR(data_emissao)=?
    GROUP BY mes ORDER BY mes
");
$mensal->execute([$ano_atual]);
$mensal_data = array_fill(1, 12, 0.0);
foreach ($mensal->fetchAll() as $row) { $mensal_data[(int)$row['mes']] = (float)$row['total']; }

// 2. Por seguradora
$por_seg = $db->prepare("
    SELECT COALESCE(s.nome,'Sem seguradora') AS nome, COALESCE(SUM(f.valor_eur),0) AS total
    FROM faturas f
    LEFT JOIN seguradoras s ON s.id = f.seguradora_id
    WHERE f.paga=1 AND YEAR(f.data_emissao)=?
    GROUP BY f.seguradora_id, s.nome ORDER BY total DESC
");
$por_seg->execute([$ano_atual]); $segs = $por_seg->fetchAll();

// 3. Por tipo de serviço
$TIPOS_LABEL = [
    'videoconsulta'       => 'Videoconsulta',
    'relatorio_clinico'   => 'Relatório Clínico',
    'sessao_jogo'         => 'Sessão por Jogo de Reabilitação',
    'consulta_medica'     => 'Consulta Médica',
    'avaliacao_funcional' => 'Avaliação Funcional',
];
$por_tipo = $db->prepare("
    SELECT COALESCE(tipo_servico,'—') AS tipo, COALESCE(SUM(valor_eur),0) AS total
    FROM faturas WHERE paga=1 AND YEAR(data_emissao)=?
    GROUP BY tipo_servico ORDER BY total DESC
");
$por_tipo->execute([$ano_atual]); $tipos = $por_tipo->fetchAll();

// 4. Por método de pagamento
$por_metodo = $db->prepare("
    SELECT COALESCE(metodo_pagamento,'não registado') AS metodo, COUNT(*) AS n, COALESCE(SUM(valor_eur),0) AS total
    FROM faturas WHERE paga=1 AND YEAR(data_emissao)=?
    GROUP BY metodo_pagamento ORDER BY total DESC
");
$por_metodo->execute([$ano_atual]); $metodos = $por_metodo->fetchAll();

// 5. Totais globais
$totais = $db->prepare("
    SELECT COALESCE(SUM(CASE WHEN paga=1 THEN valor_eur END),0) AS recebido,
           COALESCE(SUM(CASE WHEN paga=0 THEN valor_eur END),0) AS pendente,
           COUNT(*) AS total_faturas,
           COUNT(CASE WHEN paga=1 THEN 1 END) AS pagas,
           COUNT(CASE WHEN paga=0 THEN 1 END) AS nao_pagas
    FROM faturas WHERE YEAR(data_emissao)=?
");
$totais->execute([$ano_atual]); $kpi = $totais->fetch();

$metodo_labels = [
    'multibanco'    => 'Multibanco',
    'cartão'        => 'Cartão',
    'seguro'        => 'Seguro',
    'numerário'     => 'Numerário',
    'transferência' => 'Transferência',
    'não registado' => 'Não registado',
];

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Relatório Financeiro</h1>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <label class="mb-0 me-1 fw-semibold">Ano:</label>
                    <select name="ano" class="form-select fw-bold w-auto" style="font-size:1.1rem;min-width:90px;" onchange="this.form.submit()">
                        <?php foreach ($anos as $a): ?>
                            <option value="<?= $a ?>" <?= $a==$ano_atual?'selected':'' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <div class="fs-2 fw-bold text-success"><?= number_format((float)$kpi['recebido'],2,',','.') ?>€</div>
                        <div class="text-muted small">Receita Recebida <?= $ano_atual ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <div class="fs-2 fw-bold text-warning"><?= number_format((float)$kpi['pendente'],2,',','.') ?>€</div>
                        <div class="text-muted small">Pendente</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <div class="fs-2 fw-bold"><?= $kpi['pagas'] ?></div>
                        <div class="text-muted small">Faturas Pagas</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <div class="fs-2 fw-bold text-danger"><?= $kpi['nao_pagas'] ?></div>
                        <div class="text-muted small">Faturas Pendentes</div>
                    </div>
                </div>
            </div>

            <!-- Linha: tendência mensal -->
            <div class="card p-4 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-chart-line me-2" style="color:#8B0000;"></i>Receita Mensal <?= $ano_atual ?></h5>
                <canvas id="chartMensal" height="90"></canvas>
            </div>

            <!-- 3 colunas: seguradora | tipo serviço | método -->
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <h6 class="mb-3"><i class="fa-solid fa-shield-heart me-2" style="color:#8B0000;"></i>Por Seguradora</h6>
                        <canvas id="chartSeg"></canvas>
                        <div class="mt-3">
                            <?php foreach ($segs as $s): ?>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span><?= h($s['nome']) ?></span>
                                <strong><?= number_format((float)$s['total'],2,',','.') ?>€</strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <h6 class="mb-3"><i class="fa-solid fa-stethoscope me-2" style="color:#8B0000;"></i>Por Tipo de Serviço</h6>
                        <canvas id="chartTipo"></canvas>
                        <div class="mt-3">
                            <?php foreach ($tipos as $t): ?>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span><?= h($TIPOS_LABEL[$t['tipo']] ?? $t['tipo']) ?></span>
                                <strong><?= number_format((float)$t['total'],2,',','.') ?>€</strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <h6 class="mb-3"><i class="fa-solid fa-credit-card me-2" style="color:#8B0000;"></i>Método de Pagamento</h6>
                        <canvas id="chartMetodo"></canvas>
                        <div class="mt-3">
                            <?php foreach ($metodos as $m): ?>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span><?= h($metodo_labels[$m['metodo']] ?? $m['metodo']) ?></span>
                                <span><?= $m['n'] ?> fat. / <strong><?= number_format((float)$m['total'],2,',','.') ?>€</strong></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const mensal = <?= json_encode(array_values($mensal_data)) ?>;
const segLabels = <?= json_encode(array_column($segs,'nome')) ?>;
const segVals   = <?= json_encode(array_map(fn($r)=>(float)$r['total'], $segs)) ?>;
const tipoLabels = <?= json_encode(array_map(fn($t)=>($TIPOS_LABEL[$t['tipo']]??$t['tipo']), $tipos)) ?>;
const tipoVals   = <?= json_encode(array_map(fn($r)=>(float)$r['total'], $tipos)) ?>;
const metLabels  = <?= json_encode(array_map(fn($m)=>($metodo_labels[$m['metodo']]??$m['metodo']), $metodos)) ?>;
const metVals    = <?= json_encode(array_map(fn($r)=>(float)$r['total'], $metodos)) ?>;

const PALETA = ['#8B0000','#c0392b','#e74c3c','#f39c12','#27ae60','#2980b9','#8e44ad','#16a085'];

new Chart(document.getElementById('chartMensal'), {
    type: 'bar',
    data: {
        labels: meses,
        datasets: [{
            label: 'Receita (€)',
            data: mensal,
            backgroundColor: '#8B0000',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('pt-PT',{style:'currency',currency:'EUR'}) } } }
    }
});

new Chart(document.getElementById('chartSeg'), {
    type: 'doughnut',
    data: { labels: segLabels, datasets: [{ data: segVals, backgroundColor: PALETA }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font:{size:11} } } } }
});

new Chart(document.getElementById('chartTipo'), {
    type: 'bar',
    data: {
        labels: tipoLabels,
        datasets: [{ label:'€', data: tipoVals, backgroundColor: '#c0392b', borderRadius: 4 }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});

new Chart(document.getElementById('chartMetodo'), {
    type: 'doughnut',
    data: { labels: metLabels, datasets: [{ data: metVals, backgroundColor: PALETA }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font:{size:11} } } } }
});
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
