<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Detalhes Sessão'; $pagina_ativa = 'sessoes';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
$stmt = $db->prepare("SELECT s.*, u.nome AS paciente, d.codigo AS dispositivo FROM sessoes s JOIN utentes ut ON ut.id=s.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id LEFT JOIN dispositivos d ON d.id=s.dispositivo_id WHERE s.id=?");
$stmt->execute([$id]); $s = $stmt->fetch();
if (!$s) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
$metricas = $db->prepare("SELECT * FROM metricas_sessao WHERE sessao_id=?"); $metricas->execute([$id]); $m = $metricas->fetch();
$n_leituras = (int)$db->query("SELECT COUNT(*) FROM leituras_emg WHERE sessao_id=$id")->fetchColumn();
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="lista_sessoes.php">Sessões</a></li><li class="breadcrumb-item active">Sessão #<?= $id ?></li></ol></nav>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Detalhes da Sessão #<?= $id ?></h1>
                <div class="d-flex gap-2">
                    <?php if ($s['estado']==='agendada'): ?><a href="iniciar_sessao.php?id=<?= $id ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-play me-1"></i>Iniciar</a><?php endif; ?>
                    <a href="editar_sessao.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-regular fa-pen-to-square me-1"></i>Editar</a>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="card p-3">
                    <p><strong>Paciente:</strong> <?= h($s['paciente']) ?></p>
                    <p><strong>Tipo:</strong> <?= h($s['tipo'] ?? '—') ?></p>
                    <p><strong>Data/Hora:</strong> <?= h(substr($s['data_hora'],0,16)) ?></p>
                    <p><strong>Duração:</strong> <?= $s['duracao_min'] ? h($s['duracao_min']).' min' : '—' ?></p>
                    <p><strong>Dispositivo:</strong> <?= h($s['dispositivo'] ?? '—') ?></p>
                    <p><strong>Estado:</strong> <span class="badge bg-secondary"><?= h($s['estado']) ?></span></p>
                    <?php if ($s['notas']): ?><p><strong>Notas:</strong> <?= h($s['notas']) ?></p><?php endif; ?>
                </div></div>
                <?php if ($m): ?>
                <div class="col-md-6"><div class="card p-3 text-center">
                    <h5>Métricas EMG</h5><hr>
                    <div class="row">
                        <div class="col-6"><div class="fw-bold fs-4"><?= $m['rms_uv'] ? number_format((float)$m['rms_uv'],2) : '—' ?></div><small>RMS (µV)</small></div>
                        <div class="col-6"><div class="fw-bold fs-4"><?= $m['precisao_pct'] ? number_format((float)$m['precisao_pct'],1).'%' : '—' ?></div><small>Precisão</small></div>
                    </div><hr>
                    <p class="text-muted small"><?= $n_leituras ?> leituras EMG registadas</p>
                </div></div>
                <?php endif; ?>
            </div>
            <a href="lista_sessoes.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
