<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Faturação'; $pagina_ativa = 'faturacao';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';

$db = getDB();

// Notificar admins sobre faturas vencidas (uma vez por dia)
try {
    $n_venc = (int)$db->query("SELECT COUNT(*) FROM faturas WHERE paga=0 AND data_vencimento IS NOT NULL AND data_vencimento < CURDATE()")->fetchColumn();
    if ($n_venc > 0) {
        $ja = (int)$db->query("SELECT COUNT(*) FROM notificacoes WHERE tipo='info' AND titulo LIKE '%vencida%' AND DATE(criado_em)=CURDATE()")->fetchColumn();
        if (!$ja) {
            $adm_ids = $db->query("SELECT id FROM utilizadores WHERE perfil='admin' AND ativo=1")->fetchAll();
            foreach ($adm_ids as $a) {
                notificar((int)$a['id'], 'info',
                    $n_venc . ' fatura(s) vencida(s)',
                    $n_venc . ' fatura(s) ultrapassaram a data de vencimento sem pagamento.',
                    APP_URL . '/private/admin/faturacao/controlo_faturacao.php?estado=0'
                );
            }
        }
    }
} catch (\Throwable $e) {}

$filtro_estado  = $_GET['estado']  ?? '';
$filtro_periodo = (int)($_GET['periodo'] ?? 30);
$pagina_atual   = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 20; $offset = ($pagina_atual - 1) * $por_pagina;

$where  = "WHERE f.data_emissao >= DATE_SUB(NOW(), INTERVAL $filtro_periodo DAY)";
$params = [];
if (in_array($filtro_estado, ['0','1'], true)) { $where .= ' AND f.paga = ?'; $params[] = $filtro_estado; }

$total_val = $db->prepare("SELECT COALESCE(SUM(valor_eur),0), COUNT(*) FROM faturas f $where");
$total_val->execute($params);
[$soma,$cnt] = $total_val->fetch(PDO::FETCH_NUM);
$pagas     = (int)$db->query("SELECT COUNT(*) FROM faturas WHERE paga=1")->fetchColumn();
$pendentes = (int)$db->query("SELECT COUNT(*) FROM faturas WHERE paga=0")->fetchColumn();

