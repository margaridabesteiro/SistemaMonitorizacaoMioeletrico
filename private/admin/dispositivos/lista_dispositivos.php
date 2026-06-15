<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Dispositivos'; $pagina_ativa = 'dispositivos';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();

$total_disp  = (int)$db->query('SELECT COUNT(*) FROM dispositivos')->fetchColumn();
$online      = (int)$db->query("SELECT COUNT(*) FROM dispositivos WHERE ativo=1 AND ultimo_sync >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
$offline_3d  = (int)$db->query("SELECT COUNT(*) FROM dispositivos WHERE ativo=1 AND (ultimo_sync IS NULL OR ultimo_sync < DATE_SUB(NOW(), INTERVAL 3 DAY))")->fetchColumn();

// Utente atual via emprestimos_dispositivos
$stmt = $db->query("
    SELECT d.*,
           u.nome AS paciente,
           e.data_entrega
    FROM dispositivos d
    LEFT JOIN emprestimos_dispositivos e ON e.dispositivo_id=d.id AND e.data_devolucao IS NULL
    LEFT JOIN utentes ut ON ut.id=e.utente_id
    LEFT JOIN utilizadores u ON u.id=ut.utilizador_id
    ORDER BY d.codigo
");
$dispositivos = $stmt->fetchAll();

$estado_badge = [
    'disponivel'  => 'success',
    'emprestado'  => 'primary',
    'manutencao'  => 'warning',
    'avariado'    => 'danger',
    'abatido'     => 'secondary',
];
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Gestão de Dispositivos</h1>
                <div class="d-flex gap-2">
                    <a href="emprestimos.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-right-arrow-left me-1"></i>Empréstimos</a>
                    <a href="associar_dispositivo.php" class="btn btn-sm" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-plus me-1"></i>Novo Dispositivo</a>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold"><?= $total_disp ?></div><div class="text-muted small">Total</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success"><?= $online ?></div><div class="text-muted small">Online (&lt;1h)</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-danger"><?= $offline_3d ?></div><div class="text-muted small">Sem sync &gt;3 dias</div></div></div>
            </div>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Código</th><th>Estado</th><th>Utente Atual</th><th>Último Sync</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php if(empty($dispositivos)): ?><tr><td colspan="5" class="text-center text-muted py-4">Sem dispositivos.</td></tr>
                    <?php else: foreach($dispositivos as $d): ?>
                        <tr>
                            <td><strong><?= h($d['codigo']) ?></strong><br><small class="text-muted">FW: <?= h($d['firmware_versao'] ?? '—') ?></small></td>
                            <td><span class="badge bg-<?= $estado_badge[$d['estado']] ?? 'secondary' ?>"><?= h(ucfirst($d['estado'])) ?></span></td>
                            <td><?= $d['paciente'] ? h($d['paciente']) : '<span class="text-muted">—</span>' ?></td>
                            <td><?= $d['ultimo_sync'] ? h(substr($d['ultimo_sync'],0,16)) : '<span class="text-muted">Nunca</span>' ?></td>
                            <td class="d-flex gap-1">
                                <?php if ($d['estado'] === 'disponivel'): ?>
                                    <a href="novo_emprestimo.php?disp=<?= $d['id'] ?>" class="btn btn-xs btn-outline-success" title="Emprestar"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                                <?php elseif ($d['estado'] === 'emprestado'): ?>
                                    <a href="devolver_dispositivo.php?disp=<?= $d['id'] ?>" class="btn btn-xs btn-outline-warning" title="Devolver"><i class="fa-solid fa-arrow-right-to-bracket"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
