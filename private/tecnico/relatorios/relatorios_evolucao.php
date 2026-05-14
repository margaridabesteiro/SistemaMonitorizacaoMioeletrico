<?php
require_once __DIR__.'/../../../config/app.php';
require_once __DIR__.'/../../../config/database.php';
$pagina_titulo='Relatórios de Evolução'; $pagina_ativa='relatorios';
$js_head=['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__.'/../../../includes/header_tecnico.php';
require_once __DIR__.'/../../../includes/sidebar_tecnico.php';
$db=getDB();
$st=$db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $st->execute([$_SESSION['utilizador_id']]); $pid=(int)($st->fetchColumn()?:0);
$utentes=$db->prepare('SELECT ut.id,u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.tecnico_id=? AND u.ativo=1 ORDER BY u.nome'); $utentes->execute([$pid]); $utentes=$utentes->fetchAll();
$sel=(int)($_GET['utente_id']??($utentes[0]['id']??0));
$evol=[];
if($sel){
    $s=$db->prepare('SELECT s.data_hora,ms.rms_uv,ms.score_jogo FROM metricas_sessao ms JOIN sessoes s ON s.id=ms.sessao_id WHERE s.utente_id=? AND s.estado="concluida" ORDER BY s.data_hora ASC LIMIT 20'); $s->execute([$sel]); $evol=$s->fetchAll();
}
?>
<main class="content">
<h1 class="mb-4">Relatórios de Evolução</h1>
<form method="GET" class="row g-2 mb-4">
  <div class="col-md-5"><select name="utente_id" class="form-select"><option value="">-- Selecionar paciente --</option><?php foreach($utentes as $u):?><option value="<?=$u['id']?>" <?=$sel===$u['id']?'selected':''?>><?=h($u['nome'])?></option><?php endforeach;?></select></div>
  <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Mostrar</button></div>
</form>
<?php if(!empty($evol)):?>
<div class="card p-4 mb-4"><canvas id="chartEvol" height="80"></canvas></div>
<script>
new Chart(document.getElementById('chartEvol'),{type:'line',data:{
  labels:<?=json_encode(array_map(fn($r)=>substr($r['data_hora'],0,10),$evol))?>,
  datasets:[{label:'RMS (µV)',data:<?=json_encode(array_map(fn($r)=>round((float)$r['rms_uv'],2),$evol))?>,borderColor:'#8B0000',tension:0.3,fill:false}]
},options:{responsive:true,plugins:{legend:{position:'top'}},scales:{y:{title:{display:true,text:'µV'}}}}});
</script>
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
  <thead class="table-light"><tr><th>Data</th><th>RMS (µV)</th><th>Score Jogo</th></tr></thead>
  <tbody><?php foreach($evol as $r):?><tr><td><?=h(substr($r['data_hora'],0,10))?></td><td><?=number_format((float)$r['rms_uv'],2)?></td><td><?=h($r['score_jogo']??'—')?></td></tr><?php endforeach;?></tbody>
</table></div></div>
<?php elseif($sel):?><div class="alert alert-info">Sem dados de evolução para este paciente.</div><?php endif;?>
</main>
<?php require_once __DIR__.'/../../../includes/footer.php'; ?>
