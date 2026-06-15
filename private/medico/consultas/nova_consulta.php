<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

$utentes = [];
if ($pid) {
    $s = $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.medico_id=? ORDER BY u.nome");
    $s->execute([$pid]); $utentes = $s->fetchAll();
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pid) {
    $utente_id  = (int)($_POST['utente_id']        ?? 0);
    $data_hora  = trim($_POST['data_hora']          ?? '');
    $tipo       = $_POST['tipo_consulta']           ?? 'rotina';
    $motivo     = trim($_POST['motivo']             ?? '');
    $modalidade = $_POST['modalidade']              ?? 'presencial';
    $link       = trim($_POST['link_videochamada']  ?? '') ?: null;

    if (!$utente_id || !$data_hora) {
        $erro = 'Preencha o paciente e a data/hora.';
    } elseif ($modalidade === 'video' && !$link) {
        $erro = 'Link de videochamada obrigatório para consulta por vídeo.';
    } else {
        $db->prepare("INSERT INTO consultas (utente_id, medico_id, data_hora, tipo, motivo, modalidade, link_videochamada, estado)
                      VALUES (?,?,?,?,?,?,?,'agendada')")
           ->execute([$utente_id, $pid, $data_hora, $tipo, $motivo ?: null, $modalidade, $link]);
        // Notificar utente
        $uq = $db->prepare("SELECT ut.utilizador_id FROM utentes ut WHERE ut.id=?");
        $uq->execute([$utente_id]); $utente_uid = (int)$uq->fetchColumn();
        if ($utente_uid) {
            $data_fmt = date('d/m/Y \à\s H:i', strtotime($data_hora));
            notificar($utente_uid, 'sessao',
                'Nova consulta agendada',
                'Foi agendada uma consulta para ' . $data_fmt . '.',
                APP_URL . '/private/utente/sessoes_consultas.php'
            );
        }
        redirect(APP_URL . '/private/medico/consultas/consulta.php');
    }
}

$utente_pre = (int)($_GET['utente_id'] ?? 0);
$pagina_titulo = 'Nova Consulta'; $pagina_ativa = 'consultas';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Nova Consulta</h1>
                <a href="consulta.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
            </div>
            <?php if ($erro): ?><div class="alert alert-danger"><?= h($erro) ?></div><?php endif; ?>
            <?php if (!$pid): ?>
                <div class="alert alert-warning">Perfil de profissional não configurado. Contacte o administrador.</div>
            <?php else: ?>
            <div class="card p-4" style="max-width:640px;">
                <form method="POST" id="formConsulta">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Paciente <span class="text-danger">*</span></label>
                            <select name="utente_id" class="form-select" required>
                                <option value="">— Selecionar —</option>
                                <?php foreach ($utentes as $ut): ?>
                                    <option value="<?= $ut['id'] ?>" <?= (($_POST['utente_id'] ?? $utente_pre) == $ut['id']) ? 'selected' : '' ?>><?= h($ut['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($utentes)): ?><div class="form-text text-warning">Sem pacientes associados.</div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tipo de Consulta</label>
                            <select name="tipo_consulta" class="form-select">
                                <?php foreach(['rotina'=>'Rotina','inicial'=>'Inicial','alta'=>'Alta','urgente'=>'Urgente'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= (($_POST['tipo_consulta']??'rotina')===$v)?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Data e Hora <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="data_hora" class="form-control" required
                               value="<?= h($_POST['data_hora'] ?? '') ?>" min="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Modalidade</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modalidade" id="modPresencial" value="presencial" <?= (($_POST['modalidade']??'presencial')==='presencial')?'checked':'' ?>>
                                <label class="form-check-label" for="modPresencial"><i class="fa-solid fa-hospital me-1"></i>Presencial</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modalidade" id="modVideo" value="video" <?= (($_POST['modalidade']??'')==='video')?'checked':'' ?>>
                                <label class="form-check-label" for="modVideo"><i class="fa-solid fa-video me-1"></i>Videoconsulta</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="linkVideoRow" style="display:none;">
                        <label class="form-label fw-semibold">Link Videochamada <span class="text-danger">*</span></label>
                        <input type="url" name="link_videochamada" class="form-control"
                               placeholder="https://meet.google.com/..." value="<?= h($_POST['link_videochamada'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo</label>
                        <textarea name="motivo" class="form-control" rows="3" placeholder="Descreva o motivo..."><?= h($_POST['motivo'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn w-100" style="background:#8B0000;color:#fff;">
                        <i class="fa-regular fa-calendar-plus me-2"></i>Agendar Consulta
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </main>
        <script>
        function toggleVideo() {
            const video = document.getElementById('modVideo').checked;
            document.getElementById('linkVideoRow').style.display = video ? 'block' : 'none';
        }
        document.getElementById('modPresencial').addEventListener('change', toggleVideo);
        document.getElementById('modVideo').addEventListener('change', toggleVideo);
        toggleVideo();
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
