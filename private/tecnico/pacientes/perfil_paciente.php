<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Perfil Paciente'; $pagina_ativa = 'pacientes';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/tecnico/pacientes/lista_pacientes.php');
$stmt = $db->prepare("SELECT ut.*, u.nome, u.email FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.id=?");
$stmt->execute([$id]); $pac = $stmt->fetch();
if (!$pac) redirect(APP_URL . '/private/tecnico/pacientes/lista_pacientes.php');
$n_sessoes = (int)$db->query("SELECT COUNT(*) FROM sessoes WHERE utente_id=$id AND estado='concluida'")->fetchColumn();
$metricas = $db->query("SELECT AVG(m.rms_uv), AVG(m.precisao_pct) FROM metricas_sessao m JOIN sessoes s ON s.id=m.sessao_id WHERE s.utente_id=$id")->fetch(PDO::FETCH_NUM);
$ultimas = $db->prepare("SELECT s.data_hora, s.tipo, s.duracao_min, s.estado FROM sessoes s WHERE s.utente_id=? ORDER BY s.data_hora DESC LIMIT 5");
$ultimas->execute([$id]); $ultimas = $ultimas->fetchAll();
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:70px;height:70px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:2rem;"><i class="fa-regular fa-user" style="color:#1a5f8a;"></i></div>
                    <div><h1 class="mb-0"><?= h($pac['nome']) ?></h1><p class="text-muted">Paciente · ID <?= $id ?></p></div>
                </div>
                <div class="d-flex gap-2">
                    <a href="../sessoes/nova_sessao.php?utente_id=<?= $id ?>" class="btn btn-sm" style="background:#1a5f8a;color:#fff;"><i class="fa-regular fa-calendar-plus me-1"></i>Nova Sessão</a>
                    <a href="historico_paciente.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-clock-rotate-left me-1"></i>Histórico</a>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card p-3"><h5>Informação Clínica</h5><hr>
                    <p><strong>Diagnóstico:</strong> <?= h($pac['diagnostico'] ?? '—') ?></p>
                    <p><strong>Email:</strong> <?= h($pac['email']) ?></p>
                    <p><strong>Observações:</strong> <?= h($pac['observacoes'] ?? '—') ?></p>
                </div></div>
                <div class="col-md-4"><div class="card p-3 text-center"><div class="fs-2 fw-bold text-success"><?= $n_sessoes ?></div><div class="text-muted">Sessões Concluídas</div></div></div>
                <div class="col-md-4"><div class="card p-3 text-center"><div class="fs-2 fw-bold text-primary"><?= $metricas[0] ? number_format((float)$metricas[0],1) . ' µV' : '—' ?></div><div class="text-muted">RMS Médio</div></div></div>
            </div>
            <div class="card p-3">
                <h5>Últimas Sessões</h5>
                <table class="table table-sm mt-2"><thead><tr><th>Data</th><th>Tipo</th><th>Duração</th><th>Estado</th></tr></thead><tbody>
                <?php foreach($ultimas as $s): ?><tr><td><?= h(substr($s['data_hora'],0,16)) ?></td><td><?= h($s['tipo']??'—') ?></td><td><?= $s['duracao_min']?h($s['duracao_min']).' min':'—' ?></td><td><span class="badge bg-secondary"><?= h($s['estado']) ?></span></td></tr><?php endforeach; ?>
                <?php if(empty($ultimas)): ?><tr><td colspan="4" class="text-muted">Sem sessões.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
