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
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$linhas = [];
try {
    $db->exec("CREATE TABLE IF NOT EXISTS fatura_linhas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        fatura_id INT UNSIGNED NOT NULL,
        tipo_servico VARCHAR(50) NULL,
        descricao VARCHAR(200) NOT NULL,
        quantidade TINYINT UNSIGNED NOT NULL DEFAULT 1,
        preco_unit DECIMAL(8,2) NOT NULL,
        total_linha DECIMAL(8,2) NOT NULL,
        FOREIGN KEY (fatura_id) REFERENCES faturas(id) ON DELETE CASCADE,
        INDEX idx_fatura (fatura_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $sl = $db->prepare('SELECT * FROM fatura_linhas WHERE fatura_id=? ORDER BY id');
    $sl->execute([$f['id']]);
    $linhas = $sl->fetchAll();
} catch (\Throwable $e) {}
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
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> alert-dismissible py-2 no-print"><?= h($flash['mensagem']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <h1>Fatura <?= h($f['numero']) ?></h1>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa-regular fa-file-pdf me-1"></i>PDF / Imprimir</button>
                    <?php if (!$f['paga']): ?>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPagamento">
                        <i class="fa-solid fa-check me-1"></i>Registar Pagamento
                    </button>
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
                        <?php if ($f['data_vencimento']): ?><p class="text-muted small">Vence: <?= h($f['data_vencimento']) ?></p><?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($linhas)): ?>
                <table class="table table-sm table-bordered mb-3">
                    <thead class="table-light">
                        <tr>
                            <th>Descrição</th>
                            <th class="text-center" style="width:55px;">Qtd</th>
                            <th class="text-end" style="width:110px;">Preço Unit.</th>
                            <th class="text-end" style="width:100px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($linhas as $l): ?>
                        <tr>
                            <td><?= h($l['descricao']) ?></td>
                            <td class="text-center"><?= (int)$l['quantidade'] ?></td>
                            <td class="text-end"><?= number_format((float)$l['preco_unit'],2,',','.') ?>€</td>
                            <td class="text-end fw-semibold"><?= number_format((float)$l['total_linha'],2,',','.') ?>€</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold text-danger fs-5"><?= number_format((float)$f['valor_eur'],2,',','.') ?>€</td>
                        </tr>
                    </tfoot>
                </table>
                <?php else: ?>
                <div class="mb-3">
                    <p><strong>Valor:</strong> <span class="fs-4 fw-bold text-danger"><?= number_format((float)$f['valor_eur'],2,',','.') ?>€</span></p>
                </div>
                <?php endif; ?>

                <?php if ($f['notas']): ?><div class="alert alert-light"><strong>Notas:</strong> <?= h($f['notas']) ?></div><?php endif; ?>
                <hr>
                <a href="controlo_faturacao.php" class="btn btn-outline-secondary btn-sm no-print"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
            </div>
        </main>
<?php if (!$f['paga']): ?>
<!-- Modal: Registar Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="<?= APP_URL ?>/api/admin/faturacao/marcar_paga.php">
                <input type="hidden" name="num" value="<?= h($f['numero']) ?>">
                <input type="hidden" name="_origem" value="fatura">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><i class="fa-solid fa-check me-1"></i>Registar Pagamento</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-1"><strong><?= h($f['utente']) ?></strong></p>
                    <p class="small text-muted mb-3">Fatura <?= h($f['numero']) ?> — <strong><?= number_format((float)$f['valor_eur'],2,',','.') ?>€</strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Método de Pagamento <span class="text-danger">*</span></label>
                        <select name="metodo_pagamento" class="form-select" required>
                            <option value="">Selecionar...</option>
                            <option value="multibanco">Multibanco</option>
                            <option value="cartão">Cartão</option>
                            <option value="seguro">Seguro</option>
                            <option value="numerário">Numerário</option>
                            <option value="transferência">Transferência</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Data de Pagamento</label>
                        <input type="date" name="data_pagamento" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                        <i class="fa-solid fa-check me-1"></i>Confirmar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
