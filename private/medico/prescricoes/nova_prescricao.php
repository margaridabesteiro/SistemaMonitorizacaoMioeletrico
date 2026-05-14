<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Nova Prescrição'; $pagina_ativa = 'prescricoes';
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $uid = (int)$_SESSION['utilizador_id'];
    $stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
    $utente_id = (int)($_POST['utente_id'] ?? 0);
    $tipo = $_POST['tipo'] ?? ''; $prioridade = $_POST['prioridade'] ?? 'Media';
    $data_p = $_POST['data_prescricao'] ?? date('Y-m-d'); $data_v = $_POST['data_validade'] ?: null;
    $obs = trim($_POST['observacoes'] ?? '');
    if (!$utente_id) $erros[] = 'Selecione um paciente.';
    if ($tipo === '') $erros[] = 'Tipo obrigatório.';
    if (empty($erros) && $pid) {
        $db->prepare('INSERT INTO prescricoes (utente_id,medico_id,data_prescricao,data_validade,tipo,prioridade,observacoes,ativa) VALUES (?,?,?,?,?,?,?,1)')
           ->execute([$utente_id,$pid,$data_p,$data_v,$tipo,$prioridade,$obs]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Prescrição criada.']; redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
    }
}
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
$utentes = $pid ? $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.medico_id=? ORDER BY u.nome") : null;
if ($utentes) { $utentes->execute([$pid]); $utentes = $utentes->fetchAll(); } else { $utentes = []; }
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="lista_prescricoes.php">Prescrições</a></li><li class="breadcrumb-item active">Nova</li></ol></nav>
            <h1 class="mb-4">Nova Prescrição</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:700px;">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Paciente *</label>
                            <select name="utente_id" class="form-select" required><option value="">Selecionar...</option>
                                <?php foreach($utentes as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['nome']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Tipo *</label>
                            <select name="tipo" class="form-select" required>
                                <option>Força de pinça</option><option>Treino de precisão</option><option>Avaliação funcional</option><option>Jogos de reabilitação</option>
                            </select></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Prioridade</label>
                            <select name="prioridade" class="form-select"><option>Baixa</option><option selected>Media</option><option>Alta</option><option>Urgente</option></select></div>
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Data Prescrição</label><input type="date" name="data_prescricao" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Data Validade</label><input type="date" name="data_validade" class="form-control"></div>
                    </div>
                    <div class="mb-4"><label class="form-label fw-semibold">Observações</label><textarea name="observacoes" class="form-control" rows="3"></textarea></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Criar</button>
                        <a href="lista_prescricoes.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
