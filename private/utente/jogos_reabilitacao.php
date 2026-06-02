<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Jogos de Reabilitação'; $pagina_ativa = 'jogos';
require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?"); $stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();
$stats = ['n_sess'=>0,'best_score'=>null];
if ($utid) {
    $s = $db->prepare("SELECT COUNT(*) as n_sess, MAX(m.score_jogo) as best_score FROM sessoes s LEFT JOIN metricas_sessao m ON m.sessao_id=s.id WHERE s.utente_id=? AND (s.categoria LIKE '%jogo%' OR s.categoria='Sessão gamificada')");
    $s->execute([$utid]); $stats = $s->fetch() ?: $stats;
}
$jogos = [
    ['catch_game','Catch Game','Apanhe objetos em queda. Treina precisão e velocidade de resposta mioelétrica.','fa-bullseye','#667eea'],
    ['flappy_trainer','Flappy Trainer','Controle um pássaro com força muscular. Treina modulação de força.','fa-feather-pointed','#f7c948'],
    ['prosthesis_trainer','Prosthesis Trainer','Simulação completa de uso protésico — sequências e tarefas de vida diária.','fa-hand','#5cdb95'],
];
?>
        <main class="content">
            <div class="mb-4 p-4 rounded" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;">
                <h1 class="mb-1">Jogos de Reabilitação</h1>
                <p class="mb-3 opacity-75">Divirta-se enquanto melhora as suas capacidades motoras.</p>
                <div class="d-flex gap-4">
                    <div><i class="fa-solid fa-gamepad me-1"></i><?= $stats['n_sess'] ?> sessões de jogo</div>
                    <?php if ($stats['best_score']): ?><div><i class="fa-solid fa-trophy me-1"></i>Melhor score: <?= $stats['best_score'] ?></div><?php endif; ?>
                </div>
            </div>
            <div class="row g-3">
                <?php foreach ($jogos as [$slug, $titulo, $desc, $icon, $cor]): ?>
                <div class="col-md-4">
                    <div class="card p-3 h-100 text-center">
                        <div style="width:70px;height:70px;border-radius:50%;background:<?= $cor ?>22;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;font-size:2rem;color:<?= $cor ?>;"><i class="fa-solid <?= $icon ?>"></i></div>
                        <h5><?= $titulo ?></h5>
                        <p class="text-muted small flex-grow-1"><?= $desc ?></p>
                        <a href="jogos/<?= $slug ?>.php" class="btn mt-2" style="background:<?= $cor ?>;color:#fff;">Jogar</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
