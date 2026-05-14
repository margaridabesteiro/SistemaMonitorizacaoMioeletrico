<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Histórico de Sessões'; $pagina_ativa = 'historico';
require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?"); $stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();
$stats = [0,0,0,0];
if ($utid) {
    $r = $db->query("SELECT COUNT(*), SUM(estado='concluida'), SUM(estado='cancelada'), COALESCE(SUM(duracao_min),0) FROM sessoes WHERE utente_id=$utid")->fetch(PDO::FETCH_NUM);
    $stats = $r;
}
$pagina_atual = max(1,(int)($_GET['pagina'] ?? 1)); $por_pagina = 20; $offset = ($pagina_atual-1)*$por_pagina;
$sessoes = [];
if ($utid) {
    $stmt2 = $db->prepare("SELECT s.*, u.nome AS tecnico, m.rms_uv, m.precisao_pct, m.score_jogo FROM sessoes s LEFT JOIN profissionais p ON p.id=s.tecnico_id LEFT JOIN utilizadores u ON u.id=p.utilizador_id LEFT JOIN metricas_sessao m ON m.sessao_id=s.id WHERE s.utente_id=? ORDER BY s.data_hora DESC LIMIT $por_pagina OFFSET $offset");
    $stmt2->execute([$utid]); $sessoes = $stmt2->fetchAll();
}
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Histórico de Sessões</h1>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-download me-1"></i>Exportar</button>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold"><?= (int)$stats[0] ?></div><div class="text-muted small">Total</div></div></div>
                <div class="col-6 col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success"><?= (int)$stats[1] ?></div><div class="text-muted small">Realizadas</div></div></div>
                <div class="col-6 col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-danger"><?= (int)$stats[2] ?></div><div class="text-muted small">Canceladas</div></div></div>
                <div class="col-6 col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-primary"><?= (int)$stats[3] ?> min</div><div class="text-muted small">Tempo Total</div></div></div>
            </div>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Tipo</th><th>Técnico</th><th>Duração</th><th>Estado</th><th>RMS</th><th>Score</th></tr></thead>
                    <tbody>
                    <?php if (empty($sessoes)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Sem sessões.</td></tr>
                    <?php else: foreach ($sessoes as $s): ?>
                        <tr>
                            <td><?= h(substr($s['data_hora'],0,10)) ?></td>
                            <td><?= h($s['tipo'] ?? '—') ?></td>
                            <td><?= h($s['tecnico'] ?? '—') ?></td>
                            <td><?= $s['duracao_min'] ? h($s['duracao_min']).' min' : '—' ?></td>
                            <td><span class="badge bg-<?= ['concluida'=>'success','cancelada'=>'danger','agendada'=>'warning text-dark','em_curso'=>'primary'][$s['estado']] ?? 'secondary' ?>"><?= h($s['estado']) ?></span></td>
                            <td><?= $s['rms_uv'] ? number_format((float)$s['rms_uv'],1).' µV' : '—' ?></td>
                            <td><?= $s['score_jogo'] ?? '—' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
