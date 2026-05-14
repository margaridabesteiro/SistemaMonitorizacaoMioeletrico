<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Nova Sessão'; $pagina_ativa = 'sessoes';
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
    $stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
    $utente_id = (int)($_POST['utente_id'] ?? 0);
    $tipo = trim($_POST['tipo'] ?? ''); $duracao = (int)($_POST['duracao'] ?? 15);
    $data_hora = trim($_POST['data_hora'] ?? ''); $notas = trim($_POST['notas'] ?? '');
    $disp_id = (int)($_POST['dispositivo_id'] ?? 0) ?: null;
    if (!$utente_id) $erros[] = 'Selecione um paciente.';
    if ($tipo === '') $erros[] = 'Tipo obrigatório.';
    if ($data_hora === '') $erros[] = 'Data/Hora obrigatória.';
    if (empty($erros) && $pid) {
        $db->prepare('INSERT INTO sessoes (utente_id,tecnico_id,dispositivo_id,data_hora,duracao_min,tipo,estado,notas) VALUES (?,?,?,?,?,?,?,?)')
           ->execute([$utente_id,$pid,$disp_id,$data_hora,$duracao,$tipo,'agendada',$notas]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Sessão agendada.']; redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
    }
}
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
$utentes = $pid ? $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.tecnico_id=? ORDER BY u.nome") : null;
if ($utentes) { $utentes->execute([$pid]); $utentes = $utentes->fetchAll(); } else { $utentes = []; }
$dispositivos = $db->query("SELECT id, codigo, tipo FROM dispositivos WHERE ativo=1 ORDER BY codigo")->fetchAll();
$utente_pre = (int)($_GET['utente_id'] ?? 0);
?>
        <main class="content">
            <h1 class="mb-4">Nova Sessão de Treino</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:700px;">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Paciente *</label>
                            <select name="utente_id" class="form-select" required><option value="">Selecionar...</option>
                                <?php foreach($utentes as $u): ?><option value="<?= $u['id'] ?>" <?= $u['id']===$utente_pre?'selected':'' ?>><?= h($u['nome']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Tipo de Exercício *</label>
                            <select name="tipo" class="form-select" required>
                                <option>Treino de força</option><option>Treino de precisão</option><option>Treino de resistência</option><option>Sessão gamificada</option><option>Avaliação funcional</option>
                            </select></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Data / Hora *</label><input type="datetime-local" name="data_hora" class="form-control" required></div>
                        <div class="col-md-3 mb-3"><label class="form-label fw-semibold">Duração (min)</label><input type="number" name="duracao" class="form-control" value="15" min="5" max="120"></div>
                        <div class="col-md-3 mb-3"><label class="form-label fw-semibold">Dispositivo</label>
                            <select name="dispositivo_id" class="form-select"><option value="">Nenhum</option>
                                <?php foreach($dispositivos as $d): ?><option value="<?= $d['id'] ?>"><?= h($d['codigo']) ?></option><?php endforeach; ?>
                            </select></div>
                    </div>
                    <div class="mb-4"><label class="form-label fw-semibold">Notas</label><textarea name="notas" class="form-control" rows="3"></textarea></div>
                    <div class="d-flex gap-2"><button type="submit" class="btn" style="background:#1a5f8a;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Agendar</button><a href="lista_sessoes.php" class="btn btn-outline-secondary">Cancelar</a></div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
