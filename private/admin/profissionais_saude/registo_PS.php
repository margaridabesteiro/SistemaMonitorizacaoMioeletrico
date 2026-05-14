<?php
require_once __DIR__.'/../../../config/app.php';
require_once __DIR__.'/../../../config/database.php';
$pagina_titulo='Registo de Profissional'; $pagina_ativa='profissionais';
$er=[]; $d=['perfil'=>'','nome'=>'','email'=>'','especialidade'=>'','instituicao'=>'','contacto'=>''];
if($_SERVER['REQUEST_METHOD']==='POST'){
  foreach($d as $k=>$v) $d[$k]=trim($_POST[$k]??'');
  $pw=$_POST['password']??''; $cf=$_POST['password_conf']??'';
  if($d['nome']==='') $er[]='Nome obrigatório.';
  if(!filter_var($d['email'],FILTER_VALIDATE_EMAIL)) $er[]='Email inválido.';
  if(!in_array($d['perfil'],['medico','tecnico','admin'],true)) $er[]='Perfil inválido.';
  if(strlen($pw)<8) $er[]='Password mínimo 8 caracteres.';
  if($pw!==$cf) $er[]='Passwords não coincidem.';
  if(empty($er)){
    $db=getDB();
    $ex=$db->prepare('SELECT id FROM utilizadores WHERE email=?');$ex->execute([$d['email']]);
    if($ex->fetch()){$er[]='Email já existe.';}
    else{
      $hash=password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]);
      $db->prepare('INSERT INTO utilizadores(nome,email,password_hash,perfil,ativo)VALUES(?,?,?,?,1)')->execute([$d['nome'],$d['email'],$hash,$d['perfil']]);
      $uid=(int)$db->lastInsertId();
      $db->prepare('INSERT INTO profissionais(utilizador_id,especialidade,instituicao,contacto)VALUES(?,?,?,?)')->execute([$uid,$d['especialidade'],$d['instituicao'],$d['contacto']]);
      $_SESSION['flash']=['tipo'=>'success','mensagem'=>'Profissional registado.'];
      redirect(APP_URL.'/private/admin/profissionais_saude/gestao_PS.php');
    }
  }
}
require_once __DIR__.'/../../../includes/header_admin.php';
require_once __DIR__.'/../../../includes/sidebar_admin.php';
?>
<main class="content">
<div class="d-flex align-items-center gap-3 mb-4"><a href="gestao_PS.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a><h1 class="mb-0">Registo de Profissional</h1></div>
<?php if(!empty($er)):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($er as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="card p-4" style="max-width:650px;"><form method="POST">
  <div class="row">
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Tipo *</label><select name="perfil" class="form-select" required><option value="">--</option><?php foreach(['medico','tecnico','admin'] as $p):?><option value="<?=$p?>" <?=($d['perfil']===$p)?'selected':''?>><?=ucfirst($p)?></option><?php endforeach;?></select></div>
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" value="<?=h($d['nome'])?>" required></div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Email *</label><input type="email" name="email" class="form-control" value="<?=h($d['email'])?>" required></div>
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Especialidade</label><input type="text" name="especialidade" class="form-control" value="<?=h($d['especialidade'])?>"></div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Instituição</label><input type="text" name="instituicao" class="form-control" value="<?=h($d['instituicao'])?>"></div>
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Contacto</label><input type="text" name="contacto" class="form-control" value="<?=h($d['contacto'])?>"></div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Password *</label><input type="password" name="password" class="form-control" required></div>
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Confirmar *</label><input type="password" name="password_conf" class="form-control" required></div>
  </div>
  <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Registar</button>
</form></div>
</main>
<?php require_once __DIR__.'/../../../includes/footer.php'; ?>