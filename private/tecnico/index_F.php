<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
$pagina_titulo = 'Painel Técnico'; $pagina_ativa = 'dashboard';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../includes/header_tecnico.php';
require_once __DIR__ . '/../../includes/sidebar_tecnico.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);
$n_pac = $pid ? (int)$db->prepare('SELECT COUNT(*) FROM utentes WHERE tecnico_id=?')->execute([$pid]) ? $db->query("SELECT COUNT(*) FROM utentes WHERE tecnico_id=$pid")->fetchColumn() : 0 : 0;
$stmt2 = $db->prepare('SELECT COUNT(*) FROM utentes WHERE tecnico_id=?'); $stmt2->execute([$pid]); $n_pac = (int)$stmt2->fetchColumn();
$stmt3 = $db->prepare("SELECT COUNT(*) FROM sessoes WHERE tecnico_id=? AND DATE(data_hora)=CURDATE() AND estado='agendada'"); $stmt3->execute([$pid]); $n_hoje = (int)$stmt3->fetchColumn();
$stmt4 = $db->prepare("SELECT COUNT(*) FROM sessoes WHERE tecnico_id=? AND estado='concluida'"); $stmt4->execute([$pid]); $n_concluidas = (int)$stmt4->fetchColumn();
$proximas = $pid ? $db->prepare("SELECT s.data_hora, s.tipo, s.estado, u.nome AS paciente FROM sessoes s JOIN utentes ut ON ut.id=s.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE s.tecnico_id=? AND s.data_hora>=NOW() AND s.estado='agendada' ORDER BY s.data_hora LIMIT 5") : null;
if ($proximas) { $proximas->execute([$pid]); $proximas = $proximas->fetchAll(); } else { $proximas = []; }
?>
        <main class="content">
            <div class="welcome-section mb-4" style="display:flex;justify-content:space-between;align-items:center;">
                <div><h2>Bem-vindo, <?= h($_SESSION['nome']) ?></h2><p><?= date('l, d \d\e F \d\e Y') ?> · <?= $n_hoje ?> sessões agendadas para hoje</p></div>
                <i class="fa-solid fa-hand-holding-heart fa-2x" style="color:#1a5f8a;"></i>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-primary"><?= $n_pac ?></div><div class="text-muted small">Pacientes Ativos</div></div></div>
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-warning"><?= $n_hoje ?></div><div class="text-muted small">Sessões Hoje</div></div></div>
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success"><?= $n_concluidas ?></div><div class="text-muted small">Sessões Concluídas</div></div></div>
            </div>
            <div class="card p-3 mb-4">
                <h5 class="mb-3">Próximas Sessões</h5>
                <?php if(empty($proximas)): ?><p class="text-muted">Sem sessões agendadas.</p>
                <?php else: ?>
                <table class="table table-sm table-hover"><thead><tr><th>Data/Hora</th><th>Paciente</th><th>Tipo</th></tr></thead><tbody>
                <?php foreach($proximas as $s): ?><tr><td><?= h(substr($s['data_hora'],0,16)) ?></td><td><?= h($s['paciente']) ?></td><td><?= h($s['tipo'] ?? '—') ?></td></tr><?php endforeach; ?>
                </tbody></table>
                <?php endif; ?>
                <a href="sessoes/lista_sessoes.php" class="btn btn-sm btn-outline-secondary mt-2">Ver todas</a>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
