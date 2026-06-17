<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Equipa de Tratamento'; $pagina_ativa = 'mensagens_equipa';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];

// Resolver médico e técnico atribuídos ao utente
$stmt = $db->prepare("SELECT ut.medico_id, ut.tecnico_id FROM utentes ut WHERE ut.utilizador_id=?");
$stmt->execute([$uid]); $utrec = $stmt->fetch();

$destinatarios = [];
if ($utrec) {
    if ($utrec['medico_id']) {
        $s = $db->prepare("SELECT u.id, u.nome, 'medico' AS perfil FROM profissionais p JOIN utilizadores u ON u.id=p.utilizador_id WHERE p.id=? AND u.ativo=1");
        $s->execute([$utrec['medico_id']]); $d = $s->fetch();
        if ($d) $destinatarios[] = $d;
    }
    if ($utrec['tecnico_id']) {
        $s = $db->prepare("SELECT u.id, u.nome, 'tecnico' AS perfil FROM profissionais p JOIN utilizadores u ON u.id=p.utilizador_id WHERE p.id=? AND u.ativo=1");
        $s->execute([$utrec['tecnico_id']]); $d = $s->fetch();
        if ($d) $destinatarios[] = $d;
    }
}

$dest_ids = array_column($destinatarios, 'id');

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $dest  = (int)($_POST['destinatario_id'] ?? 0);
    $corpo = trim($_POST['corpo'] ?? '');
    if ($dest && $corpo !== '' && in_array($dest, $dest_ids, true)) {
        $db->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, corpo) VALUES (?,?,?)")
           ->execute([$uid, $dest, $corpo]);
        // Notificar o destinatário
        $dest_perfil = '';
        foreach ($destinatarios as $dv) { if ($dv['id'] == $dest) { $dest_perfil = $dv['perfil']; break; } }
        $url_notif = match($dest_perfil) {
            'medico'  => APP_URL . '/private/medico/mensagens/conversas.php?com=' . $uid,
            'tecnico' => APP_URL . '/private/tecnico/mensagens/conversas.php?com=' . $uid,
            default   => '',
        };
        notificar($dest, 'mensagem',
            'Nova mensagem de ' . h($_SESSION['nome'] ?? 'Utente'),
            mb_substr($corpo, 0, 80),
            $url_notif
        );
        redirect(APP_URL . '/private/utente/mensagens_equipa.php?com=' . $dest);
    }
}

// Selecionar conversa ativa
$sel = (int)($_GET['com'] ?? ($dest_ids[0] ?? 0));
if ($sel && !in_array($sel, $dest_ids, true)) $sel = 0;

