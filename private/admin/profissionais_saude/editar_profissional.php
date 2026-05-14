<?php
require_once __DIR__.'/../../../config/app.php';
require_once __DIR__.'/../../../config/database.php';
$pagina_titulo='Editar Profissional'; $pagina_ativa='profissionais';
$id=(int)($_GET['id']??0);
if($id<=0) redirect(APP_URL.'/private/admin/profissionais_saude/gestao_PS.php');
$db=getDB(); $er=[];
$st=$db->prepare('SELECT u.id,u.nome,u.email,u.ativo,p.especialidade,p.instituicao,p.contacto,p.numero_ordem FROM utilizadores u JOIN profissionais p ON p.utilizador_id=u.id WHERE u.id=?');
$st->execute([$id]); $d=$st->fetch();
if(!$d) redirect(APP_URL.'/private/admin/profissionais_saude/gestao_PS.php');
if($_SERVER['REQUEST_METHOD']==='POST'){
  $d['nome']=trim($_POST['nome']??''); $d['email']=trim($_POST['email']??'');
  $d['especialidade']=trim($_POST['especialidade']??''); $d['instituicao']=trim($_POST['instituicao']??'');
  $d['contacto']=trim($_POST['contacto']??''); $d['numero_ordem']=trim($_POST['numero_ordem']??'');
  $d['ativo']=isset($_POST['ativo'])?1:0;
  if($d['nome']==='') $er[]='Nome obrigatório.';
  if(!filter_var($d['email'],FILTER_VALIDATE_EMAIL)) $er[]='Email inválido.';
  if(empty($er)){
    $db->prepare('UPDATE utilizadores SET nome=?,email=?,ativo=? WHERE id=?')->execute([$d['nome'],$d['email'],$d['ativo'],$id]);
    $db->prepare('UPDATE profissionais SET especialidade=?,instituicao=?,contacto=?,numero_ordem=? WHERE utilizador_id=?')->execute([$d['especialidade'],$d['instituicao'],$d['contacto'],$d['numero_ordem'],$id]);
    $_SESSION['flash']=['tipo'=>'success','mensagem'=>'Profissional atualizado.']; redirect(APP_URL.'/private/admin/profissionais_saude/gestao_PS.php');
  }
}
require_once __DIR__.'/../../../includes/header_admin.php';
require_once __DIR__.'/../../../includes/sidebar_admin.php';
?>
<main class="content">
<div class="d-flex align-items-center gap-3 mb-4"><a href="gestao_PS.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a><h1 class="mb-0">Editar Profissional</h1></div>
<?php if(!empty($er)):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($er as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="card p-4" style="max-width:650px;"><form method="POST">
  <div class="row"><div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" value="<?=h($d['nome'])?>" required></div><div class="col-md-6 mb-3"><label class="form-label fw-semibold">Email *</label><input type="email" name="email" class="form-control" value="<?=h($d['email'])?>" required></div></div>
  <div class="row"><div class="col-md-6 mb-3"><label class="form-label fw-semibold">Especialidade</label><input type="text" name="especialidade" class="form-control" value="<?=h($d['especialidade']??'')?>"></div><div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nº Cédula</label><input type="text" name="numero_ordem" class="form-control" value="<?=h($d['numero_ordem']??'')?>"></div></div>
  <div class="row"><div class="col-md-6 mb-3"><label class="form-label fw-semibold">Instituição</label><input type="text" name="instituicao" class="form-control" value="<?=h($d['instituicao']??'')?>"></div><div class="col-md-6 mb-3"><label class="form-label fw-semibold">Contacto</label><input type="text" name="contacto" class="form-control" value="<?=h($d['contacto']??'')?>"></div></div>
  <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?=$d['ativo']?'checked':''?>><label class="form-check-label" for="ativo">Conta ativa</label></div>
  <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
</form></div>
</main>
<?php require_once __DIR__.'/../../../includes/footer.php'; ?>