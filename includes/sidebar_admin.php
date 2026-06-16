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
            <a href="<?= APP_URL ?>/private/admin/dispositivos/lista_dispositivos.php"
               class="nav-link<?= menuAdmin('dispositivos', $pa) ?>">
                <i class="fa-solid fa-microchip me-2"></i>Dispositivos
            </a>
            <a href="<?= APP_URL ?>/private/admin/faturacao/controlo_faturacao.php"
               class="nav-link<?= menuAdmin('faturacao', $pa) ?>">
                <i class="fa-solid fa-file-invoice-dollar me-2"></i>Faturação
            </a>
            <a href="<?= APP_URL ?>/private/admin/faturacao/tabela_precos.php"
               class="nav-link<?= menuAdmin('precos', $pa) ?>" style="padding-left:2.2rem;font-size:.88rem;">
                <i class="fa-solid fa-table me-2"></i>Tabela de Preços
            </a>
            <a href="<?= APP_URL ?>/private/admin/faturacao/relatorio_financeiro.php"
               class="nav-link<?= menuAdmin('relatorio_fin', $pa) ?>" style="padding-left:2.2rem;font-size:.88rem;">
                <i class="fa-solid fa-chart-bar me-2"></i>Relatório Financeiro
            </a>
            <a href="<?= APP_URL ?>/private/admin/relatorios/relatorios_sistema.php"
               class="nav-link<?= menuAdmin('relatorios', $pa) ?>">
                <i class="fa-regular fa-file-lines me-2"></i>Relatórios
            </a>
            <a href="<?= APP_URL ?>/private/admin/relatorios/atribuicoes.php"
               class="nav-link<?= menuAdmin('atribuicoes', $pa) ?>" style="padding-left:2.2rem;font-size:.88rem;">
                <i class="fa-solid fa-arrows-left-right-to-line me-2"></i>Atribuições
            </a>
            <a href="<?= APP_URL ?>/private/admin/fluxo_clinico/confirmar_tarefas.php"
               class="nav-link<?= menuAdmin('fluxo_clinico', $pa) ?>">
                <i class="fa-solid fa-clipboard-list me-2"></i>Fluxo Clínico
            </a>
            <a href="<?= APP_URL ?>/private/admin/seguranca/logs_acesso.php"
               class="nav-link<?= menuAdmin('seguranca', $pa) ?>">
                <i class="fa-solid fa-shield me-2"></i>Segurança
            </a>
            <a href="<?= APP_URL ?>/private/admin/seguranca/auditoria.php"
               class="nav-link<?= menuAdmin('auditoria', $pa) ?>" style="padding-left:2.2rem;font-size:.88rem;">
                <i class="fa-solid fa-clipboard-check me-2"></i>Auditoria (RGPD)
            </a>
            <a href="<?= APP_URL ?>/private/admin/mensagens/conversas.php"
               class="nav-link<?= menuAdmin('mensagens_int', $pa) ?>">
                <i class="fa-regular fa-comment-dots me-2"></i>Mensagens
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
            <hr class="my-3">
            <a href="<?= APP_URL ?>/private/admin/backoffice/backoffice_quem_somos.php"
               class="nav-link<?= menuAdmin('backoffice', $pa) ?>">
                <i class="fa-solid fa-globe me-2"></i>Backoffice
            </a>
        </nav>
