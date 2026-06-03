<?php
require_once __DIR__.'/../../../config/app.php';
require_once __DIR__.'/../../../config/database.php';
$pagina_titulo='Comparação de Pacientes'; $pagina_ativa='analise';
$js_head=['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__.'/../../../includes/header_tecnico.php';
require_once __DIR__.'/../../../includes/sidebar_tecnico.php';
$db=getDB();
$st=$db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $st->execute([$_SESSION['utilizador_id']]); $pid=(int)($st->fetchColumn()?:0);
$utentes=$db->prepare('SELECT ut.id,u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.tecnico_id=? AND u.ativo=1 ORDER BY u.nome'); $utentes->execute([$pid]); $utentes=$utentes->fetchAll();
$id1=(int)($_GET['p1']??0); $id2=(int)($_GET['p2']??0);
$dados=[];
foreach([$id1,$id2] as $uid){
    if(!$uid) continue;
    $s=$db->prepare('SELECT ms.score_jogo,ms.percentagem_final,s.data_hora FROM metricas_sessao ms JOIN sessoes s ON s.id=ms.sessao_id WHERE s.utente_id=? AND s.estado="concluida" ORDER BY s.data_hora DESC LIMIT 10'); $s->execute([$uid]); $dados[$uid]=$s->fetchAll();
}
?>
<main class="content">
<h1 class="mb-4">Comparação entre Pacientes</h1>
<form method="GET" class="row g-2 mb-4">
  <div class="col-md-4"><label class="form-label fw-semibold">Paciente A</label><select name="p1" class="form-select"><option value="">-- Selecionar --</option><?php foreach($utentes as $u):?><option value="<?=$u['id']?>" <?=$id1===$u['id']?'selected':''?>><?=h($u['nome'])?></option><?php endforeach;?></select></div>
  <div class="col-md-4"><label class="form-label fw-semibold">Paciente B</label><select name="p2" class="form-select"><option value="">-- Selecionar --</option><?php foreach($utentes as $u):?><option value="<?=$u['id']?>" <?=$id2===$u['id']?'selected':''?>><?=h($u['nome'])?></option><?php endforeach;?></select></div>
  <div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn w-100" style="background:#8B0000;color:#fff;">Comparar</button></div>
</form>
<?php if($id1 && $id2 && isset($dados[$id1],$dados[$id2])):?>
<div class="card p-4">
  <canvas id="chartComp" height="100"></canvas>
</div>
<script>
const labels = <?=json_encode(array_map(fn($r)=>substr($r['data_hora'],0,10), $dados[$id1]??[]))?>;
new Chart(document.getElementById('chartComp'),{type:'line',data:{labels,datasets:[
  {label:'Paciente A - Score',data:<?=json_encode(array_map(fn($r)=>$r['score_jogo'],$dados[$id1]??[]))?>,borderColor:'#8B0000',tension:0.3,fill:false},
  {label:'Paciente B - Score',data:<?=json_encode(array_map(fn($r)=>$r['score_jogo'],$dados[$id2]??[]))?>,borderColor:'#1a5f8a',tension:0.3,fill:false}
]},options:{responsive:true,plugins:{legend:{position:'top'}}}});
</script>
<?php elseif($id1||$id2):?><div class="alert alert-info">Selecione dois pacientes para comparar.</div><?php endif;?>
</main>
<?php require_once __DIR__.'/../../../includes/footer.php'; ?>
