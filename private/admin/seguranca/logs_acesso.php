<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Logs de Acesso'; $pagina_ativa = 'seguranca';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
$filtro_acao = $_GET['acao'] ?? '';
$filtro_data = $_GET['data'] ?? '';
$pagina_atual = max(1,(int)($_GET['pagina'] ?? 1)); $por_pagina = 25; $offset = ($pagina_atual-1)*$por_pagina;
$where = 'WHERE 1=1'; $params = [];
if ($filtro_acao !== '') { $where .= ' AND l.acao = ?'; $params[] = $filtro_acao; }
if ($filtro_data !== '') { $where .= ' AND DATE(l.criado_em) = ?'; $params[] = $filtro_data; }
$cnt = $db->prepare("SELECT COUNT(*) FROM logs_acesso l $where"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
$stmt = $db->prepare("SELECT l.acao, l.ip, l.user_agent, l.criado_em, l.detalhes, u.nome FROM logs_acesso l LEFT JOIN utilizadores u ON u.id=l.utilizador_id $where ORDER BY l.criado_em DESC LIMIT $por_pagina OFFSET $offset");
$stmt->execute($params); $logs = $stmt->fetchAll();
$acoes = $db->query("SELECT DISTINCT acao FROM logs_acesso ORDER BY acao")->fetchAll(\PDO::FETCH_COLUMN);
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4"><h1>Logs de Acesso</h1><span class="badge bg-secondary"><?= $total ?> registos</span></div>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><select name="acao" class="form-select form-select-sm"><option value="">Todas as ações</option><?php foreach($acoes as $a): ?><option value="<?= h($a) ?>" <?= $filtro_acao===$a?'selected':'' ?>><?= h($a) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><input type="date" name="data" class="form-control form-control-sm" value="<?= h($filtro_data) ?>"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
                <div class="col-md-2"><a href="logs_acesso.php" class="btn btn-sm btn-outline-secondary w-100">Limpar</a></div>
            </form>
            <div class="card"><div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Data/Hora</th><th>Utilizador</th><th>Ação</th><th>IP</th><th>Detalhes</th></tr></thead>
                    <tbody>
                    <?php if(empty($logs)): ?><tr><td colspan="5" class="text-center text-muted py-4">Sem registos.</td></tr>
                    <?php else: foreach($logs as $l): ?>
                        <tr>
                            <td><?= h(substr($l['criado_em'],0,16)) ?></td>
                            <td><?= h($l['nome'] ?? '<em>Anónimo</em>') ?></td>
                            <td><span class="badge bg-<?= str_contains($l['acao'],'falh')||str_contains($l['acao'],'neg')?'danger':'secondary' ?>"><?= h($l['acao']) ?></span></td>
                            <td><?= h($l['ip']) ?></td>
                            <td><small class="text-muted"><?= h(substr($l['detalhes']??'',0,60)) ?></small></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
