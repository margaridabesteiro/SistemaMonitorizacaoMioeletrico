<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// Redirecionar utilizadores já autenticados (exceto pré-visualização do backoffice)
if (!empty($_SESSION['utilizador_id']) && empty($_GET['preview'])) {
    $destinos = [
        'admin'   => APP_URL . '/private/admin/index_admin.php',
        'medico'  => APP_URL . '/private/medico/index_M.php',
        'tecnico' => APP_URL . '/private/tecnico/index_F.php',
        'utente'  => APP_URL . '/private/utente/index_utente.php',
    ];
    redirect($destinos[$_SESSION['perfil']] ?? APP_URL . '/private/login/login.php');
}

// Carregar todo o conteúdo editável da BD
$db = getDB();
$c  = $db->query('SELECT chave, valor FROM backoffice_conteudo')->fetchAll(PDO::FETCH_KEY_PAIR);

// Seguros — lista separada por vírgulas
$seguros_lista = array_filter(array_map('trim', explode(',', $c['seguros'] ?? 'Multicare,AdvanceCare,Médis,Allianz,SNS,Fidelidade,Lusitânia,Ageas,Real Vida,Generali')));

// Serviços
$servicos = [];
for ($i = 1; $i <= 6; $i++) {
    $servicos[] = [
        'titulo' => $c["servico_{$i}_titulo"] ?? '',
        'icone'  => $c["servico_{$i}_icone"]  ?? 'fa-solid fa-stethoscope',
        'desc'   => $c["servico_{$i}_desc"]   ?? '',
    ];
}

