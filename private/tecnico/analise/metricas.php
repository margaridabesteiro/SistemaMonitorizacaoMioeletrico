<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Métricas Avançadas'; $pagina_ativa = 'analise';
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
            $agg = null;
            if ($sel) { $sa = $db->prepare("SELECT COUNT(*) as n_sessoes, AVG(m.rms_uv) as rms, AVG(m.mav_uv) as mav, AVG(m.frequencia_hz) as freq, AVG(m.precisao_pct) as prec, AVG(m.score_jogo) as score FROM metricas_sessao m JOIN sessoes s ON s.id=m.sessao_id WHERE s.utente_id=?"); $sa->execute([$sel]); $agg = $sa->fetch(); }
            ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Métricas Avançadas</h1>
                <a href="<?= APP_URL ?>/api/sessoes/leituras.php?sessao_id=0" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download me-1"></i>Exportar</a>
            </div>
            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-4"><select name="utente_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach($pacientes as $p): ?><option value="<?= $p['id'] ?>" <?= $p['id']===$sel?'selected':'' ?>><?= h($p['nome']) ?></option><?php endforeach; ?>
                </select></div>
            </form>
            <?php if ($agg && $agg['n_sessoes'] > 0): ?>
            <div class="row g-3">
                <?php foreach([['Sessões Analisadas',$agg['n_sessoes'],'secondary',''],['RMS Médio',number_format((float)$agg['rms'],2),'primary',' µV'],['MAV Médio',number_format((float)$agg['mav'],2),'info',' µV'],['Precisão Média',number_format((float)$agg['prec'],1),'success','%'],['Score Médio',$agg['score']?number_format((float)$agg['score'],0):'—','warning',''],] as [$lbl,$val,$cor,$suf]): ?>
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-<?= $cor ?>"><?= $val ?><?= $suf ?></div><div class="text-muted small"><?= $lbl ?></div></div></div>
                <?php endforeach; ?>
            </div>
            <?php else: ?><p class="text-muted">Sem métricas calculadas para este paciente.</p><?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
