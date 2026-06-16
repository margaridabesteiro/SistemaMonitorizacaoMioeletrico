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
    $utente_id    = (int)($_POST['utente_id'] ?? 0);
    $data_emissao = date('Y-m-d');
    $data_venc    = trim($_POST['data_vencimento'] ?? '') ?: null;
    $notas        = trim($_POST['notas'] ?? '');
    $linhas       = json_decode($_POST['linhas_json'] ?? '[]', true) ?: [];

    if (!$utente_id)    $erros[] = 'Selecione um utente.';
    if (empty($linhas)) $erros[] = 'Adicione pelo menos um serviço.';
    if ($data_venc && $data_venc < $data_emissao) $erros[] = 'Data de vencimento não pode ser anterior à emissão.';

    $valor_total = 0.0;
    foreach ($linhas as $l) {
        $q = max(1, (int)($l['quantidade'] ?? 1));
        $p = max(0.0, (float)($l['preco_unit'] ?? 0));
        $valor_total += round($q * $p, 2);
    }
    if (empty($erros) && $valor_total <= 0) $erros[] = 'Total tem de ser maior que zero.';

    if (empty($erros)) {
        $db = getDB();
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS fatura_linhas (
                id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                fatura_id    INT UNSIGNED NOT NULL,
                tipo_servico VARCHAR(50)  NULL,
                descricao    VARCHAR(200) NOT NULL,
                quantidade   TINYINT UNSIGNED NOT NULL DEFAULT 1,
                preco_unit   DECIMAL(8,2) NOT NULL,
                total_linha  DECIMAL(8,2) NOT NULL,
                FOREIGN KEY (fatura_id) REFERENCES faturas(id) ON DELETE CASCADE,
                INDEX idx_fatura (fatura_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {}

        $st = $db->prepare("SELECT ut.seguradora_id, ut.cobertura_saude FROM utentes ut WHERE ut.id=?");
        $st->execute([$utente_id]);
        $row = $st->fetch();
        $seg_id    = $row['seguradora_id'] ?: null;
        $paga_auto = ($row['cobertura_saude'] === 'SNS') ? 1 : 0;

        $ano = date('Y');
        $cnt = (int)$db->query("SELECT COUNT(*)+1 FROM faturas WHERE YEAR(data_emissao)=$ano")->fetchColumn();
        $numero = sprintf('FT%d/%03d', $ano, $cnt);

        $tipo_principal = $linhas[0]['tipo_servico'] ?? null;

        $db->prepare('INSERT INTO faturas (numero,utente_id,tipo_servico,seguradora_id,valor_eur,paga,data_emissao,data_vencimento,notas)
                      VALUES (?,?,?,?,?,?,?,?,?)')
           ->execute([$numero, $utente_id, $tipo_principal, $seg_id, $valor_total, $paga_auto, $data_emissao, $data_venc, $notas]);
        $fatura_id = (int)$db->lastInsertId();

        $ins = $db->prepare('INSERT INTO fatura_linhas (fatura_id,tipo_servico,descricao,quantidade,preco_unit,total_linha) VALUES (?,?,?,?,?,?)');
        foreach ($linhas as $l) {
            $q = max(1, (int)($l['quantidade'] ?? 1));
            $p = max(0.0, (float)($l['preco_unit'] ?? 0));
            $ins->execute([$fatura_id, $l['tipo_servico'] ?: null, $l['descricao'], $q, $p, round($q * $p, 2)]);
        }

        $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => "Fatura $numero criada."];
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

$utente_map = [];
foreach ($utentes as $u) {
    $utente_map[$u['id']] = [
        'seg_id'    => $u['seguradora_id'] ?? 1,
        'seg_nome'  => $u['seg_nome'] ?? 'Particular',
        'cobertura' => $u['cobertura_saude'],
    ];
}

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

            <div class="card p-4" style="max-width:760px;">
                <form method="POST" id="formFatura" onsubmit="return prepareSubmit()">
                    <input type="hidden" name="linhas_json" id="linhas_json">

                    <!-- Utente -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Utente <span class="text-danger">*</span></label>
                        <select name="utente_id" id="utente_id" class="form-select" required onchange="onUtenteChange(this.value)">
                            <option value="">Selecionar utente...</option>
                            <?php foreach ($utentes as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= h($u['nome']) ?><?= $u['nif'] ? ' — NIF '.h($u['nif']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Seguradora (auto-preenchida) -->
                    <div class="mb-3" id="bloco-seguradora" style="display:none;">
                        <label class="form-label fw-semibold">Seguradora</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-shield-heart"></i></span>
                            <input type="text" id="seg_nome_display" class="form-control" readonly>
                        </div>
                    </div>

                    <div id="aviso_sns" class="alert alert-info py-2 small" style="display:none;">
                        <i class="fa-solid fa-circle-info me-1"></i>Utente SNS — preço corresponde à taxa moderadora aplicável.
                    </div>

                    <!-- Serviços (multi-item) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Serviços <span class="text-danger">*</span></label>

                        <!-- Tabela de itens adicionados -->
                        <div id="items-list" class="mb-2" style="display:none;">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Descrição</th>
                                        <th class="text-center" style="width:55px;">Qtd</th>
                                        <th class="text-end" style="width:100px;">Preço Unit.</th>
                                        <th class="text-end" style="width:90px;">Total</th>
                                        <th style="width:36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="items-tbody"></tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="3" class="text-end">Total</td>
                                        <td class="text-end text-danger" id="grand-total">0,00€</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Linha de adição -->
                        <div class="border rounded p-3 bg-light">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label form-label-sm mb-1">Tipo</label>
                                    <select id="add-tipo" class="form-select form-select-sm" onchange="onAddTipoChange()">
                                        <option value="">— Selecionar tipo —</option>
                                        <?php foreach ($TIPOS as $cod => $label): ?>
                                            <option value="<?= $cod ?>"><?= h($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm mb-1">Descrição</label>
                                    <input type="text" id="add-desc" class="form-control form-control-sm" placeholder="Texto livre">
                                </div>
                                <div class="col-sm-1" style="min-width:65px;">
                                    <label class="form-label form-label-sm mb-1">Qtd</label>
                                    <input type="number" id="add-qty" class="form-control form-control-sm" value="1" min="1" max="99">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm mb-1">Preço (€)</label>
                                    <input type="number" id="add-preco" class="form-control form-control-sm" step="0.01" min="0.01" placeholder="0,00">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-sm w-100" style="background:#8B0000;color:#fff;" onclick="addItem()">
                                        <i class="fa-solid fa-plus me-1"></i>Adicionar
                                    </button>
                                </div>
                            </div>
                            <div id="add-hint" class="form-text text-muted mt-2">Selecione o utente para preencher o preço automaticamente conforme o seguro associado.</div>
                        </div>
                    </div>

                    <!-- Datas -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data Emissão</label>
                            <input type="date" name="data_emissao" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data Vencimento</label>
                            <input type="date" name="data_vencimento" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <!-- Notas -->
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
const utenteMap  = <?= json_encode($utente_map) ?>;
const priceMap   = <?= json_encode($price_map) ?>;
const tipoLabels = <?= json_encode($TIPOS) ?>;
let currentSegId = 1;
let items = [];

function onUtenteChange(utenteId) {
    const info = utenteMap[utenteId];
    if (!info) {
        document.getElementById('bloco-seguradora').style.display = 'none';
        document.getElementById('aviso_sns').style.display = 'none';
        currentSegId = 1;
        document.getElementById('add-hint').textContent = 'Selecione o utente para preencher o preço automaticamente conforme o seguro associado.';
        return;
    }
    currentSegId = info.seg_id || 1;
    document.getElementById('seg_nome_display').value = info.seg_nome || 'Particular';
    document.getElementById('bloco-seguradora').style.display = 'block';
    document.getElementById('aviso_sns').style.display = info.cobertura === 'SNS' ? 'block' : 'none';
    document.getElementById('add-hint').textContent = '';
    onAddTipoChange();
}

function onAddTipoChange() {
    const tipo = document.getElementById('add-tipo').value;
    if (!tipo) return;
    document.getElementById('add-desc').value = tipoLabels[tipo] || '';
    const tipoPrecos = priceMap[tipo] || {};
    const preco = tipoPrecos[currentSegId] !== undefined ? tipoPrecos[currentSegId]
                : (tipoPrecos[1] !== undefined ? tipoPrecos[1] : '');
    if (preco !== '') {
        document.getElementById('add-preco').value = parseFloat(preco).toFixed(2);
    }
}

function addItem() {
    const tipo  = document.getElementById('add-tipo').value;
    const desc  = document.getElementById('add-desc').value.trim();
    const qty   = Math.max(1, parseInt(document.getElementById('add-qty').value) || 1);
    const preco = parseFloat(document.getElementById('add-preco').value) || 0;

    if (!desc)      { alert('Preencha a descrição do serviço.'); return; }
    if (preco <= 0) { alert('O preço tem de ser maior que zero.'); return; }

    items.push({
        tipo_servico: tipo || null,
        descricao:    desc,
        quantidade:   qty,
        preco_unit:   preco,
        total:        Math.round(qty * preco * 100) / 100
    });

    document.getElementById('add-tipo').value  = '';
    document.getElementById('add-desc').value  = '';
    document.getElementById('add-qty').value   = '1';
    document.getElementById('add-preco').value = '';

    renderItems();
}

function removeItem(idx) {
    items.splice(idx, 1);
    renderItems();
}

function renderItems() {
    const tbody    = document.getElementById('items-tbody');
    const list     = document.getElementById('items-list');
    const grandTot = document.getElementById('grand-total');

    if (!items.length) {
        list.style.display = 'none';
        grandTot.textContent = '0,00€';
        return;
    }
    list.style.display = 'block';

    let html = '', total = 0;
    items.forEach((it, i) => {
        total += it.total;
        html += `<tr>
            <td>${escHtml(it.descricao)}</td>
            <td class="text-center">${it.quantidade}</td>
            <td class="text-end">${formatEur(it.preco_unit)}</td>
            <td class="text-end fw-semibold">${formatEur(it.total)}</td>
            <td class="text-center p-1">
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeItem(${i})" title="Remover">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
    grandTot.textContent = formatEur(total);
}

function formatEur(v) {
    return new Intl.NumberFormat('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v) + '€';
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function prepareSubmit() {
    if (!items.length) {
        alert('Adicione pelo menos um serviço antes de criar a fatura.');
        return false;
    }
    document.getElementById('linhas_json').value = JSON.stringify(items);
    return true;
}
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
