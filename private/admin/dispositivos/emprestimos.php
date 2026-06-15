<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Empréstimos de Dispositivos'; $pagina_ativa = 'dispositivos';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$emprestimos = $db->query("
    SELECT e.*, d.codigo, u.nome AS utente, ut2.nome AS tecnico
    FROM emprestimos_dispositivos e
    JOIN dispositivos d ON d.id=e.dispositivo_id
    JOIN utentes ut ON ut.id=e.utente_id
    JOIN utilizadores u ON u.id=ut.utilizador_id
    LEFT JOIN profissionais p ON p.id=e.tecnico_id
    LEFT JOIN utilizadores ut2 ON ut2.id=p.utilizador_id
    ORDER BY e.data_entrega DESC LIMIT 100
")->fetchAll();
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Empréstimos de Dispositivos</h1>
                <a href="novo_emprestimo.php" class="btn btn-sm" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-plus me-1"></i>Novo Empréstimo</a>
            </div>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Dispositivo</th><th>Utente</th><th>Técnico</th><th>Entrega</th><th>Devolução Prevista</th><th>Devolvido</th><th>Estado Devolução</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php if(empty($emprestimos)): ?><tr><td colspan="8" class="text-center text-muted py-4">Sem registos.</td></tr>
                    <?php else: foreach($emprestimos as $e): ?>
                        <tr>
                            <td><strong><?= h($e['codigo']) ?></strong></td>
                            <td><?= h($e['utente']) ?></td>
                            <td><?= h($e['tecnico'] ?? '—') ?></td>
                            <td><?= h(substr($e['data_entrega'],0,10)) ?></td>
                            <td><?= $e['data_prevista_devolucao'] ? h(substr($e['data_prevista_devolucao'],0,10)) : '—' ?></td>
                            <td><?= !empty($e['data_devolucao']) ? h(substr($e['data_devolucao'],0,10)) : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <?php if (!empty($e['data_devolucao']) && !empty($e['estado_devolucao'])): ?>
                                <span class="badge bg-<?= $e['estado_devolucao']==='bom'?'success':($e['estado_devolucao']==='danificado'?'warning':'danger') ?>"><?= h($e['estado_devolucao']) ?></span>
                                <?php else: ?>
                                <span class="badge bg-primary">Em curso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (empty($e['data_devolucao'])): ?>
                                    <a href="devolver_dispositivo.php?emp=<?= $e['id'] ?>" class="btn btn-xs btn-outline-warning" title="Registar devolução"><i class="fa-solid fa-arrow-right-to-bracket"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
