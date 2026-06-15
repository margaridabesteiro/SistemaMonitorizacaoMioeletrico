<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Sessões / Consultas'; $pagina_ativa = 'sessoes';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?");
$stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();

$hoje = date('Y-m-d');

// Próximas sessões de treino
$sessoes = [];
if ($utid) {
    try {
        $s = $db->prepare("
            SELECT s.id, s.data_hora, s.estado, s.modalidade, s.link_videochamada,
                   COALESCE(j.nome, s.categoria, 'Sessão de Treino') AS titulo,
                   u.nome AS profissional, 'sessao' AS tipo_item
            FROM sessoes s
            LEFT JOIN jogos j ON j.id = s.jogo_id
            LEFT JOIN profissionais p ON p.id = s.tecnico_id
            LEFT JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE s.utente_id = ? AND s.data_hora >= NOW() AND s.estado NOT IN ('cancelada','concluida')
            ORDER BY s.data_hora ASC LIMIT 20
        ");
        $s->execute([$utid]);
        $sessoes = $s->fetchAll();
    } catch (\Throwable $e) {}
}

// Próximas consultas médicas
$consultas = [];
if ($utid) {
    try {
        $s = $db->prepare("
            SELECT c.id, c.data_hora, c.estado, c.modalidade, c.link_videochamada,
                   COALESCE(CONCAT('Consulta — ', c.tipo), 'Consulta Médica') AS titulo,
                   u.nome AS profissional, 'consulta' AS tipo_item
            FROM consultas c
            LEFT JOIN profissionais p ON p.id = c.medico_id
            LEFT JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE c.utente_id = ? AND c.data_hora >= NOW() AND c.estado != 'cancelada'
            ORDER BY c.data_hora ASC LIMIT 20
        ");
        $s->execute([$utid]);
        $consultas = $s->fetchAll();
    } catch (\Throwable $e) {
        // consultas table may not exist
    }
}

// Combinar e ordenar por data
$todos = array_merge($sessoes, $consultas);
usort($todos, fn($a, $b) => strtotime($a['data_hora']) <=> strtotime($b['data_hora']));

require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h1 class="mb-1"><i class="fa-solid fa-calendar-check me-2"></i>Sessões / Consultas</h1>
                    <p class="text-muted mb-0 small">Próximas sessões de treino e consultas médicas agendadas.</p>
                </div>
                <a href="agenda.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-regular fa-calendar me-1"></i>Ver Agenda
                </a>
            </div>

            <?php if (empty($todos)): ?>
            <div class="alert alert-light text-center py-5">
                <i class="fa-regular fa-calendar-xmark fa-3x mb-3 d-block" style="color:#667eea;opacity:.5;"></i>
                <strong>Sem sessões ou consultas agendadas</strong><br>
                <span class="text-muted small">Quando o seu médico ou técnico agendar algo, aparecerá aqui.</span>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($todos as $item):
                    $e_sessao   = $item['tipo_item'] === 'sessao';
                    $cor_tipo   = $e_sessao ? '#667eea' : '#8B0000';
                    $icon_tipo  = $e_sessao ? 'fa-dumbbell' : 'fa-stethoscope';
                    $label_tipo = $e_sessao ? 'Sessão de Treino' : 'Consulta Médica';
                    $data_hora  = strtotime($item['data_hora']);
                    $hoje_ts    = strtotime($hoje);
                    $diff_dias  = (int)(($data_hora - $hoje_ts) / 86400);
                    $urgencia   = $diff_dias === 0 ? 'Hoje' : ($diff_dias === 1 ? 'Amanhã' : 'Em ' . $diff_dias . ' dias');
                    $urgencia_cor = $diff_dias === 0 ? 'danger' : ($diff_dias === 1 ? 'warning' : 'secondary');
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100" style="border-left:4px solid <?= $cor_tipo ?>;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge" style="background:<?= $cor_tipo ?>;">
                                    <i class="fa-solid <?= $icon_tipo ?> me-1"></i><?= $label_tipo ?>
                                </span>
                                <span class="badge bg-<?= $urgencia_cor ?>"><?= $urgencia ?></span>
                            </div>
                            <h6 class="fw-semibold mb-1"><?= h($item['titulo']) ?></h6>
                            <div class="text-muted small mb-2">
                                <i class="fa-regular fa-clock me-1"></i>
                                <?= date('d/m/Y', $data_hora) ?> às <?= date('H:i', $data_hora) ?>
                            </div>
                            <?php if (!empty($item['profissional'])): ?>
                            <div class="text-muted small mb-2">
                                <i class="fa-solid <?= $e_sessao ? 'fa-user-nurse' : 'fa-user-doctor' ?> me-1"></i>
                                <?= h($item['profissional']) ?>
                            </div>
                            <?php endif; ?>
                            <div class="mb-2">
                                <span class="badge bg-<?= $item['modalidade']==='remota'||$item['modalidade']==='video'?'primary':'secondary' ?>">
                                    <i class="fa-solid <?= $item['modalidade']==='remota'||$item['modalidade']==='video' ? 'fa-video' : 'fa-hospital' ?> me-1"></i>
                                    <?= $item['modalidade']==='remota'||$item['modalidade']==='video' ? 'Remota' : 'Presencial' ?>
                                </span>
                            </div>
                            <?php if (!empty($item['link_videochamada'])): ?>
                            <a href="<?= h($item['link_videochamada']) ?>" target="_blank" rel="noopener"
                               class="btn btn-sm w-100 mt-1" style="background:#667eea;color:#fff;">
                                <i class="fa-solid fa-video me-1"></i>Entrar na sessão
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
