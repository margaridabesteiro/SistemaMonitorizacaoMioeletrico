<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Auditoria'; $pagina_ativa = 'auditoria';
requirePerfil('admin');
$db = getDB();

// ── Exportação CSV ────────────────────────────────────────────────────────────
if (isset($_GET['exportar'])) {
    $where = 'WHERE 1=1'; $params = [];
    if (!empty($_GET['nome']))    { $where .= ' AND a.nome LIKE ?'; $params[] = '%' . $_GET['nome'] . '%'; }
    if (!empty($_GET['entidade'])) { $where .= ' AND a.entidade = ?'; $params[] = $_GET['entidade']; }
    if (!empty($_GET['acao']))    { $where .= ' AND a.acao = ?'; $params[] = $_GET['acao']; }
    if (!empty($_GET['de']))      { $where .= ' AND DATE(a.criado_em) >= ?'; $params[] = $_GET['de']; }
    if (!empty($_GET['ate']))     { $where .= ' AND DATE(a.criado_em) <= ?'; $params[] = $_GET['ate']; }
    $rows = $db->prepare("SELECT a.criado_em, a.nome, a.perfil, a.acao, a.entidade, a.entidade_id, a.detalhe, a.ip FROM auditoria a $where ORDER BY a.criado_em DESC");
    $rows->execute($params); $rows = $rows->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="auditoria_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM para Excel
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Data/Hora','Utilizador','Papel','Ação','Entidade','ID Entidade','Detalhe','IP'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            date('d/m/Y H:i:s', strtotime($r['criado_em'])),
            $r['nome'] ?? '—',
            $r['perfil'] ?? '—',
            $r['acao'],
            $r['entidade'] ?? '—',
            $r['entidade_id'] ?? '—',
            $r['detalhe'] ?? '—',
            $r['ip'] ?? '—',
        ], ';');
    }
    fclose($out);
    exit;
}

// ── Filtros ───────────────────────────────────────────────────────────────────
$f_nome     = trim($_GET['nome']     ?? '');
$f_entidade = trim($_GET['entidade'] ?? '');
$f_acao     = trim($_GET['acao']     ?? '');
$f_de       = trim($_GET['de']       ?? '');
$f_ate      = trim($_GET['ate']      ?? '');
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 50;
$offset     = ($pagina - 1) * $por_pagina;

$where = 'WHERE 1=1'; $params = [];
if ($f_nome     !== '') { $where .= ' AND a.nome LIKE ?';       $params[] = '%' . $f_nome . '%'; }
if ($f_entidade !== '') { $where .= ' AND a.entidade = ?';      $params[] = $f_entidade; }
if ($f_acao     !== '') { $where .= ' AND a.acao = ?';          $params[] = $f_acao; }
if ($f_de       !== '') { $where .= ' AND DATE(a.criado_em) >= ?'; $params[] = $f_de; }
if ($f_ate      !== '') { $where .= ' AND DATE(a.criado_em) <= ?'; $params[] = $f_ate; }

$total = (int)$db->prepare("SELECT COUNT(*) FROM auditoria a $where")->execute($params)
    ? ($cnt = $db->prepare("SELECT COUNT(*) FROM auditoria a $where"))->execute($params) && $cnt->fetchColumn()
    : 0;
// Fazer a contagem correctamente
$cnt_stmt = $db->prepare("SELECT COUNT(*) FROM auditoria a $where");
$cnt_stmt->execute($params);
$total = (int)$cnt_stmt->fetchColumn();
$paginas = max(1, (int)ceil($total / $por_pagina));

$stmt = $db->prepare("SELECT a.* FROM auditoria a $where ORDER BY a.criado_em DESC LIMIT $por_pagina OFFSET $offset");
$stmt->execute($params);
$registos = $stmt->fetchAll();

