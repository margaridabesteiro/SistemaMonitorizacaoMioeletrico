<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Detalhes Sessão'; $pagina_ativa = 'sessoes';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
$stmt = $db->prepare("
    SELECT s.*, u.nome AS paciente, d.codigo AS dispositivo,
           j.nome AS jogo_nome, j.nivel AS jogo_nivel
    FROM sessoes s
    JOIN utentes ut ON ut.id=s.utente_id
    JOIN utilizadores u ON u.id=ut.utilizador_id
    LEFT JOIN dispositivos d ON d.id=s.dispositivo_id
    LEFT JOIN jogos j ON j.id=s.jogo_id
    WHERE s.id=?");
$stmt->execute([$id]); $s = $stmt->fetch();
if (!$s) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
$metricas = $db->prepare("SELECT * FROM metricas_sessao WHERE sessao_id=?");
$metricas->execute([$id]); $m = $metricas->fetch();

// Waveform EMG: amostrar até 400 pontos da sessão (canal 1)
$total_leituras = (int)$db->query("SELECT COUNT(*) FROM leituras_emg WHERE sessao_id=$id AND canal=1")->fetchColumn();
$step = max(1, (int)floor($total_leituras / 400));
$waveform = $db->query("
    SELECT timestamp_ms, amplitude_uv FROM leituras_emg
    WHERE sessao_id=$id AND canal=1
    ORDER BY timestamp_ms
")->fetchAll();
// Decimação manual para não sobrecarregar o gráfico
$wf_dec = [];
foreach ($waveform as $i => $row) {
    if ($i % $step === 0) $wf_dec[] = $row;
}

$nivel_colors = ['minimo'=>'success','medio'=>'warning','maximo'=>'danger'];
$tendencia_icons = ['melhoria'=>'fa-arrow-trend-up text-success','estavel'=>'fa-minus text-secondary','regressao'=>'fa-arrow-trend-down text-danger'];
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="lista_sessoes.php">Sessões</a></li>
                    <li class="breadcrumb-item active">Sessão #<?= $id ?></li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Detalhes da Sessão #<?= $id ?></h1>
                <div class="d-flex gap-2">
                    <?php if ($s['estado']==='agendada'): ?>
                        <a href="iniciar_sessao.php?id=<?= $id ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-play me-1"></i>Iniciar</a>
                    <?php endif; ?>
                    <?php if ($s['modalidade']==='remota' && $s['link_videochamada'] && $s['estado']==='agendada'): ?>
                        <a href="<?= h($s['link_videochamada']) ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fa-solid fa-video me-1"></i>Entrar na Videochamada</a>
                    <?php endif; ?>
                    <a href="editar_sessao.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-regular fa-pen-to-square me-1"></i>Editar</a>
                    <a href="lista_sessoes.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card p-3">
                        <p><strong>Paciente:</strong> <?= h($s['paciente']) ?></p>
                        <?php if ($s['jogo_nome']): ?>
                            <p><strong>Jogo:</strong> <?= h($s['jogo_nome']) ?>
                                <span class="badge bg-<?= $nivel_colors[$s['jogo_nivel']] ?? 'secondary' ?> ms-1"><?= h($s['jogo_nivel']) ?></span>
                            </p>
                        <?php else: ?>
                            <p><strong>Categoria:</strong> <?= h(ucfirst(str_replace('_',' ',$s['categoria']??'—'))) ?></p>
                        <?php endif; ?>
                        <p><strong>Data/Hora:</strong> <?= h(substr($s['data_hora'],0,16)) ?></p>
                        <p><strong>Duração:</strong> <?= $s['duracao_min'] ? h($s['duracao_min']).' min' : '—' ?></p>
                        <p><strong>Modalidade:</strong>
                            <?php if ($s['modalidade']==='remota'): ?>
                                <span class="badge bg-primary"><i class="fa-solid fa-video me-1"></i>Remota</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fa-solid fa-hospital me-1"></i>Presencial</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Dispositivo:</strong> <?= h($s['dispositivo'] ?? '—') ?></p>
                        <p><strong>Estado:</strong> <span class="badge bg-secondary"><?= h($s['estado']) ?></span></p>
                        <?php if ($s['objetivo_sessao']): ?><p><strong>Objetivo:</strong> <?= h($s['objetivo_sessao']) ?></p><?php endif; ?>
                        <?php if ($s['notas']): ?><p class="mb-0"><strong>Notas:</strong> <?= h($s['notas']) ?></p><?php endif; ?>
                    </div>
                </div>

                <?php if ($m): ?>
                <div class="col-md-6">
                    <div class="card p-3 text-center">
                        <h5 class="mb-3">Resultado do Jogo</h5>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="fw-bold fs-3"><?= $m['percentagem_final'] !== null ? number_format((float)$m['percentagem_final'],1).'%' : '—' ?></div>
                                <small class="text-muted">Percentagem Final</small>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold fs-3"><?= $m['score_jogo'] ?? '—' ?></div>
                                <small class="text-muted">Score</small>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-4 text-center">
                                <?php $icon = $tendencia_icons[$m['tendencia'] ?? ''] ?? 'fa-question text-muted'; ?>
                                <div class="fs-4"><i class="fa-solid <?= $icon ?>"></i></div>
                                <small class="text-muted">Tendência</small>
                            </div>
                            <div class="col-4 text-center">
                                <div class="fw-bold fs-4"><?= $m['n_tentativas'] ?? '—' ?></div>
                                <small class="text-muted">Tentativas</small>
                            </div>
                            <div class="col-4 text-center">
                                <div class="fs-4"><?= $m['passou_nivel'] ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>' ?></div>
                                <small class="text-muted">Passou nível</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Waveform EMG -->
            <div class="card p-3 mt-3">
                <h5 class="mb-1"><i class="fa-solid fa-wave-square me-2" style="color:#8B0000;"></i>Sinal EMG — Waveform (Canal 1)</h5>
                <small class="text-muted d-block mb-3">
                    <?php if ($total_leituras > 0): ?>
                        <?= number_format($total_leituras) ?> amostras · exibindo <?= count($wf_dec) ?> pontos (decimação 1:<?= $step ?>)
                        · Banda útil: 20–500 Hz · Filtro notch 50 Hz aplicado no ESP32
                    <?php else: ?>
                        Sem leituras EMG registadas nesta sessão
                    <?php endif; ?>
                </small>
                <?php if (!empty($wf_dec)): ?>
                    <canvas id="chartWaveform" style="max-height:220px;"></canvas>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fa-solid fa-wave-square fa-3x mb-2 opacity-25"></i>
                        <p class="mb-0">Sem dados de sinal EMG para esta sessão.</p>
                        <small>O ESP32 envia leituras via BLE → Wi-Fi → API durante a sessão ativa.</small>
                    </div>
                <?php endif; ?>
            </div>

            <a href="lista_sessoes.php" class="btn btn-outline-secondary mt-3">
                <i class="fa-solid fa-arrow-left me-1"></i>Voltar
            </a>
        </main>

<?php if (!empty($wf_dec)):
    $wf_labels = array_map(fn($r) => round($r['timestamp_ms'] / 1000, 2), $wf_dec);
    $wf_values = array_map(fn($r) => round((float)$r['amplitude_uv'], 2), $wf_dec);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartWaveform'), {
    type: 'line',
    data: {
        labels: <?= json_encode($wf_labels) ?>,
        datasets: [{
            label: 'Amplitude EMG (µV)',
            data: <?= json_encode($wf_values) ?>,
            borderColor: '#8B0000',
            backgroundColor: 'rgba(139,0,0,0.05)',
            borderWidth: 1,
            pointRadius: 0,
            tension: 0,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        animation: false,
        plugins: { legend: { position: 'top' } },
        scales: {
            x: { title: { display: true, text: 'Tempo (s)' } },
            y: { title: { display: true, text: 'Amplitude (µV)' } }
        }
    }
});
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
