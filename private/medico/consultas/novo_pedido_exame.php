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
    $tipo     = trim($_POST['tipo_exame']   ?? '');
    $categoria= $_POST['categoria']         ?? 'outro';
    $urgencia = $_POST['urgencia']          ?? 'rotina';
    $obs      = trim($_POST['observacoes']  ?? '') ?: null;

    if (!$tipo) $erros[] = 'Tipo de exame obrigatório.';

    if (empty($erros)) {
        $db->prepare('INSERT INTO pedidos_exame (consulta_id,tipo_exame,categoria,urgencia,estado,data_pedido,observacoes) VALUES (?,?,?,?,\'pendente\',CURDATE(),?)')
           ->execute([$consulta_id,$tipo,$categoria,$urgencia,$obs]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Pedido de exame registado.'];
        redirect(APP_URL . '/private/medico/consultas/detalhe_consulta.php?id='.$consulta_id);
    }
}
$pagina_titulo = 'Pedir Exame'; $pagina_ativa = 'consultas';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="detalhe_consulta.php?id=<?=$consulta_id?>">Consulta #<?=$consulta_id?></a></li><li class="breadcrumb-item active">Pedir Exame</li></ol></nav>
            <h1 class="mb-4">Pedir Exame</h1>
            <?php if(!empty($erros)):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif;?>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="consulta_id" value="<?=$consulta_id?>">
                    <input type="hidden" name="utente_id"   value="<?=$utente_id?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Exame <span class="text-danger">*</span></label>
                        <input type="text" name="tipo_exame" class="form-control" placeholder="Ex: EMG do coto, RMN Cervical, Hemograma" required value="<?=h($_POST['tipo_exame']??'')?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Categoria</label>
                            <select name="categoria" class="form-select">
                                <?php foreach(['imagiologia'=>'Imagiologia','laboratorial'=>'Laboratorial','funcional'=>'Funcional','neurologico'=>'Neurológico','outro'=>'Outro'] as $v=>$l): ?>
                                    <option value="<?=$v?>" <?=(($_POST['categoria']??'outro')===$v)?'selected':''?>><?=$l?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Urgência</label>
                            <select name="urgencia" class="form-select">
                                <option value="rotina">Rotina</option>
                                <option value="urgente" <?=(($_POST['urgencia']??'')==='urgente')?'selected':''?>>Urgente</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Observações / Indicações clínicas</label>
                        <textarea name="observacoes" class="form-control" rows="3"><?=h($_POST['observacoes']??'')?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Pedir Exame</button>
                        <a href="detalhe_consulta.php?id=<?=$consulta_id?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
