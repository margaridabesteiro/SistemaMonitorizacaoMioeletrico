<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Editar Fatura'; $pagina_ativa = 'faturacao';
$db = getDB();
$num = trim($_GET['num'] ?? '');
if (!$num) redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
$stmt = $db->prepare('SELECT * FROM faturas WHERE numero=?'); $stmt->execute([$num]); $f = $stmt->fetch();
if (!$f) redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');

$metodos_validos = ['multibanco','cartão','seguro','numerário','transferência'];
$metodo_labels = [
    'multibanco'    => 'Multibanco',
    'cartão'        => 'Cartão',
    'seguro'        => 'Seguro',
    'numerário'     => 'Numerário',
    'transferência' => 'Transferência',
];

$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor        = (float)str_replace(',','.',($_POST['valor'] ?? '0'));
    $data_emissao = $_POST['data_emissao'] ?? $f['data_emissao'];
    $data_venc    = $_POST['data_vencimento'] ?: null;
    $notas        = trim($_POST['notas'] ?? '');
    $paga         = isset($_POST['paga']) ? 1 : 0;
    $metodo       = ($paga && in_array($_POST['metodo_pagamento'] ?? '', $metodos_validos, true))
                    ? $_POST['metodo_pagamento'] : null;
    $data_pag     = ($paga && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data_pagamento'] ?? ''))
                    ? $_POST['data_pagamento'] : null;
    if ($valor <= 0) $erros[] = 'Valor inválido.';
    if (empty($erros)) {
        $db->prepare('UPDATE faturas SET valor_eur=?,data_emissao=?,data_vencimento=?,notas=?,paga=?,metodo_pagamento=?,data_pagamento=? WHERE numero=?')
           ->execute([$valor,$data_emissao,$data_venc,$notas,$paga,$metodo,$data_pag,$f['numero']]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Fatura atualizada.'];
        redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
    }
}
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="controlo_faturacao.php">Faturação</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
            <h1 class="mb-4">Editar Fatura <?= h($f['numero']) ?></h1>
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <div class="card p-4" style="max-width:560px;">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Valor (€) *</label>
                            <input type="number" name="valor" class="form-control" step="0.01" value="<?= $f['valor_eur'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data Emissão</label>
                            <input type="date" name="data_emissao" class="form-control" value="<?= h($f['data_emissao']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Data Vencimento</label>
                        <input type="date" name="data_vencimento" class="form-control" value="<?= h($f['data_vencimento'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notas</label>
                        <textarea name="notas" class="form-control" rows="3"><?= h($f['notas'] ?? '') ?></textarea>
                    </div>

                    <hr>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="paga" id="paga"
                               <?= $f['paga']?'checked':'' ?> onchange="togglePagamento(this.checked)">
                        <label class="form-check-label fw-semibold" for="paga">Fatura paga</label>
                    </div>

                    <div id="bloco_pagamento" style="<?= $f['paga'] ? '' : 'display:none;' ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Método de Pagamento</label>
                                <select name="metodo_pagamento" id="metodo_pagamento" class="form-select">
                                    <option value="">— Selecionar —</option>
                                    <?php foreach ($metodo_labels as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= ($f['metodo_pagamento'] ?? '') === $val ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Data de Pagamento</label>
                                <input type="date" name="data_pagamento" id="data_pagamento" class="form-control"
                                       value="<?= h($f['data_pagamento'] ?? date('Y-m-d')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
                        </button>
                        <a href="controlo_faturacao.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>

<script>
function togglePagamento(checked) {
    document.getElementById('bloco_pagamento').style.display = checked ? 'block' : 'none';
    if (checked && !document.getElementById('data_pagamento').value) {
        document.getElementById('data_pagamento').value = '<?= date('Y-m-d') ?>';
    }
}
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
