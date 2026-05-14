<?php
require_once __DIR__.'/../../../config/app.php';
require_once __DIR__.'/../../../config/database.php';
$pagina_titulo='Preferências'; $pagina_ativa='config';
$sucesso=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    // As preferências são aplicadas via sessão; sem tabela dedicada nesta versão
    $_SESSION['pref_notif_email'] = isset($_POST['notif_email'])?1:0;
    $_SESSION['pref_notif_sessao'] = isset($_POST['notif_sessao'])?1:0;
    $sucesso=true;
}
require_once __DIR__.'/../../../includes/header_tecnico.php';
require_once __DIR__.'/../../../includes/sidebar_tecnico.php';
?>
<main class="content">
<h1 class="mb-4">Preferências</h1>
<?php if($sucesso):?><div class="alert alert-success">Preferências guardadas.</div><?php endif;?>
<div class="card p-4" style="max-width:500px;"><form method="POST">
  <h6 class="mb-3 text-muted">Notificações</h6>
  <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="notif_email" id="ne" <?=($_SESSION['pref_notif_email']??0)?'checked':''?>><label class="form-check-label" for="ne">Receber notificações por email</label></div>
  <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="notif_sessao" id="ns" <?=($_SESSION['pref_notif_sessao']??1)?'checked':''?>><label class="form-check-label" for="ns">Alertas de início de sessão</label></div>
  <h6 class="mb-3 text-muted">Idioma</h6>
  <div class="mb-4"><select class="form-select" disabled><option>Português (PT)</option></select><div class="form-text">Apenas PT disponível.</div></div>
  <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
</form></div>
</main>
<?php require_once __DIR__.'/../../../includes/footer.php'; ?>
