<?php
// includes/sidebar_utente.php

function menuUtente(string $chave, string $pagina_ativa): string {
    return $chave === $pagina_ativa ? ' active' : '';
}
$pa = $pagina_ativa ?? '';
?>
        <nav class="sidebar d-print-none">
            <h2><i class="fa-solid fa-bars me-2"></i>Menu Principal</h2>

            <a href="<?= APP_URL ?>/private/utente/index_utente.php"
               class="nav-link<?= menuUtente('dashboard', $pa) ?>">
                <i class="fa-solid fa-chart-line me-2"></i>Página do Utente
            </a>
            <a href="<?= APP_URL ?>/private/utente/sessoes_agendadas.php"
               class="nav-link<?= menuUtente('sessoes', $pa) ?>">
                <i class="fa-solid fa-calendar-check me-2"></i>Sessões de Treino
            </a>
            <a href="<?= APP_URL ?>/private/utente/historico_sessoes.php"
               class="nav-link<?= menuUtente('historico', $pa) ?>">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>Histórico de Sessões
            </a>
            <a href="<?= APP_URL ?>/private/utente/jogos_reabilitacao.php"
               class="nav-link<?= menuUtente('jogos', $pa) ?>">
                <i class="fa-solid fa-gamepad me-2"></i>Jogos de Reabilitação
            </a>
            <a href="<?= APP_URL ?>/private/utente/mensagens.php"
               class="nav-link<?= menuUtente('mensagens', $pa) ?>">
                <i class="fa-regular fa-envelope me-2"></i>Mensagens
            </a>
            <a href="<?= APP_URL ?>/private/utente/pagamentos.php"
               class="nav-link<?= menuUtente('pagamentos', $pa) ?>">
                <i class="fa-solid fa-credit-card me-2"></i>Pagamentos
            </a>
            <a href="<?= APP_URL ?>/private/utente/perfil.php"
               class="nav-link<?= menuUtente('perfil', $pa) ?>">
                <i class="fa-regular fa-user me-2"></i>O Meu Perfil
            </a>
        </nav>
