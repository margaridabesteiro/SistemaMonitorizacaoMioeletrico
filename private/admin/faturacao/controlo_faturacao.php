<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Faturação'; $pagina_ativa = 'faturacao';

$db = getDB();

// Auto-adicionar coluna ativo se ainda não existir
try { $db->exec("ALTER TABLE faturas ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1"); } catch (\Throwable $e) {}

// Handler: Inativar / Reativar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['_acao'] ?? '', ['inativar','reativar'], true)) {
    $num  = trim($_POST['num'] ?? '');
    $acao = $_POST['_acao'];
    if ($num) {
        $novo = ($acao === 'reativar') ? 1 : 0;
        $db->prepare('UPDATE faturas SET ativo=? WHERE numero=?')->execute([$novo, $num]);
        registarAuditoria('ATUALIZAR', 'Fatura', null, "Fatura $num " . ($novo ? 'reativada' : 'inativada'));
        $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => "Fatura $num " . ($novo ? 'reativada.' : 'inativada.')];
    }
    redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
}

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';

// Notificar admins sobre faturas vencidas (uma vez por dia)
try {
    $n_venc = (int)$db->query("SELECT COUNT(*) FROM faturas WHERE ativo=1 AND paga=0 AND data_vencimento IS NOT NULL AND data_vencimento < CURDATE()")->fetchColumn();
    if ($n_venc > 0) {
        $ja = (int)$db->query("SELECT COUNT(*) FROM notificacoes WHERE tipo='info' AND titulo LIKE '%vencida%' AND DATE(criado_em)=CURDATE()")->fetchColumn();
        if (!$ja) {
            $adm_ids = $db->query("SELECT id FROM utilizadores WHERE perfil='admin' AND ativo=1")->fetchAll();
            foreach ($adm_ids as $a) {
                notificar((int)$a['id'], 'info',
                    $n_venc . ' fatura(s) vencida(s)',
                    $n_venc . ' fatura(s) ultrapassaram a data de vencimento sem pagamento.',
                    APP_URL . '/private/admin/faturacao/controlo_faturacao.php'
                );
            }
        }
    }
} catch (\Throwable $e) {}

// Filtros
$filtro_estado  = $_GET['estado']  ?? '';
$filtro_periodo = $_GET['periodo'] ?? '30';
$pagina_atual   = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina     = 15;

// Construir WHERE
$where_parts = [];

if ((int)$filtro_periodo > 0) {
    $where_parts[] = "f.data_emissao >= DATE_SUB(NOW(), INTERVAL " . (int)$filtro_periodo . " DAY)";
}
if ($filtro_estado === 'pagas') {
    $where_parts[] = "f.ativo = 1 AND f.paga = 1";
} elseif ($filtro_estado === 'pendente') {
    $where_parts[] = "f.ativo = 1 AND f.paga = 0 AND (f.data_vencimento IS NULL OR f.data_vencimento >= CURDATE())";
} elseif ($filtro_estado === 'vencida') {
    $where_parts[] = "f.ativo = 1 AND f.paga = 0 AND f.data_vencimento IS NOT NULL AND f.data_vencimento < CURDATE()";
} elseif ($filtro_estado === 'inativas') {
    $where_parts[] = "f.ativo = 0";
} elseif ($filtro_estado === 'inativas_vencidas') {
    $where_parts[] = "(f.ativo = 0 OR (f.ativo = 1 AND f.paga = 0 AND f.data_vencimento IS NOT NULL AND f.data_vencimento < CURDATE()))";
}
// "Todas" — sem filtro de estado: mostra tudo

$where = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

// Contagem total
$cnt_stmt = $db->query("SELECT COUNT(*), COALESCE(SUM(f.valor_eur),0) FROM faturas f $where");
[$cnt_total, $soma_total] = $cnt_stmt->fetch(PDO::FETCH_NUM);
$total_paginas = max(1, (int)ceil($cnt_total / $por_pagina));
$pagina_atual  = min($pagina_atual, $total_paginas);
$offset        = ($pagina_atual - 1) * $por_pagina;

// KPIs globais
$kpi_pagas    = (int)$db->query("SELECT COUNT(*) FROM faturas WHERE ativo=1 AND paga=1")->fetchColumn();
$kpi_inativas = (int)$db->query("SELECT COUNT(*) FROM faturas WHERE ativo=0")->fetchColumn();

