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
    $data_str   = trim($_POST['data_consulta']      ?? '');
    $hora_str   = trim($_POST['hora_consulta']      ?? '');
    $data_hora  = ($data_str && $hora_str) ? $data_str . ' ' . $hora_str . ':00' : '';
    $tipo       = $_POST['tipo_consulta']           ?? 'rotina';
    $motivo     = trim($_POST['motivo']             ?? '');
    $modalidade = $_POST['modalidade']              ?? 'presencial';
    $link       = trim($_POST['link_videochamada']  ?? '') ?: null;

    if (!$utente_id || !$data_hora) {
        $erro = 'Preencha o paciente, a data e o horário.';
    } elseif ($modalidade === 'video' && !$link) {
        $erro = 'Link de videochamada obrigatório para consulta por vídeo.';
    } else {
        $db->prepare("INSERT INTO consultas (utente_id, medico_id, data_hora, tipo, motivo, modalidade, link_videochamada, estado)
                      VALUES (?,?,?,?,?,?,?,'agendada')")
           ->execute([$utente_id, $pid, $data_hora, $tipo, $motivo ?: null, $modalidade, $link]);
        // Notificar utente e médico
        $uq = $db->prepare("SELECT ut.utilizador_id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.id=?");
        $uq->execute([$utente_id]); $urow = $uq->fetch();
        $utente_uid  = (int)($urow['utilizador_id'] ?? 0);
        $utente_nome = $urow['nome'] ?? 'utente';
        $data_fmt    = date('d/m/Y \à\s H:i', strtotime($data_hora));
        if ($utente_uid) {
            notificar($utente_uid, 'sessao',
                'Nova consulta agendada',
                'Foi agendada uma consulta para ' . $data_fmt . '.',
                APP_URL . '/private/utente/sessoes_consultas.php'
            );
        }
        // Notificar o próprio médico para preencher o relatório após a consulta
        notificar($uid, 'info',
            'Relatório a preencher',
            'Após a consulta com ' . $utente_nome . ' em ' . $data_fmt . ', lembre-se de preencher o relatório clínico do utente.',
            APP_URL . '/private/medico/pacientes/perfil_paciente.php?id=' . $utente_id
        );
        // Notificar administrador para faturação
        try {
            $tipo_labels = ['rotina'=>'Rotina','inicial'=>'Inicial','alta'=>'de Alta','urgente'=>'Urgente'];
            $modal_label = $modalidade === 'video' ? 'Videoconsulta' : 'Presencial';
            $aq = $db->query("SELECT id FROM utilizadores WHERE perfil='admin' AND ativo=1");
            foreach ($aq->fetchAll() as $adm) {
                notificar((int)$adm['id'], 'info',
                    'Nova consulta agendada — ' . $utente_nome,
                    'Consulta ' . ($tipo_labels[$tipo] ?? $tipo) . ' (' . $modal_label . ') agendada para ' . $utente_nome . ' em ' . $data_fmt . '. Registar fatura?',
                    APP_URL . '/private/admin/faturacao/nova_fatura.php'
                );
            }
        } catch (\Throwable $e) {}
        $mes_agenda = date('n', strtotime($data_hora));
        $ano_agenda = date('Y', strtotime($data_hora));
        $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Consulta agendada para ' . date('d/m/Y \à\s H:i', strtotime($data_hora)) . '.'];
        redirect(APP_URL . '/private/medico/consultas/agenda.php?mes=' . $mes_agenda . '&ano=' . $ano_agenda);
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
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
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
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="date" name="data_consulta" id="data_consulta" class="form-control" required
                                       value="<?= h($_POST['data_consulta'] ?? date('Y-m-d')) ?>"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <select name="hora_consulta" id="hora_consulta" class="form-select" required>
                                    <option value="">— Selecionar hora —</option>
                                </select>
                                <div class="form-text text-muted small" id="horario_info"></div>
                            </div>
                        </div>
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

        // Slots de horário: Seg-Sex 08:00-19:30 (clínica até 20h), Sáb 09:00-12:30 (até 13h), Dom fechado
        const SLOTS = {
            semana: { inicio: [8, 0],  fim: [20, 0] },
            sabado: { inicio: [9, 0],  fim: [13, 0] },
        };
        const horaSelect  = document.getElementById('hora_consulta');
        const dataInput   = document.getElementById('data_consulta');
        const infoDiv     = document.getElementById('horario_info');
        const valorAnterior = <?= json_encode($_POST['hora_consulta'] ?? '') ?>;

        function gerarSlots(date) {
            const dow = date.getDay(); // 0=Dom, 6=Sáb
            horaSelect.innerHTML = '<option value="">— Selecionar hora —</option>';
            if (dow === 0) {
                infoDiv.textContent = 'Clínica encerrada ao Domingo.';
                horaSelect.disabled = true;
                return;
            }
            horaSelect.disabled = false;
            const conf = (dow === 6) ? SLOTS.sabado : SLOTS.semana;
            const hoje = new Date();
            const eHoje = date.toDateString() === hoje.toDateString();
            let horaAtual = hoje.getHours() * 60 + hoje.getMinutes();
            let [h0, m0] = conf.inicio;
            let [hf, mf] = conf.fim;
            let slot = h0 * 60 + m0;
            const fim  = hf * 60 + mf;
            while (slot < fim) {
                const hh = String(Math.floor(slot / 60)).padStart(2, '0');
                const mm = String(slot % 60).padStart(2, '0');
                const val = hh + ':' + mm;
                if (!eHoje || slot > horaAtual) {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    if (val === valorAnterior) opt.selected = true;
                    horaSelect.appendChild(opt);
                }
                slot += 30;
            }
            infoDiv.textContent = dow === 6
                ? 'Sábado: 9h–13h (intervalos de 30 min)'
                : '2ª a 6ª: 8h–20h (intervalos de 30 min)';
        }

        function onDataChange() {
            const val = dataInput.value;
            if (!val) { horaSelect.innerHTML = '<option value="">— Selecionar hora —</option>'; return; }
            // Usar Date sem conversão de fuso: parse manual para evitar off-by-one
            const [y, m, d] = val.split('-').map(Number);
            gerarSlots(new Date(y, m - 1, d));
        }

        dataInput.addEventListener('change', onDataChange);
        onDataChange();
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
