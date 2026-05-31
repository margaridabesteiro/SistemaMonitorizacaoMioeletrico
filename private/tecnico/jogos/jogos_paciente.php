<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Histórico de Jogos'; $pagina_ativa = 'sessoes';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>

        <main class="content">
            <?php
            $db = getDB(); $id = (int)($_GET['utente_id'] ?? 0);
            $pac = $id ? $db->query("SELECT u.nome FROM utilizadores u JOIN utentes ut ON ut.utilizador_id=u.id WHERE ut.id=$id")->fetch() : null;
            $hist = $id ? $db->query("SELECT s.data_hora, s.categoria, m.score_jogo, m.percentagem_final FROM sessoes s LEFT JOIN metricas_sessao m ON m.sessao_id=s.id WHERE s.utente_id=$id AND s.categoria='jogo' OR s.categoria='jogo' ORDER BY s.data_hora DESC LIMIT 30")->fetchAll() : [];
            ?>
            <h1 class="mb-4">Histórico de Jogos<?= $pac ? ' — ' . h($pac['nome']) : '' ?></h1>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Jogo</th><th>Score</th><th>Precisão</th></tr></thead>
                    <tbody>
                    <?php if(empty($hist)): ?><tr><td colspan="4" class="text-center text-muted py-4">Sem sessões de jogo.</td></tr>
                    <?php else: foreach($hist as $h): ?><tr><td><?= h(substr($h['data_hora'],0,10)) ?></td><td><?= h($h['categoria']) ?></td><td><?= $h['score_jogo'] ?? '—' ?></td><td><?= $h['percentagem_final'] ? number_format((float)$h['percentagem_final'],1).'%' : '—' ?></td></tr><?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
