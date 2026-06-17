<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('tecnico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();

$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pid) {
    $utente_id = (int)($_POST['utente_id']     ?? 0);
    $jogo_id   = (int)($_POST['jogo_id']       ?? 0) ?: null;
    $categoria = $_POST['categoria']           ?? 'jogo';
    $data_hora = trim($_POST['data_hora']       ?? '');
    $duracao   = (int)($_POST['duracao']        ?? 45);
    $objetivo  = trim($_POST['objetivo_sessao'] ?? '');
    $notas     = trim($_POST['notas']           ?? '');
    $disp_id   = (int)($_POST['dispositivo_id'] ?? 0) ?: null;
    $modalidade = $_POST['modalidade'] ?? 'presencial';

    // Link de videochamada só para avaliação funcional remota
    $link = ($categoria === 'avaliacao_funcional' && $modalidade === 'remota')
        ? (trim($_POST['link_videochamada'] ?? '') ?: null)
        : null;

    if (!$utente_id) $erros[] = 'Selecione um paciente.';
    if ($data_hora === '') $erros[] = 'Data/Hora obrigatória.';
    elseif (strtotime($data_hora) < strtotime(date('Y-m-d'))) $erros[] = 'A data não pode ser no passado.';
    if ($categoria === 'avaliacao_funcional' && $modalidade === 'remota' && !$link)
        $erros[] = 'Link de videochamada obrigatório para avaliação funcional remota.';
    if ($categoria === 'jogo' && in_array($modalidade, ['em_casa','remoto'], true) && !$disp_id)
        $erros[] = 'Selecione um dispositivo para sessões em casa ou remotas — o utente precisa do dispositivo.';

    if (empty($erros)) {
        $db->prepare('INSERT INTO sessoes (utente_id,tecnico_id,dispositivo_id,data_hora,duracao_min,categoria,jogo_id,objetivo_sessao,modalidade,link_videochamada,estado,notas)
                      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
           ->execute([$utente_id,$pid,$disp_id,$data_hora,$duracao,$categoria,$jogo_id,$objetivo,$modalidade,$link,'agendada',$notas]);

        // Jogo em casa / remoto → empréstimo automático do dispositivo ao utente
        if ($categoria === 'jogo' && in_array($modalidade, ['em_casa','remoto'], true) && $disp_id) {
            try {
                $chk = $db->prepare("SELECT estado FROM dispositivos WHERE id=? AND ativo=1");
                $chk->execute([$disp_id]);
                if ($chk->fetchColumn() === 'disponivel') {
                    $modal_label = $modalidade === 'em_casa' ? 'em casa' : 'remoto';
                    $db->prepare("INSERT INTO emprestimos_dispositivos (dispositivo_id,utente_id,tecnico_id,data_entrega,notas) VALUES (?,?,?,?,?)")
                       ->execute([
                           $disp_id, $utente_id, $pid,
                           date('Y-m-d', strtotime($data_hora)),
                           'Sessão ' . $modal_label . ' agendada para ' . date('d/m/Y', strtotime($data_hora))
                       ]);
                    $db->prepare("UPDATE dispositivos SET estado='emprestado' WHERE id=?")->execute([$disp_id]);
                    registarAuditoria('CRIAR', 'Emprestimo', $disp_id, 'Empréstimo automático — sessão jogo ' . $modal_label);
                }
            } catch (\Throwable $e) {}
        }

        // Notificar administrador para faturação
        try {
            $uq2 = $db->prepare("SELECT u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.id=?");
            $uq2->execute([$utente_id]); $uname = $uq2->fetchColumn() ?: 'utente';
            $jnome = '';
            if ($jogo_id) {
                $jq = $db->prepare("SELECT nome FROM jogos WHERE id=?");
                $jq->execute([$jogo_id]); $jnome = $jq->fetchColumn() ?: '';
            }
            $cat_label = $categoria === 'jogo'
                ? 'Jogo de Reabilitação' . ($jnome ? ' — ' . $jnome : '')
                : 'Avaliação Funcional';
            $data_fmt2 = date('d/m/Y \à\s H:i', strtotime($data_hora));
            $aq = $db->query("SELECT id FROM utilizadores WHERE perfil='admin' AND ativo=1");
            foreach ($aq->fetchAll() as $adm) {
                notificar((int)$adm['id'], 'sessao',
                    'Nova sessão agendada — ' . $uname,
                    $cat_label . ' agendada para ' . $uname . ' em ' . $data_fmt2 . '. Registar fatura?',
                    APP_URL . '/private/admin/faturacao/nova_fatura.php'
                );
            }
        } catch (\Throwable $e) {}

        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Sessão agendada.'];
        redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
    }
}

$utentes = [];
if ($pid) {
    $s = $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.tecnico_id=? ORDER BY u.nome");
    $s->execute([$pid]); $utentes = $s->fetchAll();
}
$jogos        = $db->query("SELECT id, nome, nivel FROM jogos WHERE ativo=1 ORDER BY FIELD(nivel,'minimo','medio','maximo'), nome")->fetchAll();
// Apenas dispositivos disponíveis (emprestados já excluídos)
$dispositivos = $db->query("SELECT id, codigo FROM dispositivos WHERE estado='disponivel' AND ativo=1 ORDER BY codigo")->fetchAll();

// Dispositivos já reservados por data via sessões agendadas/em curso
$sessoes_disp = $db->query("
    SELECT dispositivo_id, DATE_FORMAT(data_hora,'%Y-%m-%d') AS data
    FROM sessoes
    WHERE dispositivo_id IS NOT NULL
      AND estado IN ('agendada','em_curso')
      AND data_hora >= CURDATE()
")->fetchAll();
$disp_ocupados = [];
foreach ($sessoes_disp as $sd) {
    $disp_ocupados[$sd['data']][] = (int)$sd['dispositivo_id'];
}

$utente_pre   = (int)($_GET['utente_id'] ?? 0);
$pagina_titulo = 'Nova Sessão'; $pagina_ativa = 'sessoes';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <h1 class="mb-4">Nova Sessão de Treino</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:700px;">
                <form method="POST" id="formSessao">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Paciente <span class="text-danger">*</span></label>
                            <select name="utente_id" class="form-select" required>
                                <option value="">Selecionar...</option>
                                <?php foreach($utentes as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $u['id']===$utente_pre?'selected':'' ?>><?= h($u['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Categoria</label>
                            <select name="categoria" class="form-select" id="selectCategoria">
                                <?php foreach(['jogo'=>'Jogo','avaliacao_funcional'=>'Avaliação Funcional'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= (($_POST['categoria']??'jogo')===$v)?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3" id="jogoRow">
                        <label class="form-label fw-semibold">Jogo</label>
                        <select name="jogo_id" class="form-select">
                            <option value="">— Selecionar jogo —</option>
                            <?php
                            $nivel_labels = ['minimo'=>'Mínimo','medio'=>'Médio','maximo'=>'Máximo'];
                            foreach($jogos as $j): ?>
                                <option value="<?= $j['id'] ?>" <?= (($_POST['jogo_id']??0)==$j['id'])?'selected':'' ?>>
                                    <?= h($j['nome']) ?> — Nível <?= $nivel_labels[$j['nivel']] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data / Hora <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="data_hora" class="form-control" required
                                   min="<?= date('Y-m-d') ?>T00:00"
                                   value="<?= h($_POST['data_hora'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Duração (min)</label>
                            <input type="number" name="duracao" class="form-control" value="<?= h($_POST['duracao'] ?? 45) ?>" min="5" max="120">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Dispositivo</label>
                            <select name="dispositivo_id" class="form-select" id="selectDispositivo">
                                <option value="">Nenhum</option>
                                <?php foreach($dispositivos as $d): ?>
                                <option value="<?= $d['id'] ?>" data-codigo="<?= h($d['codigo']) ?>"><?= h($d['codigo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="dispAviso" class="form-text text-warning d-none">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>Dispositivo ocupado neste dia.
                            </div>
                        </div>
                    </div>

                    <!-- Modalidade: opções variam conforme categoria -->
                    <div class="mb-3" id="modalidadeSection">
                        <label class="form-label fw-semibold">Modalidade</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modalidade" id="modPresencial" value="presencial"
                                       <?= (($_POST['modalidade']??'presencial')==='presencial')?'checked':'' ?>>
                                <label class="form-check-label" for="modPresencial">
                                    <i class="fa-solid fa-hospital me-1"></i>Presencial
                                </label>
                            </div>
                            <!-- Jogo: em casa -->
                            <div class="form-check" id="optEmCasa">
                                <input class="form-check-input" type="radio" name="modalidade" id="modEmCasa" value="em_casa"
                                       <?= (($_POST['modalidade']??'')==='em_casa')?'checked':'' ?>>
                                <label class="form-check-label" for="modEmCasa">
                                    <i class="fa-solid fa-house-medical me-1"></i>Em Casa
                                </label>
                            </div>
                            <!-- Jogo: remoto -->
                            <div class="form-check" id="optRemoto">
                                <input class="form-check-input" type="radio" name="modalidade" id="modRemoto" value="remoto"
                                       <?= (($_POST['modalidade']??'')==='remoto')?'checked':'' ?>>
                                <label class="form-check-label" for="modRemoto">
                                    <i class="fa-solid fa-wifi me-1"></i>Remoto
                                </label>
                            </div>
                            <!-- Avaliação funcional: remota -->
                            <div class="form-check" id="optRemota">
                                <input class="form-check-input" type="radio" name="modalidade" id="modRemota" value="remota"
                                       <?= (($_POST['modalidade']??'')==='remota')?'checked':'' ?>>
                                <label class="form-check-label" for="modRemota">
                                    <i class="fa-solid fa-video me-1"></i>Remota (videochamada)
                                </label>
                            </div>
                        </div>
                        <div id="avisoEmprestimo" class="form-text text-warning mt-1 d-none">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            O dispositivo ficará registado como <strong>emprestado</strong> ao utente a partir deste dia, até o técnico registar a devolução.
                        </div>
                    </div>

                    <div class="mb-3" id="linkVideoRow" style="display:none;">
                        <label class="form-label fw-semibold">Link Videochamada <span class="text-danger">*</span></label>
                        <input type="url" name="link_videochamada" class="form-control" placeholder="https://meet.jit.si/..." value="<?= h($_POST['link_videochamada'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Objetivo da sessão</label>
                        <input type="text" name="objetivo_sessao" class="form-control" placeholder="ex: Aumentar precisão de preensão para 75%" value="<?= h($_POST['objetivo_sessao'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notas</label>
                        <textarea name="notas" class="form-control" rows="2"><?= h($_POST['notas'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#1a5f8a;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Agendar</button>
                        <a href="lista_sessoes.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
        <script>
        function getModalidade() {
            var m = document.querySelector('input[name="modalidade"]:checked');
            return m ? m.value : 'presencial';
        }

        function atualizarModalidade() {
            var cat = document.getElementById('selectCategoria').value;
            var mod = getModalidade();
            // Link apenas para avaliação funcional remota
            document.getElementById('linkVideoRow').style.display =
                (cat === 'avaliacao_funcional' && mod === 'remota') ? 'block' : 'none';
            // Aviso de empréstimo para jogo em_casa / remoto
            var domiciliar = (cat === 'jogo' && (mod === 'em_casa' || mod === 'remoto'));
            document.getElementById('avisoEmprestimo').classList.toggle('d-none', !domiciliar);
        }

        function toggleCategoria() {
            var cat = document.getElementById('selectCategoria').value;
            var eJogo = cat === 'jogo';
            document.getElementById('jogoRow').style.display = eJogo ? 'block' : 'none';
            // Opções exclusivas por categoria
            document.getElementById('optEmCasa').style.display = eJogo ? '' : 'none';
            document.getElementById('optRemoto').style.display = eJogo ? '' : 'none';
            document.getElementById('optRemota').style.display = eJogo ? 'none' : '';
            // Ao trocar categoria, voltar a presencial para evitar valor inválido
            if (eJogo && (getModalidade() === 'remota')) document.getElementById('modPresencial').checked = true;
            if (!eJogo && ['em_casa','remoto'].indexOf(getModalidade()) !== -1) document.getElementById('modPresencial').checked = true;
            atualizarModalidade();
        }

        document.querySelectorAll('input[name="modalidade"]').forEach(function(r) {
            r.addEventListener('change', atualizarModalidade);
        });
        document.getElementById('selectCategoria').addEventListener('change', toggleCategoria);
        toggleCategoria();

        // Bloqueio de dispositivos já reservados no dia selecionado
        var _dispOcupados  = <?= json_encode($disp_ocupados, JSON_UNESCAPED_UNICODE) ?>;
        var _dataHoraInput = document.querySelector('input[name="data_hora"]');
        var _selectDisp    = document.getElementById('selectDispositivo');
        var _dispAviso     = document.getElementById('dispAviso');

        function atualizarDispositivosOcupados() {
            var dt      = _dataHoraInput.value;
            var data    = dt ? dt.split('T')[0] : '';
            var ocupados = (data && _dispOcupados[data]) ? _dispOcupados[data] : [];
            Array.from(_selectDisp.options).forEach(function(opt) {
                if (!opt.value) return;
                var dispId  = parseInt(opt.value);
                var ocupado = ocupados.indexOf(dispId) !== -1;
                var codigo  = opt.dataset.codigo || opt.textContent.replace(/ \(.*\)$/, '');
                opt.disabled    = ocupado;
                opt.textContent = ocupado ? codigo + ' (ocupado neste dia)' : codigo;
            });
            var sel = parseInt(_selectDisp.value);
            _dispAviso.classList.toggle('d-none', !(sel && ocupados.indexOf(sel) !== -1));
        }

        _dataHoraInput.addEventListener('change', atualizarDispositivosOcupados);
        _selectDisp.addEventListener('change', atualizarDispositivosOcupados);
        atualizarDispositivosOcupados();
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
