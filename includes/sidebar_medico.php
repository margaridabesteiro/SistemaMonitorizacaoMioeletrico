<?php
// includes/sidebar_medico.php
// Sidebar reutilizável para área do médico

function menuMedico(string $chave, string $pagina_ativa): string {
    return $chave === $pagina_ativa ? ' active' : '';
}
$pa = $pagina_ativa ?? '';
?>
        <nav class="sidebar">
            <h2><i class="fa-solid fa-bars me-2"></i>Menu Clínico</h2>

            <a href="<?= APP_URL ?>/private/medico/index_M.php"
               class="nav-link<?= menuMedico('dashboard', $pa) ?>">
                <i class="fa-solid fa-chart-line me-2"></i>Dashboard
            </a>
            <a href="<?= APP_URL ?>/private/medico/consultas/consulta.php"
               class="nav-link<?= menuMedico('consultas', $pa) ?>">
                <i class="fa-regular fa-calendar-check me-2"></i>Consultas
            </a>
            <a href="<?= APP_URL ?>/private/medico/consultas/agenda.php"
               class="nav-link<?= menuMedico('agenda', $pa) ?>">
                <i class="fa-regular fa-calendar me-2"></i>Minha Agenda
            </a>
            <a href="<?= APP_URL ?>/private/medico/prescricoes/lista_prescricoes.php"
               class="nav-link<?= menuMedico('prescricoes', $pa) ?>">
                <i class="fa-solid fa-file-medical me-2"></i>Prescrições
            </a>
            <a href="<?= APP_URL ?>/private/medico/pacientes/gestaoUtente.php"
               class="nav-link<?= menuMedico('pacientes', $pa) ?>">
                <i class="fa-solid fa-users me-2"></i>Pacientes
            </a>
            <a href="<?= APP_URL ?>/private/medico/exames/exames_disponiveis.php"
               class="nav-link<?= menuMedico('exames', $pa) ?>">
                <i class="fa-solid fa-flask me-2"></i>Exames
            </a>
            <a href="<?= APP_URL ?>/private/medico/exames/auditoria_exames.php"
               class="nav-link<?= menuMedico('auditoria', $pa) ?>">
                <i class="fa-solid fa-clipboard-check me-2"></i>Auditoria
            </a>
        </nav>
