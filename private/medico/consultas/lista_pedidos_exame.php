<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');
$db = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();

$utente_id = (int)($_GET['utente_id'] ?? 0);
$paciente_nome = '';
if ($utente_id) {
    $s = $db->prepare('SELECT u.nome FROM utilizadores u JOIN utentes ut ON ut.utilizador_id=u.id WHERE ut.id=?');
    $s->execute([$utente_id]); $paciente_nome = $s->fetchColumn() ?: '';
}

// Handle resultado update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exame_id'])) {
    $exame_id  = (int)$_POST['exame_id'];
    $resultado = trim($_POST['resultado'] ?? '');
    $estado    = $_POST['estado']         ?? 'realizado';
    $db->prepare('UPDATE pedidos_exame pe JOIN consultas c ON c.id=pe.consulta_id SET pe.resultado=?, pe.estado=?, pe.data_realizacao=CURDATE() WHERE pe.id=? AND c.medico_id=?')
       ->execute([$resultado, $estado, $exame_id, $pid]);
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Exame atualizado.'];
    redirect(APP_URL . '/private/medico/consultas/lista_pedidos_exame.php?utente_id='.$utente_id);
}

$exames = [];
if ($pid) {
    $where = $utente_id ? 'c.medico_id=? AND c.utente_id=?' : 'c.medico_id=?';
    $params = $utente_id ? [$pid, $utente_id] : [$pid];
    $s = $db->prepare("SELECT pe.*, u.nome AS paciente FROM pedidos_exame pe JOIN consultas c ON c.id=pe.consulta_id JOIN utentes ut ON ut.id=c.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE $where ORDER BY pe.data_pedido DESC LIMIT 100");
    $s->execute($params); $exames = $s->fetchAll();
}

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$pagina_titulo = 'Pedidos de Exame'; $pagina_ativa = 'exames';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Pedidos de Exame<?= $paciente_nome ? ' — '.h($paciente_nome) : '' ?></h1>
            </div>
            <?php if($flash):?><div class="alert alert-<?=h($flash['tipo'])?> py-2"><?=h($flash['mensagem'])?></div><?php endif;?>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Paciente</th><th>Exame</th><th>Categoria</th><th>Urgência</th><th>Data Pedido</th><th>Estado</th><th>Resultado</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php if(empty($exames)):?><tr><td colspan="8" class="text-center text-muted py-4">Sem pedidos.</td></tr>
                    <?php else: foreach($exames as $e): ?>
                        <tr>
                            <td><?=h($e['paciente'])?></td>
                            <td><strong><?=h($e['tipo_exame'])?></strong></td>
                            <td><?=h(ucfirst($e['categoria']))?></td>
                            <td><span class="badge bg-<?=$e['urgencia']==='urgente'?'danger':'secondary'?>"><?=h(ucfirst($e['urgencia']))?></span></td>
                            <td><?=h($e['data_pedido'])?></td>
                            <td><span class="badge bg-<?=['pendente'=>'warning text-dark','realizado'=>'success','cancelado'=>'danger'][$e['estado']]??'secondary'?>"><?=h(ucfirst($e['estado']))?></span></td>
                            <td><small><?=h(substr($e['resultado']??'—',0,40))?></small></td>
                            <td>
                                <?php if($e['estado']==='pendente'):?>
                                <button class="btn btn-xs btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalResultado"
                                        data-id="<?=$e['id']?>" data-exame="<?=h($e['tipo_exame'])?>">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <?php endif;?>
                            </td>
                        </tr>
                    <?php endforeach; endif;?>
                    </tbody>
                </table>
            </div></div>
        </main>

        <div class="modal fade" id="modalResultado" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
                <form method="POST">
                    <div class="modal-header" style="background:#8B0000;"><h5 class="modal-title text-white">Registar Resultado</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="exame_id" id="modalExameId">
                        <p class="text-muted" id="modalExameTipo"></p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <select name="estado" class="form-select"><option value="realizado">Realizado</option><option value="cancelado">Cancelado</option></select>
                        </div>
                        <div class="mb-3"><label class="form-label fw-semibold">Resultado</label><textarea name="resultado" class="form-control" rows="3"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-sm" style="background:#8B0000;color:#fff;">Guardar</button></div>
                </form>
            </div></div>
        </div>
        <script>
        document.getElementById('modalResultado').addEventListener('show.bs.modal', function(e){
            document.getElementById('modalExameId').value = e.relatedTarget.dataset.id;
            document.getElementById('modalExameTipo').textContent = e.relatedTarget.dataset.exame;
        });
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
