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

        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
