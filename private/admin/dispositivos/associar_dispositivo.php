<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Novo Dispositivo'; $pagina_ativa = 'dispositivos';

$db = getDB();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $notas  = trim($_POST['notas']  ?? '') ?: null;

    if ($codigo === '') $erros[] = 'Código obrigatório.';
    if (empty($erros)) {
        $dup = $db->prepare('SELECT id FROM dispositivos WHERE codigo=?');
        $dup->execute([$codigo]);
        if ($dup->fetch()) $erros[] = 'Código já existe.';
    }
    if (empty($erros)) {
        $token = bin2hex(random_bytes(32));
        $db->prepare("INSERT INTO dispositivos (codigo, tipo, firmware_versao, estado, token_api, ativo) VALUES (?,?,?,'disponivel',?,1)")
           ->execute([$codigo, 'ESP32-FSR406', $notas, $token]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Dispositivo registado com sucesso.'];
        redirect(APP_URL . '/private/admin/dispositivos/lista_dispositivos.php');
    }
}

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="lista_dispositivos.php">Dispositivos</a></li><li class="breadcrumb-item active">Novo</li></ol></nav>
            <h1 class="mb-4">Registar Novo Dispositivo</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:560px;">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" class="form-control" placeholder="Ex: ESP32-001" required value="<?= h($_POST['codigo'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notas</label>
                        <input type="text" name="notas" class="form-control" placeholder="Opcional" value="<?= h($_POST['notas'] ?? '') ?>">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Registar</button>
                        <a href="lista_dispositivos.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
