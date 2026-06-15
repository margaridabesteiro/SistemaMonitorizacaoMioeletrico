<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Mensagens'; $pagina_ativa = 'mensagens';
requirePerfil('medico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dest  = (int)($_POST['destinatario_id'] ?? 0);
    $corpo = trim($_POST['corpo'] ?? '');
    if ($dest && $corpo !== '') {
        $db->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, corpo) VALUES (?,?,?)")
           ->execute([$uid, $dest, $corpo]);
        redirect(APP_URL . '/private/medico/mensagens/conversas.php?com=' . $dest);
    }
}

// Lista de utilizadores com quem pode comunicar (técnicos + admins)
$destinatarios_validos = $db->query("
    SELECT id, nome, perfil FROM utilizadores
    WHERE perfil IN ('tecnico','admin') AND ativo=1 AND id != {$uid}
    ORDER BY perfil, nome
")->fetchAll();

// Conversas existentes
$conversas = $db->prepare("
    SELECT DISTINCT
        CASE WHEN m.remetente_id=? THEN m.destinatario_id ELSE m.remetente_id END AS outro_id,
        u.nome AS outro_nome, u.perfil AS outro_perfil,
        MAX(m.enviada_em) AS ultima,
        SUM(m.lida=0 AND m.destinatario_id=?) AS nao_lidas
    FROM mensagens m
    JOIN utilizadores u ON u.id = CASE WHEN m.remetente_id=? THEN m.destinatario_id ELSE m.remetente_id END
    WHERE m.remetente_id=? OR m.destinatario_id=?
    GROUP BY outro_id ORDER BY ultima DESC
");
$conversas->execute([$uid,$uid,$uid,$uid,$uid]);
$conversas = $conversas->fetchAll();

$sel = (int)($_GET['com'] ?? ($conversas[0]['outro_id'] ?? 0));
$msgs = [];
if ($sel) {
    $stmt = $db->prepare("
        SELECT m.*, u.nome AS remetente FROM mensagens m
        JOIN utilizadores u ON u.id=m.remetente_id
        WHERE (m.remetente_id=? AND m.destinatario_id=?) OR (m.remetente_id=? AND m.destinatario_id=?)
        ORDER BY m.enviada_em
    ");
    $stmt->execute([$uid,$sel,$sel,$uid]);
    $msgs = $stmt->fetchAll();
    $db->prepare("UPDATE mensagens SET lida=1 WHERE destinatario_id=? AND remetente_id=?")->execute([$uid,$sel]);
}

$sel_nome = '';
foreach ($conversas as $c) { if ($c['outro_id'] == $sel) { $sel_nome = $c['outro_nome']; break; } }
if (!$sel_nome) {
    foreach ($destinatarios_validos as $d) { if ($d['id'] == $sel) { $sel_nome = $d['nome']; break; } }
}

require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="mb-0">Mensagens</h1>
                <div class="dropdown">
                    <button class="btn btn-sm" style="background:#8B0000;color:#fff;" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-plus me-1"></i>Nova Conversa
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($destinatarios_validos as $d): ?>
                            <li><a class="dropdown-item" href="?com=<?= $d['id'] ?>">
                                <span class="badge bg-secondary me-1"><?= h(ucfirst($d['perfil'])) ?></span>
                                <?= h($d['nome']) ?>
                            </a></li>
                        <?php endforeach; ?>
                        <?php if (empty($destinatarios_validos)): ?>
                            <li><span class="dropdown-item text-muted">Sem utilizadores disponíveis</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="card p-0 overflow-hidden" style="height:600px;">
                <div class="row g-0 h-100">
                    <!-- Lista de conversas -->
                    <div class="col-md-4 border-end h-100 overflow-auto">
                        <?php foreach ($conversas as $c): ?>
                        <a href="?com=<?= $c['outro_id'] ?>" class="d-flex align-items-center gap-3 p-3 text-decoration-none <?= $c['outro_id']==$sel?'bg-light':'' ?> border-bottom">
                            <div style="width:40px;height:40px;border-radius:50%;background:#f3e5e5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa-regular fa-user" style="color:#8B0000;"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-truncate"><?= h($c['outro_nome']) ?></strong>
                                    <?php if ($c['nao_lidas'] > 0): ?>
                                        <span class="badge bg-danger ms-1"><?= $c['nao_lidas'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small"><?= h(date('d/m/Y', strtotime($c['ultima']))) ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php if (empty($conversas)): ?>
                            <p class="p-3 text-muted small">Sem conversas. Inicie uma nova conversa.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Área de mensagens -->
                    <div class="col-md-8 d-flex flex-column h-100">
                        <?php if ($sel): ?>
                        <div class="p-3 border-bottom fw-semibold bg-light">
                            <i class="fa-regular fa-user me-2" style="color:#8B0000;"></i><?= h($sel_nome) ?>
                        </div>
                        <div class="flex-grow-1 p-3 overflow-auto" id="msg-area">
                            <?php foreach ($msgs as $msg): ?>
                            <div class="mb-3 <?= $msg['remetente_id']==$uid?'text-end':'' ?>">
                                <div class="d-inline-block px-3 py-2 rounded-3"
                                     style="background:<?= $msg['remetente_id']==$uid?'#8B0000;color:#fff':'#f1f3f4' ?>;max-width:70%;word-break:break-word;">
                                    <?= h($msg['corpo']) ?>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;"><?= h(date('H:i', strtotime($msg['enviada_em']))) ?></div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($msgs)): ?><p class="text-muted small">Sem mensagens ainda. Diga olá!</p><?php endif; ?>
                        </div>
                        <form method="POST" class="p-3 border-top d-flex gap-2">
                            <input type="hidden" name="destinatario_id" value="<?= $sel ?>">
                            <input type="text" name="corpo" class="form-control" placeholder="Escrever mensagem..." autofocus required>
                            <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-paper-plane"></i></button>
                        </form>
                        <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                            <div class="text-center"><i class="fa-regular fa-comment-dots fa-3x mb-3 d-block"></i>Selecione uma conversa ou inicie uma nova.</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
<script>
const msgArea = document.getElementById('msg-area');
if (msgArea) msgArea.scrollTop = msgArea.scrollHeight;
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
