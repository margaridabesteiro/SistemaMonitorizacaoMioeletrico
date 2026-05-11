<?php
// private/tecnico/sessoes/lista_sessoes.php
// Lista de sessões de treino geridas pelo técnico

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';

$pagina_titulo = 'Sessões de Treino';
$pagina_ativa  = 'sessoes';

require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';

$db         = getDB();
$tecnico_id_utilizador = (int)$_SESSION['utilizador_id'];

// Obter profissional_id do técnico
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id = ?');
$stmt->execute([$tecnico_id_utilizador]);
$prof_id = (int)($stmt->fetchColumn() ?: 0);

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_data   = $_GET['data']   ?? '';
$pagina_atual  = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina    = 20;
$offset        = ($pagina_atual - 1) * $por_pagina;

$where  = $prof_id ? 'WHERE s.tecnico_id = ?' : 'WHERE 1=0';
$params = $prof_id ? [$prof_id] : [];

if (in_array($filtro_estado, ['agendada','em_curso','concluida','cancelada'], true)) {
    $where   .= ' AND s.estado = ?';
    $params[] = $filtro_estado;
}
if ($filtro_data !== '') {
    $where   .= ' AND DATE(s.data_hora) = ?';
    $params[] = $filtro_data;
}

$count_stmt = $db->prepare("SELECT COUNT(*) FROM sessoes s $where");
$count_stmt->execute($params);
$total_registos = (int)$count_stmt->fetchColumn();
$total_paginas  = (int)ceil($total_registos / $por_pagina);

$stmt = $db->prepare("
    SELECT s.id, s.data_hora, s.duracao_min, s.tipo, s.estado,
           u.nome AS paciente,
           d.codigo AS dispositivo
    FROM sessoes s
    JOIN utentes ut ON ut.id = s.utente_id
    JOIN utilizadores u ON u.id = ut.utilizador_id
    LEFT JOIN dispositivos d ON d.id = s.dispositivo_id
    $where
    ORDER BY s.data_hora DESC
    LIMIT $por_pagina OFFSET $offset
");
$stmt->execute($params);
$sessoes = $stmt->fetchAll();

$cores_estado = [
    'agendada'  => 'primary',
    'em_curso'  => 'warning',
    'concluida' => 'success',
    'cancelada' => 'danger',
];
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Sessões de Treino</h1>
                <a href="nova_sessao.php" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                    <i class="fa-solid fa-plus me-1"></i>Nova Sessão
                </a>
            </div>

            <!-- Filtros -->
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="date" name="data" class="form-control form-control-sm"
                           value="<?= h($filtro_data) ?>">
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos os estados</option>
                        <?php foreach (['agendada','em_curso','concluida','cancelada'] as $e): ?>
                            <option value="<?= $e ?>" <?= $filtro_estado === $e ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('_',' ',$e)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">Filtrar</button>
                </div>
                <div class="col-md-2">
                    <a href="lista_sessoes.php" class="btn btn-sm btn-outline-secondary w-100">Limpar</a>
                </div>
            </form>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data/Hora</th><th>Paciente</th><th>Tipo</th>
                                <th>Duração</th><th>Dispositivo</th><th>Estado</th><th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($sessoes)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Sem sessões.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sessoes as $s): ?>
                            <tr>
                                <td><?= h(substr($s['data_hora'],0,16)) ?></td>
                                <td><?= h($s['paciente']) ?></td>
                                <td><?= h($s['tipo'] ?? '—') ?></td>
                                <td><?= $s['duracao_min'] ? h($s['duracao_min']).' min' : '—' ?></td>
                                <td><?= h($s['dispositivo'] ?? '—') ?></td>
                                <td>
                                    <span class="badge bg-<?= $cores_estado[$s['estado']] ?? 'secondary' ?>">
                                        <?= h(str_replace('_',' ',$s['estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detalhes_sessao.php?id=<?= $s['id'] ?>"
                                       class="btn btn-xs btn-outline-primary me-1" title="Detalhes">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                    <?php if ($s['estado'] === 'agendada'): ?>
                                    <a href="iniciar_sessao.php?id=<?= $s['id'] ?>"
                                       class="btn btn-xs btn-outline-success" title="Iniciar">
                                        <i class="fa-solid fa-play"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_paginas > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm justify-content-end">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= $i === $pagina_atual ? 'active' : '' ?>">
                            <a class="page-link"
                               href="?pagina=<?= $i ?>&estado=<?= urlencode($filtro_estado) ?>&data=<?= urlencode($filtro_data) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
