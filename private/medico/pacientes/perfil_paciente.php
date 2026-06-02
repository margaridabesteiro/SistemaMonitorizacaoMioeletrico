<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');
$pagina_titulo = 'Perfil do Paciente'; $pagina_ativa = 'pacientes';
$js_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/medico/pacientes/gestaoUtente.php');

// Atribuir técnico ao paciente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_acao'] ?? '') === 'assign_tecnico') {
    $tecnico_id = (int)($_POST['tecnico_id'] ?? 0) ?: null;
    $db->prepare('UPDATE utentes SET tecnico_id=? WHERE id=? AND medico_id=?')
       ->execute([$tecnico_id, $id, $pid]);
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Técnico atribuído com sucesso.'];
    redirect(APP_URL . '/private/medico/pacientes/perfil_paciente.php?id=' . $id);
}

$stmt = $db->prepare("
    SELECT ut.*, u.nome, u.email
    FROM utentes ut
    JOIN utilizadores u ON u.id = ut.utilizador_id
    WHERE ut.id = ? AND ut.medico_id = ?
");
$stmt->execute([$id, $pid]); $pac = $stmt->fetch();
if (!$pac) redirect(APP_URL . '/private/medico/pacientes/gestaoUtente.php');

// Técnico atualmente atribuído
$tecnico_atual_nome = null;
if ($pac['tecnico_id']) {
    $t = $db->prepare('SELECT u.nome FROM profissionais p JOIN utilizadores u ON u.id=p.utilizador_id WHERE p.id=?');
    $t->execute([$pac['tecnico_id']]);
    $tecnico_atual_nome = $t->fetchColumn() ?: null;
}

// Lista de técnicos ativos
$tecnicos_lista = $db->query("
    SELECT p.id, u.nome
    FROM profissionais p
    JOIN utilizadores u ON u.id = p.utilizador_id
    WHERE u.perfil = 'tecnico' AND u.ativo = 1
    ORDER BY u.nome
")->fetchAll();

// Contagens gerais
$n_sessoes   = (int)$db->query("SELECT COUNT(*) FROM sessoes WHERE utente_id=$id AND estado='concluida'")->fetchColumn();
$n_consultas = (int)$db->query("SELECT COUNT(*) FROM consultas WHERE utente_id=$id AND medico_id=$pid")->fetchColumn();
$n_exames    = (int)$db->query("SELECT COUNT(*) FROM pedidos_exame pe JOIN consultas c ON c.id=pe.consulta_id WHERE c.utente_id=$id AND pe.estado='pendente'")->fetchColumn();

// Evolução percentagem_final ao longo das sessões concluídas
$evolucao = $db->query("
    SELECT DATE_FORMAT(s.data_hora, '%d/%m/%Y') AS data,
           ROUND(m.percentagem_final, 1) AS pct,
           j.nome AS jogo
    FROM sessoes s
    JOIN metricas_sessao m ON m.sessao_id = s.id
    LEFT JOIN jogos j ON j.id = s.jogo_id
    WHERE s.utente_id = $id AND s.estado = 'concluida' AND m.percentagem_final IS NOT NULL
    ORDER BY s.data_hora
    LIMIT 30
")->fetchAll();

// Últimas consultas
$consultas = $db->prepare("
    SELECT c.data_hora, c.tipo, c.modalidade, c.estado, c.motivo
    FROM consultas c
    WHERE c.utente_id = ? AND c.medico_id = ?
    ORDER BY c.data_hora DESC
    LIMIT 5
");
$consultas->execute([$id, $pid]); $consultas = $consultas->fetchAll();

// Medicação ativa
$medicacao = $db->query("
    SELECT medicamento, dosagem, posologia, data_inicio, data_fim
    FROM prescricoes_medicacao
    WHERE utente_id = $id AND ativa = 1
    ORDER BY data_inicio DESC
    LIMIT 5
")->fetchAll();

$fase_labels = ['avaliacao' => 'Avaliação', 'ativo' => 'Ativo', 'manutencao' => 'Manutenção', 'alta' => 'Alta'];
$fase_cores  = ['avaliacao' => 'secondary', 'ativo' => 'primary', 'manutencao' => 'info', 'alta' => 'success'];
$tipo_badge  = ['inicial' => 'info', 'rotina' => 'secondary', 'alta' => 'success', 'urgente' => 'danger'];
?>
        <main class="content">
            <?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:70px;height:70px;border-radius:50%;background:#fce8e8;display:flex;align-items:center;justify-content:center;font-size:2rem;">
                        <i class="fa-regular fa-user" style="color:#8B0000;"></i>
                    </div>
                    <div>
                        <h1 class="mb-0"><?= h($pac['nome']) ?></h1>
                        <p class="text-muted mb-0"><?= h($pac['email']) ?></p>
                        <?php $f = $pac['fase_tratamento'] ?? 'avaliacao'; ?>
                        <span class="badge bg-<?= $fase_cores[$f] ?? 'secondary' ?>"><?= $fase_labels[$f] ?? $f ?></span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="nova_consulta.php?utente_id=<?= $id ?>" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                        <i class="fa-regular fa-calendar-plus me-1"></i>Nova Consulta
                    </a>
                    <a href="gestaoUtente.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
                    </a>
                </div>
            </div>

            <!-- Métricas rápidas -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-success"><?= $n_sessoes ?></div>
                        <div class="text-muted small">Sessões Realizadas</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-primary"><?= $n_consultas ?></div>
                        <div class="text-muted small">Consultas</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3 <?= $n_exames > 0 ? 'border-warning' : '' ?>">
                        <div class="fs-2 fw-bold text-warning"><?= $n_exames ?></div>
                        <div class="text-muted small">Exames Pendentes</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-secondary"><?= count($medicacao) ?></div>
                        <div class="text-muted small">Medicações Ativas</div>
                    </div>
                </div>
            </div>

            <!-- Equipa de Tratamento -->
            <div class="card p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-user-doctor me-2" style="color:#8B0000;"></i>Equipa de Tratamento</h6>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Médico responsável</small>
                        <span class="fw-semibold"><?= h($_SESSION['nome'] ?? '—') ?></span>
                    </div>
                    <div class="col-md-8">
                        <small class="text-muted d-block mb-1">Técnico atribuído</small>
                        <?php if ($tecnico_atual_nome): ?>
                            <span class="badge bg-success me-2"><i class="fa-solid fa-user-gear me-1"></i><?= h($tecnico_atual_nome) ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark me-2">Sem técnico atribuído</span>
                        <?php endif; ?>
                        <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#formTecnico">
                            <i class="fa-solid fa-pen me-1"></i><?= $tecnico_atual_nome ? 'Alterar' : 'Atribuir' ?>
                        </button>
                        <div class="collapse mt-2" id="formTecnico">
                            <form method="POST" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="_acao" value="assign_tecnico">
                                <select name="tecnico_id" class="form-select form-select-sm" style="max-width:250px;">
                                    <option value="">— Sem técnico —</option>
                                    <?php foreach ($tecnicos_lista as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $pac['tecnico_id'] == $t['id'] ? 'selected' : '' ?>>
                                            <?= h($t['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm" style="background:#8B0000;color:#fff;">Guardar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info clínica -->
            <div class="card p-3 mb-4">
                <h6 class="fw-bold mb-2">Informação Clínica</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Diagnóstico:</strong> <span class="text-muted"><?= h($pac['diagnostico'] ?? '—') ?></span></p>
                        <?php if ($pac['categoria_clinica']): ?>
                            <p class="mb-1"><strong>Categoria:</strong> <?= h(str_replace('_', ' ', $pac['categoria_clinica'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if ($pac['membro_afetado']): ?>
                            <p class="mb-1"><strong>Membro afetado:</strong> <?= h(str_replace('_', ' ', $pac['membro_afetado'])) ?></p>
                        <?php endif; ?>
                        <?php if ($pac['data_inicio_tratamento']): ?>
                            <p class="mb-1"><strong>Início tratamento:</strong> <?= h($pac['data_inicio_tratamento']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Gráfico de evolução -->
            <?php if (!empty($evolucao)): ?>
            <div class="card p-3 mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-chart-line me-2" style="color:#8B0000;"></i>Evolução nas Sessões de Treino</h5>
                <canvas id="chartEvolucao" style="max-height:280px;"></canvas>
            </div>
            <?php endif; ?>

            <div class="row g-3">
                <!-- Últimas consultas -->
                <div class="col-md-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Últimas Consultas</h5>
                            <a href="../consultas/consulta.php" class="btn btn-xs btn-outline-secondary">Ver todas</a>
                        </div>
                        <?php if (empty($consultas)): ?>
                            <p class="text-muted small">Sem consultas.</p>
                        <?php else: ?>
                            <table class="table table-sm table-hover">
                                <thead><tr><th>Data</th><th>Tipo</th><th>Estado</th></tr></thead>
                                <tbody>
                                <?php foreach ($consultas as $c): ?>
                                    <tr>
                                        <td><?= h(substr($c['data_hora'], 0, 10)) ?></td>
                                        <td><span class="badge bg-<?= $tipo_badge[$c['tipo']] ?? 'secondary' ?>"><?= h(ucfirst($c['tipo'])) ?></span></td>
                                        <td><span class="badge bg-<?= ['agendada' => 'primary', 'realizada' => 'success', 'cancelada' => 'danger'][$c['estado']] ?? 'secondary' ?>"><?= h($c['estado']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Medicação ativa -->
                <div class="col-md-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Medicação Ativa</h5>
                            <a href="../consultas/lista_pedidos_exame.php?utente=<?= $id ?>" class="btn btn-xs btn-outline-secondary">Exames</a>
                        </div>
                        <?php if (empty($medicacao)): ?>
                            <p class="text-muted small">Sem medicação ativa.</p>
                        <?php else: ?>
                            <table class="table table-sm">
                                <thead><tr><th>Medicamento</th><th>Dosagem</th><th>Fim</th></tr></thead>
                                <tbody>
                                <?php foreach ($medicacao as $m): ?>
                                    <tr>
                                        <td><?= h($m['medicamento']) ?></td>
                                        <td><?= h($m['dosagem']) ?></td>
                                        <td><?= $m['data_fim'] ? h($m['data_fim']) : '<span class="text-muted">Contínuo</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

<?php if (!empty($evolucao)):
    $labels = array_column($evolucao, 'data');
    $pcts   = array_column($evolucao, 'pct');
?>
        <script>
        new Chart(document.getElementById('chartEvolucao'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Percentagem Final (%)',
                    data: <?= json_encode(array_map('floatval', $pcts)) ?>,
                    borderColor: '#8B0000',
                    backgroundColor: 'rgba(139,0,0,.08)',
                    tension: 0.3,
                    pointRadius: 5,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { min: 0, max: 100, title: { display: true, text: '% Final' } }
                }
            }
        });
        </script>
<?php endif; ?>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
