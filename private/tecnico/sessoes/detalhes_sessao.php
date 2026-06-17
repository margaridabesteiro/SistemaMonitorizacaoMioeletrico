<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('tecnico');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');

// Guardar comentários do relatório de avaliação funcional
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_acao'] ?? '') === 'save_comentario') {
    $comentario = trim($_POST['comentario_relatorio'] ?? '') ?: null;
    $db->prepare("UPDATE sessoes SET analise_tecnica=? WHERE id=?")->execute([$comentario, $id]);
    registarAuditoria('ATUALIZAR', 'Sessao', $id, 'Comentários do relatório de avaliação guardados');
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Comentários guardados.'];
    redirect(APP_URL . '/private/tecnico/sessoes/detalhes_sessao.php?id=' . $id);
}

$pagina_titulo = 'Detalhes Sessão'; $pagina_ativa = 'sessoes';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';

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
?>
        <main class="content">
            <?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div>
            <?php endif; ?>
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="lista_sessoes.php">Sessões</a></li>
                    <li class="breadcrumb-item active">Sessão #<?= $id ?></li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Detalhes da Sessão #<?= $id ?></h1>
                <div class="d-flex gap-2">
                    <?php if ($s['estado']==='agendada' && $s['categoria']==='avaliacao_funcional'): ?>
                        <a href="iniciar_sessao.php?id=<?= $id ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-play me-1"></i>Iniciar</a>
                    <?php endif; ?>
                    <?php if ($s['categoria']==='avaliacao_funcional' && $s['modalidade']==='remota' && !empty($s['link_videochamada'])): ?>
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
                    </div>
                </div>
                <?php endif; ?>
            </div>


            <?php if ($s['categoria'] === 'avaliacao_funcional'): ?>
            <!-- Comentários do relatório de avaliação funcional -->
            <div class="card p-3 mt-3">
                <h5 class="mb-3"><i class="fa-solid fa-file-medical me-2" style="color:#1a5f8a;"></i>Relatório da Avaliação Funcional</h5>
                <?php if (!empty($s['analise_tecnica'])): ?>
                <div class="alert alert-light border mb-3">
                    <pre class="mb-0" style="white-space:pre-wrap;font-family:inherit;"><?= h($s['analise_tecnica']) ?></pre>
                </div>
                <?php else: ?>
                <p class="text-muted small mb-3">Ainda não foram escritos comentários para esta avaliação.</p>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="_acao" value="save_comentario">
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Comentários / Observações</label>
                        <textarea name="comentario_relatorio" class="form-control" rows="5"
                                  placeholder="Descreva os resultados da avaliação, observações clínicas, recomendações..."><?= h($s['analise_tecnica'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm" style="background:#1a5f8a;color:#fff;">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Comentários
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <a href="lista_sessoes.php" class="btn btn-outline-secondary mt-3">
                <i class="fa-solid fa-arrow-left me-1"></i>Voltar
            </a>
        </main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