$msgs     = [];
$sel_nome = '';
$sel_perfil = '';
if ($sel) {
    $stmt = $db->prepare("
        SELECT m.*, u.nome AS remetente FROM mensagens m
        JOIN utilizadores u ON u.id = m.remetente_id
        WHERE (m.remetente_id=? AND m.destinatario_id=?)
           OR (m.remetente_id=? AND m.destinatario_id=?)
        ORDER BY m.enviada_em
    ");
    $stmt->execute([$uid,$sel,$sel,$uid]);
    $msgs = $stmt->fetchAll();
    $db->prepare("UPDATE mensagens SET lida=1 WHERE destinatario_id=? AND remetente_id=?")->execute([$uid,$sel]);
    foreach ($destinatarios as $dv) {
        if ($dv['id'] == $sel) { $sel_nome = $dv['nome']; $sel_perfil = $dv['perfil']; break; }
    }
}

// Contagem não lidas por destinatário
foreach ($destinatarios as &$dv) {
    $s = $db->prepare("SELECT COUNT(*) FROM mensagens WHERE destinatario_id=? AND remetente_id=? AND lida=0");
    $s->execute([$uid, $dv['id']]);
    $dv['nao_lidas'] = (int)$s->fetchColumn();
}
unset($dv);

$perfil_cor = ['medico'=>'#8B0000','tecnico'=>'#1a5f8a'];
$perfil_label = ['medico'=>'Médico','tecnico'=>'Técnico'];

require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="mb-0"><i class="fa-regular fa-comments me-2"></i>Equipa de Tratamento</h1>
            </div>

            <?php if (empty($destinatarios)): ?>
            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info me-2"></i>
                Ainda não tem médico ou técnico atribuído. Contacte a instituição.
            </div>
            <?php else: ?>

            <div class="card p-0 overflow-hidden" style="height:600px;">
                <div class="row g-0 h-100">
                    <!-- Lista de membros da equipa -->
                    <div class="col-md-4 border-end h-100 overflow-auto">
                        <div class="px-3 py-2 border-bottom bg-light">
                            <small class="text-muted fw-semibold">A minha equipa</small>
                        </div>
                        <?php foreach ($destinatarios as $dv): ?>
                        <a href="?com=<?= $dv['id'] ?>"
                           class="d-flex align-items-center gap-3 p-3 text-decoration-none border-bottom <?= $dv['id']==$sel ? 'bg-light' : '' ?>">
                            <div style="width:44px;height:44px;border-radius:50%;background:<?= $perfil_cor[$dv['perfil']] ?? '#667eea' ?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa-solid <?= $dv['perfil']==='medico'?'fa-user-doctor':'fa-user-nurse' ?>"
                                   style="color:<?= $perfil_cor[$dv['perfil']] ?? '#667eea' ?>;"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-truncate" style="color:#212529;"><?= h($dv['nome']) ?></strong>
                                    <?php if ($dv['nao_lidas'] > 0): ?>
                                    <span class="badge bg-danger ms-1"><?= $dv['nao_lidas'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="small">
                                    <span class="badge" style="background:<?= $perfil_cor[$dv['perfil']] ?? '#667eea' ?>;font-size:.7rem;">
                                        <?= $perfil_label[$dv['perfil']] ?? $dv['perfil'] ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Área de mensagens -->
                    <div class="col-md-8 d-flex flex-column h-100">
                        <?php if ($sel): ?>
                        <div class="p-3 border-bottom bg-light d-flex align-items-center gap-2">
                            <i class="fa-solid <?= $sel_perfil==='medico'?'fa-user-doctor':'fa-user-nurse' ?>"
                               style="color:<?= $perfil_cor[$sel_perfil] ?? '#667eea' ?>;"></i>
                            <span class="fw-semibold"><?= h($sel_nome) ?></span>
                            <span class="badge ms-1" style="background:<?= $perfil_cor[$sel_perfil] ?? '#667eea' ?>;font-size:.7rem;">
                                <?= $perfil_label[$sel_perfil] ?? '' ?>
                            </span>
                        </div>
                        <div class="flex-grow-1 p-3 overflow-auto" id="msg-area">
                            <?php if (empty($msgs)): ?>
                            <p class="text-muted small text-center mt-4">Ainda sem mensagens. Diga olá!</p>
                            <?php endif; ?>
                            <?php foreach ($msgs as $msg): ?>
                            <div class="mb-3 <?= $msg['remetente_id']==$uid ? 'text-end' : '' ?>">
                                <div class="d-inline-block px-3 py-2 rounded-3"
                                     style="background:<?= $msg['remetente_id']==$uid ? '#667eea;color:#fff' : '#f1f3f4;color:#212529' ?>;max-width:70%;word-break:break-word;text-align:left;">
                                    <?= h($msg['corpo']) ?>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;"><?= h(date('H:i', strtotime($msg['enviada_em']))) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" class="p-3 border-top d-flex gap-2">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="destinatario_id" value="<?= $sel ?>">
                            <input type="text" name="corpo" class="form-control" placeholder="Escrever mensagem..." autofocus required>
                            <button type="submit" class="btn" style="background:#667eea;color:#fff;">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                            <div class="text-center">
                                <i class="fa-regular fa-comments fa-3x mb-3 d-block" style="color:#667eea;opacity:.4;"></i>
                                Selecione um membro da equipa para enviar uma mensagem.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
<script>
const msgArea = document.getElementById('msg-area');
if (msgArea) msgArea.scrollTop = msgArea.scrollHeight;
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
