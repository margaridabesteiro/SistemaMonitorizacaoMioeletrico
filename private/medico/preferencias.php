<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('medico');
$pagina_titulo = 'Preferências'; $pagina_ativa = 'preferencias';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];

$stmt = $db->prepare('SELECT * FROM preferencias_utilizador WHERE utilizador_id=?');
$stmt->execute([$uid]); $prefs = $stmt->fetch();
if (!$prefs) {
    $db->prepare('INSERT IGNORE INTO preferencias_utilizador (utilizador_id) VALUES (?)')->execute([$uid]);
    $prefs = ['notif_email'=>1,'notif_inicio_sessao'=>1];
}

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $notif_email  = isset($_POST['notif_email'])          ? 1 : 0;
    $notif_sessao = isset($_POST['notif_inicio_sessao'])  ? 1 : 0;
    $db->prepare('UPDATE preferencias_utilizador SET notif_email=?, notif_inicio_sessao=? WHERE utilizador_id=?')
       ->execute([$notif_email, $notif_sessao, $uid]);
    $flash = ['tipo'=>'success','mensagem'=>'Preferências guardadas.'];
    $prefs['notif_email'] = $notif_email;
    $prefs['notif_inicio_sessao'] = $notif_sessao;
}

require_once __DIR__ . '/../../includes/header_medico.php';
require_once __DIR__ . '/../../includes/sidebar_medico.php';
?>
        <main class="content">
            <h1 class="mb-4">Preferências</h1>
            <?php if ($flash): ?><div class="alert alert-<?=h($flash['tipo'])?> py-2"><?=h($flash['mensagem'])?></div><?php endif; ?>
            <div class="card p-4" style="max-width:500px;">
                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <h5 class="mb-3">Notificações</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="notif_email" id="notifEmail" <?=$prefs['notif_email']?'checked':''?>>
                        <label class="form-check-label" for="notifEmail">Receber notificações por email</label>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="notif_inicio_sessao" id="notifSessao" <?=$prefs['notif_inicio_sessao']?'checked':''?>>
                        <label class="form-check-label" for="notifSessao">Alertas de início de sessão</label>
                    </div>
                    <hr><h5 class="mb-3 mt-3">Idioma</h5>
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="badge bg-danger py-2 px-3">PT — Português (PT)</span>
                        <small class="text-muted">Apenas PT disponível</small>
                    </div>
                    <button type="submit" class="btn w-100" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar Alterações</button>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
