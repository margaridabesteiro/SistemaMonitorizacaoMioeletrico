<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Fatura'; $pagina_ativa = 'faturacao';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
$num = trim($_GET['num'] ?? '');
if (!$num) redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
$stmt = $db->prepare("SELECT f.*, u.nome AS utente, ut2.nif FROM faturas f JOIN utentes ut ON ut.id=f.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id LEFT JOIN utentes ut2 ON ut2.id=f.utente_id WHERE f.numero=?");
$stmt->execute([$num]); $f = $stmt->fetch();
if (!$f) redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
?>
<style>
@media print {
    .topbar, .sidebar, .no-print, nav, .btn, a.btn { display: none !important; }
    .wrapper { display: block !important; }
    .content { margin: 0 !important; padding: 0 !important; }
    body { background: white !important; }
    .card { box-shadow: none !important; border: none !important; }
}
</style>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <h1>Fatura <?= h($f['numero']) ?></h1>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa-regular fa-file-pdf me-1"></i>PDF / Imprimir</button>
                    <?php if (!$f['paga']): ?>
                    <a href="<?= APP_URL ?>/api/admin/faturacao/marcar_paga.php?num=<?= urlencode($f['numero']) ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-check me-1"></i>Marcar Paga</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card p-4" style="max-width:700px;">
                <div class="d-flex justify-content-between mb-4">
                    <div><h4 style="color:#8B0000;">RehabLink</h4><p class="text-muted small mb-0">Sistema de Reabilitação</p></div>
                    <div class="text-end">
                        <div class="fw-bold fs-5"><?= h($f['numero']) ?></div>
                        <div class="text-muted small">Emitida: <?= h($f['data_emissao']) ?></div>
                        <?= $f['paga'] ? '<span class="badge bg-success">Paga</span>' : '<span class="badge bg-warning text-dark">Pendente</span>' ?>
                    </div>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Utente:</strong> <?= h($f['utente']) ?></p>
                        <p><strong>NIF:</strong> <?= h($f['nif'] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>Valor:</strong> <span class="fs-4 fw-bold text-danger"><?= number_format((float)$f['valor_eur'],2,',','.') ?>€</span></p>
                        <?php if ($f['data_vencimento']): ?><p class="text-muted small">Vence: <?= h($f['data_vencimento']) ?></p><?php endif; ?>
                    </div>
                </div>
                <?php if ($f['notas']): ?><div class="alert alert-light"><strong>Notas:</strong> <?= h($f['notas']) ?></div><?php endif; ?>
                <hr>
                <a href="controlo_faturacao.php" class="btn btn-outline-secondary btn-sm no-print"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