// Listas para dropdowns
$entidades_disponiveis = $db->query("SELECT DISTINCT entidade FROM auditoria WHERE entidade IS NOT NULL ORDER BY entidade")->fetchAll(\PDO::FETCH_COLUMN);
$acoes_disponiveis     = $db->query("SELECT DISTINCT acao     FROM auditoria ORDER BY acao")->fetchAll(\PDO::FETCH_COLUMN);

// Cores e labels das ações
$acao_styles = [
    'LOGIN'        => ['bg'=>'#dbeafe','txt'=>'#1e40af','label'=>'Login'],
    'LOGIN_FALHOU' => ['bg'=>'#fee2e2','txt'=>'#991b1b','label'=>'Login Falhado'],
    'LOGOUT'       => ['bg'=>'#f1f5f9','txt'=>'#475569','label'=>'Logout'],
    'CRIAR'        => ['bg'=>'#dcfce7','txt'=>'#166534','label'=>'Criação'],
    'ATUALIZAR'    => ['bg'=>'#fef9c3','txt'=>'#854d0e','label'=>'Atualização'],
    'ELIMINAR'     => ['bg'=>'#fee2e2','txt'=>'#b91c1c','label'=>'Eliminação'],
    'VER'          => ['bg'=>'#f0fdf4','txt'=>'#166534','label'=>'Consulta'],
    'EXPORTAR'     => ['bg'=>'#faf5ff','txt'=>'#6b21a8','label'=>'Exportação'],
];

