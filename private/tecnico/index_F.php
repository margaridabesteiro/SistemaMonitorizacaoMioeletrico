<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
$pagina_titulo = 'Painel Técnico'; $pagina_ativa = 'dashboard';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../includes/header_tecnico.php';
require_once __DIR__ . '/../../includes/sidebar_tecnico.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

$stmt2 = $db->prepare('SELECT COUNT(*) FROM utentes WHERE tecnico_id=?'); $stmt2->execute([$pid]); $n_pac = (int)$stmt2->fetchColumn();
$stmt3 = $db->prepare("SELECT COUNT(*) FROM sessoes WHERE tecnico_id=? AND DATE(data_hora)=CURDATE() AND estado='agendada'"); $stmt3->execute([$pid]); $n_hoje = (int)$stmt3->fetchColumn();
$stmt4 = $db->prepare("SELECT COUNT(*) FROM sessoes WHERE tecnico_id=? AND estado='concluida'"); $stmt4->execute([$pid]); $n_concluidas = (int)$stmt4->fetchColumn();

$proximas = [];
if ($pid) {
    $s = $db->prepare("SELECT s.data_hora, s.categoria, s.estado, s.modalidade, s.link_videochamada, u.nome AS paciente, j.nome AS jogo_nome FROM sessoes s JOIN utentes ut ON ut.id=s.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id LEFT JOIN jogos j ON j.id=s.jogo_id WHERE s.tecnico_id=? AND s.data_hora>=NOW() AND s.estado='agendada' ORDER BY s.data_hora LIMIT 5");
    $s->execute([$pid]); $proximas = $s->fetchAll();
}

// Videochamada em ≤ 30 min
$video_proxima = null;
if ($pid) {
    $s = $db->prepare("SELECT s.link_videochamada, u.nome AS paciente, s.data_hora FROM sessoes s JOIN utentes ut ON ut.id=s.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE s.tecnico_id=? AND s.modalidade='remota' AND s.link_videochamada IS NOT NULL AND s.data_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 MINUTE) AND s.estado='agendada' ORDER BY s.data_hora LIMIT 1");
    $s->execute([$pid]); $video_proxima = $s->fetch() ?: null;
}

// Último sync dos dispositivos dos pacientes deste técnico
$ultimo_sync = $pid ? $db->query("SELECT d.codigo, d.ultimo_sync FROM dispositivos d JOIN emprestimos_dispositivos e ON e.dispositivo_id=d.id JOIN utentes ut ON ut.id=e.utente_id WHERE ut.tecnico_id=$pid AND e.data_devolucao IS NULL ORDER BY d.ultimo_sync DESC LIMIT 3")->fetchAll() : [];

