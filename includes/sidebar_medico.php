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
            <a href="<?= APP_URL ?>/private/medico/mensagens/conversas.php"
               class="nav-link<?= menuMedico('mensagens', $pa) ?>">
                <i class="fa-regular fa-envelope me-2"></i>Mensagens
                <?php
                try {
                    $db_sb  = getDB();
                    $s_msg  = $db_sb->prepare("SELECT COUNT(*) FROM mensagens WHERE destinatario_id=? AND lida=0");
                    $s_msg->execute([$_SESSION['utilizador_id'] ?? 0]);
                    $n_msg  = (int)$s_msg->fetchColumn();
                    if ($n_msg > 0) echo '<span class="badge bg-danger ms-auto">' . $n_msg . '</span>';
                } catch (\Throwable $e) {}
                ?>
            </a>
            <a href="<?= APP_URL ?>/private/medico/prescricoes/lista_prescricoes.php"
               class="nav-link<?= menuMedico('prescricoes', $pa) ?>">
                <i class="fa-solid fa-file-medical me-2"></i>Tratamentos
            </a>
            <a href="<?= APP_URL ?>/private/medico/pacientes/gestaoUtente.php"
               class="nav-link<?= menuMedico('pacientes', $pa) ?>">
                <i class="fa-solid fa-users me-2"></i>Relatórios
            </a>
            <a href="<?= APP_URL ?>/private/medico/perfil.php"
               class="nav-link<?= menuMedico('perfil', $pa) ?>">
                <i class="fa-regular fa-user me-2"></i>Meu Perfil
            </a>
        </nav>
