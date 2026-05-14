<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Dispositivos'; $pagina_ativa = 'dispositivos';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
?>

        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Gestão de Dispositivos</h1>
                <a href="associar_dispositivo.php" class="btn btn-sm" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-plus me-1"></i>Associar Dispositivo</a>
            </div>
            <?php
            $total_disp = (int)$db->query('SELECT COUNT(*) FROM dispositivos')->fetchColumn();
            $online = (int)$db->query("SELECT COUNT(*) FROM dispositivos WHERE ativo=1 AND ultimo_sync >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
            $offline_24h = (int)$db->query("SELECT COUNT(*) FROM dispositivos WHERE ativo=1 AND (ultimo_sync IS NULL OR ultimo_sync < DATE_SUB(NOW(), INTERVAL 24 HOUR))")->fetchColumn();
            $stmt = $db->query("SELECT d.*, u.nome AS paciente FROM dispositivos d LEFT JOIN utentes ut ON ut.id = d.utente_id LEFT JOIN utilizadores u ON u.id = ut.utilizador_id ORDER BY d.codigo");
            $dispositivos = $stmt->fetchAll();
            ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold"><?= $total_disp ?></div><div class="text-muted small">Total Dispositivos</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success"><?= $online ?></div><div class="text-muted small">Online (&lt;1h)</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-danger"><?= $offline_24h ?></div><div class="text-muted small">Offline &gt;24h</div></div></div>
            </div>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Código</th><th>Tipo</th><th>Paciente</th><th>Último Sync</th><th>Estado</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php if(empty($dispositivos)): ?><tr><td colspan="6" class="text-center text-muted py-4">Sem dispositivos.</td></tr>
                    <?php else: foreach($dispositivos as $d): ?>
                        <tr>
                            <td><strong><?= h($d['codigo']) ?></strong><br><small class="text-muted">FW: <?= h($d['firmware_versao'] ?? '—') ?></small></td>
                            <td><?= h($d['tipo']) ?></td>
                            <td><?= h($d['paciente'] ?? '<em>Não associado</em>') ?></td>
                            <td><?= $d['ultimo_sync'] ? h(substr($d['ultimo_sync'],0,16)) : '<span class="text-muted">Nunca</span>' ?></td>
                            <td><?= $d['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
                            <td><a href="detalhes_dispositivo.php?id=<?= $d['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fa-regular fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
