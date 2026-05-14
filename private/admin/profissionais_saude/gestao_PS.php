<?php
require_once __DIR__.'/../../../config/app.php';
require_once __DIR__.'/../../../config/database.php';
$pagina_titulo='Profissionais de Saúde'; $pagina_ativa='profissionais';
require_once __DIR__.'/../../../includes/header_admin.php';
require_once __DIR__.'/../../../includes/sidebar_admin.php';
$db=getDB(); $q=trim($_GET['q']??''); $fc=$_GET['cargo']??'';
$pa=max(1,(int)($_GET['pagina']??1)); $pp=15; $off=($pa-1)*$pp;
$wh='WHERE 1=1'; $par=[];
if($q!==''){$wh.=' AND (u.nome LIKE ? OR u.email LIKE ?)';$par[]="%$q%";$par[]="%$q%";}
if(in_array($fc,['medico','tecnico','admin'],true)){$wh.=' AND u.perfil=?';$par[]=$fc;}
$ts=$db->prepare("SELECT COUNT(*) FROM utilizadores u JOIN profissionais p ON p.utilizador_id=u.id $wh");
$ts->execute($par);$total=(int)$ts->fetchColumn();$tp=(int)ceil($total/$pp);
$st=$db->prepare("SELECT u.id,u.nome,u.email,u.perfil,u.ativo,p.especialidade,p.instituicao FROM utilizadores u JOIN profissionais p ON p.utilizador_id=u.id $wh ORDER BY u.nome LIMIT $pp OFFSET $off");
$st->execute($par);$prof=$st->fetchAll();
$fl=$_SESSION['flash']??null;unset($_SESSION['flash']);
?>
<main class="content">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1>Profissionais de Saúde</h1>
  <a href="registo_PS.php" class="btn btn-sm" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-user-plus me-2"></i>Novo</a>
</div>
<?php if($fl):?><div class="alert alert-<?=h($fl['tipo'])?> py-2"><?=h($fl['mensagem'])?></div><?php endif;?>
<form method="GET" class="row g-2 mb-3">
  <div class="col-md-5"><input type="text" name="q" class="form-control form-control-sm" placeholder="Nome ou email..." value="<?=h($q)?>"></div>
  <div class="col-md-3"><select name="cargo" class="form-select form-select-sm"><option value="">Todos</option><?php foreach(['medico','tecnico','admin'] as $p):?><option value="<?=$p?>" <?=$fc===$p?'selected':''?>><?=ucfirst($p)?></option><?php endforeach;?></select></div>
  <div class="col-md-2"><button type="submit" class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead class="table-light"><tr><th>Nome</th><th>Email</th><th>Perfil</th><th>Especialidade</th><th>Instituição</th><th>Estado</th><th>Ações</th></tr></thead>
<tbody><?php if(empty($prof)):?><tr><td colspan="7" class="text-center text-muted py-4">Sem resultados.</td></tr><?php else:foreach($prof as $p):?>
<tr><td><?=h($p['nome'])?></td><td><?=h($p['email'])?></td><td><span class="badge bg-secondary"><?=h($p['perfil'])?></span></td>
<td><?=h($p['especialidade']??'—')?></td><td><?=h($p['instituicao']??'—')?></td>
<td><?=$p['ativo']?'<span class="badge bg-success">Ativo</span>':'<span class="badge bg-danger">Inativo</span>'?></td>
<td><a href="editar_profissional.php?id=<?=$p['id']?>" class="btn btn-xs btn-outline-primary"><i class="fa-regular fa-pen-to-square"></i></a></td>
</tr><?php endforeach;endif;?></tbody></table></div></div>
<?php if($tp>1):?><nav class="mt-3"><ul class="pagination pagination-sm justify-content-end"><?php for($i=1;$i<=$tp;$i++):?><li class="page-item <?=$i===$pa?'active':''?>"><a class="page-link" href="?pagina=<?=$i?>&q=<?=urlencode($q)?>&cargo=<?=urlencode($fc)?>"><?=$i?></a></li><?php endfor;?></ul></nav><?php endif;?>
</main>
<?php require_once __DIR__.'/../../../includes/footer.php'; ?>