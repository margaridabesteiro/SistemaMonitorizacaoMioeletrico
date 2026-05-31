<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Mensagens de Contacto'; $pagina_ativa = 'contactos';
$db = getDB();

// Marcar como lida se pedido
if (isset($_GET['marcar']) && is_numeric($_GET['marcar'])) {
    $db->prepare('UPDATE contactos SET lida=1 WHERE id=?')->execute([(int)$_GET['marcar']]);
    redirect(APP_URL . '/private/admin/contactos/lista_contactos.php');
}
// Marcar todas como lidas
if (isset($_GET['todas'])) {
    $db->query('UPDATE contactos SET lida=1');
    redirect(APP_URL . '/private/admin/contactos/lista_contactos.php');
}
// Apagar mensagem
if (isset($_GET['apagar']) && is_numeric($_GET['apagar'])) {
    $db->prepare('DELETE FROM contactos WHERE id=?')->execute([(int)$_GET['apagar']]);
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Mensagem apagada.'];
    redirect(APP_URL . '/private/admin/contactos/lista_contactos.php');
}

$filtro = $_GET['filtro'] ?? 'todas';
$where  = $filtro === 'nao_lidas' ? 'WHERE lida=0' : '';
$contactos    = $db->query("SELECT * FROM contactos $where ORDER BY criado_em DESC")->fetchAll();
$n_nao_lidas  = (int)$db->query("SELECT COUNT(*) FROM contactos WHERE lida=0")->fetchColumn();

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    Mensagens de Contacto
                    <?php if ($n_nao_lidas > 0): ?>
                        <span class="badge bg-danger ms-2"><?= $n_nao_lidas ?> novas</span>
                    <?php endif; ?>
                </h1>
                <?php if ($n_nao_lidas > 0): ?>
                    <a href="?todas=1" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-check-double me-1"></i>Marcar todas como lidas
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div>
            <?php endif; ?>

            <div class="d-flex gap-2 mb-3">
                <a href="?" class="btn btn-sm <?= $filtro==='todas'?'btn-danger':'btn-outline-secondary' ?>">Todas</a>
                <a href="?filtro=nao_lidas" class="btn btn-sm <?= $filtro==='nao_lidas'?'btn-danger':'btn-outline-secondary' ?>">
                    Não lidas <?php if ($n_nao_lidas > 0): ?><span class="badge bg-white text-danger ms-1"><?= $n_nao_lidas ?></span><?php endif; ?>
                </a>
            </div>

            <?php if (empty($contactos)): ?>
                <div class="card p-5 text-center text-muted">
                    <i class="fa-regular fa-envelope-open fa-3x mb-3 opacity-25"></i>
                    <p>Sem mensagens de contacto.</p>
                </div>
            <?php else: ?>
            <div class="d-flex flex-column gap-3">
            <?php foreach ($contactos as $c): ?>
                <div class="card p-3 <?= !$c['lida'] ? 'border-danger' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-bold"><?= h($c['nome']) ?></span>
                            <?php if (!$c['lida']): ?><span class="badge bg-danger ms-2">Nova</span><?php endif; ?>
                            <span class="text-muted small ms-2"><?= h($c['email']) ?></span>
                            <?php if ($c['telefone']): ?><span class="text-muted small ms-2"><i class="fa-solid fa-phone me-1"></i><?= h($c['telefone']) ?></span><?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small"><?= h(substr($c['criado_em'],0,16)) ?></span>
                            <?php if (!$c['lida']): ?>
                                <a href="?marcar=<?= $c['id'] ?>" class="btn btn-xs btn-outline-success" title="Marcar como lida">
                                    <i class="fa-solid fa-check"></i>
                                </a>
                            <?php endif; ?>
                            <a href="?apagar=<?= $c['id'] ?>"
                               class="btn btn-xs btn-outline-danger"
                               title="Apagar"
                               onclick="return confirm('Apagar esta mensagem?')">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </div>
                    </div>
                    <?php if ($c['assunto']): ?>
                        <div class="mt-1"><span class="badge bg-secondary"><?= h(ucfirst(str_replace('_',' ',$c['assunto']))) ?></span></div>
                    <?php endif; ?>
                    <p class="mt-2 mb-0 text-muted"><?= nl2br(h($c['mensagem'])) ?></p>
                    <div class="mt-2">
                        <a href="mailto:<?= h($c['email']) ?>" class="btn btn-xs btn-outline-primary">
                            <i class="fa-regular fa-envelope me-1"></i>Responder por email
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
