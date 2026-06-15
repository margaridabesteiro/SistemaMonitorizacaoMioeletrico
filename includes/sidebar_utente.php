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
            <a href="<?= APP_URL ?>/private/utente/agenda.php"
               class="nav-link<?= menuUtente('agenda', $pa) ?>">
                <i class="fa-regular fa-calendar me-2"></i>Agenda
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
            <a href="<?= APP_URL ?>/private/utente/meu_progresso.php"
               class="nav-link<?= menuUtente('progresso', $pa) ?>">
                <i class="fa-solid fa-chart-line me-2"></i>O Meu Progresso
            </a>
            <a href="<?= APP_URL ?>/private/utente/mensagens_equipa.php"
               class="nav-link<?= menuUtente('mensagens_equipa', $pa) ?>">
                <i class="fa-regular fa-comments me-2"></i>Equipa de Tratamento
                <?php
                try {
                    $_db_sb_ut = getDB();
                    $_n_msg_ut = (int)$_db_sb_ut->query("SELECT COUNT(*) FROM mensagens WHERE destinatario_id=" . (int)($_SESSION['utilizador_id']??0) . " AND lida=0")->fetchColumn();
                    if ($_n_msg_ut > 0) echo '<span class="badge bg-danger ms-auto">' . $_n_msg_ut . '</span>';
                } catch (\Throwable $e) {}
                ?>
            </a>
            <a href="<?= APP_URL ?>/private/utente/mensagens.php"
               class="nav-link<?= menuUtente('mensagens', $pa) ?>">
                <i class="fa-regular fa-envelope me-2"></i>Mensagens
            </a>
            <a href="<?= APP_URL ?>/private/utente/exames.php"
               class="nav-link<?= menuUtente('exames', $pa) ?>">
                <i class="fa-solid fa-flask me-2"></i>Os Meus Exames
            </a>
            <a href="<?= APP_URL ?>/private/utente/medicacao.php"
               class="nav-link<?= menuUtente('medicacao', $pa) ?>">
                <i class="fa-solid fa-pills me-2"></i>Medicação
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
