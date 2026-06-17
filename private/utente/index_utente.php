<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$pagina_titulo = 'Dashboard Utente';
$pagina_ativa  = 'dashboard';
$js_head       = ['https://cdn.jsdelivr.net/npm/chart.js'];

require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';

$db = getDB();
$utilizador_id = (int)$_SESSION['utilizador_id'];

// Obter utente
$stmt = $db->prepare('SELECT id, diagnostico, cobertura_saude FROM utentes WHERE utilizador_id = ?');
$stmt->execute([$utilizador_id]);
$utente = $stmt->fetch();
$utente_id       = $utente ? (int)$utente['id'] : 0;
$cobertura_saude = $utente['cobertura_saude'] ?? 'SNS';

$proximas_items = [];
if ($utente_id) {
    $proximas_items = [];
    try {
        $s1 = $db->prepare("
            SELECT s.data_hora, s.estado, s.modalidade,
                   COALESCE(j.nome, s.categoria, 'Sessão de Treino') AS titulo,
                   u.nome AS profissional, 'sessao' AS tipo_item
            FROM sessoes s
            LEFT JOIN jogos j ON j.id = s.jogo_id
            LEFT JOIN profissionais p ON p.id = s.tecnico_id
            LEFT JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE s.utente_id = ? AND s.estado NOT IN ('cancelada','concluida')
            ORDER BY s.data_hora ASC LIMIT 5
        ");
        $s1->execute([$utente_id]); $r1 = $s1->fetchAll();

        $s2 = $db->prepare("
            SELECT c.data_hora, c.estado, c.modalidade,
                   COALESCE(c.tipo, 'Consulta Médica') AS titulo,
                   u.nome AS profissional, 'consulta' AS tipo_item
            FROM consultas c
            LEFT JOIN profissionais p ON p.id = c.medico_id
            LEFT JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE c.utente_id = ? AND c.estado NOT IN ('cancelada','realizada')
            ORDER BY c.data_hora ASC LIMIT 5
        ");
        $s2->execute([$utente_id]); $r2 = $s2->fetchAll();

        $proximas_items = array_merge($r1, $r2);
        usort($proximas_items, fn($a, $b) => strtotime($a['data_hora']) <=> strtotime($b['data_hora']));
        $proximas_items = array_slice($proximas_items, 0, 5);
    } catch (\Throwable $e) {}
}

$total_sessoes = 0;
if ($utente_id) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM sessoes WHERE utente_id = ? AND estado = "concluida"');
    $stmt->execute([$utente_id]);
    $total_sessoes = (int)$stmt->fetchColumn();
}

$faturas_abertas = 0;
if ($utente_id && $cobertura_saude !== 'SNS') {
    $stmt = $db->prepare('SELECT COUNT(*) FROM faturas WHERE utente_id = ? AND paga = 0');
    $stmt->execute([$utente_id]);
    $faturas_abertas = (int)$stmt->fetchColumn();
}

$video_link = null;
if ($utente_id) {
    $sv = $db->prepare("SELECT link_videochamada FROM sessoes WHERE utente_id=? AND modalidade='remota' AND link_videochamada IS NOT NULL AND data_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 MINUTE) AND estado='agendada' ORDER BY data_hora LIMIT 1");
    $sv->execute([$utente_id]); $video_link = $sv->fetchColumn() ?: null;
}

$stmt = $db->prepare('SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lida = 0');
$stmt->execute([$utilizador_id]);
$mensagens_nao_lidas = (int)$stmt->fetchColumn();
?>
        <main class="content">
            <div class="welcome-section mb-4">
                <div class="welcome-text">
                    <h2>Bem-vindo, <?= h($_SESSION['nome']) ?></h2>
                    <p><?= dataPt() ?></p>
                </div>
            </div>
            <?php if ($video_link): ?>
            <div class="alert alert-primary d-flex align-items-center gap-3 mb-4">
                <i class="fa-solid fa-video fa-2x"></i>
                <div class="flex-grow-1"><strong>Tens uma sessão por videochamada em breve!</strong><br><small>A tua sessão começa nos próximos 30 minutos.</small></div>
                <a href="<?= h($video_link) ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fa-solid fa-video me-1"></i>Entrar agora</a>
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-success"><?= $total_sessoes ?></div>
                        <div class="text-muted small">Sessões Concluídas</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-primary"><?= count($proximas_items) ?></div>
                        <div class="text-muted small">Próximas Marcações</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3 <?= $mensagens_nao_lidas > 0 ? 'border-warning' : '' ?>">
                        <div class="fs-2 fw-bold text-warning"><?= $mensagens_nao_lidas ?></div>
                        <div class="text-muted small">Mensagens Não Lidas</div>
                    </div>
                </div>
                <?php if ($cobertura_saude !== 'SNS'): ?>
                <div class="col-md-3">
                    <div class="card text-center p-3 <?= $faturas_abertas > 0 ? 'border-danger' : '' ?>">
                        <div class="fs-2 fw-bold text-danger"><?= $faturas_abertas ?></div>
                        <div class="text-muted small">Faturas em Aberto</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="card p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Sessões / Consultas</h5>
                    <a href="sessoes_consultas.php" class="btn btn-sm btn-outline-secondary">Ver todas</a>
                </div>
                <?php if (empty($proximas_items)): ?>
                    <p class="text-muted small">Não tem sessões ou consultas marcadas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Tipo</th><th>Profissional</th><th>Data / Hora</th><th>Modalidade</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($proximas_items as $item):
                            $e_sessao = $item['tipo_item'] === 'sessao';
                            $e_video  = in_array($item['modalidade'], ['video','remota'], true);
                            $cor_tipo = $e_sessao ? '#667eea' : '#8B0000';
                            $estado_cores = ['agendada'=>'primary','em_curso'=>'info text-dark','concluida'=>'success','cancelada'=>'secondary'];
                        ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background:<?= $cor_tipo ?>;">
                                        <i class="fa-solid <?= $e_sessao ? 'fa-dumbbell' : 'fa-stethoscope' ?> me-1"></i>
                                        <?= $e_sessao ? h($item['titulo']) : 'Consulta' ?>
                                    </span>
                                </td>
                                <td class="small"><?= h($item['profissional'] ?? '—') ?></td>
                                <td class="small text-nowrap"><?= h(date('d/m/Y H:i', strtotime($item['data_hora']))) ?></td>
                                <td>
                                    <?php if ($e_video): ?>
                                        <span class="badge bg-primary"><i class="fa-solid fa-video me-1"></i>Remota</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fa-solid fa-hospital me-1"></i>Presencial</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= $estado_cores[$item['estado']] ?? 'secondary' ?>"><?= h(ucfirst($item['estado'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <a href="jogos_reabilitacao.php" class="card p-3 text-decoration-none text-dark d-block text-center">
                        <i class="fa-solid fa-gamepad fa-2x mb-2" style="color:#8B0000;"></i>
                        <div class="fw-semibold">Jogos de Reabilitação</div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="mensagens.php" class="card p-3 text-decoration-none text-dark d-block text-center">
                        <i class="fa-regular fa-envelope fa-2x mb-2" style="color:#8B0000;"></i>
                        <div class="fw-semibold">Mensagens</div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="pagamentos.php" class="card p-3 text-decoration-none text-dark d-block text-center">
                        <i class="fa-solid fa-credit-card fa-2x mb-2" style="color:#8B0000;"></i>
                        <div class="fw-semibold">Pagamentos</div>
                    </a>
                </div>
            </div>
        </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
