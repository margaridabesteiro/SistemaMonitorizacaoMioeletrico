<?php
// Exporta sessões de reabilitação em XML e valida contra o XSD (DOMDocument nativo)
// Acesso: admin e médico

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

requirePerfil('admin', 'medico');

$db = getDB();

// Filtros opcionais via GET
$estado    = $_GET['estado']    ?? null;
$categoria = $_GET['categoria'] ?? null;
$limite    = min((int)($_GET['limite'] ?? 100), 500);

$where  = [];
$params = [];

if ($estado && in_array($estado, ['agendada', 'em_curso', 'concluida', 'cancelada'], true)) {
    $where[]  = 's.estado = ?';
    $params[] = $estado;
}
if ($categoria && in_array($categoria, ['jogo', 'avaliacao_funcional'], true)) {
    $where[]  = 's.categoria = ?';
    $params[] = $categoria;
}

$clausula_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$params[] = $limite;

$stmt = $db->prepare("
    SELECT
        s.id,
        s.data_hora,
        s.categoria,
        s.modalidade,
        s.duracao_min,
        s.estado,
        u_ut.nome            AS utente_nome,
        ut.categoria_clinica,
        ut.membro_afetado,
        ut.fase_tratamento,
        u_tec.nome           AS tecnico_nome,
        pr.especialidade     AS tecnico_especialidade,
        j.nome               AS jogo_nome,
        ms.score_jogo,
        ms.percentagem_final,
        ms.passou_nivel,
        ms.tendencia,
        ms.n_tentativas
    FROM sessoes s
    JOIN utentes ut         ON ut.id  = s.utente_id
    JOIN utilizadores u_ut  ON u_ut.id = ut.utilizador_id
    JOIN profissionais pr    ON pr.id  = s.tecnico_id
    JOIN utilizadores u_tec ON u_tec.id = pr.utilizador_id
    LEFT JOIN jogos j           ON j.id  = s.jogo_id
    LEFT JOIN metricas_sessao ms ON ms.sessao_id = s.id
    $clausula_where
    ORDER BY s.data_hora DESC
    LIMIT ?
");
$stmt->execute($params);
$sessoes = $stmt->fetchAll();

// -------------------------------------------------------
// Construir XML com DOMDocument
// -------------------------------------------------------
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

// Helper: cria elemento com texto escapado (seguro contra XSS/caracteres especiais)
$el = function (string $tag, string $valor) use ($doc): DOMElement {
    $node = $doc->createElement($tag);
    $node->appendChild($doc->createTextNode($valor));
    return $node;
};

$root = $doc->createElement('rehablink');
$root->setAttribute('exportado_em', date('Y-m-d\TH:i:s'));
$doc->appendChild($root);

$nodeSessoes = $doc->createElement('sessoes');
$nodeSessoes->setAttribute('total', (string) count($sessoes));
$root->appendChild($nodeSessoes);

foreach ($sessoes as $s) {
    $nodeSessao = $doc->createElement('sessao');
    $nodeSessao->setAttribute('id', (string) $s['id']);
    $nodeSessoes->appendChild($nodeSessao);

    $nodeSessao->appendChild($el('data_hora',  date('Y-m-d\TH:i:s', strtotime($s['data_hora']))));
    $nodeSessao->appendChild($el('categoria',  $s['categoria']));
    $nodeSessao->appendChild($el('modalidade', $s['modalidade']));

    if ($s['duracao_min'] !== null) {
        $nodeSessao->appendChild($el('duracao_min', (string) $s['duracao_min']));
    }

    $nodeSessao->appendChild($el('estado', $s['estado']));

    // Utente
    $nodeUtente = $doc->createElement('utente');
    $nodeUtente->appendChild($el('nome', $s['utente_nome']));
    if ($s['categoria_clinica'] !== null) {
        $nodeUtente->appendChild($el('categoria_clinica', $s['categoria_clinica']));
    }
    if ($s['membro_afetado'] !== null) {
        $nodeUtente->appendChild($el('membro_afetado', $s['membro_afetado']));
    }
    $nodeUtente->appendChild($el('fase_tratamento', $s['fase_tratamento']));
    $nodeSessao->appendChild($nodeUtente);

    // Técnico
    $nodeTecnico = $doc->createElement('tecnico');
    $nodeTecnico->appendChild($el('nome', $s['tecnico_nome']));
    if ($s['tecnico_especialidade'] !== null) {
        $nodeTecnico->appendChild($el('especialidade', $s['tecnico_especialidade']));
    }
    $nodeSessao->appendChild($nodeTecnico);

    // Jogo (opcional)
    if ($s['jogo_nome'] !== null) {
        $nodeSessao->appendChild($el('jogo', $s['jogo_nome']));
    }

    // Métricas (só existem se houver registo em metricas_sessao)
    if ($s['n_tentativas'] !== null) {
        $nodeMetricas = $doc->createElement('metricas');

        if ($s['score_jogo'] !== null) {
            $nodeMetricas->appendChild($el('score', (string) $s['score_jogo']));
        }
        if ($s['percentagem_final'] !== null) {
            $nodeMetricas->appendChild($el('percentagem_final', number_format((float) $s['percentagem_final'], 2, '.', '')));
        }
        $nodeMetricas->appendChild($el('passou_nivel', $s['passou_nivel'] ? 'true' : 'false'));
        if ($s['tendencia'] !== null) {
            $nodeMetricas->appendChild($el('tendencia', $s['tendencia']));
        }
        $nodeMetricas->appendChild($el('n_tentativas', (string) $s['n_tentativas']));

        $nodeSessao->appendChild($nodeMetricas);
    }
}

$xmlString = $doc->saveXML();

// -------------------------------------------------------
// Validar XML gerado contra o XSD
// -------------------------------------------------------
$xsdPath = __DIR__ . '/../../schema/sessoes.xsd';

libxml_use_internal_errors(true);

$validador = new DOMDocument();
$validador->loadXML($xmlString);

if (!$validador->schemaValidate($xsdPath)) {
    $erros = libxml_get_errors();
    libxml_clear_errors();

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $mensagens = array_map(fn(LibXMLError $e): string => trim($e->message), $erros);
    echo json_encode([
        'erro'     => 'XML gerado não é válido segundo o schema XSD',
        'detalhes' => $mensagens,
    ]);
    exit;
}

libxml_clear_errors();

// -------------------------------------------------------
// Devolver XML (download ou inline)
// -------------------------------------------------------
header('Content-Type: application/xml; charset=utf-8');

if (isset($_GET['download'])) {
    $filename = 'rehablink_sessoes_' . date('Ymd_His') . '.xml';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}

echo $xmlString;
