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
        'sessoes'      => 'SELECT s.id, u.nome AS utente, s.data_hora, s.categoria, s.estado, s.duracao_min FROM sessoes s JOIN utentes ut ON ut.id=s.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id',
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

            <div class="row g-4">
                <!-- Exportação CSV -->
                <div class="col-12 col-md-6">
                    <div class="card p-4 h-100">
                        <h5 class="mb-3"><i class="fa-solid fa-file-csv me-2 text-secondary"></i>Exportar CSV</h5>
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
                </div>

                <!-- Exportação XML/XSD -->
                <div class="col-12 col-md-6">
                    <div class="card p-4 h-100 border-2" style="border-color:#8B0000 !important;">
                        <h5 class="mb-1"><i class="fa-solid fa-file-code me-2" style="color:#8B0000;"></i>Exportar XML (Interoperabilidade)</h5>
                        <p class="text-muted small mb-3">Gera um ficheiro XML das sessões de reabilitação, validado automaticamente contra o schema XSD (<code>schema/sessoes.xsd</code>).</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Filtrar por estado</label>
                            <select id="xmlEstado" class="form-select form-select-sm">
                                <option value="">Todos os estados</option>
                                <option value="concluida">Concluídas</option>
                                <option value="agendada">Agendadas</option>
                                <option value="em_curso">Em curso</option>
                                <option value="cancelada">Canceladas</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Filtrar por categoria</label>
                            <select id="xmlCategoria" class="form-select form-select-sm">
                                <option value="">Todas as categorias</option>
                                <option value="jogo">Jogo</option>
                                <option value="avaliacao_funcional">Avaliação Funcional</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button onclick="exportarXML(false)" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                                <i class="fa-solid fa-eye me-1"></i>Ver XML
                            </button>
                            <button onclick="exportarXML(true)" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-download me-1"></i>Descarregar XML
                            </button>
                        </div>
                        <div id="xmlFeedback" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </main>
<script>
function exportarXML(download) {
    const estado    = document.getElementById('xmlEstado').value;
    const categoria = document.getElementById('xmlCategoria').value;
    const feedback  = document.getElementById('xmlFeedback');

    let url = '<?= APP_URL ?>/api/export/sessoes_xml.php?limite=200';
    if (estado)    url += '&estado='    + encodeURIComponent(estado);
    if (categoria) url += '&categoria=' + encodeURIComponent(categoria);
    if (download)  url += '&download=1';

    if (download) {
        window.location.href = url;
        return;
    }

    feedback.innerHTML = '<div class="text-muted small"><i class="fa-solid fa-spinner fa-spin me-1"></i>A gerar XML...</div>';

    fetch(url, { credentials: 'same-origin' })
        .then(r => {
            if (!r.ok) return r.json().then(j => { throw new Error(j.erro + ': ' + (j.detalhes || []).join('; ')); });
            return r.text();
        })
        .then(xml => {
            feedback.innerHTML = '<div class="alert alert-success py-2 small mb-0"><i class="fa-solid fa-circle-check me-1"></i>XML válido — <strong>' +
                (xml.match(/<sessao /g) || []).length + ' sessão(ões)</strong> exportadas e validadas contra o XSD.</div>';
        })
        .catch(err => {
            feedback.innerHTML = '<div class="alert alert-danger py-2 small mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i>' + err.message + '</div>';
        });
}
</script>
