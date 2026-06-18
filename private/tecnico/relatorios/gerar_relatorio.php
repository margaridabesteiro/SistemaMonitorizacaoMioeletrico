<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Relatório Clínico'; $pagina_ativa = 'relatorios';
requirePerfil('tecnico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();

// Pacientes atribuídos a este técnico
$pacientes = $pid ? $db->prepare("
    SELECT ut.id, u.nome FROM utentes ut
    JOIN utilizadores u ON u.id=ut.utilizador_id
    WHERE ut.tecnico_id=? ORDER BY u.nome
") : null;
if ($pacientes) { $pacientes->execute([$pid]); $pacientes = $pacientes->fetchAll(); }
else { $pacientes = []; }

$sel = (int)($_GET['utente_id'] ?? ($pacientes[0]['id'] ?? 0));

$utente = $sessoes_hist = $prescricoes = $analises = null;

if ($sel) {
    // Dados base
    $su = $db->prepare("
        SELECT u.nome, u.email, ut.diagnostico, ut.cobertura_saude, ut.nif,
               s.nome AS seguradora, um.nome AS medico_nome
        FROM utentes ut
        JOIN utilizadores u ON u.id=ut.utilizador_id
        LEFT JOIN seguradoras s ON s.id=ut.seguradora_id
        LEFT JOIN profissionais pm ON pm.id=ut.medico_id
        LEFT JOIN utilizadores um ON um.id=pm.utilizador_id
        WHERE ut.id=?
    ");
    $su->execute([$sel]); $utente = $su->fetch();

    // Histórico de sessões
    $ss = $db->prepare("
        SELECT s.data_hora, s.estado, s.duracao_min, s.modalidade,
               j.nome AS jogo, s.notas,
               s.progressao, s.esforco_score, s.analise_tecnica,
               AVG(m.percentagem_final) AS prec_media, AVG(m.score_jogo) AS score_medio
        FROM sessoes s
        LEFT JOIN jogos j ON j.id=s.jogo_id
        LEFT JOIN metricas_sessao m ON m.sessao_id=s.id
        WHERE s.utente_id=?
        GROUP BY s.id ORDER BY s.data_hora DESC LIMIT 30
    ");
    try { $ss->execute([$sel]); $sessoes_hist = $ss->fetchAll(); }
    catch (\Throwable $e) { $sessoes_hist = []; }

    // Prescrições / tratamentos
    try {
        $sp = $db->prepare("
            SELECT pt.data_prescricao, pt.objetivos_clinicos, pt.membro_afetado,
                   pt.num_sessoes_prescritas, pt.data_validade, pt.observacoes, pt.ativa,
                   u.nome AS prescrito_por
            FROM programas_tratamento pt
            JOIN profissionais pr ON pr.id = pt.medico_id
            JOIN utilizadores u ON u.id = pr.utilizador_id
            WHERE pt.utente_id=? ORDER BY pt.data_prescricao DESC
        ");
        $sp->execute([$sel]); $prescricoes = $sp->fetchAll();
    } catch (\Throwable $e) { $prescricoes = []; }

    // Análises de desempenho globais
    $analises_desemp = [];
    try {
        $sad = $db->prepare("
            SELECT ad.data_analise, ad.texto, ad.progressao_geral, u.nome AS tecnico_nome
            FROM analises_desempenho ad
            JOIN profissionais p ON p.id = ad.tecnico_id
            JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE ad.utente_id = ?
            ORDER BY ad.data_analise DESC
        ");
        $sad->execute([$sel]); $analises_desemp = $sad->fetchAll();
    } catch (\Throwable $e) { $analises_desemp = []; }
}

require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="mb-0">Relatório Clínico Completo</h1>
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <select name="utente_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:200px;">
                            <option value="">— Selecionar utente —</option>
                            <?php foreach($pacientes as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $p['id']==$sel?'selected':'' ?>><?= h($p['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php if ($utente): ?>
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary d-print-none">
                        <i class="fa-regular fa-file-pdf me-1"></i>Imprimir / PDF
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$utente): ?>
            <div class="alert alert-light text-center text-muted py-5">
                <i class="fa-solid fa-user-injured fa-3x mb-3 d-block"></i>
                Selecione um utente para ver o relatório clínico completo.
            </div>
            <?php else: ?>

            <div class="card p-4 mb-4" style="border-left:4px solid #1a5f8a;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-1"><?= h($utente['nome']) ?></h3>
                        <div class="text-muted small mb-2"><?= h($utente['email']) ?> &bull; NIF: <?= h($utente['nif'] ?? '—') ?></div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-secondary"><?= h($utente['cobertura_saude'] ?? 'Particular') ?></span>
                            <?php if ($utente['seguradora']): ?>
                                <span class="badge bg-info text-dark"><?= h($utente['seguradora']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="small text-muted">Médico responsável</div>
                        <div class="fw-semibold"><?= h($utente['medico_nome'] ?? '—') ?></div>
                        <div class="small text-muted mt-1">Técnico</div>
                        <div class="fw-semibold"><?= h($_SESSION['nome']) ?></div>
                        <div class="small text-muted mt-1">Relatório gerado em <?= date('d/m/Y H:i') ?></div>
                    </div>
                </div>
                <?php if ($utente['diagnostico']): ?>
                <hr>
                <div><span class="fw-semibold">Diagnóstico:</span> <?= h($utente['diagnostico']) ?></div>
                <?php endif; ?>
            </div>

            <?php
            $n_sess = count($sessoes_hist ?? []);
            $n_conc = count(array_filter($sessoes_hist ?? [], fn($s) => $s['estado'] === 'concluida'));
            $scores = array_filter(array_column($sessoes_hist ?? [], 'prec_media'));
            $media_prec = $scores ? round(array_sum($scores)/count($scores), 1) : null;
            ?>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3"><div class="card p-3 text-center"><div class="fs-3 fw-bold text-primary"><?= $n_sess ?></div><div class="small text-muted">Total Sessões</div></div></div>
                <div class="col-6 col-md-3"><div class="card p-3 text-center"><div class="fs-3 fw-bold text-success"><?= $n_conc ?></div><div class="small text-muted">Concluídas</div></div></div>
                <div class="col-6 col-md-3"><div class="card p-3 text-center"><div class="fs-3 fw-bold text-info"><?= $media_prec !== null ? $media_prec.'%' : '—' ?></div><div class="small text-muted">Precisão Média</div></div></div>
                <div class="col-6 col-md-3"><div class="card p-3 text-center"><div class="fs-3 fw-bold"><?= count($prescricoes ?? []) ?></div><div class="small text-muted">Tratamentos</div></div></div>
            </div>

            <div class="card p-4 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-dumbbell me-2" style="color:#1a5f8a;"></i>Histórico de Sessões</h5>
                <?php if (empty($sessoes_hist)): ?>
                    <p class="text-muted">Sem sessões registadas.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Data</th><th>Jogo / Categoria</th><th>Duração</th><th>Precisão</th><th>Progressão</th><th>Esforço</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($sessoes_hist as $s):
                            $pCor = match($s['progressao'] ?? '') { 'melhoria'=>'success', 'regressao'=>'danger', default=>'secondary' };
                        ?>
                        <tr>
                            <td class="small"><?= h(date('d/m/Y', strtotime($s['data_hora']))) ?></td>
                            <td class="small"><?= h($s['jogo'] ?? '—') ?></td>
                            <td class="small"><?= $s['duracao_min'] ? $s['duracao_min'].' min' : '—' ?></td>
                            <td class="small"><?= $s['prec_media'] !== null ? number_format((float)$s['prec_media'],1).'%' : '—' ?></td>
                            <td><span class="badge bg-<?= $pCor ?>"><?= h(ucfirst($s['progressao'] ?? '—')) ?></span></td>
                            <td><?php if ($s['esforco_score']): for($i=1;$i<=5;$i++) echo $i<=$s['esforco_score']?'★':'☆'; endif; ?></td>
                            <td><span class="badge bg-<?= $s['estado']==='concluida'?'success':($s['estado']==='cancelada'?'secondary':'warning text-dark') ?>"><?= h(ucfirst($s['estado'])) ?></span></td>
                        </tr>
                        <?php if (!empty($s['analise_tecnica'])): ?>
                        <tr class="table-light"><td colspan="7" class="small text-muted ps-3"><i class="fa-solid fa-note-sticky me-1"></i><?= h($s['analise_tecnica']) ?></td></tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($prescricoes)): ?>
            <div class="card p-4 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-file-medical me-2" style="color:#1a5f8a;"></i>Tratamentos Prescritos</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Data</th><th>Objetivos Clínicos</th><th>Médico</th><th>Estado</th><th class="d-print-none"></th></tr></thead>
                        <tbody>
                        <?php foreach ($prescricoes as $idx => $p): ?>
                        <tr>
                            <td class="small text-nowrap"><?= h(date('d/m/Y', strtotime($p['data_prescricao']))) ?></td>
                            <td class="small"><?= h(mb_substr($p['objetivos_clinicos'] ?? '—', 0, 60)) ?><?= mb_strlen($p['objetivos_clinicos'] ?? '') > 60 ? '…' : '' ?></td>
                            <td class="small"><?= h($p['prescrito_por'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $p['ativa'] ? 'success' : 'secondary' ?>"><?= $p['ativa'] ? 'Ativo' : 'Inativo' ?></span></td>
                            <td class="d-print-none">
                                <button type="button" class="btn btn-xs btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#modalTratamento"
                                    onclick="verTratamento(<?= $idx ?>)"
                                    title="Ver detalhes">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal fade" id="modalTratamento" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header" style="background:#1a5f8a;color:#fff;">
                            <h5 class="modal-title mb-0"><i class="fa-solid fa-file-medical me-2"></i>Detalhes do Tratamento</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <dl class="row mb-0" id="modalTratamentoBody"></dl>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            var _tratamentos = <?= json_encode(array_values($prescricoes), JSON_UNESCAPED_UNICODE) ?>;
            function verTratamento(idx) {
                var p = _tratamentos[idx];
                var rows = [
                    ['Médico prescritor', p.prescrito_por || '—'],
                    ['Data de prescrição', p.data_prescricao ? p.data_prescricao.substring(0,10).split('-').reverse().join('/') : '—'],
                    ['Data de validade', p.data_validade ? p.data_validade.substring(0,10).split('-').reverse().join('/') : '—'],
                    ['Membro afetado', p.membro_afetado ? p.membro_afetado.replace(/_/g,' ') : '—'],
                    ['Sessões prescritas', p.num_sessoes_prescritas || '—'],
                    ['Estado', p.ativa ? 'Ativo' : 'Inativo'],
                    ['Objetivos clínicos', p.objetivos_clinicos || '—'],
                    ['Observações', p.observacoes || '—'],
                ];
                var html = '';
                rows.forEach(function(r) {
                    html += '<dt class="col-sm-4 text-muted small">' + r[0] + '</dt>'
                          + '<dd class="col-sm-8 small" style="white-space:pre-wrap;">' + r[1] + '</dd>';
                });
                document.getElementById('modalTratamentoBody').innerHTML = html;
            }
            </script>
            <?php endif; ?>

            <?php if (!empty($analises_desemp)):
                $apc = ['melhoria'=>'#198754','estavel'=>'#6c757d','regressao'=>'#dc3545'];
                $api = ['melhoria'=>'fa-arrow-trend-up','estavel'=>'fa-minus','regressao'=>'fa-arrow-trend-down'];
                $apl = ['melhoria'=>'Melhoria','estavel'=>'Estável','regressao'=>'Regressão'];
            ?>
            <div class="card p-4 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-clipboard-list me-2" style="color:#1a5f8a;"></i>Análises de Desempenho do Técnico</h5>
                <div class="d-flex flex-column gap-3">
                <?php foreach ($analises_desemp as $a): ?>
                    <?php $pg = $a['progressao_geral'] ?? 'estavel'; ?>
                    <div class="p-3 rounded" style="background:#f8f9fa;border-left:3px solid <?= $apc[$pg] ?? '#6c757d' ?>;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge me-2" style="background:<?= $apc[$pg] ?? '#6c757d' ?>;-webkit-print-color-adjust:exact;print-color-adjust:exact;">
                                    <i class="fa-solid <?= $api[$pg] ?? 'fa-minus' ?> me-1"></i><?= $apl[$pg] ?? '—' ?>
                                </span>
                                <small class="text-muted"><?= h(date('d/m/Y', strtotime($a['data_analise']))) ?></small>
                            </div>
                            <small class="text-muted"><?= h($a['tecnico_nome']) ?></small>
                        </div>
                        <p class="mb-0 small" style="white-space:pre-wrap;"><?= h($a['texto']) ?></p>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>


            <?php endif; ?>
        </main>
<style>
@media print {
    @page { size: A4 portrait; margin: 1.2cm; }

    .sidebar, .topbar, .d-print-none { display:none !important; }

    body, html { margin:0 !important; padding:0 !important; width:100% !important; }
    .wrapper { display:block !important; }
    .content { margin:0 !important; padding:0 !important; width:100% !important; max-width:100% !important; }

    table { width:100% !important; table-layout:fixed !important; border-collapse:collapse !important; font-size:9pt !important; }
    th, td { word-break:break-word !important; overflow-wrap:break-word !important; padding:4px 6px !important; border:1px solid #ccc !important; }
    thead { background:#f0f0f0 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }

    .card { box-shadow:none !important; border:1px solid #ccc !important; page-break-inside:avoid; }
    .row { display:flex !important; flex-wrap:wrap !important; }

    .badge { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; border:1px solid #999 !important; }

    h5 { page-break-after:avoid; }
}
</style>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
