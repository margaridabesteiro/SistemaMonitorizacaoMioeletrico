<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('medico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

// Mês a visualizar
$ano = (int)($_GET['ano'] ?? date('Y'));
$mes = (int)($_GET['mes'] ?? date('m'));
$mes = max(1, min(12, $mes));

$primeiro_dia = mktime(0,0,0,$mes,1,$ano);
$ultimo_dia   = mktime(0,0,0,$mes+1,0,$ano);

// Consultas do mês
$consultas_mes = [];
if ($pid) {
    $stmt2 = $db->prepare("
        SELECT c.*, u.nome AS paciente
        FROM consultas c
        JOIN utentes ut ON ut.id = c.utente_id
        JOIN utilizadores u ON u.id = ut.utilizador_id
        WHERE c.medico_id = ?
          AND c.data_hora BETWEEN ? AND ?
        ORDER BY c.data_hora
    ");
    $stmt2->execute([$pid, date('Y-m-01', $primeiro_dia), date('Y-m-t 23:59:59', $primeiro_dia)]);
    foreach ($stmt2->fetchAll() as $c) {
        $dia = (int)date('j', strtotime($c['data_hora']));
        $consultas_mes[$dia][] = $c;
    }
}

$mes_anterior = $mes == 1 ? ['mes' => 12, 'ano' => $ano - 1] : ['mes' => $mes - 1, 'ano' => $ano];
$mes_seguinte = $mes == 12 ? ['mes' => 1,  'ano' => $ano + 1] : ['mes' => $mes + 1, 'ano' => $ano];
$nomes_meses  = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$dia_semana_inicio = (int)date('N', $primeiro_dia); // 1=Seg, 7=Dom
$total_dias   = (int)date('t', $primeiro_dia);

$pagina_titulo = 'Minha Agenda'; $pagina_ativa = 'agenda';
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> alert-dismissible py-2 mb-3"><?= h($flash['mensagem']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Minha Agenda</h1>
                <a href="nova_consulta.php" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                    <i class="fa-regular fa-calendar-plus me-1"></i>Nova Consulta
                </a>
            </div>

            <!-- Navegação do calendário -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <a href="?mes=<?= $mes_anterior['mes'] ?>&ano=<?= $mes_anterior['ano'] ?>"
                   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-chevron-left"></i></a>
                <h4 class="mb-0 fw-bold"><?= $nomes_meses[$mes] ?> <?= $ano ?></h4>
                <a href="?mes=<?= $mes_seguinte['mes'] ?>&ano=<?= $mes_seguinte['ano'] ?>"
                   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-chevron-right"></i></a>
                <a href="?mes=<?= date('m') ?>&ano=<?= date('Y') ?>"
                   class="btn btn-sm btn-outline-danger ms-2">Hoje</a>
            </div>

            <!-- Calendário -->
            <div class="card p-3">
                <table class="table table-bordered mb-0" style="table-layout:fixed;">
                    <thead>
                        <tr>
                            <?php foreach(['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'] as $d): ?>
                                <th class="text-center text-muted small py-2"><?= $d ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $celula = 1;
                        $total_celulas = $dia_semana_inicio - 1 + $total_dias;
                        $linhas = ceil($total_celulas / 7);
                        $dia_atual = date('j') . '-' . date('n') . '-' . date('Y');

                        for ($linha = 0; $linha < $linhas; $linha++):
                        ?>
                        <tr>
                            <?php for ($col = 1; $col <= 7; $col++):
                                $num_celula = $linha * 7 + $col;
                                $dia = $num_celula - $dia_semana_inicio + 1;
                                $e_hoje = ($dia == date('j') && $mes == date('n') && $ano == date('Y'));
                            ?>
                            <td style="height:80px;vertical-align:top;padding:4px;"
                                class="<?= $e_hoje ? 'table-danger' : '' ?>">
                                <?php if ($dia >= 1 && $dia <= $total_dias): ?>
                                    <div class="fw-bold small mb-1 <?= $e_hoje ? 'text-danger' : 'text-muted' ?>">
                                        <?= $dia ?>
                                    </div>
                                    <?php foreach ($consultas_mes[$dia] ?? [] as $c): ?>
                                        <div class="badge w-100 text-truncate mb-1"
                                             style="background:<?= ['agendada'=>'#0d6efd','realizada'=>'#198754','cancelada'=>'#dc3545'][$c['estado']] ?? '#6c757d' ?>;font-size:.7rem;"
                                             title="<?= h($c['paciente']) ?> — <?= h(substr($c['data_hora'],11,5)) ?>">
                                            <?= h(substr($c['data_hora'],11,5)) ?> <?= h($c['paciente']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-3 mt-3 small">
                <span><span class="badge" style="background:#0d6efd">●</span> Agendada</span>
                <span><span class="badge" style="background:#198754">●</span> Realizada</span>
                <span><span class="badge" style="background:#dc3545">●</span> Cancelada</span>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
