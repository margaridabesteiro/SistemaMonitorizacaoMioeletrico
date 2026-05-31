<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Faturação'; $pagina_ativa = 'faturacao';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';

$db = getDB();
$filtro_estado  = $_GET['estado']  ?? '';
$filtro_periodo = (int)($_GET['periodo'] ?? 30);
$pagina_atual   = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 20; $offset = ($pagina_atual - 1) * $por_pagina;

$where = "WHERE f.data_emissao >= DATE_SUB(NOW(), INTERVAL $filtro_periodo DAY)"; $params = [];
if (in_array($filtro_estado, ['0','1'], true)) { $where .= ' AND f.paga = ?'; $params[] = $filtro_estado; }

$total_val = $db->prepare("SELECT COALESCE(SUM(valor_eur),0), COUNT(*) FROM faturas f $where");
$total_val->execute($params);
[$soma,$cnt] = $total_val->fetch(PDO::FETCH_NUM);
$pagas    = (int)$db->query("SELECT COUNT(*) FROM faturas WHERE paga=1")->fetchColumn();
$pendentes = (int)$db->query("SELECT COUNT(*) FROM faturas WHERE paga=0")->fetchColumn();
$stmt = $db->prepare("SELECT f.*, u.nome AS utente FROM faturas f JOIN utentes ut ON ut.id=f.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id $where ORDER BY f.data_emissao DESC LIMIT $por_pagina OFFSET $offset");
$stmt->execute($params); $faturas = $stmt->fetchAll();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Controlo de Faturação</h1>
                <a href="nova_fatura.php" class="btn btn-sm" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-plus me-1"></i>Nova Fatura</a>
            </div>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>
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
                    <thead class="table-light"><tr><th>Nº Fatura</th><th>Utente</th><th>Valor</th><th>Emissão</th><th>Vencimento</th><th>Estado</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php if(empty($faturas)): ?><tr><td colspan="7" class="text-center text-muted py-4">Sem faturas.</td></tr>
                    <?php else: foreach($faturas as $f): ?>
                        <tr>
                            <td><?= h($f['numero']) ?></td><td><?= h($f['utente']) ?></td>
                            <td class="fw-bold"><?= number_format((float)$f['valor_eur'],2,',','.') ?>€</td>
                            <td><?= h($f['data_emissao']) ?></td><td><?= h($f['data_vencimento'] ?? '—') ?></td>
                            <td><?= $f['paga'] ? '<span class="badge bg-success">Paga</span>' : '<span class="badge bg-warning text-dark">Pendente</span>' ?></td>
                            <td>
                                <a href="fatura.php?id=<?= $f['id'] ?>" class="btn btn-xs btn-outline-primary me-1" title="Ver"><i class="fa-regular fa-eye"></i></a>
                                <a href="editar_fatura.php?id=<?= $f['id'] ?>" class="btn btn-xs btn-outline-secondary me-1" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                <a href="<?= APP_URL ?>/api/admin/faturacao/toggle_paga.php?id=<?= $f['id'] ?>"
                                   class="btn btn-xs <?= $f['paga'] ? 'btn-outline-warning' : 'btn-outline-success' ?> me-1"
                                   title="<?= $f['paga'] ? 'Marcar pendente' : 'Marcar paga' ?>">
                                    <i class="fa-solid fa-<?= $f['paga'] ? 'rotate-left' : 'check' ?>"></i>
                                </a>
                                <a href="apagar_fatura.php?id=<?= $f['id'] ?>" class="btn btn-xs btn-outline-danger" title="Apagar"><i class="fa-regular fa-trash-can"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
