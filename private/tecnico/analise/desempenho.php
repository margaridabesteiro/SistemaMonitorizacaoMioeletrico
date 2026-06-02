<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Análise de Desempenho'; $pagina_ativa = 'analise';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>

        <main class="content">
            <?php
            $db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
            $stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
            $pacientes = $pid ? $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.tecnico_id=? ORDER BY u.nome") : null;
            if ($pacientes) { $pacientes->execute([$pid]); $pacientes = $pacientes->fetchAll(); } else { $pacientes = []; }
            $sel = (int)($_GET['utente_id'] ?? ($pacientes[0]['id'] ?? 0));
            $dados_emg = [];
            if ($sel) {
                try {
                    $st2 = $db->prepare("SELECT s.data_hora, m.rms_uv, m.precisao_pct FROM metricas_sessao m JOIN sessoes s ON s.id=m.sessao_id WHERE s.utente_id=? ORDER BY s.data_hora LIMIT 20");
                    $st2->execute([$sel]); $dados_emg = $st2->fetchAll();
                } catch (\Throwable $e) { $dados_emg = []; }
            }
            ?>
            <h1 class="mb-4">Análise de Desempenho</h1>
            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-4"><label class="form-label">Paciente</label>
                    <select name="utente_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach($pacientes as $p): ?><option value="<?= $p['id'] ?>" <?= $p['id']===$sel?'selected':'' ?>><?= h($p['nome']) ?></option><?php endforeach; ?>
                    </select></div>
            </form>
            <?php if (!empty($dados_emg)): ?>
            <div class="card p-3 mb-4"><canvas id="chartRMS" height="80"></canvas></div>
            <div class="card p-3">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Data</th><th>RMS (µV)</th><th>Precisão</th></tr></thead>
                    <tbody><?php foreach($dados_emg as $d): ?><tr><td><?= h(substr($d['data_hora'],0,10)) ?></td><td><?= $d['rms_uv']?number_format((float)$d['rms_uv'],2):'—' ?></td><td><?= $d['precisao_pct']?number_format((float)$d['precisao_pct'],1).'%':'—' ?></td></tr><?php endforeach; ?></tbody>
                </table>
            </div>
            <script>
            const labels = <?= json_encode(array_map(fn($d)=>substr($d['data_hora'],0,10), $dados_emg)) ?>;
            const rms    = <?= json_encode(array_map(fn($d)=>$d['rms_uv']?round((float)$d['rms_uv'],2):null, $dados_emg)) ?>;
            new Chart(document.getElementById('chartRMS'),{type:'line',data:{labels,datasets:[{label:'RMS (µV)',data:rms,borderColor:'#1a5f8a',tension:0.4,fill:false}]},options:{responsive:true}});
            </script>
            <?php else: ?><p class="text-muted">Sem dados EMG para este paciente.</p><?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
