<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('tecnico');

$pagina_titulo = 'Histórico de Empréstimos'; $pagina_ativa = 'dispositivos';

$db    = getDB();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

// Filtros via GET
$f_disp = (int)($_GET['dispositivo_id'] ?? 0);

// Lista de dispositivos para o filtro
$todos_disp = $db->query("SELECT id, codigo FROM dispositivos ORDER BY codigo")->fetchAll();

// Query com filtros dinâmicos
$where  = ['1=1'];
$params = [];

if ($f_disp) {
    $where[]  = 'e.dispositivo_id = ?';
    $params[] = $f_disp;
}

$sql = "
    SELECT e.*, d.codigo,
           u.nome   AS utente,
           ut2.nome AS tecnico
    FROM emprestimos_dispositivos e
    JOIN dispositivos d  ON d.id  = e.dispositivo_id
    JOIN utentes ut      ON ut.id = e.utente_id
    JOIN utilizadores u  ON u.id  = ut.utilizador_id
    LEFT JOIN profissionais p   ON p.id   = e.tecnico_id
    LEFT JOIN utilizadores ut2  ON ut2.id = p.utilizador_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.data_entrega DESC
    LIMIT 100
";
$s = $db->prepare($sql);
$s->execute($params);
$emprestimos = $s->fetchAll();

require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Histórico de Empréstimos</h1>
                <div class="d-flex gap-2">
                    <a href="lista_dispositivos.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-microchip me-1"></i>Dispositivos
                    </a>
                    <a href="novo_emprestimo.php" class="btn btn-sm" style="background:#1a5f8a;color:#fff;">
                        <i class="fa-solid fa-plus me-1"></i>Novo Empréstimo
                    </a>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div>
            <?php endif; ?>

            <!-- Filtros -->
            <form method="GET" class="card p-3 mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold mb-1 small">Dispositivo</label>
                        <select name="dispositivo_id" class="form-select form-select-sm">
                            <option value="">— Todos —</option>
                            <?php foreach ($todos_disp as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $f_disp === $d['id'] ? 'selected' : '' ?>>
                                    <?= h($d['codigo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-filter me-1"></i>Filtrar
                        </button>
                        <a href="emprestimos.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
                    </div>
                </div>
            </form>

            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Dispositivo</th>
                            <th>Utente</th>
                            <th>Técnico</th>
                            <th>Entrega</th>
                            <th>Dev. Prevista</th>
                            <th>Devolvido</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($emprestimos)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Sem registos.</td></tr>
                    <?php else: foreach ($emprestimos as $e): ?>
                        <tr>
                            <td><strong><?= h($e['codigo']) ?></strong></td>
                            <td><?= h($e['utente']) ?></td>
                            <td><?= h($e['tecnico'] ?? '—') ?></td>
                            <td class="small"><?= h(substr($e['data_entrega'],0,10)) ?></td>
                            <td class="small"><?= $e['data_prevista_devolucao'] ? h(substr($e['data_prevista_devolucao'],0,10)) : '—' ?></td>
                            <td class="small"><?= !empty($e['data_devolucao']) ? h(substr($e['data_devolucao'],0,10)) : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <?php if (!empty($e['data_devolucao']) && !empty($e['estado_devolucao'])): ?>
                                    <span class="badge bg-<?= $e['estado_devolucao']==='bom' ? 'success' : ($e['estado_devolucao']==='danificado' ? 'warning text-dark' : 'danger') ?>">
                                        <?= h(ucfirst($e['estado_devolucao'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Em curso</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
