<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'A Minha Medicação'; $pagina_ativa = 'medicacao';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM utentes WHERE utilizador_id=?');
$stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();

$medicacao = [];
if ($utid) {
    $s = $db->prepare("
        SELECT pm.*, um.nome AS medico
        FROM prescricoes_medicacao pm
        JOIN consultas c ON c.id=pm.consulta_id
        JOIN profissionais p ON p.id=c.medico_id
        JOIN utilizadores um ON um.id=p.utilizador_id
        WHERE c.utente_id=?
        ORDER BY pm.ativa DESC, pm.data_inicio DESC
    ");
    $s->execute([$utid]); $medicacao = $s->fetchAll();
}

$programas_trat = [];
if ($utid) {
    try {
        $spt = $db->prepare("
            SELECT pt.data_prescricao, pt.objetivos_clinicos, pt.membro_afetado,
                   pt.num_sessoes_prescritas, pt.data_validade, pt.observacoes, pt.ativa,
                   u.nome AS medico_nome
            FROM programas_tratamento pt
            JOIN profissionais pf ON pf.id = pt.medico_id
            JOIN utilizadores u ON u.id = pf.utilizador_id
            WHERE pt.utente_id = ?
            ORDER BY pt.ativa DESC, pt.data_prescricao DESC
        ");
        $spt->execute([$utid]); $programas_trat = $spt->fetchAll();
    } catch (\Throwable $e) { $programas_trat = []; }
}

require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
?>
        <main class="content">
            <h1 class="mb-4">Tratamentos e Medicação</h1>

            <!-- Programas de Tratamento -->
            <?php if (!empty($programas_trat)): ?>
            <h5 class="mb-3"><i class="fa-solid fa-file-medical me-2" style="color:#8B0000;"></i>Programas de Tratamento</h5>
            <?php foreach ($programas_trat as $pt): ?>
            <div class="card p-3 mb-3 <?= $pt['ativa'] ? 'border-success' : '' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-<?= $pt['ativa'] ? 'success' : 'secondary' ?> me-2"><?= $pt['ativa'] ? 'Ativo' : 'Concluído' ?></span>
                        <small class="text-muted">Prescrito em <?= h(date('d/m/Y', strtotime($pt['data_prescricao']))) ?> pelo Dr. <?= h($pt['medico_nome']) ?></small>
                    </div>
                    <?php if ($pt['data_validade']): ?>
                    <small class="text-muted">Válido até <?= h(date('d/m/Y', strtotime($pt['data_validade']))) ?></small>
                    <?php endif; ?>
                </div>
                <p class="mb-1"><strong>Objetivos:</strong> <?= h($pt['objetivos_clinicos']) ?></p>
                <?php if ($pt['membro_afetado']): ?>
                <p class="mb-1 small"><strong>Membro:</strong> <?= h(str_replace('_', ' ', $pt['membro_afetado'])) ?></p>
                <?php endif; ?>
                <?php if ($pt['num_sessoes_prescritas']): ?>
                <p class="mb-1 small"><strong>Sessões prescritas:</strong> <?= h($pt['num_sessoes_prescritas']) ?></p>
                <?php endif; ?>
                <?php if ($pt['observacoes']): ?>
                <p class="mb-0 small text-muted fst-italic"><?= h($pt['observacoes']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <hr class="my-4">
            <?php endif; ?>

            <h5 class="mb-3"><i class="fa-solid fa-pills me-2" style="color:#8B0000;"></i>A Minha Medicação</h5>
            <?php if (empty($medicacao)): ?>
                <div class="card p-4 text-center text-muted">
                    <i class="fa-solid fa-pills fa-3x mb-3 opacity-25"></i>
                    <p>Não tem medicação prescrita registada.</p>
                </div>
            <?php else: ?>
            <?php $ativa = array_filter($medicacao, fn($m) => $m['ativa']); ?>
            <?php $inativa = array_filter($medicacao, fn($m) => !$m['ativa']); ?>

            <?php if ($ativa): ?>
                <h5 class="mb-3">Medicação Ativa</h5>
                <div class="row g-3 mb-4">
                <?php foreach($ativa as $m): ?>
                    <div class="col-md-6">
                        <div class="card p-3 border-success">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold mb-0"><?= h($m['medicamento']) ?></h6>
                                <span class="badge bg-success">Ativa</span>
                            </div>
                            <p class="text-muted small mb-1"><strong>Dosagem:</strong> <?= h($m['dosagem']) ?></p>
                            <p class="text-muted small mb-1"><strong>Posologia:</strong> <?= h($m['posologia']) ?></p>
                            <p class="text-muted small mb-1"><strong>Início:</strong> <?= h($m['data_inicio']) ?> <?= $m['data_fim'] ? '· <strong>Fim:</strong> '.h($m['data_fim']) : '· Contínuo' ?></p>
                            <p class="text-muted small mb-0"><strong>Médico:</strong> <?= h($m['medico']) ?></p>
                            <?php if ($m['observacoes']): ?><p class="small mt-2 fst-italic"><?= h($m['observacoes']) ?></p><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($inativa): ?>
                <h5 class="mb-3 text-muted">Histórico</h5>
                <div class="card"><div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Medicamento</th><th>Dosagem</th><th>Início</th><th>Fim</th><th>Médico</th></tr></thead>
                        <tbody>
                        <?php foreach($inativa as $m): ?>
                            <tr class="text-muted">
                                <td><?= h($m['medicamento']) ?></td>
                                <td><?= h($m['dosagem']) ?></td>
                                <td><?= h($m['data_inicio']) ?></td>
                                <td><?= h($m['data_fim'] ?? 'Contínuo') ?></td>
                                <td><?= h($m['medico']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div></div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
