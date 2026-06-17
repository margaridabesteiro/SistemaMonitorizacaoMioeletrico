<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('tecnico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$st  = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$st->execute([$uid]); $pid = (int)($st->fetchColumn() ?: 0);

// Criar tabela se não existir
try {
    $db->exec("CREATE TABLE IF NOT EXISTS analises_desempenho (
        id INT AUTO_INCREMENT PRIMARY KEY,
        utente_id INT NOT NULL,
        tecnico_id INT NOT NULL,
        data_analise DATE NOT NULL,
        texto TEXT NOT NULL,
        progressao_geral ENUM('melhoria','estavel','regressao') DEFAULT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_utente (utente_id),
        INDEX idx_tecnico (tecnico_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Throwable $e) {}

// POST: guardar nova análise
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_acao'] ?? '') === 'nova_analise' && $pid) {
    $utente_id  = (int)($_POST['utente_id'] ?? 0);
    $texto      = trim($_POST['texto'] ?? '');
    $progressao = in_array($_POST['progressao_geral'] ?? '', ['melhoria','estavel','regressao'])
                  ? $_POST['progressao_geral'] : null;

    if ($utente_id && $texto) {
        $db->prepare("INSERT INTO analises_desempenho (utente_id, tecnico_id, data_analise, texto, progressao_geral) VALUES (?,?,CURDATE(),?,?)")
           ->execute([$utente_id, $pid, $texto, $progressao]);

        // Notificar utente
        try {
            $uq = $db->prepare("SELECT utilizador_id FROM utentes WHERE id=?");
            $uq->execute([$utente_id]);
            $utente_uid = (int)$uq->fetchColumn();
            if ($utente_uid) {
                notificar($utente_uid, 'info',
                    'Nova análise de desempenho',
                    'O seu técnico registou uma nova análise de desempenho. Consulte o seu progresso.',
                    APP_URL . '/private/utente/meu_progresso.php'
                );
            }
        } catch (\Throwable $e) {}

        registarAuditoria('CRIAR', 'AnaliseDesempenho', $utente_id, 'Técnico registou análise de desempenho global');
        $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Análise de desempenho registada com sucesso.'];
    } else {
        $_SESSION['flash'] = ['tipo' => 'danger', 'mensagem' => 'O texto da análise é obrigatório.'];
    }
    redirect(APP_URL . '/private/tecnico/analise/desempenho.php?utente_id=' . $utente_id);
}

$pacientes = [];
if ($pid) {
    $sp = $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.tecnico_id=? ORDER BY u.nome");
    $sp->execute([$pid]); $pacientes = $sp->fetchAll();
}

$sel           = (int)($_GET['utente_id'] ?? ($pacientes[0]['id'] ?? 0));
$dados         = [];
$analises_hist = [];

if ($sel) {
    $sd = $db->prepare("
        SELECT s.data_hora, j.nome AS jogo, m.percentagem_final, m.score_jogo, m.passou_nivel, m.tendencia
        FROM metricas_sessao m
        JOIN sessoes s ON s.id = m.sessao_id
        LEFT JOIN jogos j ON j.id = s.jogo_id
        WHERE s.utente_id = ? AND m.percentagem_final IS NOT NULL
        ORDER BY s.data_hora ASC
        LIMIT 30
    ");
    $sd->execute([$sel]); $dados = $sd->fetchAll();

    try {
        $sa = $db->prepare("
            SELECT ad.id, ad.data_analise, ad.texto, ad.progressao_geral, u.nome AS tecnico_nome
            FROM analises_desempenho ad
            JOIN profissionais p ON p.id = ad.tecnico_id
            JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE ad.utente_id = ?
            ORDER BY ad.data_analise DESC, ad.criado_em DESC
        ");
        $sa->execute([$sel]); $analises_hist = $sa->fetchAll();
    } catch (\Throwable $e) { $analises_hist = []; }
}

$prog_cor   = ['melhoria'=>'#198754','estavel'=>'#6c757d','regressao'=>'#dc3545'];
$prog_icon  = ['melhoria'=>'fa-arrow-trend-up','estavel'=>'fa-minus','regressao'=>'fa-arrow-trend-down'];
$prog_label = ['melhoria'=>'Melhoria','estavel'=>'Estável','regressao'=>'Regressão'];

$pagina_titulo = 'Análise de Desempenho'; $pagina_ativa = 'analise';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> py-2 alert-dismissible">
                    <?= h($flash['mensagem']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <h1 class="mb-4">Análise de Desempenho</h1>

            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Paciente</label>
                    <select name="utente_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($pacientes as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['id'] === $sel ? 'selected' : '' ?>><?= h($p['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if ($sel): ?>

            <div class="card p-4 mb-4" style="border-left:4px solid #1a5f8a;">
                <h5 class="mb-3"><i class="fa-solid fa-pen-to-square me-2" style="color:#1a5f8a;"></i>Registar Análise de Desempenho</h5>
                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="_acao" value="nova_analise">
                    <input type="hidden" name="utente_id" value="<?= $sel ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Progressão Geral</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="progressao_geral" id="pgMelhoria" value="melhoria">
                                <label class="form-check-label text-success fw-semibold" for="pgMelhoria">
                                    <i class="fa-solid fa-arrow-trend-up me-1"></i>Melhoria
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="progressao_geral" id="pgEstavel" value="estavel" checked>
                                <label class="form-check-label text-secondary fw-semibold" for="pgEstavel">
                                    <i class="fa-solid fa-minus me-1"></i>Estável
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="progressao_geral" id="pgRegressao" value="regressao">
                                <label class="form-check-label text-danger fw-semibold" for="pgRegressao">
                                    <i class="fa-solid fa-arrow-trend-down me-1"></i>Regressão
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Análise</label>
                        <textarea name="texto" class="form-control" rows="5" required
                            placeholder="Descreva a evolução do paciente, pontos fortes, áreas a melhorar, recomendações para as próximas sessões..."></textarea>
                    </div>
                    <button type="submit" class="btn" style="background:#1a5f8a;color:#fff;">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Análise
                    </button>
                </form>
            </div>

            <?php if (!empty($analises_hist)): ?>
            <div class="card p-4 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-clock-rotate-left me-2" style="color:#1a5f8a;"></i>Análises Registadas</h5>
                <div class="d-flex flex-column gap-3">
                <?php foreach ($analises_hist as $a): ?>
                    <?php $pg = $a['progressao_geral'] ?? 'estavel'; ?>
                    <div class="p-3 rounded" style="background:#f8f9fa;border-left:3px solid <?= $prog_cor[$pg] ?? '#6c757d' ?>;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge me-2" style="background:<?= $prog_cor[$pg] ?? '#6c757d' ?>;">
                                    <i class="fa-solid <?= $prog_icon[$pg] ?? 'fa-minus' ?> me-1"></i><?= $prog_label[$pg] ?? '—' ?>
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

            <?php if (!empty($dados)): ?>
            <div class="card p-3 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-chart-line me-2" style="color:#667eea;"></i>Evolução de Precisão</h5>
                <canvas id="chartDesempenho" height="80"></canvas>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;">
                            <tr><th>Data</th><th>Jogo</th><th>Precisão</th><th>Score</th><th>Passou</th><th>Tendência</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dados as $d): ?>
                            <tr>
                                <td><?= h(substr($d['data_hora'], 0, 10)) ?></td>
                                <td><?= h($d['jogo'] ?? '—') ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px;">
                                            <div class="progress-bar" style="width:<?= (float)$d['percentagem_final'] ?>%;background:linear-gradient(90deg,#667eea,#764ba2);"></div>
                                        </div>
                                        <span class="fw-bold"><?= number_format((float)$d['percentagem_final'], 1) ?>%</span>
                                    </div>
                                </td>
                                <td><?= $d['score_jogo'] ?? '—' ?></td>
                                <td>
                                    <?php if ($d['passou_nivel']): ?>
                                        <span class="badge bg-success">✔ Sim</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">✘ Não</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $tend = [
                                        'melhoria'  => '<i class="fa-solid fa-arrow-trend-up text-success"></i> Melhoria',
                                        'estavel'   => '<i class="fa-solid fa-minus text-secondary"></i> Estável',
                                        'regressao' => '<i class="fa-solid fa-arrow-trend-down text-danger"></i> Regressão',
                                    ];
                                    echo $tend[$d['tendencia']] ?? '—';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
            new Chart(document.getElementById('chartDesempenho'), {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_map(fn($d) => substr($d['data_hora'], 0, 10), $dados)) ?>,
                    datasets: [{
                        label: 'Precisão (%)',
                        data: <?= json_encode(array_map(fn($d) => round((float)$d['percentagem_final'], 1), $dados)) ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102,126,234,0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#764ba2',
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { min: 0, max: 100, title: { display: true, text: 'Precisão (%)' } } }
                }
            });
            </script>

            <?php else: ?>
                <div class="alert alert-info">Sem dados de sessões para este paciente.</div>
            <?php endif; ?>

            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
