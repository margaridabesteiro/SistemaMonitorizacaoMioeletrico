<?php
// includes/sidebar_admin.php
// Sidebar reutilizável para área administrativa
// Variável esperada: $pagina_ativa (string) — identifica o item de menu ativo

// Função auxiliar local: retorna 'active' se o link for o ativo
function menuAdmin(string $chave, string $pagina_ativa): string {
    return $chave === $pagina_ativa ? ' active' : '';
}
$pa = $pagina_ativa ?? '';
?>
        <nav class="sidebar">
            <h2><i class="fa-solid fa-bars me-2"></i>Menu Administrativo</h2>

            <a href="<?= APP_URL ?>/private/admin/index_admin.php"
               class="nav-link<?= menuAdmin('dashboard', $pa) ?>">
                <i class="fa-solid fa-chart-line me-2"></i>Dashboard
            </a>
            <a href="<?= APP_URL ?>/private/admin/utilizadores/lista_utilizadores.php"
               class="nav-link<?= menuAdmin('utilizadores', $pa) ?>">
                <i class="fa-solid fa-users me-2"></i>Utilizadores
            </a>
            <a href="<?= APP_URL ?>/private/admin/profissionais_saude/gestao_PS.php"
               class="nav-link<?= menuAdmin('profissionais', $pa) ?>">
                <i class="fa-solid fa-user-doctor me-2"></i>Profissionais Saúde
            </a>
            <a href="<?= APP_URL ?>/private/admin/dispositivos/lista_dispositivos.php"
               class="nav-link<?= menuAdmin('dispositivos', $pa) ?>">
                <i class="fa-solid fa-microchip me-2"></i>Dispositivos
            </a>
            <a href="<?= APP_URL ?>/private/admin/faturacao/controlo_faturacao.php"
               class="nav-link<?= menuAdmin('faturacao', $pa) ?>">
                <i class="fa-solid fa-file-invoice-dollar me-2"></i>Faturação
            </a>
            <a href="<?= APP_URL ?>/private/admin/relatorios/relatorios_sistema.php"
               class="nav-link<?= menuAdmin('relatorios', $pa) ?>">
                <i class="fa-regular fa-file-lines me-2"></i>Relatórios
            </a>
            <a href="<?= APP_URL ?>/private/admin/seguranca/logs_acesso.php"
               class="nav-link<?= menuAdmin('seguranca', $pa) ?>">
                <i class="fa-solid fa-shield me-2"></i>Segurança
            </a>
            <a href="<?= APP_URL ?>/private/admin/configuracao/sistema.php"
               class="nav-link<?= menuAdmin('configuracao', $pa) ?>">
                <i class="fa-solid fa-gear me-2"></i>Configuração
            </a>
            <hr class="my-3">
            <a href="<?= APP_URL ?>/private/admin/backoffice/backoffice_quem_somos.php"
               class="nav-link<?= menuAdmin('backoffice', $pa) ?>">
                <i class="fa-solid fa-globe me-2"></i>Backoffice
            </a>
            <a href="<?= APP_URL ?>/private/admin/contactos/lista_contactos.php"
               class="nav-link<?= menuAdmin('contactos', $pa) ?>">
                <i class="fa-regular fa-envelope me-2"></i>Contactos
                <?php
                try {
                    $db_sb = getDB();
                    $n_sb  = (int)$db_sb->query("SELECT COUNT(*) FROM contactos WHERE lida=0")->fetchColumn();
                    if ($n_sb > 0) echo '<span class="badge bg-danger ms-auto">' . $n_sb . '</span>';
                } catch (\Throwable $e) {}
                ?>
            </a>
            <a href="<?= APP_URL ?>/private/admin/preferencias.php"
               class="nav-link<?= menuAdmin('preferencias', $pa) ?>">
                <i class="fa-solid fa-sliders me-2"></i>Preferências
            </a>
        </nav>
