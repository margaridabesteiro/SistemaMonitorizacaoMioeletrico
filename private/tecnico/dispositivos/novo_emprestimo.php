<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('tecnico');

$pagina_titulo = 'Novo Empréstimo'; $pagina_ativa = 'dispositivos';
$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];

$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $disp_id   = (int)($_POST['dispositivo_id']          ?? 0);
    $utente_id = (int)($_POST['utente_id']               ?? 0);
    $devolucao = trim($_POST['data_prevista_devolucao']   ?? '') ?: null;
    $notas     = trim($_POST['notas']                    ?? '') ?: null;
    $entrega   = date('Y-m-d');

    if (!$disp_id)   $erros[] = 'Selecione um dispositivo.';
    if (!$utente_id) $erros[] = 'Selecione um utente.';

    // Verificar que o dispositivo está disponível (não avariado/perdido/emprestado)
    if ($disp_id && empty($erros)) {
        $se = $db->prepare('SELECT estado FROM dispositivos WHERE id=? AND ativo=1');
        $se->execute([$disp_id]);
        $estado_disp = $se->fetchColumn();
        if ($estado_disp !== 'disponivel') {
            $erros[] = 'Este dispositivo não está disponível para empréstimo (estado: ' . ($estado_disp ?: 'desconhecido') . ').';
        }
    }

    if (empty($erros)) {
        $db->prepare('INSERT INTO emprestimos_dispositivos (dispositivo_id,utente_id,tecnico_id,data_entrega,data_prevista_devolucao,notas) VALUES (?,?,?,?,?,?)')
           ->execute([$disp_id, $utente_id, $pid ?: null, $entrega, $devolucao, $notas]);
        $db->prepare("UPDATE dispositivos SET estado='emprestado' WHERE id=?")->execute([$disp_id]);
        registarAuditoria('CRIAR', 'Emprestimo', $disp_id, 'Empréstimo registado pelo técnico');
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Empréstimo registado com sucesso.'];
        redirect(APP_URL . '/private/tecnico/dispositivos/emprestimos.php');
    }
}

// Apenas dispositivos disponíveis
$dispositivos = $db->query("SELECT id, codigo FROM dispositivos WHERE estado='disponivel' AND ativo=1 ORDER BY codigo")->fetchAll();
$utentes      = $db->query("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id ORDER BY u.nome")->fetchAll();
$pre_disp     = (int)($_GET['disp'] ?? 0);

require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="lista_dispositivos.php">Dispositivos</a></li>
                    <li class="breadcrumb-item"><a href="emprestimos.php">Empréstimos</a></li>
                    <li class="breadcrumb-item active">Novo</li>
                </ol>
            </nav>
            <h1 class="mb-4">Registar Empréstimo</h1>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <?php if (empty($dispositivos)): ?>
                <div class="alert alert-warning">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    Não há dispositivos disponíveis para empréstimo. Todos estão emprestados, avariados ou desaparecidos.
                </div>
            <?php else: ?>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dispositivo <span class="text-danger">*</span></label>
                        <select name="dispositivo_id" class="form-select" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($dispositivos as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $d['id'] == $pre_disp ? 'selected' : '' ?>>
                                    <?= h($d['codigo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Utente <span class="text-danger">*</span></label>
                        <select name="utente_id" class="form-select" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($utentes as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= (($_POST['utente_id'] ?? 0) == $u['id']) ? 'selected' : '' ?>>
                                    <?= h($u['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data de Entrega</label>
                            <input type="text" class="form-control" value="<?= date('d/m/Y') ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Devolução Prevista</label>
                            <input type="date" name="data_prevista_devolucao" class="form-control"
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= h($_POST['data_prevista_devolucao'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notas</label>
                        <textarea name="notas" class="form-control" rows="2"
                                  placeholder="Observações sobre o empréstimo..."><?= h($_POST['notas'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#1a5f8a;color:#fff;">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Registar Empréstimo
                        </button>
                        <a href="lista_dispositivos.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
