<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();

$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pid) {
    $utente_id   = (int)($_POST['utente_id']          ?? 0);
    $data_p      = $_POST['data_prescricao']           ?? date('Y-m-d');
    $data_v      = $_POST['data_validade']             ?: null;
    $num_s       = (int)($_POST['num_sessoes_prescritas'] ?? 0) ?: null;
    $membro      = $_POST['membro_afetado']            ?: null;
    $objetivos   = trim($_POST['objetivos_clinicos']   ?? '');
    $obs         = trim($_POST['observacoes']          ?? '');

    if (!$utente_id) $erros[] = 'Selecione um paciente.';
    if (empty($objetivos)) $erros[] = 'Objetivos clínicos são obrigatórios.';

    if (empty($erros)) {
        $db->prepare('INSERT INTO programas_tratamento
            (utente_id, medico_id, data_prescricao, data_validade,
             num_sessoes_prescritas, objetivos_clinicos, membro_afetado, observacoes, ativa)
            VALUES (?,?,?,?,?,?,?,?,1)')
           ->execute([$utente_id, $pid, $data_p, $data_v, $num_s, $objetivos, $membro, $obs]);
        registarAuditoria('CRIAR', 'Prescricao', (int)$db->lastInsertId(), 'Programa de tratamento criado para utente_id=' . $utente_id);
        // Notificar técnico atribuído ao utente
        try {
            $tq = $db->prepare("
                SELECT u.id FROM utentes ut
                JOIN profissionais p ON p.id = ut.tecnico_id
                JOIN utilizadores u ON u.id = p.utilizador_id
                WHERE ut.id=? AND u.ativo=1
            ");
            $tq->execute([$utente_id]);
            $tecnico_uid = $tq->fetchColumn();
            if ($tecnico_uid) {
                notificar((int)$tecnico_uid, 'prescricao',
                    'Nova prescrição de tratamento',
                    'O Dr. ' . ($_SESSION['nome'] ?? '') . ' criou um novo programa. Verifique e agende as sessões.',
                    APP_URL . '/private/tecnico/relatorios/gerar_relatorio.php'
                );
            }
        } catch (\Throwable $e) {}
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Programa de tratamento criado.'];
        redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
    }
}

$utentes = [];
if ($pid) {
    $s = $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.medico_id=? ORDER BY u.nome");
    $s->execute([$pid]); $utentes = $s->fetchAll();
}

$pagina_titulo = 'Novo Programa de Tratamento'; $pagina_ativa = 'prescricoes';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="lista_prescricoes.php">Programas</a></li>
                    <li class="breadcrumb-item active">Novo</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Novo Programa de Tratamento</h1>
                <a href="lista_prescricoes.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
            </div>
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <div class="card p-4" style="max-width:700px;">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Paciente <span class="text-danger">*</span></label>
                            <select name="utente_id" class="form-select" required>
                                <option value="">Selecionar...</option>
                                <?php foreach($utentes as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= (($_POST['utente_id'] ?? 0) == $u['id']) ? 'selected' : '' ?>><?= h($u['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Membro Afetado</label>
                            <select name="membro_afetado" class="form-select">
                                <option value="">— Não especificado —</option>
                                <?php foreach(['mao_esquerda'=>'Mão esquerda','mao_direita'=>'Mão direita','ambas'=>'Ambas as mãos','perna_esquerda'=>'Perna esquerda','perna_direita'=>'Perna direita','outro'=>'Outro'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= (($_POST['membro_afetado'] ?? '') === $v) ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Nº Sessões Prescritas</label>
                            <input type="number" name="num_sessoes_prescritas" class="form-control" min="1" max="200" value="<?= h($_POST['num_sessoes_prescritas'] ?? '') ?>" placeholder="ex: 12">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Data Início</label>
                            <input type="date" name="data_prescricao" class="form-control" value="<?= h($_POST['data_prescricao'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Data Validade</label>
                            <input type="date" name="data_validade" class="form-control" value="<?= h($_POST['data_validade'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Objetivos Clínicos <span class="text-danger">*</span></label>
                        <textarea name="objetivos_clinicos" class="form-control" rows="3" required
                                  placeholder="O que se pretende atingir com este programa de tratamento..."><?= h($_POST['objetivos_clinicos'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Observações adicionais</label>
                        <textarea name="observacoes" class="form-control" rows="2"><?= h($_POST['observacoes'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Criar Programa</button>
                        <a href="lista_prescricoes.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
