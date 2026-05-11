<?php
// includes/sidebar_tecnico.php

function menuTecnico(string $chave, string $pagina_ativa): string {
    return $chave === $pagina_ativa ? ' active' : '';
}
$pa = $pagina_ativa ?? '';
?>
        <nav class="sidebar">
            <h2><i class="fa-solid fa-bars me-2"></i>Menu Principal</h2>

            <a href="<?= APP_URL ?>/private/tecnico/index_F.php"
               class="nav-link<?= menuTecnico('dashboard', $pa) ?>">
                <i class="fa-solid fa-chart-line me-2"></i>Dashboard
            </a>
            <a href="<?= APP_URL ?>/private/tecnico/pacientes/lista_pacientes.php"
               class="nav-link<?= menuTecnico('pacientes', $pa) ?>">
                <i class="fa-solid fa-users me-2"></i>Pacientes
            </a>
            <a href="<?= APP_URL ?>/private/tecnico/sessoes/lista_sessoes.php"
               class="nav-link<?= menuTecnico('sessoes', $pa) ?>">
                <i class="fa-regular fa-calendar-check me-2"></i>Sessões de Treino
            </a>
            <a href="<?= APP_URL ?>/private/tecnico/analise/desempenho.php"
               class="nav-link<?= menuTecnico('analise', $pa) ?>">
                <i class="fa-solid fa-chart-bar me-2"></i>Análise de Desempenho
            </a>
            <a href="<?= APP_URL ?>/private/tecnico/mensagens/conversas.php"
               class="nav-link<?= menuTecnico('mensagens', $pa) ?>">
                <i class="fa-regular fa-envelope me-2"></i>Mensagens
            </a>
            <a href="<?= APP_URL ?>/private/tecnico/relatorios/gerar_relatorio.php"
               class="nav-link<?= menuTecnico('relatorios', $pa) ?>">
                <i class="fa-regular fa-file-lines me-2"></i>Relatórios
            </a>
            <a href="<?= APP_URL ?>/private/tecnico/config/calibracao.php"
               class="nav-link<?= menuTecnico('calibracao', $pa) ?>">
                <i class="fa-solid fa-sliders me-2"></i>Calibração
            </a>
            <hr class="my-3" style="border-color:#e2e8f0;">
            <a href="<?= APP_URL ?>/private/tecnico/config/preferencias.php"
               class="nav-link<?= menuTecnico('preferencias', $pa) ?>">
                <i class="fa-solid fa-gear me-2"></i>Preferências
            </a>
            <a href="<?= APP_URL ?>/private/tecnico/ajuda/faq.php"
               class="nav-link<?= menuTecnico('ajuda', $pa) ?>">
                <i class="fa-regular fa-circle-question me-2"></i>Ajuda
            </a>
        </nav>
