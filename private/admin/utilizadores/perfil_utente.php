<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Perfil do Utente'; $pagina_ativa = 'utilizadores';
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
$db = getDB();
$stmt = $db->prepare("SELECT u.id, u.nome, u.email, u.ativo, ut.*, p.nome AS medico_nome, p2.nome AS tecnico_nome
    FROM utilizadores u
    JOIN utentes ut ON ut.utilizador_id=u.id
    LEFT JOIN profissionais prof ON prof.id=ut.medico_id LEFT JOIN utilizadores p ON p.id=prof.utilizador_id
    LEFT JOIN profissionais prof2 ON prof2.id=ut.tecnico_id LEFT JOIN utilizadores p2 ON p2.id=prof2.utilizador_id
    WHERE u.id=? AND u.perfil='utente'");
$stmt->execute([$id]); $d = $stmt->fetch();
if (!$d) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cobertura = $_POST['cobertura_saude'] ?? 'SNS';
    $fase      = $_POST['fase_tratamento'] ?? 'avaliacao';
    $cat       = $_POST['categoria_clinica'] ?: null;
    $membro    = $_POST['membro_afetado']    ?: null;
    $inicio    = $_POST['data_inicio_tratamento'] ?: null;
    $alta      = $_POST['data_alta']         ?: null;
    $db->prepare('UPDATE utentes SET cobertura_saude=?,fase_tratamento=?,categoria_clinica=?,membro_afetado=?,data_inicio_tratamento=?,data_alta=? WHERE utilizador_id=?')
       ->execute([$cobertura,$fase,$cat,$membro,$inicio,$alta,$id]);
    $flash = ['tipo'=>'success','mensagem'=>'Perfil clínico atualizado.'];
    $stmt->execute([$id]); $d = $stmt->fetch();
}

$medicos  = $db->query("SELECT p.id, u.nome FROM profissionais p JOIN utilizadores u ON u.id=p.utilizador_id WHERE u.perfil='medico' ORDER BY u.nome")->fetchAll();
$tecnicos = $db->query("SELECT p.id, u.nome FROM profissionais p JOIN utilizadores u ON u.id=p.utilizador_id WHERE u.perfil='tecnico' ORDER BY u.nome")->fetchAll();

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$fase_labels = ['avaliacao'=>'Avaliação','ativo'=>'Ativo','manutencao'=>'Manutenção','alta'=>'Alta'];
$cat_labels  = ['avc'=>'AVC','amputacao_ms'=>'Amputação MS','amputacao_mi'=>'Amputação MI','lesao_medular'=>'Lesão Medular','lesao_nervosa_periferica'=>'Lesão Nervosa Periférica','paralisia_cerebral'=>'Paralisia Cerebral','outro'=>'Outro'];
$membro_labels=['mao_esquerda'=>'Mão esquerda','mao_direita'=>'Mão direita','ambas'=>'Ambas','perna_esquerda'=>'Perna esquerda','perna_direita'=>'Perna direita','outro'=>'Outro'];
?>
        <main class="content">
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="lista_utilizadores.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
                <h1 class="mb-0">Perfil Clínico — <?=h($d['nome'])?></h1>
            </div>
            <?php if($flash):?><div class="alert alert-<?=h($flash['tipo'])?> py-2"><?=h($flash['mensagem'])?></div><?php endif;?>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card p-3">
                        <h6 class="fw-bold mb-3">Dados de Conta</h6>
                        <p><strong>Email:</strong> <?=h($d['email'])?></p>
                        <p><strong>Estado:</strong> <?=$d['ativo']?'<span class="badge bg-success">Ativo</span>':'<span class="badge bg-danger">Inativo</span>'?></p>
                        <p><strong>Diagnóstico:</strong><br><small class="text-muted"><?=h($d['diagnostico']??'—')?></small></p>
                        <p class="mb-0"><strong>Médico:</strong> <?=h($d['medico_nome']??'—')?></p>
                        <p class="mb-0"><strong>Técnico:</strong> <?=h($d['tecnico_nome']??'—')?></p>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-4">
                        <h6 class="fw-bold mb-3">Dados Clínicos (Admin)</h6>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Cobertura de Saúde</label>
                                    <select name="cobertura_saude" class="form-select">
                                        <?php foreach(['SNS','Particular','Seguro'] as $c): ?>
                                            <option value="<?=$c?>" <?=($d['cobertura_saude']??'SNS')===$c?'selected':''?>><?=$c?></option>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Fase de Tratamento</label>
                                    <select name="fase_tratamento" class="form-select">
                                        <?php foreach($fase_labels as $v=>$l): ?>
                                            <option value="<?=$v?>" <?=($d['fase_tratamento']??'avaliacao')===$v?'selected':''?>><?=$l?></option>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Categoria Clínica</label>
                                    <select name="categoria_clinica" class="form-select">
                                        <option value="">— Não definida —</option>
                                        <?php foreach($cat_labels as $v=>$l): ?>
                                            <option value="<?=$v?>" <?=($d['categoria_clinica']??'')===$v?'selected':''?>><?=$l?></option>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Membro Afetado</label>
                                    <select name="membro_afetado" class="form-select">
                                        <option value="">— Não definido —</option>
                                        <?php foreach($membro_labels as $v=>$l): ?>
                                            <option value="<?=$v?>" <?=($d['membro_afetado']??'')===$v?'selected':''?>><?=$l?></option>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Data Início Tratamento</label>
                                    <input type="date" name="data_inicio_tratamento" class="form-control" value="<?=h($d['data_inicio_tratamento']??'')?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Data de Alta <span class="text-muted small">(se aplicável)</span></label>
                                    <input type="date" name="data_alta" class="form-control" value="<?=h($d['data_alta']??'')?>">
                                </div>
                            </div>
                            <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
