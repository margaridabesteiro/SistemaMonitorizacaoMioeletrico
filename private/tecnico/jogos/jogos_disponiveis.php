<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Jogos Disponíveis'; $pagina_ativa = 'sessoes';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>

        <main class="content">
            <?php
            $db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
            $stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
            $pacientes = $pid ? $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.tecnico_id=? ORDER BY u.nome") : null;
            if ($pacientes) { $pacientes->execute([$pid]); $pacientes = $pacientes->fetchAll(); } else { $pacientes = []; }
            $sel = (int)($_GET['utente_id'] ?? ($pacientes[0]['id'] ?? 0));
            $jogos = [['catch_game','Catch Game','Apanhe objetos com a prótese — treina precisão e velocidade de resposta.','fa-hands-catching'],['flappy_trainer','Flappy Trainer','Controle um pássaro com força muscular — treina modulação de força.','fa-feather-pointed'],['prosthesis_trainer','Prosthesis Trainer','Simulação completa de uso protésico — sequências e tarefas.','fa-hand'],];
            ?>
            <h1 class="mb-4">Jogos de Reabilitação</h1>
            <form method="GET" class="mb-4">
                <select name="utente_id" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                    <?php foreach($pacientes as $p): ?><option value="<?= $p['id'] ?>" <?= $p['id']===$sel?'selected':'' ?>><?= h($p['nome']) ?></option><?php endforeach; ?>
                </select>
            </form>
            <div class="row g-3">
                <?php foreach($jogos as [$slug,$titulo,$desc,$icon]): ?>
                <div class="col-md-4">
                    <div class="card p-3 text-center">
                        <i class="fa-solid <?= $icon ?> fa-2x mb-2" style="color:#1a5f8a;"></i>
                        <h5><?= $titulo ?></h5>
                        <p class="text-muted small"><?= $desc ?></p>
                        <a href="<?= $slug ?>.php?utente_id=<?= $sel ?>" class="btn btn-sm" style="background:#1a5f8a;color:#fff;">Iniciar Jogo</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
