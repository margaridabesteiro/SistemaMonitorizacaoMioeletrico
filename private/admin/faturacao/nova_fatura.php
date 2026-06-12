<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Nova Fatura'; $pagina_ativa = 'faturacao';
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utente_id = (int)($_POST['utente_id'] ?? 0);
    $valor = (float)str_replace(',','.',($_POST['valor'] ?? '0'));
    $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
    $data_venc = $_POST['data_vencimento'] ?? null;
    $notas = trim($_POST['notas'] ?? '');
    if (!$utente_id) $erros[] = 'Selecione um utente.';
    if ($valor < 0) $erros[] = 'Valor inválido.';
    if (empty($erros)) {
        $db = getDB();
        // Verificar cobertura SNS
        $sc = $db->prepare("SELECT cobertura_saude FROM utentes WHERE id=?"); $sc->execute([$utente_id]);
        $paga_auto = ($sc->fetchColumn() === 'SNS') ? 1 : 0;
        $ano = date('Y'); $sf = $db->prepare("SELECT COUNT(*)+1 FROM faturas WHERE YEAR(data_emissao)=?"); $sf->execute([$ano]); $cnt = (int)$sf->fetchColumn();
        $numero = sprintf('FT%d/%03d', $ano, $cnt);
        $db->prepare('INSERT INTO faturas (numero,utente_id,valor_eur,paga,data_emissao,data_vencimento,notas) VALUES (?,?,?,?,?,?,?)')->execute([$numero,$utente_id,$valor,$paga_auto,$data_emissao,$data_venc?:null,$notas]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>"Fatura $numero criada."]; redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
    }
}
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
$utentes = $db->query("SELECT ut.id, u.nome, ut.nif, ut.cobertura_saude FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id ORDER BY u.nome")->fetchAll();
$cobertura_map = [];
foreach ($utentes as $u) { $cobertura_map[$u['id']] = $u['cobertura_saude']; }
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="controlo_faturacao.php">Faturação</a></li><li class="breadcrumb-item active">Nova Fatura</li></ol></nav>
            <h1 class="mb-4">Nova Fatura</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
                    <div class="mb-3"><label class="form-label fw-semibold">Utente *</label>
                        <select name="utente_id" id="utente_id" class="form-select" required onchange="verificarSNS(this)"><option value="">Selecionar...</option>
                            <?php foreach($utentes as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['nome']) ?><?= $u['nif'] ? ' — NIF: '.h($u['nif']) : '' ?></option><?php endforeach; ?>
                        </select></div>
                    <div id="aviso_sns" class="alert alert-info py-2 small" style="display:none;">
                        <i class="fa-solid fa-circle-info me-1"></i>Utente com cobertura SNS — valor definido automaticamente a 0,00 €.
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Valor (€) *</label><input type="number" name="valor" id="valor" class="form-control" step="0.01" min="0" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Data Emissão *</label><input type="date" name="data_emissao" id="data_emissao" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Data Vencimento</label><input type="date" name="data_vencimento" id="data_vencimento" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>"></div>
                    <div class="mb-4"><label class="form-label fw-semibold">Notas</label><textarea name="notas" class="form-control" rows="3"></textarea></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Criar Fatura</button>
                        <a href="controlo_faturacao.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
                <script>
                const coberturaMap = <?= json_encode($cobertura_map) ?>;
                function verificarSNS(sel) {
                    const isSNS = coberturaMap[sel.value] === 'SNS';
                    document.getElementById('aviso_sns').style.display = isSNS ? 'block' : 'none';
                    const campoValor = document.getElementById('valor');
                    if (isSNS) { campoValor.value = '0.00'; campoValor.readOnly = true; }
                    else        { campoValor.value = '';     campoValor.readOnly = false; }
                }
                </script>
            </div>
        </main>