// Alertas clínicos: pacientes com tendência de regressão ou % final < 50 nos últimos 7 dias
$alertas_clinicos = [];
if ($pid) {
    try {
        $sa = $db->prepare("
            SELECT u.nome AS paciente, s.id AS sessao_id,
                   DATE_FORMAT(s.data_hora,'%d/%m %H:%i') AS quando,
                   m.percentagem_final, m.tendencia, m.rms_uv
            FROM sessoes s
            JOIN metricas_sessao m ON m.sessao_id = s.id
            JOIN utentes ut ON ut.id = s.utente_id
            JOIN utilizadores u ON u.id = ut.utilizador_id
            WHERE s.tecnico_id = ?
              AND s.estado = 'concluida'
              AND s.data_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND (m.tendencia = 'regressao' OR m.percentagem_final < 50)
            ORDER BY s.data_hora DESC
            LIMIT 5
        ");
        $sa->execute([$pid]);
        $alertas_clinicos = $sa->fetchAll();
    } catch (\Throwable $e) { $alertas_clinicos = []; }
}
?>
        <main class="content">
            <div class="welcome-section mb-4" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h2>Bem-vindo, <?= h($_SESSION['nome']) ?></h2>
                    <p><?= dataPt() ?> · <?= $n_hoje ?> sessões hoje</p>
                </div>
                <i class="fa-solid fa-hand-holding-heart fa-2x" style="color:#1a5f8a;"></i>
            </div>

            <?php if (!empty($alertas_clinicos)): ?>
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white py-2 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Alertas Clínicos</strong>
                    <span class="badge bg-white text-danger ms-1"><?= count($alertas_clinicos) ?></span>
                    <small class="ms-auto opacity-75">Últimos 7 dias · RMS baixo ou tendência de regressão</small>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Paciente</th><th>Sessão</th><th>% Final</th><th>Tendência</th><th>RMS (µV)</th></tr></thead>
                        <tbody>
                        <?php foreach ($alertas_clinicos as $al): ?>
                            <tr>
                                <td class="fw-semibold"><?= h($al['paciente']) ?></td>
                                <td><a href="sessoes/detalhes_sessao.php?id=<?= $al['sessao_id'] ?>" class="text-decoration-none"><?= h($al['quando']) ?></a></td>
                                <td><?= $al['percentagem_final'] !== null ? '<span class="text-danger fw-bold">'.number_format((float)$al['percentagem_final'],1).'%</span>' : '—' ?></td>
                                <td><?= $al['tendencia'] === 'regressao' ? '<span class="badge bg-danger"><i class="fa-solid fa-arrow-trend-down me-1"></i>Regressão</span>' : '<span class="badge bg-secondary">'.$al['tendencia'].'</span>' ?></td>
                                <td><?= $al['rms_uv'] !== null ? number_format((float)$al['rms_uv'],1).' µV' : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($video_proxima): ?>
            <div class="alert alert-primary d-flex align-items-center gap-3 mb-4">
                <i class="fa-solid fa-video fa-2x"></i>
                <div class="flex-grow-1">
                    <strong>Sessão remota em breve!</strong><br>
                    <small>Com <?= h($video_proxima['paciente']) ?> às <?= h(substr($video_proxima['data_hora'],11,5)) ?></small>
                </div>
                <a href="<?= h($video_proxima['link_videochamada']) ?>" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-video me-1"></i>Entrar
                </a>
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-primary"><?= $n_pac ?></div><div class="text-muted small">Pacientes Ativos</div></div></div>
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-warning"><?= $n_hoje ?></div><div class="text-muted small">Sessões Hoje</div></div></div>
                <div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success"><?= $n_concluidas ?></div><div class="text-muted small">Sessões Concluídas</div></div></div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="card p-3">
                        <h5 class="mb-3">Próximas Sessões</h5>
                        <?php if(empty($proximas)): ?><p class="text-muted">Sem sessões agendadas.</p>
                        <?php else: ?>
                        <table class="table table-sm table-hover"><thead><tr><th>Data/Hora</th><th>Paciente</th><th>Jogo / Categoria</th><th></th></tr></thead><tbody>
                        <?php foreach($proximas as $s): ?>
                            <tr>
                                <td><?= h(substr($s['data_hora'],0,16)) ?></td>
                                <td><?= h($s['paciente']) ?></td>
                                <td><?= h($s['jogo_nome'] ?? ucfirst(str_replace('_',' ',$s['categoria']??'—'))) ?></td>
                                <td>
                                    <?php if($s['modalidade']==='remota' && $s['link_videochamada']): ?>
                                        <a href="<?= h($s['link_videochamada']) ?>" target="_blank" class="btn btn-xs btn-primary"><i class="fa-solid fa-video"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody></table>
                        <?php endif; ?>
                        <a href="sessoes/lista_sessoes.php" class="btn btn-sm btn-outline-secondary mt-2">Ver todas</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3">
                        <h5 class="mb-3">Último Sync — Dispositivos</h5>
                        <?php if (empty($ultimo_sync)): ?>
                            <p class="text-muted small">Sem dispositivos emprestados.</p>
                        <?php else: foreach($ultimo_sync as $d): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold small"><?= h($d['codigo']) ?></span>
                                <?php
                                $sync = $d['ultimo_sync'];
                                $diff = $sync ? (time() - strtotime($sync)) : null;
                                $cor  = !$diff ? 'secondary' : ($diff < 3600 ? 'success' : ($diff < 259200 ? 'warning' : 'danger'));
                                $txt  = !$sync ? 'Nunca' : h(substr($sync,0,16));
                                ?>
                                <span class="badge bg-<?= $cor ?>"><?= $txt ?></span>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
