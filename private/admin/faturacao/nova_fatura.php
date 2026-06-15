<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Nova Fatura'; $pagina_ativa = 'faturacao';

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

$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utente_id    = (int)($_POST['utente_id']    ?? 0);
    $tipo_servico = $_POST['tipo_servico']        ?? '';
    $valor        = (float)str_replace(',','.', ($_POST['valor'] ?? '0'));
    $data_emissao = $_POST['data_emissao']        ?? date('Y-m-d');
    $data_venc    = $_POST['data_vencimento']     ?? null;
    $notas        = trim($_POST['notas']          ?? '');

    if (!$utente_id) $erros[] = 'Selecione um utente.';
    if (!array_key_exists($tipo_servico, $TIPOS)) $erros[] = 'Selecione um tipo de serviço.';
    if ($valor <= 0) $erros[] = 'Valor tem de ser maior que zero.';

    if (empty($erros)) {
        $db = getDB();
        $st = $db->prepare("SELECT ut.seguradora_id, ut.cobertura_saude FROM utentes ut WHERE ut.id=?");
        $st->execute([$utente_id]);
        $row = $st->fetch();
        $seg_id   = $row['seguradora_id'] ?: null;
        $paga_auto = ($row['cobertura_saude'] === 'SNS') ? 1 : 0;

        $ano = date('Y');
        $cnt = (int)$db->prepare("SELECT COUNT(*)+1 FROM faturas WHERE YEAR(data_emissao)=?")->execute([$ano]) ? $db->query("SELECT COUNT(*)+1 FROM faturas WHERE YEAR(data_emissao)=$ano")->fetchColumn() : 1;
        $numero = sprintf('FT%d/%03d', $ano, $cnt);

        $db->prepare('INSERT INTO faturas (numero,utente_id,tipo_servico,seguradora_id,valor_eur,paga,data_emissao,data_vencimento,notas)
                      VALUES (?,?,?,?,?,?,?,?,?)')
           ->execute([$numero,$utente_id,$tipo_servico,$seg_id,$valor,$paga_auto,$data_emissao,$data_venc?:null,$notas]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>"Fatura $numero criada."];
        redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
    }
}

$db = getDB();
$utentes = $db->query("
    SELECT ut.id, u.nome, ut.nif, ut.cobertura_saude, ut.seguradora_id, s.nome AS seg_nome
    FROM utentes ut
    JOIN utilizadores u ON u.id=ut.utilizador_id
    LEFT JOIN seguradoras s ON s.id=ut.seguradora_id
    ORDER BY u.nome
")->fetchAll();

// Mapa utente_id → {seguradora_id, seg_nome, cobertura}
$utente_map = [];
foreach ($utentes as $u) {
    $utente_map[$u['id']] = [
        'seg_id'    => $u['seguradora_id'] ?? 1,
        'seg_nome'  => $u['seg_nome'] ?? 'Particular',
        'cobertura' => $u['cobertura_saude'],
    ];
}

// Mapa de preços: [tipo_servico][seguradora_id] → preco
$precos_raw = $db->query('SELECT tipo_servico, seguradora_id, preco_eur FROM tabela_precos')->fetchAll();
$price_map = [];
foreach ($precos_raw as $p) {
    $price_map[$p['tipo_servico']][$p['seguradora_id']] = (float)$p['preco_eur'];
}

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="controlo_faturacao.php">Faturação</a></li>
                    <li class="breadcrumb-item active">Nova Fatura</li>
                </ol>
            </nav>
            <h1 class="mb-4">Nova Fatura</h1>
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <div class="card p-4" style="max-width:650px;">
                <form method="POST" id="formFatura">

                    <!-- Utente -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Utente *</label>
                        <select name="utente_id" id="utente_id" class="form-select" required onchange="onUtenteChange(this.value)">
                            <option value="">Selecionar utente...</option>
                            <?php foreach ($utentes as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= h($u['nome']) ?><?= $u['nif'] ? ' — NIF '.h($u['nif']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Seguradora (read-only, preenchida automaticamente) -->
                    <div class="mb-3" id="bloco-seguradora" style="display:none;">
                        <label class="form-label fw-semibold">Seguradora</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-shield-heart"></i></span>
                            <input type="text" id="seg_nome_display" class="form-control" readonly>
                        </div>
                    </div>

                    <!-- Aviso SNS -->
                    <div id="aviso_sns" class="alert alert-info py-2 small" style="display:none;">
                        <i class="fa-solid fa-circle-info me-1"></i>Utente SNS — preço corresponde à taxa moderadora aplicável.
                    </div>

                    <!-- Tipo de serviço -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Serviço *</label>
                        <select name="tipo_servico" id="tipo_servico" class="form-select" required onchange="onTipoChange()">
                            <option value="">Selecionar serviço...</option>
                            <?php foreach ($TIPOS as $cod => $label): ?>
                                <option value="<?= $cod ?>"><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Valor (€) *</label>
                            <div class="input-group">
                                <input type="number" name="valor" id="valor" class="form-control" step="0.01" min="0.01" required>
                                <span class="input-group-text">€</span>
                            </div>
                            <div class="form-text" id="preco_hint"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data Emissão *</label>
                            <input type="date" name="data_emissao" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Data Vencimento</label>
                        <input type="date" name="data_vencimento" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notas</label>
                        <textarea name="notas" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Criar Fatura
                        </button>
                        <a href="controlo_faturacao.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>

<script>
const utenteMap = <?= json_encode($utente_map) ?>;
const priceMap  = <?= json_encode($price_map) ?>;
let currentSegId = 1;

function onUtenteChange(utenteId) {
    const info = utenteMap[utenteId];
    if (!info) {
        document.getElementById('bloco-seguradora').style.display = 'none';
        document.getElementById('aviso_sns').style.display = 'none';
        currentSegId = 1;
        return;
    }
    currentSegId = info.seg_id || 1;
    document.getElementById('seg_nome_display').value = info.seg_nome || 'Particular';
    document.getElementById('bloco-seguradora').style.display = 'block';
    document.getElementById('aviso_sns').style.display = (info.cobertura === 'SNS') ? 'block' : 'none';
    onTipoChange();
}

function onTipoChange() {
    const tipo = document.getElementById('tipo_servico').value;
    if (!tipo) return;
    const tipoPrecos = priceMap[tipo] || {};
    // Tenta preço da seguradora, fallback para "Particular" (id=1)
    const preco = tipoPrecos[currentSegId] !== undefined ? tipoPrecos[currentSegId]
                : (tipoPrecos[1] !== undefined ? tipoPrecos[1] : '');
    if (preco !== '') {
        document.getElementById('valor').value = parseFloat(preco).toFixed(2);
        const fromBase = (tipoPrecos[currentSegId] === undefined && currentSegId != 1);
        document.getElementById('preco_hint').textContent = fromBase
            ? 'Preço base aplicado (seguradora sem preço específico)'
            : 'Preço automático da tabela — pode ser ajustado';
    } else {
        document.getElementById('valor').value = '';
        document.getElementById('preco_hint').textContent = 'Introduza o valor manualmente';
    }
}
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
