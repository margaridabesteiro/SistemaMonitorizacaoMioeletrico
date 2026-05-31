<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Nova Fatura'; $pagina_ativa = 'faturacao';
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utente_id = (int)($_POST['utente_id'] ?? 0);
    $valor = (float)str_replace(',','.',($_POST['valor'] ?? '0'));
    $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
    // Data de vencimento: 3 dias após emissão
    $data_venc = date('Y-m-d', strtotime($data_emissao . ' +3 days'));
    $notas = trim($_POST['notas'] ?? '');
    if (!$utente_id) $erros[] = 'Selecione um utente.';
    if ($valor <= 0) $erros[] = 'Valor inválido.';
    if (empty($erros)) {
        $db = getDB();
        $ano = date('Y'); $cnt = (int)$db->query("SELECT COUNT(*)+1 FROM faturas WHERE YEAR(data_emissao)=$ano")->fetchColumn();
        $numero = sprintf('FT%d/%03d', $ano, $cnt);
        $db->prepare('INSERT INTO faturas (numero,utente_id,valor_eur,paga,data_emissao,data_vencimento,notas) VALUES (?,?,?,0,?,?,?)')->execute([$numero,$utente_id,$valor,$data_emissao,$data_venc?:null,$notas]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>"Fatura $numero criada."]; redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
    }
}
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
$utentes = $db->query("SELECT ut.id, u.nome, ut.nif FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id ORDER BY u.nome")->fetchAll();
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="controlo_faturacao.php">Faturação</a></li><li class="breadcrumb-item active">Nova Fatura</li></ol></nav>
            <h1 class="mb-4">Nova Fatura</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
                    <div class="mb-3"><label class="form-label fw-semibold">Utente *</label>
                        <select name="utente_id" class="form-select" required><option value="">Selecionar...</option>
                            <?php foreach($utentes as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['nome']) ?><?= $u['nif'] ? ' — NIF: '.h($u['nif']) : '' ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Valor (€) *</label><input type="number" name="valor" class="form-control" step="0.01" min="0" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Data Emissão *</label><input type="date" name="data_emissao" id="data_emissao" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="atualizarVenc(this)"></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Data Vencimento <small class="text-muted">(automático: 3 dias após emissão)</small></label><input type="text" id="data_venc_display" class="form-control" value="<?= date('d/m/Y', strtotime('+3 days')) ?>" readonly style="background:#f8f9fa;"></div>
                    <div class="mb-4"><label class="form-label fw-semibold">Notas</label><textarea name="notas" class="form-control" rows="3"></textarea></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Criar Fatura</button>
                        <a href="controlo_faturacao.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
                <script>
                function atualizarVenc(inp) {
                    var d = new Date(inp.value);
                    d.setDate(d.getDate() + 3);
                    var dd = String(d.getDate()).padStart(2,'0');
                    var mm = String(d.getMonth()+1).padStart(2,'0');
                    document.getElementById('data_venc_display').value = dd+'/'+mm+'/'+d.getFullYear();
                }
                </script>
            </div>
        </main>