$stmt = $db->prepare("
    SELECT f.*, u.nome AS utente, ut.cobertura_saude, s.nome AS seg_nome
    FROM faturas f
    JOIN utentes ut ON ut.id = f.utente_id
    JOIN utilizadores u ON u.id = ut.utilizador_id
    LEFT JOIN seguradoras s ON s.id = f.seguradora_id
    $where
    ORDER BY f.data_emissao DESC
    LIMIT $por_pagina OFFSET $offset
");
$stmt->execute($params);
$faturas = $stmt->fetchAll();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$metodo_labels = [
    'multibanco'    => 'Multibanco',
    'cartão'        => 'Cartão',
    'seguro'        => 'Seguro',
    'numerário'     => 'Numerário',
    'transferência' => 'Transferência',
];
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Controlo de Faturação</h1>
                <div class="d-flex gap-2">
                    <a href="relatorio_financeiro.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-chart-bar me-1"></i>Relatório
                    </a>
                    <a href="nova_fatura.php" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                        <i class="fa-solid fa-plus me-1"></i>Nova Fatura
                    </a>
                </div>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> alert-dismissible py-2">
                    <?= h($flash['mensagem']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold"><?= $cnt ?></div><div class="text-muted small">Faturas (<?= $filtro_periodo ?>d)</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-danger"><?= number_format((float)$soma,2,',','.') ?>€</div><div class="text-muted small">Volume</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success"><?= $pagas ?></div><div class="text-muted small">Pagas</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-warning"><?= $pendentes ?></div><div class="text-muted small">Pendentes</div></div></div>
            </div>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><select name="periodo" class="form-select form-select-sm"><option value="7" <?= $filtro_periodo==7?'selected':'' ?>>7 dias</option><option value="30" <?= $filtro_periodo==30?'selected':'' ?>>30 dias</option><option value="90" <?= $filtro_periodo==90?'selected':'' ?>>90 dias</option><option value="365" <?= $filtro_periodo==365?'selected':'' ?>>1 ano</option></select></div>
                <div class="col-md-3"><select name="estado" class="form-select form-select-sm"><option value="">Todas</option><option value="0" <?= $filtro_estado==='0'?'selected':'' ?>>Pendentes</option><option value="1" <?= $filtro_estado==='1'?'selected':'' ?>>Pagas</option></select></div>
                <div class="col-md-2"><button type="submit" class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
            </form>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nº Fatura</th><th>Utente</th><th>Tipo</th><th>Seguradora</th>
                            <th>Valor</th><th>Emissão</th><th>Estado</th><th>Método</th><th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($faturas)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Sem faturas.</td></tr>
                    <?php else: foreach ($faturas as $f): ?>
                        <tr>
                            <td><code><?= h($f['numero']) ?></code></td>
                            <td><?= h($f['utente']) ?></td>
                            <td class="small text-muted"><?= h($f['tipo_servico'] ?? '—') ?></td>
                            <td class="small"><?= h($f['seg_nome'] ?? '—') ?></td>
                            <td class="fw-bold"><?= number_format((float)$f['valor_eur'],2,',','.') ?>€</td>
                            <td class="small"><?= h($f['data_emissao']) ?></td>
                            <td>
                                <?php if ($f['paga']): ?>
                                    <span class="badge bg-success">Paga</span>
                                    <?php if ($f['data_pagamento']): ?>
                                        <br><span class="text-muted" style="font-size:.75rem;"><?= h($f['data_pagamento']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pendente</span>
                                    <?php if ($f['data_vencimento'] && $f['data_vencimento'] < date('Y-m-d')): ?>
                                        <br><span class="text-danger" style="font-size:.75rem;">Vencida</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?php if ($f['paga'] && $f['metodo_pagamento']): ?>
                                    <span class="badge bg-light text-dark border"><?= h($metodo_labels[$f['metodo_pagamento']] ?? $f['metodo_pagamento']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="fatura.php?num=<?= urlencode($f['numero']) ?>" class="btn btn-xs btn-outline-primary me-1" title="Ver"><i class="fa-regular fa-eye"></i></a>
                                <a href="editar_fatura.php?num=<?= urlencode($f['numero']) ?>" class="btn btn-xs btn-outline-secondary me-1" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                <?php if ($f['cobertura_saude'] !== 'SNS'): ?>
                                    <?php if (!$f['paga']): ?>
                                    <button type="button"
                                            class="btn btn-xs btn-outline-success me-1"
                                            title="Registar Pagamento"
                                            onclick="abrirModalPagamento('<?= h(addslashes($f['numero'])) ?>','<?= h(addslashes($f['utente'])) ?>',<?= $f['valor_eur'] ?>)">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <?php else: ?>
                                    <a href="<?= APP_URL ?>/api/admin/faturacao/toggle_paga.php?num=<?= urlencode($f['numero']) ?>"
                                       class="btn btn-xs btn-outline-warning me-1"
                                       title="Reverter para pendente"
                                       onclick="return confirm('Reverter fatura <?= h(addslashes($f['numero'])) ?> para pendente?')">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a href="apagar_fatura.php?num=<?= urlencode($f['numero']) ?>" class="btn btn-xs btn-outline-danger" title="Apagar"><i class="fa-regular fa-trash-can"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>

<!-- Modal: Registar Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="<?= APP_URL ?>/api/admin/faturacao/marcar_paga.php">
                <input type="hidden" name="num" id="mp_num">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Registar Pagamento</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-1"><strong id="mp_utente"></strong></p>
                    <p class="small text-muted mb-3">Fatura <span id="mp_num_display"></span> — <strong id="mp_valor"></strong>€</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Método de Pagamento *</label>
                        <select name="metodo_pagamento" class="form-select" required>
                            <option value="">Selecionar...</option>
                            <?php foreach ($metodo_labels as $val => $label): ?>
                                <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Data de Pagamento *</label>
                        <input type="date" name="data_pagamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
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

<script>
function abrirModalPagamento(num, utente, valor) {
    document.getElementById('mp_num').value        = num;
    document.getElementById('mp_num_display').textContent = num;
    document.getElementById('mp_utente').textContent      = utente;
    document.getElementById('mp_valor').textContent       = parseFloat(valor).toFixed(2).replace('.',',');
    new bootstrap.Modal(document.getElementById('modalPagamento')).show();
}
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
