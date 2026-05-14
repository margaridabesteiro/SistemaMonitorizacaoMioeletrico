<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Gerar Relatório'; $pagina_ativa = 'relatorios';
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
            $rel = null;
            if ($sel) {
                $stmt2 = $db->prepare("SELECT u.nome, ut.diagnostico, COUNT(s.id) as n_sessoes, AVG(m.rms_uv) as rms, AVG(m.precisao_pct) as prec FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id LEFT JOIN sessoes s ON s.utente_id=ut.id AND s.estado='concluida' LEFT JOIN metricas_sessao m ON m.sessao_id=s.id WHERE ut.id=? GROUP BY ut.id");
                $stmt2->execute([$sel]); $rel = $stmt2->fetch();
            }
            ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Relatório Clínico</h1>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa-regular fa-file-pdf me-1"></i>PDF / Imprimir</button>
                </div>
            </div>
            <form method="GET" class="mb-4">
                <select name="utente_id" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                    <?php foreach($pacientes as $p): ?><option value="<?= $p['id'] ?>" <?= $p['id']===$sel?'selected':'' ?>><?= h($p['nome']) ?></option><?php endforeach; ?>
                </select>
            </form>
            <?php if ($rel): ?>
            <div class="card p-4" style="max-width:700px;">
                <div class="text-center mb-4"><i class="fa-solid fa-hand-holding-heart fa-3x" style="color:#1a5f8a;"></i><h2 style="color:#0f3b5e;">RehabLink</h2><p class="text-muted">Relatório Clínico — Fisioterapia · <?= date('d/m/Y') ?></p></div>
                <hr>
                <h4><?= h($rel['nome']) ?></h4>
                <p><strong>Diagnóstico:</strong> <?= h($rel['diagnostico'] ?? '—') ?></p>
                <p><strong>Sessões concluídas:</strong> <?= $rel['n_sessoes'] ?></p>
                <p><strong>RMS médio:</strong> <?= $rel['rms'] ? number_format((float)$rel['rms'],2).' µV' : '—' ?></p>
                <p><strong>Precisão média:</strong> <?= $rel['prec'] ? number_format((float)$rel['prec'],1).'%' : '—' ?></p>
                <p><strong>Relatório gerado em:</strong> <?= date('d/m/Y H:i') ?> por <?= h($_SESSION['nome']) ?></p>
            </div>
            <?php else: ?><p class="text-muted">Selecione um paciente.</p><?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
