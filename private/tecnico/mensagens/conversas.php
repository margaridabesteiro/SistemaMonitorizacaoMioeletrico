<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Mensagens'; $pagina_ativa = 'mensagens';
requirePerfil('tecnico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $dest  = (int)($_POST['destinatario_id'] ?? 0);
    $corpo = trim($_POST['corpo'] ?? '');
    if ($dest && $corpo !== '') {
        $db->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, corpo) VALUES (?,?,?)")
           ->execute([$uid, $dest, $corpo]);
        redirect(APP_URL . '/private/tecnico/mensagens/conversas.php?com=' . $dest);
    }
}

// Pode comunicar com médicos e admins
$destinatarios_validos = $db->query("
    SELECT id, nome, perfil FROM utilizadores
    WHERE perfil IN ('medico','admin','utente') AND ativo=1 AND id != {$uid}
    ORDER BY perfil, nome
")->fetchAll();

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

require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="mb-0">Mensagens</h1>
                <div class="dropdown">
                    <button class="btn btn-sm" style="background:#1a5f8a;color:#fff;" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-plus me-1"></i>Nova Conversa
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="max-height:300px;overflow-y:auto;">
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

            <div class="card p-0 overflow-hidden" style="height:620px;">
                <div style="display:grid;grid-template-columns:300px 1fr;height:100%;overflow:hidden;">
                    <!-- Lista de conversas -->
                    <div style="border-right:1px solid #dee2e6;overflow-y:auto;display:flex;flex-direction:column;">
                        <div style="padding:12px 16px;background:#f8f9fa;border-bottom:1px solid #dee2e6;font-size:.8rem;font-weight:600;color:#6c757d;text-transform:uppercase;letter-spacing:.04em;">
                            Conversas
                        </div>
                        <?php foreach ($conversas as $c): ?>
                        <a href="?com=<?= $c['outro_id'] ?>"
                           style="display:flex;align-items:center;gap:12px;padding:14px 16px;text-decoration:none;border-bottom:1px solid #f0f0f0;background:<?= $c['outro_id']==$sel?'#e8f4fd':'' ?>;transition:background .15s;"
                           onmouseover="if(<?= $c['outro_id']!=$sel?'true':'false' ?>)this.style.background='#f0f8ff'"
                           onmouseout="if(<?= $c['outro_id']!=$sel?'true':'false' ?>)this.style.background=''">
                            <div style="width:40px;height:40px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa-regular fa-user" style="color:#1a5f8a;font-size:.9rem;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <span style="font-weight:600;font-size:.88rem;color:#212529;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($c['outro_nome']) ?></span>
                                    <?php if ($c['nao_lidas'] > 0): ?>
                                        <span class="badge bg-danger ms-1" style="font-size:.68rem;"><?= $c['nao_lidas'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:.75rem;color:#adb5bd;">
                                    <span class="badge bg-secondary" style="font-size:.62rem;"><?= h(ucfirst($c['outro_perfil'])) ?></span>
                                    <?= h(date('d/m/Y', strtotime($c['ultima']))) ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php if (empty($conversas)): ?>
                            <p style="padding:16px;font-size:.82rem;color:#adb5bd;">Sem conversas ainda. Use "Nova Conversa".</p>
                        <?php endif; ?>
                    </div>

                    <!-- Área de mensagens -->
                    <div style="display:flex;flex-direction:column;overflow:hidden;">
                        <?php if ($sel): ?>
                        <div style="padding:14px 18px;border-bottom:1px solid #dee2e6;background:#f8f9fa;font-weight:600;display:flex;align-items:center;gap:8px;">
                            <div style="width:36px;height:36px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;">
                                <i class="fa-regular fa-user" style="color:#1a5f8a;font-size:.85rem;"></i>
                            </div>
                            <span><?= h($sel_nome) ?></span>
                        </div>
                        <div style="flex:1;overflow-y:auto;padding:20px;" id="msg-area">
                            <?php if (empty($msgs)): ?>
                            <p style="text-align:center;color:#adb5bd;font-size:.85rem;margin-top:40px;">Ainda sem mensagens. Diga olá!</p>
                            <?php endif; ?>
                            <?php foreach ($msgs as $msg):
                                $proprio = $msg['remetente_id'] == $uid;
                            ?>
                            <div style="margin-bottom:14px;display:flex;flex-direction:column;align-items:<?= $proprio?'flex-end':'flex-start' ?>;">
                                <div style="max-width:70%;padding:10px 14px;border-radius:<?= $proprio?'18px 18px 4px 18px':'18px 18px 18px 4px' ?>;
                                     background:<?= $proprio?'#1a5f8a':'#fff' ?>;
                                     color:<?= $proprio?'#fff':'#212529' ?>;
                                     <?= $proprio?'':'border:1px solid #dee2e6;' ?>
                                     word-break:break-word;font-size:.9rem;line-height:1.5;">
                                    <?= nl2br(h($msg['corpo'])) ?>
                                </div>
                                <span style="font-size:.68rem;color:#adb5bd;margin-top:3px;"><?= date('H:i', strtotime($msg['enviada_em'])) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" style="padding:14px 16px;border-top:1px solid #dee2e6;display:flex;gap:8px;background:#fff;">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="destinatario_id" value="<?= $sel ?>">
                            <input type="text" name="corpo" class="form-control" placeholder="Escrever mensagem..." autofocus required
                                   style="border-radius:24px;padding:8px 16px;">
                            <button type="submit" class="btn" style="background:#1a5f8a;color:#fff;border-radius:50%;width:42px;height:42px;flex-shrink:0;padding:0;">
                                <i class="fa-solid fa-paper-plane" style="font-size:.85rem;"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <div style="flex:1;display:flex;align-items:center;justify-content:center;color:#adb5bd;">
                            <div style="text-align:center;">
                                <i class="fa-regular fa-comment-dots" style="font-size:3rem;display:block;margin-bottom:12px;opacity:.4;"></i>
                                Selecione uma conversa ou inicie uma nova.
                            </div>
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
