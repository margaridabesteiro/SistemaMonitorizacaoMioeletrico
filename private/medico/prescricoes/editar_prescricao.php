<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Editar Programa'; $pagina_ativa = 'prescricoes';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
$stmt = $db->prepare('SELECT * FROM programas_tratamento WHERE id=?'); $stmt->execute([$id]); $p = $stmt->fetch();
if (!$p) redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $data_v    = $_POST['data_validade']          ?: null;
    $num_s     = (int)($_POST['num_sessoes_prescritas'] ?? 0) ?: null;
    $membro    = $_POST['membro_afetado']          ?: null;
    $objetivos = trim($_POST['objetivos_clinicos'] ?? '');
    $obs       = trim($_POST['observacoes']        ?? '');
    if (empty($objetivos)) $erros[] = 'Objetivos clínicos são obrigatórios.';
    if (empty($erros)) {
        $db->prepare('UPDATE programas_tratamento SET data_validade=?,num_sessoes_prescritas=?,membro_afetado=?,objetivos_clinicos=?,observacoes=? WHERE id=?')
           ->execute([$data_v,$num_s,$membro,$objetivos,$obs,$id]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Programa atualizado.'];
        redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
    }
}
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
$membro_labels = ['mao_esquerda'=>'Mão esquerda','mao_direita'=>'Mão direita','ambas'=>'Ambas as mãos','perna_esquerda'=>'Perna esquerda','perna_direita'=>'Perna direita','outro'=>'Outro'];
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="lista_prescricoes.php">Programas</a></li>
                    <li class="breadcrumb-item active">Editar #<?= $p['id'] ?></li>
                </ol>
            </nav>
            <h1 class="mb-4">Editar Programa de Tratamento</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:700px;">
                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Membro Afetado</label>
                            <select name="membro_afetado" class="form-select">
                                <option value="">— Não especificado —</option>
                                <?php foreach($membro_labels as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($p['membro_afetado']===$v)?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Nº Sessões</label>
                            <input type="number" name="num_sessoes_prescritas" class="form-control" min="1" value="<?= h($p['num_sessoes_prescritas'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Validade</label>
                            <input type="date" name="data_validade" class="form-control" value="<?= h($p['data_validade'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Objetivos Clínicos <span class="text-danger">*</span></label>
                        <textarea name="objetivos_clinicos" class="form-control" rows="3" required><?= h($p['objetivos_clinicos'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"><?= h($p['observacoes'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
                        <a href="lista_prescricoes.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
