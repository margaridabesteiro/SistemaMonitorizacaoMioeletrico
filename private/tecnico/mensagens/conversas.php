<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Mensagens'; $pagina_ativa = 'mensagens';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>

        <main class="content">
            <?php
            $db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
            $conversas = $db->prepare("SELECT DISTINCT CASE WHEN m.remetente_id=? THEN m.destinatario_id ELSE m.remetente_id END AS outro_id, u.nome AS outro_nome, MAX(m.enviada_em) AS ultima, SUM(m.lida=0 AND m.destinatario_id=?) AS nao_lidas FROM mensagens m JOIN utilizadores u ON u.id = CASE WHEN m.remetente_id=? THEN m.destinatario_id ELSE m.remetente_id END WHERE m.remetente_id=? OR m.destinatario_id=? GROUP BY outro_id ORDER BY ultima DESC");
            $conversas->execute([$uid,$uid,$uid,$uid,$uid]); $conversas = $conversas->fetchAll();
            $sel = (int)($_GET['com'] ?? ($conversas[0]['outro_id'] ?? 0));
            $msgs = [];
            if ($sel) {
                $stmt = $db->prepare("SELECT m.*, u.nome AS remetente FROM mensagens m JOIN utilizadores u ON u.id=m.remetente_id WHERE (m.remetente_id=? AND m.destinatario_id=?) OR (m.remetente_id=? AND m.destinatario_id=?) ORDER BY m.enviada_em");
                $stmt->execute([$uid,$sel,$sel,$uid]); $msgs = $stmt->fetchAll();
                $db->prepare("UPDATE mensagens SET lida=1 WHERE destinatario_id=? AND remetente_id=?")->execute([$uid,$sel]);
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sel) {
                $corpo = trim($_POST['corpo'] ?? '');
                if ($corpo !== '') { $db->prepare("INSERT INTO mensagens (remetente_id,destinatario_id,corpo) VALUES (?,?,?)")->execute([$uid,$sel,$corpo]); redirect(APP_URL . '/private/tecnico/mensagens/conversas.php?com=' . $sel); }
            }
            ?>
            <div class="d-flex justify-content-between align-items-center mb-4"><h1>Mensagens</h1></div>
            <div class="row" style="height:600px;">
                <div class="col-md-4 border-end">
                    <?php foreach($conversas as $c): ?>
                    <a href="?com=<?= $c['outro_id'] ?>" class="d-flex align-items-center gap-3 p-3 text-decoration-none <?= $c['outro_id']===$sel?'bg-light':'' ?> border-bottom">
                        <div style="width:40px;height:40px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;"><i class="fa-regular fa-user" style="color:#1a5f8a;"></i></div>
                        <div class="flex-grow-1"><strong><?= h($c['outro_nome']) ?></strong><?php if($c['nao_lidas']>0): ?> <span class="badge bg-danger"><?= $c['nao_lidas'] ?></span><?php endif; ?>
                        <div class="text-muted small"><?= h(substr($c['ultima'],0,10)) ?></div></div>
                    </a>
                    <?php endforeach; ?>
                    <?php if(empty($conversas)): ?><p class="p-3 text-muted small">Sem conversas.</p><?php endif; ?>
                </div>
                <div class="col-md-8 d-flex flex-column">
                    <div class="flex-grow-1 p-3" style="overflow-y:auto;max-height:480px;">
                        <?php foreach($msgs as $msg): ?>
                        <div class="mb-3 <?= $msg['remetente_id']===$uid?'text-end':'' ?>">
                            <div class="d-inline-block p-2 rounded" style="background:<?= $msg['remetente_id']===$uid?'#1a5f8a;color:#fff':'#f1f3f4' ?>;max-width:70%;">
                                <?= h($msg['corpo']) ?></div>
                            <div class="text-muted small"><?= h(substr($msg['enviada_em'],11,5)) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if($sel && empty($msgs)): ?><p class="text-muted">Sem mensagens ainda.</p><?php endif; ?>
                    </div>
                    <?php if($sel): ?>
                    <form method="POST" class="p-3 border-top d-flex gap-2">
                        <input type="text" name="corpo" class="form-control" placeholder="Escrever mensagem..." autofocus>
                        <button type="submit" class="btn" style="background:#1a5f8a;color:#fff;"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
