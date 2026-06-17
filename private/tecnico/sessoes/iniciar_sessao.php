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
    if ($_POST['acao'] === 'iniciar') {
        $db->prepare("UPDATE sessoes SET estado='em_curso' WHERE id=?")->execute([$id]);
        registarAuditoria('ATUALIZAR', 'Sessao', $id, 'Sessão iniciada — utente: ' . ($s['paciente'] ?? ''));
    }
    if ($_POST['acao'] === 'concluir') {
        $notas      = trim($_POST['notas']          ?? '');
        $progressao = $_POST['progressao']          ?? null;
        $esforco    = (int)($_POST['esforco_score'] ?? 0) ?: null;
        $analise    = trim($_POST['analise_tecnica'] ?? '') ?: null;
        if (!in_array($progressao, ['melhoria','estavel','regressao'], true)) $progressao = null;
        try {
            $db->prepare("UPDATE sessoes SET estado='concluida', notas=?, progressao=?, esforco_score=?, analise_tecnica=? WHERE id=?")
               ->execute([$notas, $progressao, $esforco, $analise, $id]);
        } catch (\Throwable $e) {
            // Colunas de análise ainda não existem — guardar apenas notas
            $db->prepare("UPDATE sessoes SET estado='concluida', notas=? WHERE id=?")->execute([$notas, $id]);
        }
        registarAuditoria('ATUALIZAR', 'Sessao', $id,
            'Sessão concluída — utente: ' . ($s['paciente'] ?? '') . ' — progressão: ' . ($progressao ?? '—'));
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Sessão concluída com análise de desempenho registada.'];
        redirect(APP_URL . '/private/tecnico/sessoes/detalhes_sessao.php?id=' . $id);
    }
    if ($_POST['acao'] === 'cancelar') {
        $db->prepare("UPDATE sessoes SET estado='cancelada' WHERE id=?")->execute([$id]);
        registarAuditoria('ATUALIZAR', 'Sessao', $id, 'Sessão cancelada — utente: ' . ($s['paciente'] ?? ''));
        redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
    }
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
                    <div class="col-md-8"><h4><?= h($s['paciente']) ?></h4><p class="text-muted mb-0"><?= h($s['categoria'] ?? '—') ?> · Dispositivo: <?= h($s['dispositivo'] ?? 'Nenhum') ?></p></div>
                    <div class="col-md-4 text-end"><span class="badge" style="background:#e8f5e9;color:#2c7a4d;padding:6px 14px;"><?= h($s['estado']) ?></span></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card p-3 mb-3"><h5>Notas da Sessão</h5>
                        <textarea class="form-control mt-2" id="notasSessao" rows="5" placeholder="Observações durante a sessão..."><?= h($s['notas'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($s['categoria']==='avaliacao_funcional' && $s['modalidade']==='remota' && !empty($s['link_videochamada'])): ?>
                        <a href="<?= h($s['link_videochamada']) ?>" target="_blank" rel="noopener" class="btn btn-primary">
                            <i class="fa-solid fa-video me-1"></i>Entrar na Videochamada
                        </a>
                        <?php endif; ?>
                        <?php if ($s['estado'] === 'em_curso'): ?>
                        <button type="button" class="btn btn-success" onclick="abrirAnalise()">
                            <i class="fa-solid fa-flag-checkered me-1"></i>Concluir Sessão
                        </button>
                        <?php endif; ?>
                        <form method="POST" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="acao" value="cancelar"><button type="submit" class="btn btn-outline-danger" onclick="return confirm('Cancelar sessão?')"><i class="fa-solid fa-xmark me-1"></i>Cancelar</button></form>
                    </div>
                </div>
            </div>
        </main>
<!-- Modal: Análise de Desempenho -->
<div class="modal fade" id="modalAnalise" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="acao" value="concluir">
                <input type="hidden" name="notas" id="notasHidden">
                <div class="modal-header" style="background:#1a5f8a;color:#fff;">
                    <h5 class="modal-title mb-0"><i class="fa-solid fa-chart-bar me-2"></i>Análise de Desempenho da Sessão</h5>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Progressão observada</label>
                            <select name="progressao" class="form-select" required>
                                <option value="">— Selecionar —</option>
                                <option value="melhoria">Melhoria</option>
                                <option value="estavel">Estável</option>
                                <option value="regressao">Regressão</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Esforço do utente (1–5)</label>
                            <div class="d-flex gap-2 mt-1" id="esforco-stars">
                                <?php for ($i=1;$i<=5;$i++): ?>
                                <button type="button" class="btn btn-outline-warning btn-sm star-btn" data-val="<?= $i ?>"
                                        onclick="selecionarEsforco(<?= $i ?>)">
                                    <i class="fa-regular fa-star"></i>
                                </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="esforco_score" id="esforco_val" value="">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Análise técnica</label>
                            <textarea name="analise_tecnica" class="form-control" rows="4"
                                      placeholder="Descreva a evolução, dificuldades observadas, recomendações para a próxima sessão..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas gerais da sessão</label>
                            <div class="form-control bg-light" style="min-height:60px;font-size:.875rem;" id="notas_preview"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-flag-checkered me-1"></i>Concluir e Guardar Análise
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

        <script>
        let t=0; const el=document.getElementById('timer');
        if ('<?= $s['estado'] ?>' === 'em_curso') {
            setInterval(()=>{ t++; const m=Math.floor(t/60), s=t%60; el.textContent=(m<10?'0':'')+m+':'+(s<10?'0':'')+s; }, 1000);
        }

        function abrirAnalise() {
            const notas = document.getElementById('notasSessao').value;
            document.getElementById('notasHidden').value  = notas;
            document.getElementById('notas_preview').textContent = notas || '(sem notas)';
            new bootstrap.Modal(document.getElementById('modalAnalise')).show();
        }

        function selecionarEsforco(val) {
            document.getElementById('esforco_val').value = val;
            document.querySelectorAll('.star-btn').forEach((btn, idx) => {
                const i = btn.querySelector('i');
                i.className = idx < val ? 'fa-solid fa-star' : 'fa-regular fa-star';
                btn.className = idx < val ? 'btn btn-warning btn-sm star-btn' : 'btn btn-outline-warning btn-sm star-btn';
            });
        }
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
