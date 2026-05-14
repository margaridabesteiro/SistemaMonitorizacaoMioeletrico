<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Exportar Dados'; $pagina_ativa = 'relatorios';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../../config/app.php';
    require_once __DIR__ . '/../../../config/database.php';
    requirePerfil('admin');
    $tipo = $_POST['tipo'] ?? 'utilizadores';
    $db = getDB();
    $queries = [
        'utilizadores' => 'SELECT id,nome,email,perfil,ativo,criado_em,ultimo_login FROM utilizadores',
        'sessoes'      => 'SELECT s.id, u.nome AS utente, s.data_hora, s.tipo, s.estado, s.duracao_min FROM sessoes s JOIN utentes ut ON ut.id=s.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id',
        'dispositivos' => 'SELECT * FROM dispositivos',
        'faturas'      => 'SELECT f.numero, u.nome AS utente, f.valor_eur, f.paga, f.data_emissao FROM faturas f JOIN utentes ut ON ut.id=f.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id',
        'logs'         => 'SELECT l.criado_em, u.nome, l.acao, l.ip FROM logs_acesso l LEFT JOIN utilizadores u ON u.id=l.utilizador_id ORDER BY l.criado_em DESC LIMIT 5000',
    ];
    $sql = $queries[$tipo] ?? $queries['utilizadores'];
    $rows = $db->query($sql)->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $tipo . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output','w'); fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    if (!empty($rows)) { fputcsv($out, array_keys($rows[0])); foreach($rows as $r) fputcsv($out,$r); }
    fclose($out); exit;
}
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="relatorios_sistema.php">Relatórios</a></li><li class="breadcrumb-item active">Exportar Dados</li></ol></nav>
            <h1 class="mb-4">Exportar Dados</h1>
            <div class="card p-4" style="max-width:500px;">
                <form method="POST">
                    <div class="mb-3"><label class="form-label fw-semibold">Tipo de Dados</label>
                        <select name="tipo" class="form-select">
                            <option value="utilizadores">Utilizadores</option>
                            <option value="sessoes">Sessões</option>
                            <option value="dispositivos">Dispositivos</option>
                            <option value="faturas">Faturas</option>
                            <option value="logs">Logs de Acesso</option>
                        </select></div>
                    <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-download me-1"></i>Exportar CSV</button>
                </form>
            </div>
        </main>