// Dados da página
$faturas = $db->query("
    SELECT f.*, u.nome AS utente, ut.cobertura_saude, s.nome AS seg_nome
    FROM faturas f
    JOIN utentes ut ON ut.id = f.utente_id
    JOIN utilizadores u ON u.id = ut.utilizador_id
    LEFT JOIN seguradoras s ON s.id = f.seguradora_id
    $where
    ORDER BY f.data_emissao DESC
    LIMIT $por_pagina OFFSET $offset
")->fetchAll();

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

            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold"><?= (int)$cnt_total ?></div><div class="text-muted small">Selecionadas</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-danger"><?= number_format((float)$soma_total,2,',','.') ?>€</div><div class="text-muted small">Volume</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success"><?= $kpi_pagas ?></div><div class="text-muted small">Pagas (total)</div></div></div>
                <div class="col-md-3"><div class="card text-center p-3"><div class="fs-2 fw-bold text-secondary"><?= $kpi_inativas ?></div><div class="text-muted small">Inativas (total)</div></div></div>
            </div>

            <!-- Filtros -->
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="periodo" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0"   <?= $filtro_periodo==='0'  ?'selected':'' ?>>Todas as datas</option>
                        <option value="7"   <?= $filtro_periodo==='7'  ?'selected':'' ?>>7 dias</option>
                        <option value="30"  <?= $filtro_periodo==='30' ?'selected':'' ?>>30 dias</option>
                        <option value="90"  <?= $filtro_periodo==='90' ?'selected':'' ?>>90 dias</option>
                        <option value="365" <?= $filtro_periodo==='365'?'selected':'' ?>>1 ano</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value=""         <?= $filtro_estado===''          ?'selected':'' ?>>Todas</option>
                        <option value="pagas"    <?= $filtro_estado==='pagas'     ?'selected':'' ?>>Paga</option>
                        <option value="pendente" <?= $filtro_estado==='pendente'  ?'selected':'' ?>>Pendente</option>
                        <option value="vencida"  <?= $filtro_estado==='vencida'   ?'selected':'' ?>>Vencida</option>
                        <option value="inativas"         <?= $filtro_estado==='inativas'         ?'selected':'' ?>>Inativa</option>
                        <option value="inativas_vencidas" <?= $filtro_estado==='inativas_vencidas'?'selected':'' ?>>Inativas e Vencidas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">Filtrar</button>
                </div>
            </form>

            <!-- Tabela -->
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
                        <tr <?= !$f['ativo'] ? 'class="table-secondary"' : '' ?>>
                            <td><code><?= h($f['numero']) ?></code></td>
                            <td><?= h($f['utente']) ?></td>
                            <td class="small text-muted"><?= h($f['tipo_servico'] ?? '—') ?></td>
                            <td class="small"><?= h($f['seg_nome'] ?? '—') ?></td>
                            <td class="fw-bold"><?= number_format((float)$f['valor_eur'],2,',','.') ?>€</td>
                            <td class="small"><?= h($f['data_emissao']) ?></td>
                            <td>
                                <?php if (!$f['ativo']): ?>
                                    <span class="badge bg-secondary">Inativa</span>
                                <?php elseif ($f['paga']): ?>
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
                                <?php if ($f['ativo']): ?>
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
                                    <form method="POST" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="_acao" value="inativar">
                                        <input type="hidden" name="num" value="<?= h($f['numero']) ?>">
                                        <button type="submit" class="btn btn-xs btn-outline-secondary" title="Inativar" onclick="return confirm('Inativar fatura <?= h(addslashes($f['numero'])) ?>?')">
                                            <i class="fa-regular fa-circle-pause"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="_acao" value="reativar">
                                        <input type="hidden" name="num" value="<?= h($f['numero']) ?>">
                                        <button type="submit" class="btn btn-xs btn-outline-success" title="Reativar">
                                            <i class="fa-regular fa-circle-play"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
            <nav class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">Página <?= $pagina_atual ?> de <?= $total_paginas ?> — <?= (int)$cnt_total ?> faturas</div>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($pagina_atual > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$pagina_atual-1])) ?>"><i class="fa-solid fa-chevron-left"></i></a></li>
                    <?php endif; ?>
                    <?php
                    $pi = max(1, $pagina_atual - 2);
                    $pf = min($total_paginas, $pagina_atual + 2);
                    if ($pi > 1): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina'=>1])) ?>">1</a></li>
                        <?php if ($pi > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($pp = $pi; $pp <= $pf; $pp++): ?>
                    <li class="page-item <?= $pp===$pagina_atual?'active':'' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$pp])) ?>"><?= $pp ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($pf < $total_paginas):
                        if ($pf < $total_paginas - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$total_paginas])) ?>"><?= $total_paginas ?></a></li>
                    <?php endif; ?>
                    <?php if ($pagina_atual < $total_paginas): ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$pagina_atual+1])) ?>"><i class="fa-solid fa-chevron-right"></i></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>

<!-- Modal: Registar Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="<?= APP_URL ?>/api/admin/faturacao/marcar_paga.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="num" id="mp_num">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Registar Pagamento</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-1"><strong id="mp_utente"></strong></p>
                    <p class="small text-muted mb-3">Fatura <span id="mp_num_display"></span> — <strong id="mp_valor"></strong>€</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Método de Pagamento <span class="text-danger">*</span></label>
                        <select name="metodo_pagamento" class="form-select" required>
                            <option value="">Selecionar...</option>
                            <?php foreach ($metodo_labels as $val => $label): ?>
                                <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
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

<script>
function abrirModalPagamento(num, utente, valor) {
    document.getElementById('mp_num').value               = num;
    document.getElementById('mp_num_display').textContent = num;
    document.getElementById('mp_utente').textContent      = utente;
    document.getElementById('mp_valor').textContent       = parseFloat(valor).toFixed(2).replace('.',',');
    new bootstrap.Modal(document.getElementById('modalPagamento')).show();
}
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
