<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$pagina_titulo = 'Painel do Médico';
$pagina_ativa  = 'dashboard';

require_once __DIR__ . '/../../includes/header_medico.php';
require_once __DIR__ . '/../../includes/sidebar_medico.php';

$db        = getDB();
$medico_id = (int)$_SESSION['utilizador_id'];

$profissional = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id = ?');
$profissional->execute([$medico_id]);
$prof_id = (int)($profissional->fetchColumn() ?: 0);

// Consultas hoje
$n_consultas = 0;
if ($prof_id) {
    $s = $db->prepare('SELECT COUNT(*) FROM consultas WHERE medico_id=? AND DATE(data_hora)=CURDATE() AND estado="agendada"');
    $s->execute([$prof_id]); $n_consultas = (int)$s->fetchColumn();
}

// Total pacientes
$n_pacientes = 0;
if ($prof_id) {
    $s = $db->prepare('SELECT COUNT(*) FROM utentes WHERE medico_id=?');
    $s->execute([$prof_id]); $n_pacientes = (int)$s->fetchColumn();
}

// Programas ativos
$n_programas = 0;
if ($prof_id) {
    $s = $db->prepare('SELECT COUNT(*) FROM programas_tratamento WHERE medico_id=? AND ativa=1');
    $s->execute([$prof_id]); $n_programas = (int)$s->fetchColumn();
}

// Próximas consultas com tipo e modalidade
$proximas = [];
if ($prof_id) {
    $s = $db->prepare('
        SELECT c.data_hora, c.estado, c.tipo, c.modalidade, c.link_videochamada,
               u.nome AS paciente
        FROM consultas c
        JOIN utentes ut ON ut.id=c.utente_id
        JOIN utilizadores u ON u.id=ut.utilizador_id
        WHERE c.medico_id=? AND c.data_hora>=NOW()
        ORDER BY c.data_hora ASC LIMIT 5
    ');
    $s->execute([$prof_id]); $proximas = $s->fetchAll();
}

// Videochamada em ≤ 30 min
$video_proxima = null;
if ($prof_id) {
    $s = $db->prepare("SELECT c.link_videochamada, u.nome AS paciente, c.data_hora FROM consultas c JOIN utentes ut ON ut.id=c.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE c.medico_id=? AND c.modalidade='video' AND c.link_videochamada IS NOT NULL AND c.data_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 MINUTE) AND c.estado='agendada' ORDER BY c.data_hora LIMIT 1");
    $s->execute([$prof_id]); $video_proxima = $s->fetch() ?: null;
}

$tipo_badge = ['inicial'=>'info','rotina'=>'secondary','alta'=>'success','urgente'=>'danger'];

// Alertas clínicos: pacientes com tendência de regressão ou % final < 50 nos últimos 7 dias
$alertas_clinicos = [];
if ($prof_id) {
    try {
        $sa = $db->prepare("
            SELECT u.nome AS paciente, s.id AS sessao_id,
                   DATE_FORMAT(s.data_hora,'%d/%m %H:%i') AS quando,
                   m.percentagem_final, m.tendencia
            FROM sessoes s
            JOIN metricas_sessao m ON m.sessao_id = s.id
            JOIN utentes ut ON ut.id = s.utente_id
            JOIN utilizadores u ON u.id = ut.utilizador_id
            WHERE ut.medico_id = ?
              AND s.estado = 'concluida'
              AND s.data_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND (m.tendencia = 'regressao' OR m.percentagem_final < 50)
            ORDER BY s.data_hora DESC
            LIMIT 5
        ");
        $sa->execute([$prof_id]);
        $alertas_clinicos = $sa->fetchAll();
    } catch (\Throwable $e) { $alertas_clinicos = []; }
}
?>
        <main class="content">
            <div class="welcome-section mb-4">
                <div class="welcome-text">
                    <h2>Bem-vindo, <?= h($_SESSION['nome']) ?></h2>
                    <p><?= dataPt() ?> · <?= $n_consultas ?> consulta(s) hoje</p>
                </div>
                <div class="welcome-icon">
                    <i class="fa-solid fa-stethoscope fa-2x" style="color:#8B0000;"></i>
                </div>
            </div>

            <?php if (!empty($alertas_clinicos)): ?>
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white py-2 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Alertas Clínicos</strong>
                    <small class="ms-auto opacity-75">Últimos 7 dias · Regressão ou % &lt; 50</small>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Paciente</th><th>Data</th><th>% Final</th><th>Tendência</th></tr></thead>
                        <tbody>
                        <?php foreach ($alertas_clinicos as $al): ?>
                            <tr>
                                <td class="fw-semibold"><?= h($al['paciente']) ?></td>
                                <td><?= h($al['quando']) ?></td>
                                <td><?= $al['percentagem_final'] !== null ? '<span class="text-danger fw-bold">'.number_format((float)$al['percentagem_final'],1).'%</span>' : '—' ?></td>
                                <td><?= $al['tendencia'] === 'regressao' ? '<span class="badge bg-danger"><i class="fa-solid fa-arrow-trend-down me-1"></i>Regressão</span>' : '<span class="badge bg-secondary">'.$al['tendencia'].'</span>' ?></td>
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
                    <strong>Videoconsulta em breve!</strong><br>
                    <small>Com <?= h($video_proxima['paciente']) ?> às <?= h(substr($video_proxima['data_hora'],11,5)) ?></small>
                </div>
                <a href="<?= h($video_proxima['link_videochamada']) ?>" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-video me-1"></i>Entrar
                </a>
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-primary"><?= $n_consultas ?></div>
                        <div class="text-muted small">Consultas Hoje</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-success"><?= $n_programas ?></div>
                        <div class="text-muted small">Programas Ativos</div>
                    </div>
                </div>
            </div>

            <div class="card p-3 mb-4">
                <h5 class="mb-3">Próximas Consultas</h5>
                <?php if (empty($proximas)): ?>
                    <p class="text-muted">Sem consultas agendadas.</p>
                <?php else: ?>
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Data/Hora</th><th>Paciente</th><th>Tipo</th><th>Modalidade</th></tr></thead>
                        <tbody>
                        <?php foreach ($proximas as $c): ?>
                            <tr>
                                <td><?= h(substr($c['data_hora'],0,16)) ?></td>
                                <td><?= h($c['paciente']) ?></td>
                                <td><span class="badge bg-<?= $tipo_badge[$c['tipo']] ?? 'secondary' ?>"><?= h(ucfirst($c['tipo'])) ?></span></td>
                                <td>
                                    <?php if ($c['modalidade']==='video' && $c['link_videochamada']): ?>
                                        <a href="<?= h($c['link_videochamada']) ?>" target="_blank" class="btn btn-xs btn-primary"><i class="fa-solid fa-video me-1"></i>Vídeo</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Presencial</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <a href="consultas/consulta.php" class="btn btn-sm btn-outline-secondary mt-2">Ver todas</a>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
