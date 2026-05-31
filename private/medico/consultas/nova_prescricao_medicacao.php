<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');
$db = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();

$consulta_id = (int)($_GET['consulta_id'] ?? $_POST['consulta_id'] ?? 0);
$utente_id   = (int)($_GET['utente_id']   ?? $_POST['utente_id']   ?? 0);
if (!$consulta_id || !$utente_id) redirect(APP_URL . '/private/medico/consultas/consulta.php');

$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pid) {
    $med      = trim($_POST['medicamento']  ?? '');
    $dosagem  = trim($_POST['dosagem']      ?? '');
    $posologia= trim($_POST['posologia']    ?? '');
    $inicio   = $_POST['data_inicio']       ?? date('Y-m-d');
    $fim      = $_POST['data_fim']          ?: null;
    $obs      = trim($_POST['observacoes']  ?? '') ?: null;

    if (!$med)      $erros[] = 'Medicamento obrigatório.';
    if (!$dosagem)  $erros[] = 'Dosagem obrigatória.';
    if (!$posologia)$erros[] = 'Posologia obrigatória.';

    if (empty($erros)) {
        $db->prepare('INSERT INTO prescricoes_medicacao (consulta_id,utente_id,medico_id,medicamento,dosagem,posologia,data_inicio,data_fim,observacoes,ativa) VALUES (?,?,?,?,?,?,?,?,?,1)')
           ->execute([$consulta_id,$utente_id,$pid,$med,$dosagem,$posologia,$inicio,$fim,$obs]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Medicação adicionada.'];
        redirect(APP_URL . '/private/medico/consultas/detalhe_consulta.php?id='.$consulta_id);
    }
}
$pagina_titulo = 'Prescrever Medicação'; $pagina_ativa = 'consultas';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="detalhe_consulta.php?id=<?=$consulta_id?>">Consulta #<?=$consulta_id?></a></li><li class="breadcrumb-item active">Nova Medicação</li></ol></nav>
            <h1 class="mb-4">Prescrever Medicação</h1>
            <?php if(!empty($erros)):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif;?>
            <div class="card p-4" style="max-width:640px;">
                <form method="POST">
                    <input type="hidden" name="consulta_id" value="<?=$consulta_id?>">
                    <input type="hidden" name="utente_id"   value="<?=$utente_id?>">
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-semibold">Medicamento <span class="text-danger">*</span></label>
                            <input type="text" name="medicamento" class="form-control" placeholder="Ex: Ibuprofeno" required value="<?=h($_POST['medicamento']??'')?>">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-semibold">Dosagem <span class="text-danger">*</span></label>
                            <input type="text" name="dosagem" class="form-control" placeholder="Ex: 400mg" required value="<?=h($_POST['dosagem']??'')?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Posologia <span class="text-danger">*</span></label>
                        <textarea name="posologia" class="form-control" rows="2" required placeholder="Ex: 1 comprimido de 8 em 8 horas às refeições"><?=h($_POST['posologia']??'')?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data Início</label>
                            <input type="date" name="data_inicio" class="form-control" value="<?=date('Y-m-d')?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data Fim <span class="text-muted small">(vazio = contínuo)</span></label>
                            <input type="date" name="data_fim" class="form-control">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"><?=h($_POST['observacoes']??'')?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
                        <a href="detalhe_consulta.php?id=<?=$consulta_id?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
