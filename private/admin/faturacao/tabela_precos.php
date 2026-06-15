<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Tabela de Preços'; $pagina_ativa = 'precos';
$db = getDB();
$flash = null;

$TIPOS = [
    'avaliacao_emg'       => 'Avaliação EMG de Superfície',
    'treino_mioeletrico'  => 'Sessão Treino Mioeléctrico',
    'consulta_medica'     => 'Consulta Médica (Fisiatria)',
    'sessao_biofeedback'  => 'Sessão de Biofeedback EMG',
    'avaliacao_funcional' => 'Avaliação Funcional',
    'sessao_jogo'         => 'Sessão por Jogo de Reabilitação',
    'teleconsulta'        => 'Teleconsulta (Videochamada)',
    'relatorio_clinico'   => 'Relatório Clínico',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_seguradora') {
        $seg_id = (int)($_POST['seg_id'] ?? 0);
        $nome   = trim($_POST['nome']    ?? '');
        $tipo   = in_array($_POST['tipo'] ?? '', ['SNS','Seguro','Particular']) ? $_POST['tipo'] : 'Seguro';
        $ativa  = isset($_POST['ativa']) ? 1 : 0;
        $notas  = trim($_POST['notas']   ?? '');
        if ($nome === '') {
            $flash = ['tipo'=>'danger','mensagem'=>'Nome da seguradora é obrigatório.'];
        } elseif ($seg_id) {
            $db->prepare('UPDATE seguradoras SET nome=?,tipo=?,ativa=?,notas=? WHERE id=?')
               ->execute([$nome,$tipo,$ativa,$notas,$seg_id]);
            $flash = ['tipo'=>'success','mensagem'=>'Seguradora atualizada.'];
        } else {
            $db->prepare('INSERT INTO seguradoras (nome,tipo,ativa,notas) VALUES (?,?,?,?)')
               ->execute([$nome,$tipo,$ativa,$notas]);
            $flash = ['tipo'=>'success','mensagem'=>'Seguradora criada.'];
        }
    } elseif ($action === 'save_preco') {
        $tipo_servico = $_POST['tipo_servico'] ?? '';
        $seg_id       = (int)($_POST['seg_id'] ?? 1);
        $preco        = (float)str_replace(',','.', $_POST['preco'] ?? '0');
        if (array_key_exists($tipo_servico, $TIPOS) && $seg_id > 0 && $preco >= 0) {
            $db->prepare('INSERT INTO tabela_precos (tipo_servico,seguradora_id,preco_eur)
                          VALUES (?,?,?)
                          ON DUPLICATE KEY UPDATE preco_eur=VALUES(preco_eur)')
               ->execute([$tipo_servico,$seg_id,$preco]);
            $flash = ['tipo'=>'success','mensagem'=>'Preço atualizado.'];
        }
    }
}

$seguradoras = $db->query('SELECT * FROM seguradoras ORDER BY tipo, nome')->fetchAll();
$precos_raw  = $db->query('SELECT tipo_servico, seguradora_id, preco_eur FROM tabela_precos')->fetchAll();
$matrix = [];
foreach ($precos_raw as $p) {
    $matrix[$p['tipo_servico']][$p['seguradora_id']] = (float)$p['preco_eur'];
}
$segs_ativas = array_filter($seguradoras, fn($s) => $s['ativa']);

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <h1 class="mb-4">Tabela de Preços</h1>
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> alert-dismissible py-2">
                    <?= h($flash['mensagem']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ── TABELA DE PREÇOS ──────────────────────────────────── -->
            <div class="card p-4">
                <h5 class="mb-3"><i class="fa-solid fa-table me-2" style="color:#8B0000;"></i>Preços por Serviço e Seguradora</h5>
                <p class="small text-muted mb-3">Clique num preço para editar.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo de Serviço</th>
                                <?php foreach ($segs_ativas as $seg): ?>
                                    <th class="text-center" style="min-width:90px;"><?= $seg['nome'] === 'Particular' ? 'Preço Base' : h($seg['nome']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($TIPOS as $codigo => $label): ?>
                        <tr>
                            <td class="fw-semibold"><?= h($label) ?></td>
                            <?php foreach ($segs_ativas as $seg): ?>
                                <?php $preco = $matrix[$codigo][$seg['id']] ?? null; ?>
                                <td class="text-center">
                                    <button class="btn btn-link btn-sm p-0 text-dark text-decoration-none"
                                            data-bs-toggle="modal" data-bs-target="#modalPreco"
                                            onclick="abrirModalPreco('<?= $codigo ?>','<?= addslashes($label) ?>',<?= $seg['id'] ?>,'<?= h($seg['nome']) ?>','<?= $preco !== null ? number_format($preco,2,',','.') : '' ?>')">
                                        <?php if ($preco !== null): ?>
                                            <strong><?= number_format($preco,2,',','.') ?>€</strong>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </button>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

<!-- Modal: Preço -->
<div class="modal fade" id="modalPreco" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="save_preco">
                <input type="hidden" name="tipo_servico" id="mp_tipo">
                <input type="hidden" name="seg_id" id="mp_seg_id">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0" id="mp_titulo">Editar Preço</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2" id="mp_desc"></p>
                    <label class="form-label fw-semibold">Preço (€)</label>
                    <input type="number" name="preco" id="mp_preco" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm" style="background:#8B0000;color:#fff;">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalPreco(tipo, label, segId, segNome, precoAtual) {
    document.getElementById('mp_tipo').value    = tipo;
    document.getElementById('mp_seg_id').value  = segId;
    document.getElementById('mp_titulo').textContent = 'Editar Preço';
    document.getElementById('mp_desc').textContent   = label + ' — ' + segNome;
    document.getElementById('mp_preco').value   = precoAtual.replace(',','.');
}
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
