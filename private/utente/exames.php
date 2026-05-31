<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Os Meus Exames'; $pagina_ativa = 'exames';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM utentes WHERE utilizador_id=?');
$stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();

$exames = [];
if ($utid) {
    $s = $db->prepare("
        SELECT pe.*, um.nome AS medico
        FROM pedidos_exame pe
        JOIN profissionais pm ON pm.id=pe.medico_id
        JOIN utilizadores um ON um.id=pm.utilizador_id
        WHERE pe.utente_id=?
        ORDER BY pe.data_pedido DESC
    ");
    $s->execute([$utid]); $exames = $s->fetchAll();
}

$estado_badge = ['pendente'=>'warning text-dark','realizado'=>'success','cancelado'=>'danger'];
$urgencia_badge = ['rotina'=>'secondary','urgente'=>'danger'];

require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
?>
        <main class="content">
            <h1 class="mb-4">Os Meus Exames</h1>
            <?php if (empty($exames)): ?>
                <div class="card p-4 text-center text-muted">
                    <i class="fa-solid fa-flask fa-3x mb-3 opacity-25"></i>
                    <p>Não tem pedidos de exame registados.</p>
                </div>
            <?php else: ?>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Exame</th><th>Categoria</th><th>Médico</th><th>Data Pedido</th><th>Urgência</th><th>Estado</th><th>Resultado</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($exames as $e): ?>
                        <tr>
                            <td><strong><?= h($e['tipo_exame']) ?></strong></td>
                            <td><?= h(ucfirst($e['categoria'])) ?></td>
                            <td><?= h($e['medico']) ?></td>
                            <td><?= h($e['data_pedido']) ?></td>
                            <td><span class="badge bg-<?= $urgencia_badge[$e['urgencia']] ?? 'secondary' ?>"><?= h(ucfirst($e['urgencia'])) ?></span></td>
                            <td><span class="badge bg-<?= $estado_badge[$e['estado']] ?? 'secondary' ?>"><?= h(ucfirst($e['estado'])) ?></span></td>
                            <td>
                                <?php if ($e['resultado']): ?>
                                    <small><?= h(substr($e['resultado'],0,80)) ?><?= strlen($e['resultado'])>80?'…':'' ?></small>
                                <?php else: ?>
                                    <span class="text-muted small">Aguarda resultado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
