<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Detalhes do Programa'; $pagina_ativa = 'prescricoes';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
$stmt = $db->prepare("SELECT p.*, u.nome AS paciente, um.nome AS medico FROM programas_tratamento p JOIN utentes ut ON ut.id=p.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id JOIN profissionais pm ON pm.id=p.medico_id JOIN utilizadores um ON um.id=pm.utilizador_id WHERE p.id=?");
$stmt->execute([$id]); $p = $stmt->fetch();
if (!$p) redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');

$membro_labels = ['mao_esquerda'=>'Mão esquerda','mao_direita'=>'Mão direita','ambas'=>'Ambas as mãos','perna_esquerda'=>'Perna esquerda','perna_direita'=>'Perna direita','outro'=>'Outro'];
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="lista_prescricoes.php">Programas</a></li>
                    <li class="breadcrumb-item active">#<?= $p['id'] ?></li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Programa de Tratamento</h1>
                <div class="d-flex gap-2">
                    <a href="lista_prescricoes.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
                    <a href="editar_prescricao.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-regular fa-pen-to-square me-1"></i>Editar</a>
                </div>
            </div>
            <div class="card p-4" style="max-width:700px;">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Paciente:</strong> <?= h($p['paciente']) ?></p>
                        <p><strong>Médico:</strong> <?= h($p['medico']) ?></p>
                        <p><strong>Membro afetado:</strong> <?= h($membro_labels[$p['membro_afetado'] ?? ''] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Data início:</strong> <?= h($p['data_prescricao']) ?></p>
                        <p><strong>Validade:</strong> <?= h($p['data_validade'] ?? '—') ?></p>
                        <p><strong>Sessões prescritas:</strong> <?= $p['num_sessoes_prescritas'] ? h($p['num_sessoes_prescritas']) : '—' ?></p>
                    </div>
                </div>
                <hr>
                <?php if ($p['objetivos_clinicos']): ?>
                    <p><strong>Objetivos clínicos:</strong><br><span class="text-muted"><?= h($p['objetivos_clinicos']) ?></span></p>
                <?php endif; ?>
                <?php if ($p['observacoes']): ?>
                    <p class="mb-0"><strong>Observações:</strong><br><span class="text-muted"><?= h($p['observacoes']) ?></span></p>
                <?php endif; ?>
                <hr>
                <p class="mb-0"><strong>Estado:</strong>
                    <?= $p['ativa'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?>
                </p>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
