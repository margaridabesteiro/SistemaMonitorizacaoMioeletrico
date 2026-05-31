<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Auditoria'; $pagina_ativa = 'auditoria';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
$programas = [];
if ($pid) {
    $s = $db->prepare("SELECT p.id, p.data_prescricao, p.ativa, p.num_sessoes_prescritas, u.nome AS paciente
        FROM programas_tratamento p
        JOIN utentes ut ON ut.id=p.utente_id
        JOIN utilizadores u ON u.id=ut.utilizador_id
        WHERE p.medico_id=? ORDER BY p.data_prescricao DESC LIMIT 50");
    $s->execute([$pid]); $programas = $s->fetchAll();
}
?>
        <main class="content">
            <h1 class="mb-4">Auditoria de Programas de Tratamento</h1>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Paciente</th><th>Sessões Prescritas</th><th>Data Início</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php if(empty($programas)): ?><tr><td colspan="5" class="text-center text-muted py-4">Sem dados.</td></tr>
                    <?php else: foreach($programas as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= h($p['paciente']) ?></td>
                            <td><?= $p['num_sessoes_prescritas'] ?? '—' ?></td>
                            <td><?= h($p['data_prescricao']) ?></td>
                            <td><?= $p['ativa']?'<span class="badge bg-success">Ativo</span>':'<span class="badge bg-secondary">Inativo</span>' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
