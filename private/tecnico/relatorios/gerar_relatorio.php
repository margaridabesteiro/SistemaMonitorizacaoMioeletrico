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

// Dados clínicos completos do utente selecionado
$utente = $sessoes_hist = $prescricoes = $exames = $medicacoes = $analises = null;

if ($sel) {
    // Dados base
    $su = $db->prepare("
        SELECT u.nome, u.email, ut.diagnostico, ut.cobertura_saude, ut.nif,
               s.nome AS seguradora, um.nome AS medico_nome,
               ut.criado_em
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
            SELECT p.data_prescricao, p.descricao, p.estado, p.notas,
                   u.nome AS prescrito_por
            FROM prescricoes p
            JOIN profissionais pr ON pr.id=p.medico_id
            JOIN utilizadores u ON u.id=pr.utilizador_id
            WHERE p.utente_id=? ORDER BY p.data_prescricao DESC
        ");
        $sp->execute([$sel]); $prescricoes = $sp->fetchAll();
    } catch (\Throwable $e) { $prescricoes = []; }

    // Exames
    try {
        $se = $db->prepare("
            SELECT pe.data_pedido, pe.tipo_exame, pe.resultado, pe.estado, pe.observacoes
            FROM pedidos_exame pe
            WHERE pe.utente_id=? ORDER BY pe.data_pedido DESC
        ");
        $se->execute([$sel]); $exames = $se->fetchAll();
    } catch (\Throwable $e) { $exames = []; }

    // Medicação
    try {
        $sm = $db->prepare("
            SELECT m.nome_medicamento, m.dosagem, m.frequencia, m.data_inicio, m.data_fim, m.ativo
            FROM medicacoes m
            WHERE m.utente_id=? ORDER BY m.ativo DESC, m.data_inicio DESC
        ");
        $sm->execute([$sel]); $medicacoes = $sm->fetchAll();
    } catch (\Throwable $e) { $medicacoes = []; }
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

            <!-- Cabeçalho do relatório -->
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

            <!-- KPIs rápidos -->
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

            <!-- Histórico de Sessões -->
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

            <!-- Tratamentos / Prescrições -->
            <?php if (!empty($prescricoes)): ?>
            <div class="card p-4 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-file-medical me-2" style="color:#1a5f8a;"></i>Tratamentos Prescritos</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Data</th><th>Descrição</th><th>Prescrito por</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($prescricoes as $p): ?>
                        <tr>
                            <td class="small"><?= h(date('d/m/Y', strtotime($p['data_prescricao']))) ?></td>
                            <td class="small"><?= h($p['descricao'] ?? '—') ?></td>
                            <td class="small"><?= h($p['prescrito_por'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $p['estado']==='ativo'?'success':'secondary' ?>"><?= h(ucfirst($p['estado'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Exames -->
            <?php if (!empty($exames)): ?>
            <div class="card p-4 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-flask me-2" style="color:#1a5f8a;"></i>Exames</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Data</th><th>Tipo</th><th>Estado</th><th>Resultado</th></tr></thead>
                        <tbody>
                        <?php foreach ($exames as $e): ?>
                        <tr>
                            <td class="small"><?= h(date('d/m/Y', strtotime($e['data_pedido']))) ?></td>
                            <td class="small"><?= h($e['tipo_exame'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $e['estado']==='concluido'?'success':'warning text-dark' ?>"><?= h(ucfirst($e['estado'] ?? '—')) ?></span></td>
                            <td class="small"><?= h($e['resultado'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Medicação -->
            <?php if (!empty($medicacoes)): ?>
            <div class="card p-4 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-pills me-2" style="color:#1a5f8a;"></i>Medicação</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Medicamento</th><th>Dosagem</th><th>Frequência</th><th>Início</th><th>Fim</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($medicacoes as $m): ?>
                        <tr>
                            <td class="small fw-semibold"><?= h($m['nome_medicamento'] ?? '—') ?></td>
                            <td class="small"><?= h($m['dosagem'] ?? '—') ?></td>
                            <td class="small"><?= h($m['frequencia'] ?? '—') ?></td>
                            <td class="small"><?= $m['data_inicio'] ? h(date('d/m/Y', strtotime($m['data_inicio']))) : '—' ?></td>
                            <td class="small"><?= $m['data_fim']   ? h(date('d/m/Y', strtotime($m['data_fim'])))   : '—' ?></td>
                            <td><span class="badge bg-<?= $m['ativo']?'success':'secondary' ?>"><?= $m['ativo']?'Ativo':'Inativo' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </main>
<style>
@media print {
    .sidebar, .topbar, .d-print-none { display:none !important; }
    .content { margin:0 !important; padding:0 !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
}
</style>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
