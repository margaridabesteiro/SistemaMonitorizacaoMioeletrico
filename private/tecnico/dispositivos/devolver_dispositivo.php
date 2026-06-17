<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('tecnico');

$pagina_titulo = 'Registar Devolução'; $pagina_ativa = 'dispositivos';
$db     = getDB();
$emp_id = (int)($_GET['emp'] ?? 0);
if (!$emp_id) redirect(APP_URL . '/private/tecnico/dispositivos/emprestimos.php');

$stmt = $db->prepare("
    SELECT e.*, d.codigo, d.id AS dispositivo_id,
           u.nome AS utente
    FROM emprestimos_dispositivos e
    JOIN dispositivos d ON d.id = e.dispositivo_id
    JOIN utentes ut ON ut.id = e.utente_id
    JOIN utilizadores u ON u.id = ut.utilizador_id
    WHERE e.id = ? AND e.data_devolucao IS NULL
");
$stmt->execute([$emp_id]); $emp = $stmt->fetch();
if (!$emp) redirect(APP_URL . '/private/tecnico/dispositivos/emprestimos.php');

$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $estado_dev = $_POST['estado_devolucao'] ?? 'bom';
    $data_dev   = trim($_POST['data_devolucao'] ?? '') ?: date('Y-m-d H:i:s');
    $notas      = trim($_POST['notas'] ?? '') ?: null;

    if (!in_array($estado_dev, ['bom','danificado','perdido'], true)) {
        $erros[] = 'Estado de devolução inválido.';
    }

    if (empty($erros)) {
        $db->prepare('UPDATE emprestimos_dispositivos SET data_devolucao=?, estado_devolucao=?, notas=CONCAT(COALESCE(notas,\'\'), ?) WHERE id=?')
           ->execute([$data_dev, $estado_dev, $notas ? "\nDevolução: $notas" : '', $emp_id]);

        $novo_estado = match($estado_dev) {
            'danificado' => 'avariado',
            'perdido'    => 'perdido',
            default      => 'disponivel',
        };
        $db->prepare('UPDATE dispositivos SET estado=? WHERE id=?')->execute([$novo_estado, $emp['dispositivo_id']]);
        registarAuditoria('ATUALIZAR', 'Dispositivo', $emp['dispositivo_id'], 'Devolução registada — estado: ' . $novo_estado);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Devolução registada. Dispositivo marcado como ' . ucfirst($novo_estado) . '.'];
        redirect(APP_URL . '/private/tecnico/dispositivos/emprestimos.php');
    }
}

require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="lista_dispositivos.php">Dispositivos</a></li>
                    <li class="breadcrumb-item"><a href="emprestimos.php">Empréstimos</a></li>
                    <li class="breadcrumb-item active">Devolução</li>
                </ol>
            </nav>
            <h1 class="mb-4">Registar Devolução</h1>

            <div class="alert alert-info py-2 mb-4">
                <i class="fa-solid fa-microchip me-2"></i>
                <strong><?= h($emp['codigo']) ?></strong> emprestado a <strong><?= h($emp['utente']) ?></strong>
                em <?= h(substr($emp['data_entrega'],0,10)) ?>
            </div>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <div class="card p-4" style="max-width:500px;">
                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado na Devolução</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="estado_devolucao"
                                       value="bom" id="estadoBom" checked>
                                <label class="form-check-label text-success" for="estadoBom">
                                    <i class="fa-solid fa-circle-check me-1"></i>Bom estado
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="estado_devolucao"
                                       value="danificado" id="estadoDanif">
                                <label class="form-check-label text-warning" for="estadoDanif">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Danificado
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="estado_devolucao"
                                       value="perdido" id="estadoPerdido">
                                <label class="form-check-label text-danger" for="estadoPerdido">
                                    <i class="fa-solid fa-circle-xmark me-1"></i>Perdido
                                </label>
                            </div>
                        </div>
                        <div class="form-text mt-2">
                            <span class="text-success">Bom estado</span> → disponível para novo empréstimo &nbsp;|&nbsp;
                            <span class="text-warning">Danificado</span> → marcado como avariado &nbsp;|&nbsp;
                            <span class="text-danger">Perdido</span> → marcado como desaparecido
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Data de Devolução</label>
                        <input type="datetime-local" name="data_devolucao" class="form-control"
                               value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notas</label>
                        <textarea name="notas" class="form-control" rows="2"
                                  placeholder="Observações sobre o estado do dispositivo..."></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#1a5f8a;color:#fff;">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Confirmar Devolução
                        </button>
                        <a href="emprestimos.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
