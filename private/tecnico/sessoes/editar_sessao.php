<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Editar Sessão'; $pagina_ativa = 'sessoes';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
$stmt = $db->prepare("SELECT s.*, u.nome AS paciente FROM sessoes s JOIN utentes ut ON ut.id=s.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE s.id=?");
$stmt->execute([$id]); $s = $stmt->fetch();
if (!$s) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = trim($_POST['tipo'] ?? ''); $duracao = (int)($_POST['duracao'] ?? 15); $notas = trim($_POST['notas'] ?? ''); $data_hora = trim($_POST['data_hora'] ?? '');
    if (empty($erros)) { $db->prepare('UPDATE sessoes SET tipo=?,duracao_min=?,notas=?,data_hora=? WHERE id=?')->execute([$tipo,$duracao,$notas,$data_hora,$id]); $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Sessão atualizada.']; redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php'); }
}
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="lista_sessoes.php">Sessões</a></li><li class="breadcrumb-item active">Editar #<?= $id ?></li></ol></nav>
            <h1 class="mb-4">Editar Sessão #<?= $id ?></h1>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
                    <div class="mb-3"><label class="form-label">Paciente</label><input type="text" class="form-control" value="<?= h($s['paciente']) ?>" disabled></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Tipo</label>
                            <select name="tipo" class="form-select"><option <?= $s['tipo']==='Treino de força'?'selected':'' ?>>Treino de força</option><option <?= $s['tipo']==='Treino de precisão'?'selected':'' ?>>Treino de precisão</option><option <?= $s['tipo']==='Sessão gamificada'?'selected':'' ?>>Sessão gamificada</option></select></div>
                        <div class="col-md-3 mb-3"><label class="form-label fw-semibold">Duração (min)</label><input type="number" name="duracao" class="form-control" value="<?= h($s['duracao_min'] ?? 15) ?>"></div>
                        <div class="col-md-12 mb-3"><label class="form-label fw-semibold">Data/Hora</label><input type="datetime-local" name="data_hora" class="form-control" value="<?= str_replace(' ','T', h(substr($s['data_hora'],0,16))) ?>"></div>
                        <div class="col-md-12 mb-4"><label class="form-label fw-semibold">Notas</label><textarea name="notas" class="form-control" rows="3"><?= h($s['notas'] ?? '') ?></textarea></div>
                    </div>
                    <div class="d-flex gap-2"><button type="submit" class="btn" style="background:#1a5f8a;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button><a href="lista_sessoes.php" class="btn btn-outline-secondary">Cancelar</a></div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
