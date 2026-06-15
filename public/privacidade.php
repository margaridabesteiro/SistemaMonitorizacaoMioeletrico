<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade — RehabLink</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/public/assets/img/logo.jpg" type="image/jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/common.css">
    <style>
        body { background: #f8f9fa; }
        .priv-header { background: #764ba2; color: #fff; padding: 48px 0 32px; }
        .priv-body   { max-width: 820px; margin: 0 auto; padding: 40px 20px 80px; }
        h2 { color: #764ba2; font-size: 1.15rem; margin-top: 2rem; }
        .badge-rgpd  { background: #764ba2; color: #fff; font-size: .75rem; padding: 3px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="priv-header text-center">
        <h1 class="fw-bold fs-3"><i class="fa-solid fa-shield-halved me-2"></i>Política de Privacidade</h1>
        <p class="mb-0 opacity-75">RehabLink · Sistema de Telerreabilitação</p>
        <p class="small opacity-60 mt-1">Última atualização: <?= date('d/m/Y') ?></p>
    </div>

    <div class="priv-body">
        <div class="alert alert-warning d-flex gap-2 align-items-start">
            <i class="fa-solid fa-scale-balanced fa-lg mt-1"></i>
            <div>
                <strong>Base legal (RGPD)</strong> — O tratamento dos dados de saúde nesta plataforma é realizado ao abrigo do
                <strong>Art.&nbsp;9.º, n.º&nbsp;2, al.&nbsp;h)</strong> do Regulamento (UE) 2016/679 (RGPD):
                <em>«tratamento necessário para efeitos de medicina preventiva ou do trabalho, diagnóstico médico, prestação de cuidados de saúde»</em>.
            </div>
        </div>

        <h2><i class="fa-solid fa-building me-2"></i>1. Responsável pelo Tratamento</h2>
        <p>
            <strong>RehabLink, Lda.</strong> — Av. da República, 1000, 1050-100 Lisboa<br>
            Email de contacto DPO: <a href="mailto:privacidade@rehablink.pt">privacidade@rehablink.pt</a>
        </p>

        <h2><i class="fa-solid fa-database me-2"></i>2. Dados Recolhidos e Finalidade</h2>
        <table class="table table-sm table-bordered">
            <thead class="table-light"><tr><th>Categoria de dados</th><th>Finalidade</th><th>Base legal</th></tr></thead>
            <tbody>
                <tr><td>Dados de identificação (nome, email, NIF)</td><td>Gestão do utente e faturação</td><td>RGPD Art. 6(1)(b)</td></tr>
                <tr><td>Dados de saúde — sinal EMG, métricas de reabilitação</td><td>Telemonitorização e telerreabilitação</td><td>RGPD Art. 9(2)(h)</td></tr>
                <tr><td>Dados de sessões e prescrições</td><td>Acompanhamento clínico</td><td>RGPD Art. 9(2)(h)</td></tr>
                <tr><td>Logs de acesso (IP, user-agent)</td><td>Segurança e auditoria</td><td>RGPD Art. 6(1)(c) + (f)</td></tr>
            </tbody>
        </table>

        <h2><i class="fa-solid fa-lock me-2"></i>3. Segurança e Encriptação</h2>
        <p>A plataforma RehabLink implementa as seguintes medidas técnicas e organizativas (RGPD Art.&nbsp;32):</p>
        <ul>
            <li>Passwords armazenadas com <strong>bcrypt</strong> (fator de custo 12) — nunca em texto claro</li>
            <li>Comunicação cliente–servidor via <strong>HTTPS/TLS</strong></li>
            <li>Transmissão sensor–gateway via <strong>Bluetooth Low Energy (BLE)</strong> com emparelhamento seguro</li>
            <li>Transmissão gateway–servidor via <strong>Wi-Fi (WPA2) + HTTPS</strong></li>
            <li>Controlo de acesso por perfil (RBAC): admin, médico, técnico, utente</li>
            <li>Proteção CSRF em todos os formulários</li>
            <li>Registo de logs de acesso para auditoria</li>
        </ul>

        <h2><i class="fa-solid fa-clock me-2"></i>4. Retenção de Dados</h2>
        <ul>
            <li>Dados clínicos: <strong>mínimo 5 anos</strong> após o fim do tratamento (Lei de Bases da Saúde)</li>
            <li>Logs de acesso: <strong>12 meses</strong></li>
            <li>Dados de faturação: <strong>10 anos</strong> (obrigação fiscal)</li>
        </ul>

        <h2><i class="fa-solid fa-user-check me-2"></i>5. Direitos do Titular</h2>
        <p>Ao abrigo dos Arts.&nbsp;15.º a 22.º do RGPD, o titular tem direito a:</p>
        <ul>
            <li><strong>Acesso</strong> aos seus dados pessoais</li>
            <li><strong>Retificação</strong> de dados inexatos</li>
            <li><strong>Apagamento</strong> («direito a ser esquecido») — salvo obrigações legais de conservação</li>
            <li><strong>Limitação</strong> do tratamento</li>
            <li><strong>Portabilidade</strong> dos dados em formato estruturado</li>
            <li><strong>Oposição</strong> ao tratamento para fins não clínicos</li>
        </ul>
        <p>Para exercer estes direitos, contacte: <a href="mailto:privacidade@rehablink.pt">privacidade@rehablink.pt</a></p>

        <h2><i class="fa-solid fa-gavel me-2"></i>6. Autoridade de Controlo</h2>
        <p>
            O titular tem o direito de apresentar reclamação junto da
            <strong>Comissão Nacional de Proteção de Dados (CNPD)</strong> —
            <a href="https://www.cnpd.pt" target="_blank" rel="noopener">www.cnpd.pt</a>
        </p>

        <hr class="my-4">
        <a href="<?= APP_URL ?>/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Voltar à página principal
        </a>
    </div>
</body>
</html>
