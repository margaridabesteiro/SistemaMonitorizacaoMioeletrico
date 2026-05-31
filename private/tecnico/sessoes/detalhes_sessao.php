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
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
