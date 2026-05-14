<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Permissões'; $pagina_ativa = 'seguranca';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <h1 class="mb-4">Gestão de Permissões e Perfis</h1>
            <div class="card p-3 mb-4">
                <h5>Matriz de Permissões por Perfil</h5>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered text-center">
                        <thead class="table-light"><tr><th>Funcionalidade</th><th>Admin</th><th>Médico</th><th>Técnico</th><th>Utente</th></tr></thead>
                        <tbody>
                            <?php $m = [
                                ['Gestão de Utilizadores', 1,0,0,0],
                                ['Profissionais de Saúde', 1,0,0,0],
                                ['Dispositivos EMG',      1,0,1,0],
                                ['Faturação',             1,0,0,1],
                                ['Prescrições',           0,1,0,0],
                                ['Consultas',             0,1,0,0],
                                ['Sessões de Treino',     0,0,1,1],
                                ['Jogos Reabilitação',    0,0,1,1],
                                ['Relatórios',            1,1,1,0],
                                ['Mensagens',             0,1,1,1],
                                ['Logs de Acesso',        1,0,0,0],
                            ]; foreach($m as $r): ?>
                            <tr>
                                <td class="text-start"><?= $r[0] ?></td>
                                <?php for($i=1;$i<=4;$i++): ?><td><?= $r[$i] ? '<i class="fa-solid fa-check text-success"></i>' : '<i class="fa-solid fa-xmark text-danger"></i>' ?></td><?php endfor; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
