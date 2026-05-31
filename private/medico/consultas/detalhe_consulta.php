<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');
$pagina_titulo = 'Detalhe Consulta'; $pagina_ativa = 'consultas';
$db = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/medico/consultas/consulta.php');

$stmt = $db->prepare("SELECT c.*, u.nome AS paciente, ut.id AS utente_id FROM consultas c JOIN utentes ut ON ut.id=c.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE c.id=? AND c.medico_id=?");
$stmt->execute([$id, $pid]); $c = $stmt->fetch();
if (!$c) redirect(APP_URL . '/private/medico/consultas/consulta.php');

// Medicação prescrita nesta consulta
$medicacao = $db->prepare("SELECT * FROM prescricoes_medicacao WHERE consulta_id=?");
$medicacao->execute([$id]); $medicacao = $medicacao->fetchAll();

// Exames pedidos nesta consulta
$exames = $db->prepare("SELECT * FROM pedidos_exame WHERE consulta_id=?");
$exames->execute([$id]); $exames = $exames->fetchAll();

$tipo_badge = ['inicial'=>'info','rotina'=>'secondary','alta'=>'success','urgente'=>'danger'];
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="consulta.php">Consultas</a></li>
                    <li class="breadcrumb-item active">Consulta #<?= $id ?></li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Detalhe da Consulta</h1>
                <div class="d-flex gap-2">
                    <a href="consulta.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
                    <?php if ($c['modalidade']==='video' && $c['link_videochamada']): ?>
                        <a href="<?=h($c['link_videochamada'])?>" target="_blank" class="btn btn-sm btn-primary"><i class="fa-solid fa-video me-1"></i>Entrar na Videochamada</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($flash): ?><div class="alert alert-<?=h($flash['tipo'])?> py-2"><?=h($flash['mensagem'])?></div><?php endif; ?>

            <div class="card p-3 mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Paciente:</strong> <?=h($c['paciente'])?></p>
                        <p><strong>Data/Hora:</strong> <?=h(substr($c['data_hora'],0,16))?></p>
                        <p><strong>Tipo:</strong> <span class="badge bg-<?=$tipo_badge[$c['tipo']]??'secondary'?>"><?=h(ucfirst($c['tipo']))?></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Modalidade:</strong> <?=$c['modalidade']==='video'?'<span class="badge bg-primary">Vídeo</span>':'<span class="badge bg-secondary">Presencial</span>'?></p>
                        <p><strong>Estado:</strong> <span class="badge bg-<?=['agendada'=>'primary','realizada'=>'success','cancelada'=>'danger'][$c['estado']]??'secondary'?>"><?=h($c['estado'])?></span></p>
                        <?php if ($c['evolucao']): ?><p><strong>Evolução:</strong> <?=h(ucfirst($c['evolucao']))?></p><?php endif; ?>
                    </div>
                </div>
                <?php if ($c['motivo']): ?><p class="mb-0"><strong>Motivo:</strong> <?=h($c['motivo'])?></p><?php endif; ?>
                <?php if ($c['notas']): ?><p class="mb-0 mt-2"><strong>Notas:</strong> <span class="text-muted"><?=h($c['notas'])?></span></p><?php endif; ?>
            </div>

            <!-- Medicação -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="fa-solid fa-pills me-2 text-primary"></i>Medicação Prescrita</h5>
                <a href="nova_prescricao_medicacao.php?consulta_id=<?=$id?>&utente_id=<?=$c['utente_id']?>" class="btn btn-xs btn-outline-primary"><i class="fa-solid fa-plus me-1"></i>Adicionar</a>
            </div>
            <?php if(empty($medicacao)): ?>
                <p class="text-muted small mb-4">Sem medicação prescrita nesta consulta.</p>
            <?php else: ?>
            <div class="card mb-4"><div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Medicamento</th><th>Dosagem</th><th>Posologia</th><th>Início</th><th>Fim</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach($medicacao as $m): ?>
                        <tr>
                            <td><?=h($m['medicamento'])?></td>
                            <td><?=h($m['dosagem'])?></td>
                            <td><small><?=h($m['posologia'])?></small></td>
                            <td><?=h($m['data_inicio'])?></td>
                            <td><?=$m['data_fim']?h($m['data_fim']):'Contínuo'?></td>
                            <td><?=$m['ativa']?'<span class="badge bg-success">Ativa</span>':'<span class="badge bg-secondary">Inativa</span>'?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
            <?php endif; ?>

            <!-- Exames -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="fa-solid fa-flask me-2 text-warning"></i>Exames Pedidos</h5>
                <a href="novo_pedido_exame.php?consulta_id=<?=$id?>&utente_id=<?=$c['utente_id']?>" class="btn btn-xs btn-outline-warning"><i class="fa-solid fa-plus me-1"></i>Pedir Exame</a>
            </div>
            <?php if(empty($exames)): ?>
                <p class="text-muted small">Sem exames pedidos nesta consulta.</p>
            <?php else: ?>
            <div class="card"><div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Exame</th><th>Categoria</th><th>Urgência</th><th>Estado</th><th>Resultado</th></tr></thead>
                    <tbody>
                    <?php foreach($exames as $e): ?>
                        <tr>
                            <td><?=h($e['tipo_exame'])?></td>
                            <td><?=h(ucfirst($e['categoria']))?></td>
                            <td><span class="badge bg-<?=$e['urgencia']==='urgente'?'danger':'secondary'?>"><?=h(ucfirst($e['urgencia']))?></span></td>
                            <td><span class="badge bg-<?=['pendente'=>'warning text-dark','realizado'=>'success','cancelado'=>'danger'][$e['estado']]??'secondary'?>"><?=h(ucfirst($e['estado']))?></span></td>
                            <td><small><?=h(substr($e['resultado']??'—',0,60))?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
