<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Perfil Paciente'; $pagina_ativa = 'pacientes';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/tecnico/pacientes/lista_pacientes.php');

$stmt = $db->prepare("
    SELECT ut.*, u.nome, u.email,
           ut.fase_tratamento, ut.categoria_clinica, ut.membro_afetado
    FROM utentes ut
    JOIN utilizadores u ON u.id = ut.utilizador_id
    WHERE ut.id = ?
");
$stmt->execute([$id]); $pac = $stmt->fetch();
if (!$pac) redirect(APP_URL . '/private/tecnico/pacientes/lista_pacientes.php');

$s = $db->prepare("SELECT COUNT(*) FROM sessoes WHERE utente_id=? AND estado='concluida'");
$s->execute([$id]); $n_sessoes = (int)$s->fetchColumn();

$s = $db->prepare("SELECT ROUND(AVG(m.percentagem_final), 1) FROM metricas_sessao m JOIN sessoes s ON s.id = m.sessao_id WHERE s.utente_id=? AND m.percentagem_final IS NOT NULL");
$s->execute([$id]); $media_pct = $s->fetchColumn();

$ultimas = $db->prepare("
    SELECT s.data_hora, s.categoria, s.duracao_min, s.estado,
           j.nome AS jogo_nome, j.nivel AS jogo_nivel,
           m.percentagem_final, m.passou_nivel, m.tendencia
    FROM sessoes s
    LEFT JOIN jogos j ON j.id = s.jogo_id
    LEFT JOIN metricas_sessao m ON m.sessao_id = s.id
    WHERE s.utente_id = ?
    ORDER BY s.data_hora DESC
    LIMIT 5
");
$ultimas->execute([$id]); $ultimas = $ultimas->fetchAll();

// Dados para o gráfico — evolução por jogo
$jogos_utente = $db->query("
    SELECT DISTINCT j.id, j.nome, j.nivel
    FROM sessoes s
    JOIN jogos j ON j.id = s.jogo_id
    WHERE s.utente_id = $id AND s.estado = 'concluida'
    ORDER BY j.nivel, j.nome
")->fetchAll();

$chart_datasets = [];
$nivel_colors = ['minimo' => '#198754', 'medio' => '#ffc107', 'maximo' => '#dc3545'];
foreach ($jogos_utente as $jogo) {
    $pontos = $db->query("
        SELECT DATE_FORMAT(s.data_hora, '%d/%m/%Y') AS data, m.percentagem_final
        FROM sessoes s
        JOIN metricas_sessao m ON m.sessao_id = s.id
        WHERE s.utente_id = $id AND s.jogo_id = {$jogo['id']} AND s.estado = 'concluida'
          AND m.percentagem_final IS NOT NULL
        ORDER BY s.data_hora
        LIMIT 20
    ")->fetchAll();
    if ($pontos) {
        $chart_datasets[] = [
            'label'           => $jogo['nome'] . ' (' . $jogo['nivel'] . ')',
            'data'            => array_column($pontos, 'percentagem_final'),
            'labels'          => array_column($pontos, 'data'),
            'borderColor'     => $nivel_colors[$jogo['nivel']] ?? '#666',
            'backgroundColor' => 'transparent',
            'tension'         => 0.3,
            'pointRadius'     => 4,
        ];
    }
}

$fase_labels  = ['avaliacao' => 'Avaliação', 'ativo' => 'Ativo', 'manutencao' => 'Manutenção', 'alta' => 'Alta'];
$fase_cores   = ['avaliacao' => 'secondary', 'ativo' => 'primary', 'manutencao' => 'info', 'alta' => 'success'];
$nivel_badges = ['minimo' => 'success', 'medio' => 'warning', 'maximo' => 'danger'];
$tend_icons   = ['melhoria' => '↑', 'estavel' => '→', 'regressao' => '↓'];
$tend_cores   = ['melhoria' => 'text-success', 'estavel' => 'text-secondary', 'regressao' => 'text-danger'];
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:70px;height:70px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:2rem;">
                        <i class="fa-regular fa-user" style="color:#1a5f8a;"></i>
                    </div>
                    <div>
                        <h1 class="mb-0"><?= h($pac['nome']) ?></h1>
                        <p class="text-muted mb-0">Paciente · ID <?= $id ?></p>
                        <?php $f = $pac['fase_tratamento'] ?? 'avaliacao'; ?>
                        <span class="badge bg-<?= $fase_cores[$f] ?? 'secondary' ?>"><?= $fase_labels[$f] ?? $f ?></span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="../sessoes/nova_sessao.php?utente_id=<?= $id ?>" class="btn btn-sm" style="background:#1a5f8a;color:#fff;">
                        <i class="fa-regular fa-calendar-plus me-1"></i>Nova Sessão
                    </a>
                    <a href="historico_paciente.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i>Histórico
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <div class="card p-3 h-100">
                        <h6 class="fw-bold mb-3">Informação Clínica</h6>
                        <p><strong>Diagnóstico:</strong><br><small class="text-muted"><?= h($pac['diagnostico'] ?? '—') ?></small></p>
                        <p><strong>Email:</strong> <?= h($pac['email']) ?></p>
                        <?php if ($pac['membro_afetado']): ?>
                            <p class="mb-0"><strong>Membro:</strong> <?= h(str_replace('_', ' ', $pac['membro_afetado'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center h-100 d-flex flex-column justify-content-center">
                        <div class="fs-2 fw-bold text-success"><?= $n_sessoes ?></div>
                        <div class="text-muted small">Sessões Concluídas</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center h-100 d-flex flex-column justify-content-center">
                        <div class="fs-2 fw-bold text-primary">
                            <?= $media_pct !== false && $media_pct !== null ? number_format((float)$media_pct, 1) . '%' : '—' ?>
                        </div>
                        <div class="text-muted small">Média Percentagem Final</div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de evolução por jogo -->
            <?php if (!empty($chart_datasets)): ?>
            <div class="card p-3 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-chart-line me-2 text-primary"></i>Evolução por Jogo</h5>
                <canvas id="chartEvolucao" style="max-height:280px;"></canvas>
            </div>
            <?php endif; ?>

            <!-- Últimas sessões -->
            <div class="card p-3">
                <h5 class="mb-3">Últimas Sessões</h5>
                <table class="table table-sm table-hover">
                    <thead>
                        <tr><th>Data</th><th>Jogo / Categoria</th><th>Duração</th><th>Resultado</th><th>Tend.</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($ultimas)): ?>
                        <tr><td colspan="6" class="text-muted text-center">Sem sessões.</td></tr>
                    <?php else: foreach ($ultimas as $s): ?>
                        <tr>
                            <td><?= h(substr($s['data_hora'], 0, 10)) ?></td>
                            <td>
                                <?php if ($s['jogo_nome']): ?>
                                    <?= h($s['jogo_nome']) ?>
                                    <span class="badge bg-<?= $nivel_badges[$s['jogo_nivel']] ?? 'secondary' ?> ms-1" style="font-size:.65rem"><?= h($s['jogo_nivel']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted"><?= h(ucfirst(str_replace('_', ' ', $s['categoria'] ?? '—'))) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= $s['duracao_min'] ? h($s['duracao_min']) . ' min' : '—' ?></td>
                            <td>
                                <?php if ($s['percentagem_final'] !== null): ?>
                                    <strong><?= number_format((float)$s['percentagem_final'], 1) ?>%</strong>
                                    <?= $s['passou_nivel'] ? '<span class="text-success ms-1">✓</span>' : '<span class="text-danger ms-1">✗</span>' ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['tendencia']): ?>
                                    <span class="fw-bold <?= $tend_cores[$s['tendencia']] ?? '' ?>">
                                        <?= $tend_icons[$s['tendencia']] ?? '' ?>
                                    </span>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td><span class="badge bg-<?= ['concluida' => 'success', 'agendada' => 'warning text-dark', 'em_curso' => 'primary', 'cancelada' => 'danger'][$s['estado']] ?? 'secondary' ?>"><?= h($s['estado']) ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <a href="historico_paciente.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary mt-2">Ver histórico completo</a>
            </div>
        </main>

<?php if (!empty($chart_datasets)):
    // Usar todos os labels das sessões (eixo X unificado por data)
    $all_labels = [];
    foreach ($chart_datasets as $ds) {
        foreach ($ds['labels'] as $lbl) {
            $all_labels[] = $lbl;
        }
    }
    $all_labels = array_values(array_unique($all_labels));
    sort($all_labels);

    // Preencher pontos ausentes com null
    $chart_ds_final = [];
    foreach ($chart_datasets as $ds) {
        $map = array_combine($ds['labels'], $ds['data']);
        $points = [];
        foreach ($all_labels as $lbl) {
            $points[] = isset($map[$lbl]) ? (float)$map[$lbl] : null;
        }
        $chart_ds_final[] = [
            'label'           => $ds['label'],
            'data'            => $points,
            'borderColor'     => $ds['borderColor'],
            'backgroundColor' => $ds['backgroundColor'],
            'tension'         => $ds['tension'],
            'pointRadius'     => $ds['pointRadius'],
            'spanGaps'        => true,
        ];
    }
?>
        <script>
        (function() {
            const labels   = <?= json_encode($all_labels) ?>;
            const datasets = <?= json_encode($chart_ds_final) ?>;
            new Chart(document.getElementById('chartEvolucao'), {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: {
                            min: 0, max: 100,
                            title: { display: true, text: '% Final' }
                        }
                    }
                }
            });
        })();
        </script>
<?php endif; ?>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
