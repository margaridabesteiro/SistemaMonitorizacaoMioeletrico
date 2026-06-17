<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Mensagens'; $pagina_ativa = 'mensagens';
require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$conversas = $db->prepare("
    SELECT DISTINCT
        CASE WHEN m.remetente_id=? THEN m.destinatario_id ELSE m.remetente_id END AS outro_id,
        u.nome AS outro_nome,
        MAX(m.enviada_em) AS ultima,
        SUM(m.lida=0 AND m.destinatario_id=?) AS nao_lidas
    FROM mensagens m
    JOIN utilizadores u ON u.id = CASE WHEN m.remetente_id=? THEN m.destinatario_id ELSE m.remetente_id END
    WHERE m.remetente_id=? OR m.destinatario_id=?
    GROUP BY outro_id ORDER BY ultima DESC");
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
    if ($corpo !== '') { $db->prepare("INSERT INTO mensagens (remetente_id,destinatario_id,corpo) VALUES (?,?,?)")->execute([$uid,$sel,$corpo]); redirect(APP_URL . '/private/utente/mensagens.php?com='.$sel); }
}
?>
        <main class="content">
            <h1 class="mb-4">Mensagens</h1>
            <div class="row" style="height:600px;border:1px solid #dee2e6;border-radius:8px;overflow:hidden;">
                <div class="col-md-4 border-end p-0" style="overflow-y:auto;">
                    <div class="p-3 border-bottom bg-light"><input type="text" class="form-control form-control-sm" placeholder="Pesquisar conversas..." oninput="filtrar(this.value)"></div>
                    <div id="lista-conversas">
                    <?php foreach ($conversas as $c): ?>
                    <a href="?com=<?= $c['outro_id'] ?>" class="d-flex align-items-center gap-3 p-3 text-decoration-none <?= $c['outro_id']===$sel?'bg-primary bg-opacity-10':'' ?> border-bottom">
                        <div style="width:42px;height:42px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa-regular fa-user" style="color:#667eea;"></i></div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-truncate"><?= h($c['outro_nome']) ?></strong>
                                <?php if ($c['nao_lidas'] > 0): ?><span class="badge bg-danger ms-1"><?= $c['nao_lidas'] ?></span><?php endif; ?>
                            </div>
                            <small class="text-muted"><?= h(substr($c['ultima'],0,10)) ?></small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($conversas)): ?><p class="p-3 text-muted small">Sem conversas.</p><?php endif; ?>
                    </div>
                </div>
                <div class="col-md-8 d-flex flex-column p-0">
                    <div class="flex-grow-1 p-3" style="overflow-y:auto;max-height:520px;" id="chat-msgs">
                    <?php foreach ($msgs as $msg): ?>
                        <div class="mb-3 <?= $msg['remetente_id']===$uid?'text-end':'' ?>">
                            <div class="d-inline-block px-3 py-2 rounded-3" style="background:<?= $msg['remetente_id']===$uid?'#667eea;color:#fff':'#f1f3f4;color:#333' ?>;max-width:70%;">
                                <?= h($msg['corpo']) ?>
                            </div>
                            <div class="text-muted" style="font-size:.75rem;"><?= h(substr($msg['enviada_em'],11,5)) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($sel && empty($msgs)): ?><p class="text-muted text-center mt-4">Sem mensagens. Seja o primeiro a escrever.</p><?php endif; ?>
                    </div>
                    <?php if ($sel): ?>
                    <form method="POST" class="p-3 border-top d-flex gap-2">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="text" name="corpo" class="form-control" placeholder="Escrever mensagem..." autofocus>
                        <button type="submit" class="btn" style="background:#667eea;color:#fff;"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <script>
        document.getElementById('chat-msgs')?.scrollTo(0,99999);
        function filtrar(q){ document.querySelectorAll('#lista-conversas a').forEach(a=>{ a.style.display=a.textContent.toLowerCase().includes(q.toLowerCase())?'flex':'none'; }); }
        </script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
