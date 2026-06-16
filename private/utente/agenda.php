<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Agenda'; $pagina_ativa = 'agenda';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?");
$stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();

// Mês/ano a mostrar
$mes = (int)($_GET['mes'] ?? date('n'));
$ano = (int)($_GET['ano'] ?? date('Y'));
if ($mes < 1)  { $mes = 12; $ano--; }
if ($mes > 12) { $mes = 1;  $ano++; }
$mes_anterior = $mes === 1  ? ['mes'=>12,'ano'=>$ano-1] : ['mes'=>$mes-1,'ano'=>$ano];
$mes_seguinte = $mes === 12 ? ['mes'=>1, 'ano'=>$ano+1] : ['mes'=>$mes+1,'ano'=>$ano];

// Buscar sessões do mês
$eventos_mes = [];
if ($utid) {
    $s = $db->prepare("
        SELECT s.data_hora, s.estado, s.modalidade, s.link_videochamada,
               j.nome AS jogo, u.nome AS tecnico, 'sessao' AS tipo_item
        FROM sessoes s
        LEFT JOIN jogos j ON j.id=s.jogo_id
        LEFT JOIN profissionais p ON p.id=s.tecnico_id
        LEFT JOIN utilizadores u ON u.id=p.utilizador_id
        WHERE s.utente_id=? AND YEAR(s.data_hora)=? AND MONTH(s.data_hora)=?
        ORDER BY s.data_hora
    ");
    $s->execute([$utid, $ano, $mes]);
    foreach ($s->fetchAll() as $row) {
        $dia = (int)date('j', strtotime($row['data_hora']));
        $eventos_mes[$dia][] = $row;
    }
}
// Buscar consultas médicas do mês
if ($utid) {
    try {
        $s = $db->prepare("
            SELECT c.data_hora, c.estado, c.modalidade, c.link_videochamada,
                   COALESCE(c.tipo, 'médica') AS jogo, u.nome AS tecnico, 'consulta' AS tipo_item
            FROM consultas c
            LEFT JOIN profissionais p ON p.id = c.medico_id
            LEFT JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE c.utente_id=? AND YEAR(c.data_hora)=? AND MONTH(c.data_hora)=?
              AND c.estado != 'cancelada'
            ORDER BY c.data_hora
        ");
        $s->execute([$utid, $ano, $mes]);
        foreach ($s->fetchAll() as $row) {
            $dia = (int)date('j', strtotime($row['data_hora']));
            $eventos_mes[$dia][] = $row;
        }
    } catch (\Throwable $e) {}
}

// Dados do calendário
$primeiro_dia      = mktime(0,0,0,$mes,1,$ano);
$dias_no_mes       = (int)date('t', $primeiro_dia);
$dia_semana_inicio = (int)date('N', $primeiro_dia); // 1=Seg ... 7=Dom

$nomes_meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$hoje_dia = (int)date('j'); $hoje_mes = (int)date('n'); $hoje_ano = (int)date('Y');

require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="mb-0"><i class="fa-regular fa-calendar me-2"></i>Agenda</h1>
                <div class="d-flex align-items-center gap-2">
                    <a href="?mes=<?= $mes_anterior['mes'] ?>&ano=<?= $mes_anterior['ano'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <span class="fw-semibold" style="min-width:160px;text-align:center;">
                        <?= $nomes_meses[$mes] ?> <?= $ano ?>
                    </span>
                    <a href="?mes=<?= $mes_seguinte['mes'] ?>&ano=<?= $mes_seguinte['ano'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <a href="?mes=<?= date('n') ?>&ano=<?= date('Y') ?>" class="btn btn-sm btn-outline-primary">Hoje</a>
                </div>
            </div>

            <!-- Legenda -->
            <div class="d-flex gap-3 mb-3 flex-wrap small">
                <span><span class="badge" style="background:#667eea;">&nbsp;</span> Sessão agendada</span>
                <span><span class="badge bg-success">&nbsp;</span> Concluída</span>
                <span><span class="badge bg-warning text-dark">&nbsp;</span> Em curso</span>
                <span><span class="badge" style="background:#8B0000;">&nbsp;</span> Consulta médica</span>
            </div>

            <!-- Calendário -->
            <div class="card p-0 overflow-hidden">
                <!-- Cabeçalho dias da semana -->
                <div class="d-grid" style="grid-template-columns:repeat(7,1fr);border-bottom:1px solid #dee2e6;">
                    <?php foreach (['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'] as $d): ?>
                        <div class="text-center py-2 fw-semibold small" style="background:#f8f9fa;border-right:1px solid #dee2e6;"><?= $d ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Grelha de dias -->
                <div class="d-grid" style="grid-template-columns:repeat(7,1fr);">
                    <?php
                    // Células vazias antes do primeiro dia
                    for ($i = 1; $i < $dia_semana_inicio; $i++):
                    ?>
                        <div style="min-height:90px;border-right:1px solid #dee2e6;border-bottom:1px solid #dee2e6;background:#f8f9fa;"></div>
                    <?php endfor; ?>

                    <?php for ($dia = 1; $dia <= $dias_no_mes; $dia++):
                        $e_hoje = ($dia === $hoje_dia && $mes === $hoje_mes && $ano === $hoje_ano);
                        $eventos = $eventos_mes[$dia] ?? [];
                        $col_pos = (($dia_semana_inicio - 1 + $dia - 1) % 7) + 1;
                        $ultimo_col = $col_pos === 7;
                    ?>
                        <div style="min-height:90px;border-right:<?= $ultimo_col?'none':'1px solid #dee2e6' ?>;border-bottom:1px solid #dee2e6;padding:6px 6px 4px;<?= $e_hoje?'background:#eff2ff;':'' ?>">
                            <div class="d-flex justify-content-center mb-1">
                                <span class="<?= $e_hoje?'badge rounded-circle text-white':'small text-muted' ?>"
                                      style="<?= $e_hoje?'background:linear-gradient(135deg,#667eea,#764ba2);width:26px;height:26px;line-height:26px;font-size:.8rem;':'font-size:.78rem;' ?>">
                                    <?= $dia ?>
                                </span>
                            </div>
                            <?php foreach ($eventos as $ev):
                                $hora      = date('H:i', strtotime($ev['data_hora']));
                                $e_consulta = ($ev['tipo_item'] ?? 'sessao') === 'consulta';
                                $cor = match($ev['estado']) {
                                    'concluida', 'realizada' => '#198754',
                                    'em_curso'               => '#ffc107',
                                    default                  => $e_consulta ? '#8B0000' : '#667eea',
                                };
                                $txt_cor  = $ev['estado'] === 'em_curso' ? '#000' : '#fff';
                                $etiqueta = $e_consulta
                                    ? 'Consulta ' . mb_substr($ev['jogo'] ?? 'médica', 0, 10)
                                    : ($ev['jogo'] ? mb_substr($ev['jogo'], 0, 14) : 'Sessão');
                            ?>
                                <div class="mb-1 rounded px-1 py-0" style="background:<?= $cor ?>;color:<?= $txt_cor ?>;font-size:.7rem;line-height:1.4;cursor:default;"
                                     title="<?= h($ev['jogo'] ?? ($e_consulta ? 'Consulta' : 'Sessão')) ?> — <?= h($ev['tecnico'] ?? '') ?> — <?= h($ev['estado']) ?>">
                                    <strong><?= $hora ?></strong> <?= h($etiqueta) ?>
                                    <?php if (!empty($ev['link_videochamada'])): ?><i class="fa-solid fa-video ms-1"></i><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>

                    <?php
                    // Células vazias no final para completar a linha
                    $total_celulas = $dia_semana_inicio - 1 + $dias_no_mes;
                    $restam = (7 - ($total_celulas % 7)) % 7;
                    for ($i = 0; $i < $restam; $i++):
                    ?>
                        <div style="min-height:90px;border-right:<?= $i<$restam-1?'1px solid #dee2e6':'none' ?>;border-bottom:1px solid #dee2e6;background:#f8f9fa;"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Lista de eventos do mês -->
            <?php
            $todos_eventos = [];
            foreach ($eventos_mes as $dia => $evs) {
                foreach ($evs as $ev) { $todos_eventos[] = array_merge($ev, ['dia'=>$dia]); }
            }
            usort($todos_eventos, fn($a,$b) => strtotime($a['data_hora']) <=> strtotime($b['data_hora']));
            ?>
            <?php if (!empty($todos_eventos)): ?>
            <div class="card mt-4 p-3">
                <h6 class="fw-semibold mb-3"><i class="fa-solid fa-list me-2" style="color:#667eea;"></i>Agenda de <?= $nomes_meses[$mes] ?></h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Data</th><th>Hora</th><th>Tipo</th><th>Descrição</th><th>Profissional</th><th>Modalidade</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($todos_eventos as $ev):
                            $e_consulta_ev = ($ev['tipo_item'] ?? 'sessao') === 'consulta';
                            $estadoCor = match($ev['estado']) {
                                'concluida','realizada' => 'success',
                                'em_curso'              => 'warning text-dark',
                                'cancelada'             => 'secondary',
                                default                 => 'info text-dark',
                            };
                            $agora_ts2 = time();
                            $ts_ev     = strtotime($ev['data_hora']);
                            $diff_ev   = $ts_ev - $agora_ts2;
                        ?>
                            <tr>
                                <td><?= date('d/m', $ts_ev) ?></td>
                                <td><?= date('H:i', $ts_ev) ?></td>
                                <td>
                                    <?php if ($e_consulta_ev): ?>
                                        <span class="badge" style="background:#8B0000;"><i class="fa-solid fa-stethoscope me-1"></i>Consulta</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#667eea;"><i class="fa-solid fa-dumbbell me-1"></i>Sessão</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($ev['jogo'] ?? ($e_consulta_ev ? 'Consulta médica' : 'Sessão de treino')) ?></td>
                                <td><?= h($ev['tecnico'] ?? '—') ?></td>
                                <td><?= in_array($ev['modalidade'],['remota','video'],true) ? '<span class="badge bg-primary">Remota</span>' : '<span class="badge bg-secondary">Presencial</span>' ?></td>
                                <td><span class="badge bg-<?= $estadoCor ?>"><?= h(ucfirst($ev['estado'])) ?></span></td>
                                <td>
                                    <?php if (!empty($ev['link_videochamada']) && $diff_ev <= 900 && $diff_ev >= -3600): ?>
                                    <a href="<?= h($ev['link_videochamada']) ?>" target="_blank" rel="noopener"
                                       class="btn btn-xs btn-primary">
                                        <i class="fa-solid fa-video me-1"></i>Entrar
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-light mt-4 text-center text-muted">
                <i class="fa-regular fa-calendar-xmark fa-2x mb-2 d-block"></i>
                Sem eventos agendados em <?= $nomes_meses[$mes] ?> <?= $ano ?>.
            </div>
            <?php endif; ?>
        </main>
<style>
.d-grid { display: grid !important; }
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
