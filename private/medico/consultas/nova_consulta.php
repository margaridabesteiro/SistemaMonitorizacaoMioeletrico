<?php
// private/medico/consultas/nova_consulta.php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

// Carregar utentes associados a este médico
$utentes = $pid
    ? $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.medico_id=? ORDER BY u.nome")
    : null;
if ($utentes) { $utentes->execute([$pid]); $utentes = $utentes->fetchAll(); }
else { $utentes = []; }

$erro = ''; $sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pid) {
    $utente_id = (int)($_POST['utente_id'] ?? 0);
    $data_hora = trim($_POST['data_hora'] ?? '');
    $motivo    = trim($_POST['motivo']    ?? '');

    if (!$utente_id || !$data_hora) {
        $erro = 'Preencha o utente e a data/hora.';
    } else {
        $db->prepare("INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, estado) VALUES (?,?,?,?,'agendada')")
           ->execute([$utente_id, $pid, $data_hora, $motivo ?: null]);
        redirect(APP_URL . '/private/medico/consultas/consulta.php');
    }
}

$pagina_titulo = 'Nova Consulta'; $pagina_ativa = 'consultas';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Nova Consulta</h1>
                <a href="consulta.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Voltar
                </a>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= h($erro) ?></div>
            <?php endif; ?>
            <?php if (!$pid): ?>
                <div class="alert alert-warning">O seu perfil de profissional ainda não está configurado. Contacte o administrador.</div>
            <?php else: ?>

            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Paciente <span class="text-danger">*</span></label>
                        <select name="utente_id" class="form-select" required>
                            <option value="">— Selecionar paciente —</option>
                            <?php foreach ($utentes as $ut): ?>
                                <option value="<?= $ut['id'] ?>" <?= (($_POST['utente_id'] ?? '') == $ut['id']) ? 'selected' : '' ?>>
                                    <?= h($ut['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($utentes)): ?>
                            <div class="form-text text-warning">Não tem pacientes associados. O administrador deve associar pacientes ao seu perfil.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Data e Hora <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="data_hora" class="form-control"
                               value="<?= h($_POST['data_hora'] ?? '') ?>" required
                               min="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo da consulta</label>
                        <textarea name="motivo" class="form-control" rows="3"
                                  placeholder="Descreva o motivo..."><?= h($_POST['motivo'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm w-100" style="background:#8B0000;color:#fff;">
                        <i class="fa-regular fa-calendar-plus me-2"></i>Agendar Consulta
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
