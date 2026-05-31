<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
// Utentes SNS não têm faturação — redirecionar
$_db_pag = getDB();
$_cob = $_db_pag->prepare('SELECT cobertura_saude FROM utentes WHERE utilizador_id=?');
$_cob->execute([(int)$_SESSION['utilizador_id']]);
if (($_cob->fetchColumn() ?: 'SNS') === 'SNS') {
    redirect(APP_URL . '/private/utente/index_utente.php');
}
$pagina_titulo = 'Pagamentos'; $pagina_ativa = 'pagamentos';
require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?"); $stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();
$stats = $utid ? $db->query("SELECT COALESCE(SUM(CASE WHEN paga=1 THEN valor_eur END),0) AS total_pago, COALESCE(SUM(CASE WHEN paga=0 THEN valor_eur END),0) AS pendente, COUNT(CASE WHEN paga=0 THEN 1 END) AS n_pendentes FROM faturas WHERE utente_id=$utid")->fetch() : ['total_pago'=>0,'pendente'=>0,'n_pendentes'=>0];
$filtro = (int)($_GET['periodo'] ?? 365);
$faturas = $utid ? $db->query("SELECT * FROM faturas WHERE utente_id=$utid AND data_emissao >= DATE_SUB(NOW(), INTERVAL $filtro DAY) ORDER BY data_emissao DESC")->fetchAll() : [];
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Histórico de Pagamentos</h1>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Imprimir Extrato</button>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card p-3 text-center"><div class="fs-2 fw-bold text-success"><?= number_format((float)$stats['total_pago'],2,',','.') ?>€</div><div class="text-muted small">Total Pago</div></div></div>
                <div class="col-md-4"><div class="card p-3 text-center"><div class="fs-2 fw-bold text-warning"><?= number_format((float)$stats['pendente'],2,',','.') ?>€</div><div class="text-muted small"><?= $stats['n_pendentes'] ?> fatura(s) pendente(s)</div></div></div>
                <div class="col-md-4"><div class="card p-3 text-center"><div class="fs-2 fw-bold text-secondary"><?= count($faturas) ?></div><div class="text-muted small">Faturas no período</div></div></div>
            </div>
            <form method="GET" class="mb-3 d-print-none">
                <select name="periodo" class="form-select form-select-sm w-auto d-inline-block" onchange="this.form.submit()">
                    <option value="30" <?= $filtro==30?'selected':'' ?>>Últimos 30 dias</option>
                    <option value="90" <?= $filtro==90?'selected':'' ?>>Últimos 3 meses</option>
                    <option value="180" <?= $filtro==180?'selected':'' ?>>Últimos 6 meses</option>
                    <option value="365" <?= $filtro==365?'selected':'' ?>>Último ano</option>
                </select>
            </form>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Nº Fatura</th><th>Data</th><th>Valor</th><th>Vencimento</th><th>Estado</th><th class="d-print-none">Ações</th></tr></thead>
                    <tbody>
                    <?php if (empty($faturas)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Sem faturas.</td></tr>
                    <?php else: foreach ($faturas as $f): ?>
                        <tr>
                            <td><?= h($f['numero']) ?></td>
                            <td><?= h($f['data_emissao']) ?></td>
                            <td class="fw-bold"><?= number_format((float)$f['valor_eur'],2,',','.') ?>€</td>
                            <td><?= h($f['data_vencimento'] ?? '—') ?></td>
                            <td><?= $f['paga'] ? '<span class="badge bg-success">Paga</span>' : '<span class="badge bg-warning text-dark">Pendente</span>' ?></td>
                            <td class="d-print-none"><a href="detalhes.php?id=<?= $f['id'] ?>" class="btn btn-xs btn-outline-secondary"><i class="fa-regular fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
