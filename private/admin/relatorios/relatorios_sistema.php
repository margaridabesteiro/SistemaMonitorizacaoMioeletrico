<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Relatórios do Sistema'; $pagina_ativa = 'relatorios';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
$periodo = (int)($_GET['periodo'] ?? 30);
$sessoes_periodo = (int)$db->query("SELECT COUNT(*) FROM sessoes WHERE data_hora >= DATE_SUB(NOW(), INTERVAL $periodo DAY)")->fetchColumn();
$utentes_novos = (int)$db->query("SELECT COUNT(*) FROM utilizadores WHERE perfil='utente' AND criado_em >= DATE_SUB(NOW(), INTERVAL $periodo DAY)")->fetchColumn();
$fat_periodo = $db->query("SELECT COALESCE(SUM(valor_eur),0) FROM faturas WHERE data_emissao >= DATE_SUB(NOW(), INTERVAL $periodo DAY)")->fetchColumn();
$sessoes_por_dia = $db->query("SELECT DATE(data_hora) as dia, COUNT(*) as total FROM sessoes WHERE data_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY dia ORDER BY dia")->fetchAll();
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Relatórios do Sistema</h1>
                <form method="GET" class="d-flex gap-2">
                    <select name="periodo" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="7" <?= $periodo==7?'selected':'' ?>>7 dias</option>
                        <option value="30" <?= $periodo==30?'selected':'' ?>>30 dias</option>
                        <option value="90" <?= $periodo==90?'selected':'' ?>>90 dias</option>
                        <option value="365" <?= $periodo==365?'selected':'' ?>>1 ano</option>
                    </select>
                </form>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-primary"><?= $sessoes_periodo ?></div><div class="text-muted small">Sessões (<?= $periodo ?>d)</div></div></div>
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success"><?= $utentes_novos ?></div><div class="text-muted small">Novos Utentes</div></div></div>
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-danger"><?= number_format((float)$fat_periodo,2,',','.') ?>€</div><div class="text-muted small">Faturação</div></div></div>
            </div>
            <div class="card p-3">
                <h5 class="mb-3">Sessões por Dia (últimos 30 dias)</h5>
                <canvas id="chartSessoes" height="80"></canvas>
            </div>
        </main>
        <script>
        const labels = <?= json_encode(array_column($sessoes_por_dia,'dia')) ?>;
        const data   = <?= json_encode(array_column($sessoes_por_dia,'total')) ?>;
        new Chart(document.getElementById('chartSessoes'), {
            type:'bar', data:{labels, datasets:[{label:'Sessões', data, backgroundColor:'rgba(139,0,0,0.7)'}]},
            options:{responsive:true, plugins:{legend:{display:false}}}
        });
        </script>
<?php require_once __DIR__ . "/../../../includes/footer.php"; ?>
