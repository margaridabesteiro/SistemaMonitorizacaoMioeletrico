<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Detalhes Prescrição'; $pagina_ativa = 'prescricoes';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
$stmt = $db->prepare("SELECT p.*, u.nome AS paciente, um.nome AS medico FROM prescricoes p JOIN utentes ut ON ut.id=p.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id JOIN profissionais pm ON pm.id=p.medico_id JOIN utilizadores um ON um.id=pm.utilizador_id WHERE p.id=?");
$stmt->execute([$id]); $p = $stmt->fetch();
if (!$p) redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="lista_prescricoes.php">Prescrições</a></li>
                    <li class="breadcrumb-item active">#<?= $p['id'] ?></li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Detalhes da Prescrição</h1>
                <div class="d-flex gap-2">
                    <a href="lista_prescricoes.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
                    </a>
                    <a href="editar_prescricao.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-regular fa-pen-to-square me-1"></i>Editar
                    </a>
                </div>
            </div>

            <div class="card p-4" style="max-width:700px;">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Paciente:</strong> <?= h($p['paciente']) ?></p>
                        <p><strong>Médico:</strong> <?= h($p['medico']) ?></p>
                        <p><strong>Tipo:</strong> <?= h($p['tipo']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Data:</strong> <?= h($p['data_prescricao']) ?></p>
                        <p><strong>Validade:</strong> <?= h($p['data_validade'] ?? '—') ?></p>
                        <p><strong>Prioridade:</strong>
                            <span class="badge bg-<?= ['Baixa'=>'success','Media'=>'info','Alta'=>'warning','Urgente'=>'danger'][$p['prioridade']] ?? 'secondary' ?>">
                                <?= h($p['prioridade']) ?>
                            </span>
                        </p>
                    </div>
                </div>
                <hr>
                <p class="mb-0">
                    <strong>Estado:</strong>
                    <?= $p['ativa']
                        ? '<span class="badge bg-success">Ativa</span>'
                        : '<span class="badge bg-secondary">Inativa</span>' ?>
                </p>
                <?php if ($p['observacoes']): ?>
                    <hr>
                    <p class="mb-0"><strong>Observações:</strong><br><?= h($p['observacoes']) ?></p>
                <?php endif; ?>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
