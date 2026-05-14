<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Iniciar Sessão'; $pagina_ativa = 'sessoes';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
$stmt = $db->prepare("SELECT s.*, u.nome AS paciente, d.codigo AS dispositivo FROM sessoes s JOIN utentes ut ON ut.id=s.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id LEFT JOIN dispositivos d ON d.id=s.dispositivo_id WHERE s.id=?");
$stmt->execute([$id]); $s = $stmt->fetch();
if (!$s || $s['estado'] !== 'agendada') redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'iniciar') { $db->prepare("UPDATE sessoes SET estado='em_curso' WHERE id=?")->execute([$id]); }
    if ($_POST['acao'] === 'concluir') {
        $notas = trim($_POST['notas'] ?? '');
        $db->prepare("UPDATE sessoes SET estado='concluida', notas=? WHERE id=?")->execute([$notas,$id]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Sessão concluída.']; redirect(APP_URL . '/private/tecnico/sessoes/detalhes_sessao.php?id=' . $id);
    }
    if ($_POST['acao'] === 'cancelar') { $db->prepare("UPDATE sessoes SET estado='cancelada' WHERE id=?")->execute([$id]); redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php'); }
    // Re-fetch after state change
    $stmt->execute([$id]); $s = $stmt->fetch();
}
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Sessão em Progresso</h1>
                <span style="font-size:1.4rem;font-weight:bold;color:#1a5f8a;"><i class="fa-regular fa-clock me-1"></i><span id="timer">00:00</span></span>
            </div>
            <div class="card p-3 mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8"><h4><?= h($s['paciente']) ?></h4><p class="text-muted mb-0"><?= h($s['tipo'] ?? '—') ?> · Dispositivo: <?= h($s['dispositivo'] ?? 'Nenhum') ?></p></div>
                    <div class="col-md-4 text-end"><span class="badge" style="background:#e8f5e9;color:#2c7a4d;padding:6px 14px;"><?= h($s['estado']) ?></span></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card p-3 mb-3"><h5>Notas da Sessão</h5>
                        <textarea class="form-control mt-2" id="notasSessao" rows="5" placeholder="Observações durante a sessão..."><?= h($s['notas'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($s['estado'] === 'agendada'): ?>
                        <form method="POST" class="d-inline"><input type="hidden" name="acao" value="iniciar"><button type="submit" class="btn btn-success"><i class="fa-solid fa-play me-1"></i>Iniciar</button></form>
                        <?php endif; ?>
                        <?php if ($s['estado'] === 'em_curso'): ?>
                        <form method="POST" class="d-inline"><input type="hidden" name="acao" value="concluir"><input type="hidden" name="notas" id="notasHidden"><button type="submit" class="btn btn-primary" onclick="document.getElementById('notasHidden').value=document.getElementById('notasSessao').value"><i class="fa-solid fa-flag-checkered me-1"></i>Concluir</button></form>
                        <?php endif; ?>
                        <form method="POST" class="d-inline"><input type="hidden" name="acao" value="cancelar"><button type="submit" class="btn btn-outline-danger" onclick="return confirm('Cancelar sessão?')"><i class="fa-solid fa-xmark me-1"></i>Cancelar</button></form>
                    </div>
                </div>
            </div>
        </main>
        <script>
        let t=0; const el=document.getElementById('timer');
        if ('<?= $s['estado'] ?>' === 'em_curso') {
            setInterval(()=>{ t++; const m=Math.floor(t/60), s=t%60; el.textContent=(m<10?'0':'')+m+':'+(s<10?'0':'')+s; }, 1000);
        }
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