$perfil_cores = ['admin'=>'danger','medico'=>'primary','tecnico'=>'success','utente'=>'secondary'];

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                <div>
                    <h1 class="mb-0">Auditoria</h1>
                    <p class="text-muted small mb-0">
                        <i class="fa-solid fa-scale-balanced me-1"></i>
                        Registos mantidos ao abrigo do <strong>Art.&nbsp;30.º do RGPD</strong>
                        (Registos das Atividades de Tratamento de Dados Pessoais de Saúde).
                    </p>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="badge bg-secondary fs-6"><?= number_format($total) ?> registos</span>
                    <a href="?<?= http_build_query(array_filter(['nome'=>$f_nome,'entidade'=>$f_entidade,'acao'=>$f_acao,'de'=>$f_de,'ate'=>$f_ate,'exportar'=>'1'])) ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-file-csv me-1"></i>Exportar CSV
                    </a>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card p-3 mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Utilizador</label>
                        <input type="text" name="nome" class="form-control form-control-sm"
                               placeholder="Pesquisar nome..." value="<?= h($f_nome) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Entidade</label>
                        <select name="entidade" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach ($entidades_disponiveis as $e): ?>
                                <option value="<?= h($e) ?>" <?= $f_entidade===$e?'selected':'' ?>><?= h($e) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Ação</label>
                        <select name="acao" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach ($acoes_disponiveis as $a): ?>
                                <option value="<?= h($a) ?>" <?= $f_acao===$a?'selected':'' ?>>
                                    <?= h($acao_styles[$a]['label'] ?? $a) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">De</label>
                        <input type="date" name="de"  class="form-control form-control-sm" value="<?= h($f_de) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Até</label>
                        <input type="date" name="ate" class="form-control form-control-sm" value="<?= h($f_ate) ?>">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-secondary w-100">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                        <a href="auditoria.php" class="btn btn-sm btn-outline-secondary" title="Limpar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tabela -->
            <div class="card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle" style="font-size:.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width:145px;">Data / Hora</th>
                                <th>Utilizador</th>
                                <th style="width:80px;">Papel</th>
                                <th style="width:130px;">Ação</th>
                                <th style="width:110px;">Entidade</th>
                                <th style="max-width:300px;">Detalhe</th>
                                <th style="width:110px;">IP</th>
                            </tr>
                        </thead>
                        <tbody id="tabela-auditoria">
                        <?php if (empty($registos)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Sem registos para os filtros seleccionados.</td></tr>
                        <?php else: foreach ($registos as $i => $r):
                            $estilo = $acao_styles[$r['acao']] ?? ['bg'=>'#f1f5f9','txt'=>'#475569','label'=>$r['acao']];
                            $pCor   = $perfil_cores[$r['perfil'] ?? ''] ?? 'secondary';
                        ?>
                            <tr style="cursor:pointer;" onclick="toggleDetalhe(<?= $i ?>)" title="Clique para ver detalhes">
                                <td class="font-monospace small"><?= h(date('d/m/Y H:i:s', strtotime($r['criado_em']))) ?></td>
                                <td><?= h($r['nome'] ?? '<em class="text-muted">anónimo</em>') ?></td>
                                <td>
                                    <?php if ($r['perfil']): ?>
                                    <span class="badge bg-<?= $pCor ?>" style="font-size:.7rem;"><?= h(ucfirst($r['perfil'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge" style="background:<?= $estilo['bg'] ?>;color:<?= $estilo['txt'] ?>;font-size:.75rem;">
                                        <?= h($estilo['label']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= h($r['entidade'] ?? '—') ?></td>
                                <td class="small text-truncate" style="max-width:300px;"><?= h($r['detalhe'] ?? '—') ?></td>
                                <td class="font-monospace small text-muted"><?= h($r['ip'] ?? '—') ?></td>
                            </tr>
                            <tr id="detalhe-<?= $i ?>" style="display:none;background:#f8f9fa;">
                                <td colspan="7" class="ps-4 py-2">
                                    <div class="row g-3 small">
                                        <div class="col-auto">
                                            <span class="text-muted">ID registo:</span>
                                            <strong class="font-monospace"><?= $r['id'] ?></strong>
                                        </div>
                                        <?php if ($r['utilizador_id']): ?>
                                        <div class="col-auto">
                                            <span class="text-muted">ID utilizador:</span>
                                            <strong class="font-monospace"><?= $r['utilizador_id'] ?></strong>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($r['entidade_id']): ?>
                                        <div class="col-auto">
                                            <span class="text-muted">ID entidade:</span>
                                            <strong class="font-monospace"><?= $r['entidade_id'] ?></strong>
                                        </div>
                                        <?php endif; ?>
                                        <div class="col-12">
                                            <span class="text-muted">Descrição completa:</span>
                                            <span><?= h($r['detalhe'] ?? '—') ?></span>
                                        </div>
                                        <div class="col-auto">
                                            <span class="text-muted">Data/Hora:</span>
                                            <span><?= h(date('d/m/Y \à\s H:i:s', strtotime($r['criado_em']))) ?></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginação -->
            <?php if ($paginas > 1): ?>
            <nav class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    Página <?= $pagina ?> de <?= $paginas ?> &mdash; <?= number_format($total) ?> registos
                </div>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina-1])) ?>">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    $inicio = max(1, $pagina - 2);
                    $fim    = min($paginas, $pagina + 2);
                    if ($inicio > 1):
                    ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina'=>1])) ?>">1</a></li>
                    <?php if ($inicio > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $inicio; $p <= $fim; $p++): ?>
                    <li class="page-item <?= $p===$pagina?'active':'' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$p])) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($fim < $paginas):
                        if ($fim < $paginas - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$paginas])) ?>"><?= $paginas ?></a></li>
                    <?php endif; ?>

                    <?php if ($pagina < $paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina+1])) ?>">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <!-- Nota RGPD -->
            <div class="alert alert-light border mt-4 small text-muted">
                <i class="fa-solid fa-circle-info me-2"></i>
                <strong>Art.&nbsp;30.º do RGPD</strong> — Este registo de auditoria é mantido em cumprimento do
                Regulamento Geral sobre a Proteção de Dados. Cada entrada identifica o responsável pelo tratamento,
                a data, a hora e a natureza da operação realizada sobre dados pessoais de saúde.
                Os registos são imutáveis e de acesso exclusivo aos administradores do sistema.
            </div>
        </main>

<script>
function toggleDetalhe(idx) {
    const row = document.getElementById('detalhe-' + idx);
    row.style.display = row.style.display === 'none' ? '' : 'none';
}
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
