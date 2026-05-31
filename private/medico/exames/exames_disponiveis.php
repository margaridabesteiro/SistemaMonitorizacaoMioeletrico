<?php
// private/medico/exames/exames_disponiveis.php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

// Carregar utentes do médico para o modal
$utentes = [];
if ($pid) {
    $s = $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.medico_id=? ORDER BY u.nome");
    $s->execute([$pid]); $utentes = $s->fetchAll();
}

$sucesso = '';
// Processar prescrição de exame
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pid) {
    $utente_id  = (int)($_POST['utente_id']  ?? 0);
    $tipo_exame = trim($_POST['tipo_exame']  ?? '');
    $obs        = trim($_POST['observacoes'] ?? '');
    if ($utente_id && $tipo_exame) {
        $db->prepare("INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, tipo, observacoes, ativa) VALUES (?,?,CURDATE(),'Particular',?,1)")
           ->execute([$utente_id, $pid, "Exame: {$tipo_exame}" . ($obs ? " — {$obs}" : '')]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>"Exame \"{$tipo_exame}\" prescrito com sucesso."];
        redirect(APP_URL . '/private/medico/exames/exames_disponiveis.php');
    }
}
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$exames = [
    ['Análises Clínicas',  'fa-dna',          '#1e7b4b', 'Hemograma, bioquímica, ionograma, PCR, entre outros.'],
    ['Radiologia',         'fa-x-ray',         '#0d6efd', 'Raio-X, TAC, Ressonância Magnética, ecografia.'],
    ['Cardiologia',        'fa-heart-pulse',   '#dc3545', 'ECG, Holter 24h, ecocardiograma, teste de esforço.'],
    ['Neurologia',         'fa-brain',         '#6f42c1', 'EEG, EMG clínico, potenciais evocados.'],
    ['Ortopedia',          'fa-bone',          '#fd7e14', 'Avaliação funcional, escalas de mobilidade articular.'],
    ['Fisioterapia EMG',   'fa-bolt',          '#8B0000', 'Eletromiografia de superfície, análise mioeléctrica.'],
];

$pagina_titulo = 'Exames Disponíveis'; $pagina_ativa = 'exames';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <h1 class="mb-4">Exames Disponíveis</h1>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div>
            <?php endif; ?>
            <?php if (empty($utentes)): ?>
                <div class="alert alert-warning">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    Não tem pacientes associados. O administrador deve associar pacientes ao seu perfil para poder prescrever exames.
                </div>
            <?php endif; ?>

            <div class="row">
                <?php foreach ($exames as [$titulo, $icone, $cor, $desc]): ?>
                <div class="col-md-4 mb-3">
                    <div class="card p-3 h-100 d-flex flex-column">
                        <h5><i class="fa-solid <?= $icone ?> me-2" style="color:<?= $cor ?>;"></i><?= $titulo ?></h5>
                        <p class="text-muted small flex-grow-1"><?= $desc ?></p>
                        <button class="btn btn-sm mt-auto" style="background:<?= $cor ?>;color:#fff;"
                                <?= empty($utentes) ? 'disabled' : '' ?>
                                data-bs-toggle="modal" data-bs-target="#modalExame"
                                data-exame="<?= h($titulo) ?>">
                            Selecionar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- Modal prescrição de exame -->
        <div class="modal fade" id="modalExame" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Prescrever Exame — <span id="modalTitulo"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="tipo_exame" id="inputTipoExame">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Paciente <span class="text-danger">*</span></label>
                                <select name="utente_id" class="form-select" required>
                                    <option value="">— Selecionar —</option>
                                    <?php foreach ($utentes as $ut): ?>
                                        <option value="<?= $ut['id'] ?>"><?= h($ut['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Observações</label>
                                <textarea name="observacoes" class="form-control" rows="2"
                                          placeholder="Indicações clínicas, urgência..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                                <i class="fa-solid fa-file-medical me-1"></i>Prescrever
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
        document.getElementById('modalExame').addEventListener('show.bs.modal', function(e) {
            var btn = e.relatedTarget;
            var exame = btn.getAttribute('data-exame');
            document.getElementById('modalTitulo').textContent   = exame;
            document.getElementById('inputTipoExame').value = exame;
        });
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
