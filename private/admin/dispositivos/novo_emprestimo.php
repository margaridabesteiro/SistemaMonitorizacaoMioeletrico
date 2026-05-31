<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Novo Empréstimo'; $pagina_ativa = 'dispositivos';
$db = getDB();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $disp_id  = (int)($_POST['dispositivo_id'] ?? 0);
    $utente_id= (int)($_POST['utente_id']      ?? 0);
    $tecnico_id=(int)($_POST['tecnico_id']     ?? 0) ?: null;
    $entrega  = trim($_POST['data_entrega']     ?? '');
    $devolucao= trim($_POST['data_prevista_devolucao'] ?? '') ?: null;
    $notas    = trim($_POST['notas']            ?? '') ?: null;

    if (!$disp_id)   $erros[] = 'Selecione um dispositivo.';
    if (!$utente_id) $erros[] = 'Selecione um utente.';
    if (!$entrega)   $erros[] = 'Data de entrega obrigatória.';

    if (empty($erros)) {
        $db->prepare('INSERT INTO emprestimos_dispositivos (dispositivo_id,utente_id,tecnico_id,data_entrega,data_prevista_devolucao,notas) VALUES (?,?,?,?,?,?)')
           ->execute([$disp_id,$utente_id,$tecnico_id,$entrega,$devolucao,$notas]);
        $db->prepare('UPDATE dispositivos SET estado=\'emprestado\' WHERE id=?')->execute([$disp_id]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Empréstimo registado.'];
        redirect(APP_URL . '/private/admin/dispositivos/emprestimos.php');
    }
}

$dispositivos = $db->query("SELECT id, codigo, tipo FROM dispositivos WHERE estado='disponivel' AND ativo=1 ORDER BY codigo")->fetchAll();
$utentes      = $db->query("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id ORDER BY u.nome")->fetchAll();
$tecnicos     = $db->query("SELECT p.id, u.nome FROM profissionais p JOIN utilizadores u ON u.id=p.utilizador_id WHERE u.perfil='tecnico' ORDER BY u.nome")->fetchAll();

$pre_disp = (int)($_GET['disp'] ?? 0);

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="emprestimos.php">Empréstimos</a></li><li class="breadcrumb-item active">Novo</li></ol></nav>
            <h1 class="mb-4">Registar Empréstimo</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dispositivo <span class="text-danger">*</span></label>
                        <select name="dispositivo_id" class="form-select" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach($dispositivos as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $d['id']===$pre_disp?'selected':'' ?>><?= h($d['codigo']) ?> (<?= h($d['tipo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($dispositivos)): ?><div class="form-text text-warning">Sem dispositivos disponíveis.</div><?php endif; ?>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Utente <span class="text-danger">*</span></label>
                            <select name="utente_id" id="sel-utente" class="form-select" required>
                                <option value="">— Selecionar —</option>
                                <?php foreach($utentes as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['nome']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Técnico responsável <span class="text-muted small fw-normal" id="tecnico-hint"></span></label>
                            <select name="tecnico_id" id="sel-tecnico" class="form-select">
                                <option value="">— Nenhum —</option>
                                <?php foreach($tecnicos as $t): ?><option value="<?= $t['id'] ?>"><?= h($t['nome']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data de Entrega <span class="text-danger">*</span></label>
                            <input type="date" name="data_entrega" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Devolução Prevista</label>
                            <input type="date" name="data_prevista_devolucao" class="form-control">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notas</label>
                        <textarea name="notas" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Registar Empréstimo</button>
                        <a href="emprestimos.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
        <script>
        document.getElementById('sel-utente').addEventListener('change', function () {
            const utenteId = this.value;
            const selTecnico = document.getElementById('sel-tecnico');
            const hint = document.getElementById('tecnico-hint');
            if (!utenteId) { hint.textContent = ''; return; }
            fetch('<?= APP_URL ?>/api/admin/utentes/get_tecnico.php?id=' + utenteId)
                .then(r => r.json())
                .then(data => {
                    if (data.tecnico_id) {
                        selTecnico.value = data.tecnico_id;
                        hint.textContent = '(pré-preenchido do utente)';
                    } else {
                        selTecnico.value = '';
                        hint.textContent = '(sem técnico atribuído ao utente)';
                    }
                })
                .catch(() => { hint.textContent = ''; });
        });
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
