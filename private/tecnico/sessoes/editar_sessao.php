<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Editar Sessão'; $pagina_ativa = 'sessoes';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
$stmt = $db->prepare("SELECT s.*, u.nome AS paciente, j.nome AS jogo_nome FROM sessoes s JOIN utentes ut ON ut.id=s.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id LEFT JOIN jogos j ON j.id=s.jogo_id WHERE s.id=?");
$stmt->execute([$id]); $s = $stmt->fetch();
if (!$s) redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $categoria = trim($_POST['categoria'] ?? 'jogo');
    $jogo_id   = (int)($_POST['jogo_id'] ?? 0) ?: null;
    $duracao   = (int)($_POST['duracao'] ?? 45);
    $notas     = trim($_POST['notas'] ?? '');
    $data_hora = trim($_POST['data_hora'] ?? '');
    $objetivo  = trim($_POST['objetivo_sessao'] ?? '');
    if (empty($erros)) {
        $db->prepare('UPDATE sessoes SET categoria=?,jogo_id=?,duracao_min=?,notas=?,data_hora=?,objetivo_sessao=? WHERE id=?')
           ->execute([$categoria,$jogo_id,$duracao,$notas,$data_hora,$objetivo,$id]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Sessão atualizada.'];
        redirect(APP_URL . '/private/tecnico/sessoes/lista_sessoes.php');
    }
}
$jogos = $db->query("SELECT id, nome, nivel FROM jogos WHERE ativo=1 ORDER BY FIELD(nivel,'minimo','medio','maximo'), nome")->fetchAll();
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="lista_sessoes.php">Sessões</a></li><li class="breadcrumb-item active">Editar #<?= $id ?></li></ol></nav>
            <h1 class="mb-4">Editar Sessão #<?= $id ?></h1>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3"><label class="form-label">Paciente</label><input type="text" class="form-control" value="<?= h($s['paciente']) ?>" disabled></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Categoria</label>
                            <select name="categoria" class="form-select" id="editCategoria">
                                <?php foreach(['jogo'=>'Jogo','avaliacao_funcional'=>'Avaliação Funcional'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($s['categoria']===$v)?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="editJogoRow">
                            <label class="form-label fw-semibold">Jogo</label>
                            <select name="jogo_id" class="form-select">
                                <option value="">— Selecionar —</option>
                                <?php $nivel_labels = ['minimo'=>'Mínimo','medio'=>'Médio','maximo'=>'Máximo']; foreach($jogos as $j): ?>
                                    <option value="<?= $j['id'] ?>" <?= ($s['jogo_id']==$j['id'])?'selected':'' ?>><?= h($j['nome']) ?> — <?= $nivel_labels[$j['nivel']] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3"><label class="form-label fw-semibold">Duração (min)</label><input type="number" name="duracao" class="form-control" value="<?= h($s['duracao_min'] ?? 45) ?>"></div>
                        <div class="col-md-9 mb-3"><label class="form-label fw-semibold">Data/Hora</label><input type="datetime-local" name="data_hora" class="form-control" value="<?= str_replace(' ','T', h(substr($s['data_hora'],0,16))) ?>"></div>
                        <div class="col-md-12 mb-3"><label class="form-label fw-semibold">Objetivo</label><input type="text" name="objetivo_sessao" class="form-control" value="<?= h($s['objetivo_sessao'] ?? '') ?>"></div>
                        <div class="col-md-12 mb-4"><label class="form-label fw-semibold">Notas</label><textarea name="notas" class="form-control" rows="2"><?= h($s['notas'] ?? '') ?></textarea></div>
                    </div>
                    <div class="d-flex gap-2"><button type="submit" class="btn" style="background:#1a5f8a;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button><a href="lista_sessoes.php" class="btn btn-outline-secondary">Cancelar</a></div>
                </form>
            </div>
        </main>
        <script>
        function toggleJogoEdit() {
            const cat = document.getElementById('editCategoria').value;
            document.getElementById('editJogoRow').style.display = cat === 'jogo' ? 'block' : 'none';
        }
        document.getElementById('editCategoria').addEventListener('change', toggleJogoEdit);
        toggleJogoEdit();
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
