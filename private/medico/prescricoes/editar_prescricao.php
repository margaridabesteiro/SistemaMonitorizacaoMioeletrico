<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Editar Prescrição'; $pagina_ativa = 'prescricoes';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
$stmt = $db->prepare('SELECT * FROM prescricoes WHERE id=?'); $stmt->execute([$id]); $p = $stmt->fetch();
if (!$p) redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? ''; $prioridade = $_POST['prioridade'] ?? 'Media';
    $data_v = $_POST['data_validade'] ?: null; $obs = trim($_POST['observacoes'] ?? ''); $ativa = isset($_POST['ativa']) ? 1 : 0;
    if (empty($erros)) { $db->prepare('UPDATE prescricoes SET tipo=?,prioridade=?,data_validade=?,observacoes=?,ativa=? WHERE id=?')->execute([$tipo,$prioridade,$data_v,$obs,$ativa,$id]); $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Prescrição atualizada.']; redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php'); }
}
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="lista_prescricoes.php">Prescrições</a></li><li class="breadcrumb-item active">Editar</li></ol></nav>
            <h1 class="mb-4">Editar Prescrição #<?= $p['id'] ?></h1>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
                    <div class="mb-3"><label class="form-label fw-semibold">Tipo</label>
                        <select name="tipo" class="form-select"><option <?= $p['tipo']==='Força de pinça'?'selected':'' ?>>Força de pinça</option><option <?= $p['tipo']==='Treino de precisão'?'selected':'' ?>>Treino de precisão</option><option <?= $p['tipo']==='Avaliação funcional'?'selected':'' ?>>Avaliação funcional</option></select></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Prioridade</label>
                            <select name="prioridade" class="form-select"><?php foreach(['Baixa','Media','Alta','Urgente'] as $pr): ?><option <?= $p['prioridade']===$pr?'selected':'' ?>><?= $pr ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Data Validade</label><input type="date" name="data_validade" class="form-control" value="<?= h($p['data_validade'] ?? '') ?>"></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Observações</label><textarea name="observacoes" class="form-control" rows="3"><?= h($p['observacoes'] ?? '') ?></textarea></div>
                    <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="ativa" id="ativa" <?= $p['ativa']?'checked':'' ?>><label class="form-check-label" for="ativa">Prescrição ativa</label></div>
                    <div class="d-flex gap-2"><button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button><a href="lista_prescricoes.php" class="btn btn-outline-secondary">Cancelar</a></div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
