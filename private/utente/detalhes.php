<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Detalhes da Fatura'; $pagina_ativa = 'pagamentos';
require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?"); $stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();
$id = (int)($_GET['id'] ?? 0);
$f = ($id && $utid) ? $db->prepare("SELECT * FROM faturas WHERE id=? AND utente_id=?")->execute([$id,$utid]) ? $db->prepare("SELECT * FROM faturas WHERE id=? AND utente_id=?") : null : null;
if ($id && $utid) { $stmt2 = $db->prepare("SELECT * FROM faturas WHERE id=? AND utente_id=?"); $stmt2->execute([$id,$utid]); $f = $stmt2->fetch(); }
if (!$f) redirect(APP_URL . '/private/utente/pagamentos.php');
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Detalhe — <?= h($f['numero']) ?></h1>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print me-1"></i>Imprimir</button>
                    <a href="pagamentos.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
                </div>
            </div>
            <div class="card p-4" style="max-width:600px;">
                <div class="d-flex justify-content-between mb-4">
                    <div><h4 style="color:#667eea;">RehabLink</h4><p class="text-muted small mb-0">Serviço de Reabilitação</p></div>
                    <div class="text-end">
                        <div class="fw-bold"><?= h($f['numero']) ?></div>
                        <?= $f['paga'] ? '<span class="badge bg-success">Paga</span>' : '<span class="badge bg-warning text-dark">Pendente</span>' ?>
                    </div>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-6"><p><strong>Emissão:</strong> <?= h($f['data_emissao']) ?></p></div>
                    <div class="col-6 text-end"><p><strong>Vencimento:</strong> <?= h($f['data_vencimento'] ?? '—') ?></p></div>
                </div>
                <div class="p-3 rounded mb-3" style="background:#f8f9fa;">
                    <div class="d-flex justify-content-between">
                        <span>Valor total</span>
                        <strong class="fs-4" style="color:#667eea;"><?= number_format((float)$f['valor_eur'],2,',','.') ?>€</strong>
                    </div>
                </div>
                <?php if ($f['notas']): ?><p class="text-muted small"><strong>Notas:</strong> <?= h($f['notas']) ?></p><?php endif; ?>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
