<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Auditoria de Exames'; $pagina_ativa = 'auditoria';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
$prescricoes = $pid ? $db->prepare("SELECT p.id, p.tipo, p.data_prescricao, p.ativa, u.nome AS paciente FROM prescricoes p JOIN utentes ut ON ut.id=p.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE p.medico_id=? ORDER BY p.data_prescricao DESC LIMIT 50") : null;
if ($prescricoes) { $prescricoes->execute([$pid]); $prescricoes = $prescricoes->fetchAll(); } else { $prescricoes = []; }
?>
        <main class="content">
            <h1 class="mb-4">Auditoria de Exames / Prescrições</h1>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Paciente</th><th>Tipo</th><th>Data</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php if(empty($prescricoes)): ?><tr><td colspan="5" class="text-center text-muted py-4">Sem dados.</td></tr>
                    <?php else: foreach($prescricoes as $p): ?>
                        <tr><td><?= $p['id'] ?></td><td><?= h($p['paciente']) ?></td><td><?= h($p['tipo']) ?></td><td><?= h($p['data_prescricao']) ?></td><td><?= $p['ativa']?'<span class="badge bg-success">Ativa</span>':'<span class="badge bg-secondary">Inativa</span>' ?></td></tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
