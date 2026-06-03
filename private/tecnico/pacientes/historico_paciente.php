<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Histórico Paciente'; $pagina_ativa = 'pacientes';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/private/tecnico/pacientes/lista_pacientes.php');
$stmt = $db->prepare("SELECT ut.*, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.id=?"); $stmt->execute([$id]); $pac = $stmt->fetch();
if (!$pac) redirect(APP_URL . '/private/tecnico/pacientes/lista_pacientes.php');
$sessoes = $db->prepare("SELECT s.*, m.score_jogo, m.percentagem_final FROM sessoes s LEFT JOIN metricas_sessao m ON m.sessao_id=s.id WHERE s.utente_id=? ORDER BY s.data_hora DESC LIMIT 50");
$sessoes->execute([$id]); $sessoes = $sessoes->fetchAll();
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="lista_pacientes.php">Pacientes</a></li><li class="breadcrumb-item"><a href="perfil_paciente.php?id=<?= $id ?>"><?= h($pac['nome']) ?></a></li><li class="breadcrumb-item active">Histórico</li></ol></nav>
            <h1 class="mb-4">Histórico — <?= h($pac['nome']) ?></h1>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Data/Hora</th><th>Tipo</th><th>Duração</th><th>Estado</th><th>Score</th><th>Precisão</th></tr></thead>
                    <tbody>
                    <?php if(empty($sessoes)): ?><tr><td colspan="6" class="text-center text-muted py-4">Sem sessões.</td></tr>
                    <?php else: foreach($sessoes as $s): ?>
                        <tr>
                            <td><?= h(substr($s['data_hora'],0,16)) ?></td>
                            <td><?= h($s['categoria'] ?? '—') ?></td>
                            <td><?= $s['duracao_min'] ? h($s['duracao_min']).' min' : '—' ?></td>
                            <td><span class="badge bg-<?= ['concluida'=>'success','agendada'=>'primary','em_curso'=>'warning','cancelada'=>'danger'][$s['estado']] ?? 'secondary' ?>"><?= h($s['estado']) ?></span></td>
                            <td><?= $s['score_jogo'] ?? '—' ?></td>
                            <td><?= $s['percentagem_final'] ? number_format((float)$s['percentagem_final'],1).'%' : '—' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
