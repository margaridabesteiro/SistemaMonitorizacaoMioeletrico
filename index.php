<?php
// index.php — página pública de entrada (landing page)
// Redireciona utilizadores já autenticados para a sua área

require_once __DIR__ . '/config/app.php';

if (!empty($_SESSION['utilizador_id'])) {
    $destinos = [
        'admin'   => APP_URL . '/private/admin/index_admin.php',
        'medico'  => APP_URL . '/private/medico/index_M.php',
        'tecnico' => APP_URL . '/private/tecnico/index_F.php',
        'utente'  => APP_URL . '/private/utente/index_utente.php',
    ];
    redirect($destinos[$_SESSION['perfil']] ?? APP_URL . '/private/login/login.php');
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
            <a href="private/login/login.php" class="navbar-btn">Área Privada</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-pattern"></div>
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fa-regular fa-hand"></i>
                Inovação em Reabilitação
            </span>

            <h1>
                Tecnologia e Humanização
                <span>para a sua recuperação</span>
            </h1>

            <p>
                Na RehabLink, combinamos fisioterapia tradicional com jogos de reabilitação e monitorização contínua para resultados mais rápidos e eficazes.
            </p>

            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="number">+5000</span>
                    <span class="label">Utentes tratados</span>
                </div>

                <div class="hero-stat">
                    <span class="number">98%</span>
                    <span class="label">Taxa de sucesso</span>
                </div>

                <div class="hero-stat">
                    <span class="number">15</span>
                    <span class="label">Unidades</span>
                </div>
            </div>
        </div>

        <div class="hero-image floating">
            <img src="public/assets/img/logo.jpg"
                alt="Reabilitação com tecnologia">
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
                <img src="public/assets/img/healthtechlabssede.jpg"
                    alt="Instalações RehabLink">
            </div>

            <div class="about-content">
                <h3>Inovação, qualidade e compromisso com a sua saúde</h3>

                <p>
                    A RehabLink nasceu da convicção de que a reabilitação pode ser mais eficaz quando aliada à tecnologia.
                    Desde 2020, temos ajudado milhares de utentes a recuperar a sua qualidade de vida através de métodos inovadores e personalizados.
                </p>

                <div class="about-features">

                    <div class="about-feature">
                        <i class="fa-solid fa-shield-alt"></i>
                        <span>Rigor científico</span>
                    </div>

                    <div class="about-feature">
                        <i class="fa-solid fa-user-md"></i>
                        <span>Equipa qualificada</span>
                    </div>

                    <div class="about-feature">
                        <i class="fa-solid fa-gamepad"></i>
                        <span>Jogos terapêuticos</span>
                    </div>

                    <div class="about-feature">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Monitorização contínua</span>
                    </div>

                    <div class="about-feature">
                        <i class="fa-solid fa-award"></i>
                        <span>Certificação ISO</span>
                    </div>

                    <div class="about-feature">
                        <i class="fa-solid fa-lightbulb"></i>
                        <span>Inovação tecnológica</span>
                    </div>

                </div>
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

            <div class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-dumbbell"></i>
                </div>

                <h3>Fisioterapia Tradicional</h3>

                <p>
                    Sessões personalizadas com fisioterapeutas especializados para recuperação de lesões e melhoria da mobilidade.
                </p>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-gamepad"></i>
                </div>

                <h3>Jogos de Reabilitação</h3>

                <p>
                    Terapia gamificada com jogos interativos que tornam o processo de recuperação mais envolvente e motivador.
                </p>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <h3>Monitorização Contínua</h3>

                <p>
                    Acompanhamento remoto do seu progresso com relatórios detalhados para si e para o seu fisioterapeuta.
                </p>
            </div>

        </div>
    </section>

    <!-- Unidades -->
    <section id="unidades" class="units-section">
        <div class="section-title">
            <h2>As Nossas Unidades</h2>
            <p>Espalhadas por todo o país para estar sempre perto de si</p>
        </div>

        <div class="units-grid">

            <div class="unit-card">
                <div class="unit-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <h3>Unidade Central</h3>

                <p>
                    Av. da República, 1000<br>
                    Lisboa
                </p>

                <p class="unit-phone">
                    <i class="fa-solid fa-phone me-2"></i>
                    21 345 6789
                </p>
            </div>

            <div class="unit-card">
                <div class="unit-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <h3>Unidade Norte</h3>

                <p>
                    Rua de Santa Catarina, 500<br>
                    Porto
                </p>

                <p class="unit-phone">
                    <i class="fa-solid fa-phone me-2"></i>
                    22 456 7890
                </p>
            </div>

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

                    <div class="contact-icon">
                        <i class="fa-regular fa-map"></i>
                    </div>

                    <div class="contact-text">
                        <h4>Morada</h4>

                        <p>
                            Av. da República, 1000<br>
                            1050-100 Lisboa
                        </p>
                    </div>

                </div>

                <div class="contact-detail">

                    <div class="contact-icon">
                        <i class="fa-regular fa-envelope"></i>
                    </div>

                    <div class="contact-text">
                        <h4>Email</h4>

                        <p>
                            geral@rehablink.pt<br>
                            apoio.cliente@rehablink.pt
                        </p>
                    </div>

                </div>

            </div>

            <div class="contact-form">

                <h3>Envie-nos uma mensagem</h3>

                <form id="contactForm">

                    <div class="form-group">
                        <input type="text"
                            class="form-control"
                            placeholder="O seu nome"
                            required>
                    </div>

                    <div class="form-group">
                        <input type="email"
                            class="form-control"
                            placeholder="O seu email"
                            required>
                    </div>

                    <div class="form-group">
                        <textarea class="form-control"
                            placeholder="A sua mensagem"
                            required></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        Enviar Mensagem
                    </button>

                </form>

            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">

        <div class="footer-grid">

            <div class="footer-col">

                <h4>RehabLink</h4>

                <p>
                    Inovação e humanização na reabilitação.
                    A tecnologia ao serviço da sua recuperação.
                </p>

            </div>

            <div class="footer-col">

                <h4>Links Úteis</h4>

                <a href="#quem-somos">Quem Somos</a>
                <a href="#nossa-equipa">A Nossa Equipa</a>
                <a href="#servicos">Serviços</a>
                <a href="#unidades">Unidades</a>
                <a href="#contacto">Contactos</a>

            </div>

        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 RehabLink. Todos os direitos reservados.</p>
        </div>

    </footer>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {

                e.preventDefault();

                const target = document.querySelector(
                    this.getAttribute('href')
                );

                if (target) {

                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                }

            });
        });

        document.getElementById('contactForm')?.addEventListener('submit', function(e) {

            e.preventDefault();

            alert('Mensagem enviada com sucesso!');

            this.reset();

        });

        window.addEventListener('scroll', function() {

            const navbar = document.querySelector('.navbar-rehab');

            if (window.scrollY > 100) {

                navbar.style.background = 'rgba(255,255,255,0.98)';
                navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';

            } else {

                navbar.style.background = 'rgba(255,255,255,0.95)';
                navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.1)';

            }

        });
    </script>

    <script src="public/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>

</html>