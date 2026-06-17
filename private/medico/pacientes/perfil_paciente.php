<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/medico/pacientes/gestaoUtente.php');

// Atribuir técnico ao paciente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_acao'] ?? '') === 'assign_tecnico') {
    $tecnico_id = (int)($_POST['tecnico_id'] ?? 0) ?: null;
    $db->prepare('UPDATE utentes SET tecnico_id=? WHERE id=? AND medico_id=?')
       ->execute([$tecnico_id, $id, $pid]);
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Técnico atribuído com sucesso.'];
    redirect(APP_URL . '/private/medico/pacientes/perfil_paciente.php?id=' . $id);
}

// Editar informação clínica (diagnóstico)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_acao'] ?? '') === 'edit_clinica') {
    $diagnostico = trim($_POST['diagnostico'] ?? '') ?: null;
    $observacoes = trim($_POST['observacoes'] ?? '') ?: null;
    $db->prepare('UPDATE utentes SET diagnostico=?, observacoes=? WHERE id=? AND medico_id=?')
       ->execute([$diagnostico, $observacoes, $id, $pid]);
    registarAuditoria('ATUALIZAR', 'Utente', $id, 'Informação clínica atualizada pelo médico');

    $nq = $db->prepare("SELECT u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.id=?");
    $nq->execute([$id]); $utente_nome = $nq->fetchColumn() ?: 'utente';
    notificar($uid, 'prescricao',
        'Ir para Tratamentos',
        'Relatório clínico de ' . $utente_nome . ' atualizado. Verifique e complete os tratamentos prescritos.',
        APP_URL . '/private/medico/prescricoes/lista_prescricoes.php'
    );

    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Informação clínica atualizada.'];
    redirect(APP_URL . '/private/medico/pacientes/perfil_paciente.php?id=' . $id);
}

$pagina_titulo = 'Perfil do Paciente'; $pagina_ativa = 'pacientes';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';