// Unidades
$unidades = [];
for ($i = 1; $i <= 4; $i++) {
    $unidades[] = [
        'nome'   => $c["unidade_{$i}_nome"]   ?? '',
        'morada' => $c["unidade_{$i}_morada"] ?? '',
        'tel'    => $c["unidade_{$i}_tel"]    ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink</title>
    <link rel="shortcut icon" href="public/assets/img/logo.jpg" type="image/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="public/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/common.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar-rehab">
        <div class="navbar-logo">
            <img src="public/assets/img/logo.jpg" alt="RehabLink Logo">
            <span>RehabLink</span>
        </div>
        <div class="navbar-links">
            <a href="#quem-somos">Quem Somos</a>
            <a href="#nossa-equipa">Equipa</a>
            <a href="#servicos">Serviços</a>
            <a href="#unidades">Unidades</a>
            <a href="#seguros">Acordos</a>
            <a href="#contacto">Contactos</a>
            <a href="public/login.php" class="navbar-btn">Área Privada</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-pattern"></div>
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fa-solid fa-wifi"></i>
                Telerreabilitação · Telemonitorização
            </span>
            <h1>
                <?= h($c['hero_titulo'] ?? 'Tecnologia e Humanização') ?>
                <span><?= h($c['hero_subtitulo'] ?? 'para a sua recuperação') ?></span>
            </h1>
            <p><?= h($c['hero_descricao'] ?? '') ?></p>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="number"><?= h($c['hero_stat1_num'] ?? '+5000') ?></span>
                    <span class="label"><?= h($c['hero_stat1_label'] ?? 'Utentes tratados') ?></span>
                </div>
                <div class="hero-stat">
                    <span class="number"><?= h($c['hero_stat2_num'] ?? '98%') ?></span>
                    <span class="label"><?= h($c['hero_stat2_label'] ?? 'Taxa de sucesso') ?></span>
                </div>
                <div class="hero-stat">
                    <span class="number"><?= h($c['hero_stat3_num'] ?? '4') ?></span>
                    <span class="label"><?= h($c['hero_stat3_label'] ?? 'Unidades') ?></span>
                </div>
            </div>
        </div>
        <div class="hero-image floating">
            <img src="public/assets/img/logo.jpg" alt="Reabilitação com tecnologia"
                 onerror="this.src='https://via.placeholder.com/600x400/667eea/ffffff?text=RehabLink'">
        </div>
    </section>

    <!-- Quem Somos -->
    <section id="quem-somos">
        <div class="section-title">
            <h2>Quem Somos</h2>
            <p>Conheça a RehabLink, a clínica que está a revolucionar a reabilitação em Portugal</p>
        </div>
        <div class="about-container">
            <div class="about-image">
                <img src="public/assets/img/healthtechlabssede.jpg" alt="Instalações RehabLink"
                     onerror="this.src='https://via.placeholder.com/600x400/667eea/ffffff?text=Nossas+Instalações'">
            </div>
            <div class="about-content">
                <h3><?= h($c['qs_h3'] ?? 'Inovação, qualidade e compromisso com a sua saúde') ?></h3>
                <p><?= h($c['qs_texto'] ?? '') ?></p>
                <div class="about-features">
                    <div class="about-feature"><i class="fa-solid fa-shield-alt"></i><span>Rigor científico</span></div>
                    <div class="about-feature"><i class="fa-solid fa-user-md"></i><span>Equipa qualificada</span></div>
                    <div class="about-feature"><i class="fa-solid fa-gamepad"></i><span>Jogos terapêuticos</span></div>
                    <div class="about-feature"><i class="fa-solid fa-chart-line"></i><span>Monitorização contínua</span></div>
                    <div class="about-feature"><i class="fa-solid fa-award"></i><span>Certificação ISO</span></div>
                    <div class="about-feature"><i class="fa-solid fa-lightbulb"></i><span>Inovação tecnológica</span></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Nossa Equipa Médica -->
    <section id="nossa-equipa">
        <div class="section-title">
            <h2>Equipa Médica</h2>
            <p>Profissionais especializados dedicados à sua recuperação</p>
        </div>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-image">
                    <img src="public/assets/img/medico.jpg" alt="Dr. António Ribeiro" onerror="this.src='https://via.placeholder.com/300x400/667eea/ffffff?text=Dr.+António'">
                </div>
                <div class="team-info">
                    <h3>Dr. António Ribeiro <a href="mailto:geral@rehablink.pt" title="Enviar email" style="text-decoration:none;margin-left:4px;"><i class="fa-regular fa-envelope"></i></a></h3>
                    <p class="cargo">Fisiatria</p>
                    <p class="descricao">Especialista em Medicina Física e Reabilitação com 15 anos de experiência em lesões desportivas.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image">
                    <img src="public/assets/img/medico4.jpg" alt="Dra. Marta Fernandes" onerror="this.src='https://via.placeholder.com/300x400/667eea/ffffff?text=Dra.+Marta'">
                </div>
                <div class="team-info">
                    <h3>Dra. Marta Fernandes <a href="mailto:geral@rehablink.pt" title="Enviar email" style="text-decoration:none;margin-left:4px;"><i class="fa-regular fa-envelope"></i></a></h3>
                    <p class="cargo">Fisioterapia Neurológica</p>
                    <p class="descricao">Especialista em reabilitação neurológica, AVC e lesões medulares. Doutorada em Neurociências.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image">
                    <img src="public/assets/img/medico2.jpg" alt="Dr. Ricardo Silva" onerror="this.src='https://via.placeholder.com/300x400/667eea/ffffff?text=Dr.+Ricardo'">
                </div>
                <div class="team-info">
                    <h3>Dr. Ricardo Silva <a href="mailto:geral@rehablink.pt" title="Enviar email" style="text-decoration:none;margin-left:4px;"><i class="fa-regular fa-envelope"></i></a></h3>
                    <p class="cargo">Fisioterapia Ortopédica</p>
                    <p class="descricao">Especialista em reabilitação ortopédica e pós-cirúrgica, com formação internacional em terapia manual.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image">
                    <img src="public/assets/img/medico3.jpg" alt="Dra. Ana Almeida" onerror="this.src='https://via.placeholder.com/300x400/667eea/ffffff?text=Dra.+Ana'">
                </div>
                <div class="team-info">
                    <h3>Dra. Ana Almeida <a href="mailto:geral@rehablink.pt" title="Enviar email" style="text-decoration:none;margin-left:4px;"><i class="fa-regular fa-envelope"></i></a></h3>
                    <p class="cargo">Fisioterapia Respiratória</p>
                    <p class="descricao">Especialista em reabilitação respiratória e doenças crónicas, com experiência em cuidados intensivos.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image">
                    <img src="public/assets/img/medico5.jpg" alt="Dr. João Lopes" onerror="this.src='https://via.placeholder.com/300x400/667eea/ffffff?text=Dr.+João'">
                </div>
                <div class="team-info">
                    <h3>Dr. João Lopes <a href="mailto:geral@rehablink.pt" title="Enviar email" style="text-decoration:none;margin-left:4px;"><i class="fa-regular fa-envelope"></i></a></h3>
                    <p class="cargo">Fisioterapia Desportiva</p>
                    <p class="descricao">Especialista em reabilitação desportiva e prevenção de lesões, trabalhou com atletas de alta competição.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Equipa Técnica -->
    <section class="tech-team-section">
        <div class="section-title">
            <h2>Equipa Técnica</h2>
            <p>Profissionais qualificados para garantir o melhor acompanhamento</p>
        </div>
        <div class="tech-team-grid">
            <div class="tech-card">
                <span class="certificado-badge"><i class="fas fa-certificate"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnica1.png" alt="Ana Silva" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=AS'"></div>
                <h4>Ana Silva</h4><p>Técnica de Reabilitação</p><p class="laboratorio">Unidade Central</p>
            </div>
            <div class="tech-card">
                <span class="certificado-badge"><i class="fas fa-certificate"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnico1.jpeg" alt="Bruno Ferreira" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=BF'"></div>
                <h4>Bruno Ferreira</h4><p>Técnico de Reabilitação</p><p class="laboratorio">Unidade Norte</p>
            </div>
            <div class="tech-card">
                <span class="certificado-badge"><i class="fas fa-certificate"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnica2.webp" alt="Carla Santos" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=CS'"></div>
                <h4>Carla Santos</h4><p>Técnica de Reabilitação</p><p class="laboratorio">Unidade Sul</p>
            </div>
            <div class="tech-card">
                <span class="nao-certificado-badge"><i class="fas fa-times-circle"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnico2.jpg" alt="Daniel Costa" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=DC'"></div>
                <h4>Daniel Costa</h4><p>Auxiliar Reabilitação</p><p class="laboratorio">Unidade Central</p>
            </div>
            <div class="tech-card">
                <span class="certificado-badge"><i class="fas fa-certificate"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnica3.jpg" alt="Eduarda Martins" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=EM'"></div>
                <h4>Eduarda Martins</h4><p>Técnica de Reabilitação</p><p class="laboratorio">Unidade Coimbra</p>
            </div>
            <div class="tech-card">
                <span class="nao-certificado-badge"><i class="fas fa-times-circle"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnico3.avif" alt="Filipe Gomes" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=FG'"></div>
                <h4>Filipe Gomes</h4><p>Técnico Reabilitação</p><p class="laboratorio">Unidade Lisboa</p>
            </div>
            <div class="tech-card">
                <span class="certificado-badge"><i class="fas fa-certificate"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnica4.jpg" alt="Gabriela Rocha" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=GR'"></div>
                <h4>Gabriela Rocha</h4><p>Técnica Reabilitação</p><p class="laboratorio">Unidade Central</p>
            </div>
            <div class="tech-card">
                <span class="nao-certificado-badge"><i class="fas fa-times-circle"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnico4.jpg" alt="Hugo Pereira" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=HP'"></div>
                <h4>Hugo Pereira</h4><p>Auxiliar Reabilitação</p><p class="laboratorio">Unidade Norte</p>
            </div>
            <div class="tech-card">
                <span class="certificado-badge"><i class="fas fa-certificate"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnica5.jpg" alt="Inês Almeida" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=IA'"></div>
                <h4>Inês Almeida</h4><p>Técnica Reabilitação</p><p class="laboratorio">Unidade Algarve</p>
            </div>
            <div class="tech-card">
                <span class="nao-certificado-badge"><i class="fas fa-times-circle"></i></span>
                <div class="tech-image"><img src="public/assets/img/tecnico5.webp" alt="João Rodrigues" onerror="this.src='https://via.placeholder.com/120x120/667eea/ffffff?text=JR'"></div>
                <h4>João Rodrigues</h4><p>Técnico Reabilitação</p><p class="laboratorio">Unidade Central</p>
            </div>
        </div>
    </section>

    <!-- Serviços -->
    <section id="servicos">
        <div class="section-title">
            <h2>Os Nossos Serviços</h2>
            <p>Soluções completas para a sua reabilitação</p>
        </div>
        <div class="services-grid">
            <?php foreach ($servicos as $s): if (!$s['titulo']) continue; ?>
            <div class="service-card">
                <div class="service-icon">
                    <i class="<?= h($s['icone']) ?>"></i>
                </div>
                <h3><?= h($s['titulo']) ?></h3>
                <p><?= h($s['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Unidades -->
    <section id="unidades" class="units-section">
        <div class="section-title">
            <h2>As Nossas Unidades</h2>
            <p>Espalhadas por todo o país para estar sempre perto de si</p>
        </div>
        <div class="units-grid">
            <?php foreach ($unidades as $u): if (!$u['nome']) continue; ?>
            <div class="unit-card">
                <div class="unit-icon"><i class="fa-solid fa-building"></i></div>
                <h3><?= h($u['nome']) ?></h3>
                <p><?= h($u['morada']) ?></p>
                <p class="unit-phone"><i class="fa-solid fa-phone me-2"></i><?= h($u['tel']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Acordos e Seguros -->
    <section id="seguros" class="partners-section">
        <div class="section-title">
            <h2>Acordos e Seguros</h2>
            <p>Trabalhamos com as principais seguradoras e entidades</p>
        </div>
        <div class="partners-grid">
            <?php foreach ($seguros_lista as $seguro): ?>
            <div class="partner-item"><i class="fa-solid fa-building"></i> <?= h($seguro) ?></div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Contacto -->
    <section id="contacto" class="contact-section">
        <div class="section-title">
            <h2>Contacte-nos</h2>
            <p>Estamos aqui para responder a todas as suas questões</p>
        </div>
        <div class="contact-container">
            <div class="contact-info">
                <h3>Informações de Contacto</h3>
                <div class="contact-detail">
                    <div class="contact-icon"><i class="fa-regular fa-map"></i></div>
                    <div class="contact-text">
                        <h4>Morada</h4>
                        <p><?= h($c['contacto_morada'] ?? '') ?></p>
                    </div>
                </div>
                <div class="contact-detail">
                    <div class="contact-icon"><i class="fa-regular fa-envelope"></i></div>
                    <div class="contact-text">
                        <h4>Email</h4>
                        <p><a href="mailto:geral@rehablink.pt" style="text-decoration:none;color:inherit;">geral@rehablink.pt</a></p>
                    </div>
                </div>
                <div class="contact-detail">
                    <div class="contact-icon"><i class="fa-regular fa-clock"></i></div>
                    <div class="contact-text">
                        <h4>Horário</h4>
                        <p><?= h($c['contacto_horario_semana'] ?? '2ª a 6ª: 8h - 20h') ?><br><?= h($c['contacto_horario_sabado'] ?? 'Sábado: 9h - 13h') ?></p>
                    </div>
                </div>
                <div class="contact-detail">
                    <div class="contact-icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="contact-text">
                        <h4>Telefone</h4>
                        <p><?= h($c['contacto_tel'] ?? '') ?><br><?= h($c['contacto_telemovel'] ?? '') ?></p>
                    </div>
                </div>
            </div>
            <div class="contact-form">
                <h3>Envie-nos uma mensagem</h3>
                <div id="contactSucesso" style="display:none;" class="alert alert-success">
                    <i class="fa-solid fa-circle-check me-2"></i>Mensagem enviada com sucesso! Entraremos em contacto brevemente.
                </div>
                <div id="contactErro" style="display:none;" class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><span id="contactErroTexto"></span>
                </div>
                <form id="contactForm">
                    <div class="form-group">
                        <input type="text" name="nome" class="form-control" placeholder="O seu nome" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" class="form-control" placeholder="O seu email" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="telefone" class="form-control" placeholder="O seu telefone">
                    </div>
                    <div class="form-group">
                        <select name="assunto" class="form-control">
                            <option value="">Assunto</option>
                            <option value="marcacao">Marcação de consulta</option>
                            <option value="informacao">Pedido de informação</option>
                            <option value="seguro">Questão sobre seguro</option>
                            <option value="outro">Outro assunto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <textarea name="mensagem" class="form-control" placeholder="A sua mensagem" required></textarea>
                    </div>
                    <button type="submit" class="submit-btn" id="btnEnviar">Enviar Mensagem</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>RehabLink</h4>
                <p>Inovação e humanização na reabilitação. A tecnologia ao serviço da sua recuperação.</p>
            </div>
            <div class="footer-col">
                <h4>Links Úteis</h4>
                <a href="#quem-somos">Quem Somos</a>
                <a href="#nossa-equipa">A Nossa Equipa</a>
                <a href="#servicos">Serviços</a>
                <a href="#unidades">Unidades</a>
                <a href="#seguros">Acordos e Seguros</a>
                <a href="#contacto">Contactos</a>
            </div>
            <div class="footer-col">
                <h4>Horário</h4>
                <p><?= h($c['contacto_horario_semana'] ?? '2ª a 6ª: 8h - 20h') ?></p>
                <p><?= h($c['contacto_horario_sabado'] ?? 'Sábado: 9h - 13h') ?></p>
                <p>Domingos e Feriados: Encerrado</p>
            </div>
            <div class="footer-col">
                <h4>Contactos</h4>
                <p><i class="fa-regular fa-envelope me-2"></i>geral@rehablink.pt</p>
                <p><i class="fa-solid fa-phone me-2"></i><?= h($c['contacto_tel'] ?? '21 345 6789') ?></p>
                <p><i class="fa-solid fa-mobile-screen me-2"></i><?= h($c['contacto_telemovel'] ?? '91 234 5678') ?></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>
                &copy; <?= date('Y') ?> RehabLink. Todos os direitos reservados. &nbsp;|&nbsp;
                <a href="public/privacidade.php" style="color:inherit;text-decoration:underline;">Política de Privacidade</a>
                &nbsp;|&nbsp; Sistema classificado como <strong>Telerreabilitação</strong> + <strong>Telemonitorização</strong>,
                suportando comunicação em tempo real e <em>store-and-forward</em>
            </p>
        </div>
    </footer>

    <script src="public/assets/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        document.getElementById('contactForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn     = document.getElementById('btnEnviar');
            const sucesso = document.getElementById('contactSucesso');
            const erro    = document.getElementById('contactErro');
            const erroTxt = document.getElementById('contactErroTexto');
            btn.disabled = true; btn.textContent = 'A enviar...';
            sucesso.style.display = 'none'; erro.style.display = 'none';
            fetch('api/contacto/enviar.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json())
                .then(data => {
                    if (data.sucesso) { sucesso.style.display = 'block'; this.reset(); }
                    else { erroTxt.textContent = data.erro || 'Erro ao enviar.'; erro.style.display = 'block'; }
                })
                .catch(() => { erroTxt.textContent = 'Erro de ligação.'; erro.style.display = 'block'; })
                .finally(() => { btn.disabled = false; btn.textContent = 'Enviar Mensagem'; });
        });

        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-rehab');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow  = '0 4px 20px rgba(0,0,0,0.1)';
            } else {
                navbar.style.background = '';
                navbar.style.boxShadow  = '';
            }
        });
    </script>
</body>
</html>