$stmt = $db->prepare("
    SELECT ut.*, u.nome, u.email
    FROM utentes ut
    JOIN utilizadores u ON u.id = ut.utilizador_id
    WHERE ut.id = ? AND ut.medico_id = ?
");
$stmt->execute([$id, $pid]); $pac = $stmt->fetch();
if (!$pac) redirect(APP_URL . '/private/medico/pacientes/gestaoUtente.php');

// Técnico atualmente atribuído
$tecnico_atual_nome = null;
if ($pac['tecnico_id']) {
    $t = $db->prepare('SELECT u.nome FROM profissionais p JOIN utilizadores u ON u.id=p.utilizador_id WHERE p.id=?');
    $t->execute([$pac['tecnico_id']]);
    $tecnico_atual_nome = $t->fetchColumn() ?: null;
}

// Lista de técnicos ativos
$tecnicos_lista = $db->query("
    SELECT p.id, u.nome
    FROM profissionais p
    JOIN utilizadores u ON u.id = p.utilizador_id
    WHERE u.perfil = 'tecnico' AND u.ativo = 1
    ORDER BY u.nome
")->fetchAll();

// Contagens gerais
$s = $db->prepare("SELECT COUNT(*) FROM sessoes WHERE utente_id=? AND estado='concluida'");
$s->execute([$id]); $n_sessoes = (int)$s->fetchColumn();

// Sessões do utente para o médico ver (Feature 7)
$sessoes_medico = [];
try {
    $sq = $db->prepare("
        SELECT s.id, s.data_hora, s.estado, s.modalidade, s.link_videochamada,
               s.notas, s.categoria,
               j.nome AS jogo,
               u.nome AS tecnico,
               s.progressao, s.esforco_score, s.analise_tecnica
        FROM sessoes s
        LEFT JOIN jogos j ON j.id = s.jogo_id
        LEFT JOIN profissionais p ON p.id = s.tecnico_id
        LEFT JOIN utilizadores u ON u.id = p.utilizador_id
        WHERE s.utente_id = ?
        ORDER BY s.data_hora DESC LIMIT 30
    ");
    $sq->execute([$id]);
    $sessoes_medico = $sq->fetchAll();
} catch (\Throwable $e) {}
$s = $db->prepare("SELECT COUNT(*) FROM consultas WHERE utente_id=? AND medico_id=?");
$s->execute([$id, $pid]); $n_consultas = (int)$s->fetchColumn();

// Evolução percentagem_final ao longo das sessões concluídas
$evolucao = $db->query("
    SELECT DATE_FORMAT(s.data_hora, '%d/%m/%Y') AS data,
           ROUND(m.percentagem_final, 1) AS pct,
           j.nome AS jogo
    FROM sessoes s
    JOIN metricas_sessao m ON m.sessao_id = s.id
    LEFT JOIN jogos j ON j.id = s.jogo_id
    WHERE s.utente_id = $id AND s.estado = 'concluida' AND m.percentagem_final IS NOT NULL
    ORDER BY s.data_hora
    LIMIT 30
")->fetchAll();

// Últimas consultas
$consultas = $db->prepare("
    SELECT c.data_hora, c.tipo, c.modalidade, c.estado, c.motivo
    FROM consultas c
    WHERE c.utente_id = ? AND c.medico_id = ?
    ORDER BY c.data_hora DESC
    LIMIT 5
");
$consultas->execute([$id, $pid]); $consultas = $consultas->fetchAll();

// Programas de tratamento deste médico para este utente
$programas_trat = [];
try {
    $spt = $db->prepare("
        SELECT pt.data_prescricao, pt.objetivos_clinicos, pt.membro_afetado,
               pt.num_sessoes_prescritas, pt.data_validade, pt.observacoes, pt.ativa
        FROM programas_tratamento pt
        WHERE pt.utente_id = ? AND pt.medico_id = ?
        ORDER BY pt.ativa DESC, pt.data_prescricao DESC
    ");
    $spt->execute([$id, $pid]); $programas_trat = $spt->fetchAll();
} catch (\Throwable $e) { $programas_trat = []; }

$fase_labels = ['avaliacao' => 'Avaliação', 'ativo' => 'Ativo', 'manutencao' => 'Manutenção', 'alta' => 'Alta'];
$fase_cores  = ['avaliacao' => 'secondary', 'ativo' => 'primary', 'manutencao' => 'info', 'alta' => 'success'];
$tipo_badge  = ['inicial' => 'info', 'rotina' => 'secondary', 'alta' => 'success', 'urgente' => 'danger'];
?>
        <main class="content">
            <?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:70px;height:70px;border-radius:50%;background:#fce8e8;display:flex;align-items:center;justify-content:center;font-size:2rem;">
                        <i class="fa-regular fa-user" style="color:#8B0000;"></i>
                    </div>
                    <div>
                        <h1 class="mb-0"><?= h($pac['nome']) ?></h1>
                        <p class="text-muted mb-0"><?= h($pac['email']) ?></p>
                        <?php $f = $pac['fase_tratamento'] ?? 'avaliacao'; ?>
                        <span class="badge bg-<?= $fase_cores[$f] ?? 'secondary' ?>"><?= $fase_labels[$f] ?? $f ?></span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="nova_consulta.php?utente_id=<?= $id ?>" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                        <i class="fa-regular fa-calendar-plus me-1"></i>Nova Consulta
                    </a>
                    <a href="gestaoUtente.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
                    </a>
                </div>
            </div>

            <!-- Métricas rápidas -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-success"><?= $n_sessoes ?></div>
                        <div class="text-muted small">Sessões Realizadas</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-primary"><?= $n_consultas ?></div>
                        <div class="text-muted small">Consultas</div>
                    </div>
                </div>
            </div>

            <!-- Equipa de Tratamento -->
            <div class="card p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-user-doctor me-2" style="color:#8B0000;"></i>Equipa de Tratamento</h6>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Médico responsável</small>
                        <span class="fw-semibold"><?= h($_SESSION['nome'] ?? '—') ?></span>
                    </div>
                    <div class="col-md-8">
                        <small class="text-muted d-block mb-1">Técnico atribuído</small>
                        <?php if ($tecnico_atual_nome): ?>
                            <span class="badge bg-success me-2"><i class="fa-solid fa-user-gear me-1"></i><?= h($tecnico_atual_nome) ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark me-2">Sem técnico atribuído</span>
                        <?php endif; ?>
                        <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#formTecnico">
                            <i class="fa-solid fa-pen me-1"></i><?= $tecnico_atual_nome ? 'Alterar' : 'Atribuir' ?>
                        </button>
                        <div class="collapse mt-2" id="formTecnico">
                            <form method="POST" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="_acao" value="assign_tecnico">
                                <select name="tecnico_id" class="form-select form-select-sm" style="max-width:250px;">
                                    <option value="">— Sem técnico —</option>
                                    <?php foreach ($tecnicos_lista as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $pac['tecnico_id'] == $t['id'] ? 'selected' : '' ?>>
                                            <?= h($t['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm" style="background:#8B0000;color:#fff;">Guardar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info clínica -->
            <div class="card p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-stethoscope me-2" style="color:#8B0000;"></i>Informação Clínica</h6>
                    <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#formClinica">
                        <i class="fa-solid fa-pen me-1"></i>Editar
                    </button>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Diagnóstico:</strong> <span class="text-muted"><?= h($pac['diagnostico'] ?? '—') ?></span></p>
                    </div>
                    <div class="col-md-6">
                        <?php if ($pac['membro_afetado']): ?>
                            <p class="mb-1"><strong>Membro afetado:</strong> <?= h(str_replace('_', ' ', $pac['membro_afetado'])) ?></p>
                        <?php endif; ?>
                        <?php if ($pac['data_inicio_tratamento']): ?>
                            <p class="mb-1"><strong>Início tratamento:</strong> <?= h($pac['data_inicio_tratamento']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($pac['observacoes'])): ?>
                            <p class="mb-1"><strong>Observações:</strong> <span class="text-muted"><?= nl2br(h($pac['observacoes'])) ?></span></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="collapse" id="formClinica">
                    <hr class="my-2">
                    <form method="POST" class="row g-2">
                        <input type="hidden" name="_acao" value="edit_clinica">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Diagnóstico</label>
                            <input type="text" name="diagnostico" class="form-control form-control-sm"
                                   placeholder="Ex: AVC com hemiplegia esquerda"
                                   value="<?= h($pac['diagnostico'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Observações clínicas</label>
                            <textarea name="observacoes" class="form-control form-control-sm" rows="2"
                                      placeholder="Notas clínicas adicionais..."><?= h($pac['observacoes'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Informação Clínica
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Gráfico de evolução -->
            <?php if (!empty($evolucao)): ?>
            <div class="card p-3 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-chart-line me-2" style="color:#8B0000;"></i>Evolução nas Sessões de Treino</h5>
                <canvas id="chartEvolucao" style="max-height:280px;"></canvas>
            </div>
            <?php endif; ?>

            <div class="row g-3">
                <!-- Últimas consultas -->
                <div class="col-12">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Últimas Consultas</h5>
                            <a href="../consultas/consulta.php" class="btn btn-xs btn-outline-secondary">Ver todas</a>
                        </div>
                        <?php if (empty($consultas)): ?>
                            <p class="text-muted small">Sem consultas.</p>
                        <?php else: ?>
                            <table class="table table-sm table-hover">
                                <thead><tr><th>Data</th><th>Tipo</th><th>Estado</th></tr></thead>
                                <tbody>
                                <?php foreach ($consultas as $c): ?>
                                    <tr>
                                        <td><?= h(substr($c['data_hora'], 0, 10)) ?></td>
                                        <td><span class="badge bg-<?= $tipo_badge[$c['tipo']] ?? 'secondary' ?>"><?= h(ucfirst($c['tipo'])) ?></span></td>
                                        <td><span class="badge bg-<?= ['agendada' => 'primary', 'realizada' => 'success', 'cancelada' => 'danger'][$c['estado']] ?? 'secondary' ?>"><?= h($c['estado']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Programas de Tratamento -->
            <?php if (!empty($programas_trat)): ?>
            <div class="card p-3 mt-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fa-solid fa-file-medical me-2" style="color:#8B0000;"></i>Programas de Tratamento</h5>
                    <a href="<?= APP_URL ?>/private/medico/prescricoes/nova_prescricao.php" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                        <i class="fa-solid fa-plus me-1"></i>Novo Programa
                    </a>
                </div>
                <?php foreach ($programas_trat as $pt): ?>
                <div class="border rounded p-3 mb-3 <?= $pt['ativa'] ? 'border-success' : 'border-secondary' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-<?= $pt['ativa'] ? 'success' : 'secondary' ?>"><?= $pt['ativa'] ? 'Ativo' : 'Inativo' ?></span>
                        <div class="text-end">
                            <small class="text-muted">Prescrito em <?= h(date('d/m/Y', strtotime($pt['data_prescricao']))) ?></small>
                            <?php if ($pt['data_validade']): ?>
                            <br><small class="text-muted">Válido até <?= h(date('d/m/Y', strtotime($pt['data_validade']))) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="mb-1"><strong>Objetivos:</strong> <?= h($pt['objetivos_clinicos'] ?? '—') ?></p>
                    <?php if ($pt['membro_afetado']): ?>
                    <p class="mb-1 small"><strong>Membro:</strong> <?= h(str_replace('_', ' ', $pt['membro_afetado'])) ?></p>
                    <?php endif; ?>
                    <?php if ($pt['num_sessoes_prescritas']): ?>
                    <p class="mb-1 small"><strong>Sessões prescritas:</strong> <?= h($pt['num_sessoes_prescritas']) ?></p>
                    <?php endif; ?>
                    <?php if ($pt['observacoes']): ?>
                    <p class="mb-0 small text-muted fst-italic"><?= h($pt['observacoes']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Sessões do paciente (Feature 7 + 10) -->
            <?php
            $prog_cor_m   = ['melhoria'=>'#198754','estavel'=>'#6c757d','regressao'=>'#dc3545'];
            $prog_icon_m  = ['melhoria'=>'fa-arrow-trend-up','estavel'=>'fa-minus','regressao'=>'fa-arrow-trend-down'];
            $prog_label_m = ['melhoria'=>'Melhoria','estavel'=>'Estável','regressao'=>'Regressão'];
            $estado_cor_m = ['agendada'=>'warning text-dark','em_curso'=>'info text-dark','concluida'=>'success','cancelada'=>'secondary'];
            ?>
            <div class="card p-3 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fa-solid fa-calendar-check me-2" style="color:#8B0000;"></i>Sessões</h5>
                    <small class="text-muted">Últimas 30 · clique para detalhes</small>
                </div>
                <?php if (empty($sessoes_medico)): ?>
                <p class="text-muted small">Sem sessões registadas.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th><th>Sessão</th><th>Técnico</th><th>Estado</th>
                                <th>Progressão</th><th>Esforço</th><th>Teleconsulta</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sessoes_medico as $sv):
                            $prog_s = $sv['progressao'] ?? null;
                        ?>
                        <tr>
                            <td class="text-nowrap"><?= date('d/m/Y', strtotime($sv['data_hora'])) ?><br>
                                <small class="text-muted"><?= date('H:i', strtotime($sv['data_hora'])) ?></small>
                            </td>
                            <td><?= h($sv['jogo'] ?? ucfirst(str_replace('_',' ',$sv['categoria']??'—'))) ?></td>
                            <td><?= h($sv['tecnico'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $estado_cor_m[$sv['estado']] ?? 'secondary' ?>"><?= h($sv['estado']) ?></span></td>
                            <td>
                                <?php if ($prog_s): ?>
                                <span class="badge" style="background:<?= $prog_cor_m[$prog_s] ?>;">
                                    <i class="fa-solid <?= $prog_icon_m[$prog_s] ?> me-1"></i><?= $prog_label_m[$prog_s] ?>
                                </span>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sv['esforco_score']): ?>
                                <span style="color:#ffc107;font-size:.9rem;">
                                    <?= str_repeat('★',(int)$sv['esforco_score']) ?><?= str_repeat('☆',5-(int)$sv['esforco_score']) ?>
                                </span>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sv['link_videochamada']): ?>
                                    <a href="<?= h($sv['link_videochamada']) ?>" target="_blank" class="btn btn-xs btn-primary" title="Entrar">
                                        <i class="fa-solid fa-video"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (in_array($sv['estado'], ['agendada','em_curso'])): ?>
                                    <button type="button" class="btn btn-xs btn-outline-secondary ms-1"
                                            onclick="abrirModalLink(<?= $sv['id'] ?>,'<?= h(addslashes($sv['link_videochamada']??'')) ?>')"
                                            title="<?= $sv['link_videochamada'] ? 'Alterar link' : 'Adicionar link' ?>">
                                        <i class="fa-solid fa-<?= $sv['link_videochamada'] ? 'pen' : 'plus' ?>"></i>
                                    </button>
                                <?php elseif (!$sv['link_videochamada']): ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sv['analise_tecnica']): ?>
                                <button type="button" class="btn btn-xs btn-outline-primary"
                                        onclick="verAnalise(<?= $sv['id'] ?>)"
                                        title="Ver análise técnica">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($sv['analise_tecnica']): ?>
                        <tr id="analise-<?= $sv['id'] ?>" style="display:none;">
                            <td colspan="8" class="ps-4" style="background:#f8f9fa;">
                                <small class="text-muted fw-semibold">Análise técnica:</small>
                                <p class="mb-0 small mt-1"><?= nl2br(h($sv['analise_tecnica'])) ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>

<!-- Modal: Definir Link Teleconsulta -->
<div class="modal fade" id="modalLink" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#8B0000;color:#fff;">
                <h6 class="modal-title mb-0"><i class="fa-solid fa-video me-2"></i>Link de Teleconsulta</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="linkSessaoId">
                <label class="form-label small fw-semibold">URL da videochamada</label>
                <input type="url" id="linkInput" class="form-control" placeholder="https://meet.google.com/...">
                <div id="linkErro" class="text-danger small mt-1" style="display:none;"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="guardarLink('')">Remover link</button>
                <button type="button" class="btn btn-sm" style="background:#8B0000;color:#fff;" onclick="guardarLink(document.getElementById('linkInput').value)">Guardar</button>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($evolucao)):
    $labels = array_column($evolucao, 'data');
    $pcts   = array_column($evolucao, 'pct');
?>
        <script>
        new Chart(document.getElementById('chartEvolucao'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Percentagem Final (%)',
                    data: <?= json_encode(array_map('floatval', $pcts)) ?>,
                    borderColor: '#8B0000',
                    backgroundColor: 'rgba(139,0,0,.08)',
                    tension: 0.3,
                    pointRadius: 5,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { min: 0, max: 100, title: { display: true, text: '% Final' } }
                }
            }
        });
        </script>
<?php endif; ?>
<script>
function verAnalise(id) {
    var row = document.getElementById('analise-' + id);
    if (row) row.style.display = row.style.display === 'none' ? '' : 'none';
}

var _linkModal = null;
function abrirModalLink(sessaoId, linkAtual) {
    document.getElementById('linkSessaoId').value = sessaoId;
    document.getElementById('linkInput').value = linkAtual || '';
    document.getElementById('linkErro').style.display = 'none';
    if (!_linkModal) _linkModal = new bootstrap.Modal(document.getElementById('modalLink'));
    _linkModal.show();
}

function guardarLink(link) {
    var sessaoId = document.getElementById('linkSessaoId').value;
    var erroEl   = document.getElementById('linkErro');
    erroEl.style.display = 'none';
    fetch('<?= APP_URL ?>/api/medico/sessao/definir_link.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'sessao_id=' + encodeURIComponent(sessaoId) + '&link=' + encodeURIComponent(link)
    })
    .then(r => r.json())
    .then(function(data) {
        if (data.ok) {
            if (_linkModal) _linkModal.hide();
            location.reload();
        } else {
            erroEl.textContent = data.erro || 'Erro ao guardar.';
            erroEl.style.display = '';
        }
    })
    .catch(function() {
        erroEl.textContent = 'Erro de rede.';
        erroEl.style.display = '';
    });
}
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
