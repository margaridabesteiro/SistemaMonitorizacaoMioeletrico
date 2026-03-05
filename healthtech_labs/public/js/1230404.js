// Mostrar serviços na página pública
function mostrarServicosPublico() {
  const servicos = JSON.parse(localStorage.getItem('servicos')) || [];
  const container = document.getElementById('servicos-publico');
  if (!container) return;

  container.innerHTML = servicos.map(s => `
    <div class="cartao-servico">
      <i class="${s.icone}"></i>
      <h3>${s.titulo}</h3>
      <p>${s.descricao}</p>
    </div>
  `).join('');
}

function mostrarServicosPublico() {
  const servicos = JSON.parse(localStorage.getItem('servicos')) || [];
  const container = document.getElementById('servicos-publico');
  if (!container) return;

  container.innerHTML = servicos.map(s => `
    <div class="cartao-servico">
      <i class="${s.icone}"></i>
      <h3>${s.titulo}</h3>
      <p>${s.descricao}</p>
    </div>
  `).join('');
}

const servicos = [
  {
    titulo: "Exames Cardiológicos",
    descricao: "ECG, Holter, MAPA e exames especializados para avaliação cardíaca.",
    icone: "fa-solid fa-heart-pulse"
  },
  {
    titulo: "Análises Clínicas",
    descricao: "Check-ups laboratoriais com resultados rápidos e confidenciais.",
    icone: "fa-solid fa-vial"
  },
  {
    titulo: "Testes Genéticos",
    descricao: "Descubra predisposições genéticas para doenças hereditárias.",
    icone: "fa-solid fa-dna"
  },
  {
    titulo: "Testes COVID-19",
    descricao: "Realizamos testes PCR, antigénio e serológicos com agendamento online.",
    icone: "fa-solid fa-virus-covid"
  },
  {
    titulo: "Exames Oftalmológicos",
    descricao: "Avaliação visual completa e rastreios de patologias oculares.",
    icone: "fa-solid fa-eye"
  },
  {
    titulo: "Função Respiratória",
    descricao: "Espirometria e exames para diagnóstico de doenças pulmonares.",
    icone: "fa-solid fa-lungs"
  },
  {
    titulo: "Realização de Testes de Saúde",
    descricao: "Inclui rastreios laboratoriais, análises específicas e exames regulares.",
    icone: "fa-solid fa-clipboard-check"
  },
  {
    titulo: "Exames de Medicina do Trabalho",
    descricao: "Aptidões, admissões e vigilância para empresas e trabalhadores.",
    icone: "fa-solid fa-briefcase-medical"
  },
  {
    titulo: "Saúde Sexual e Reprodutiva",
    descricao: "Rastreios de infeções, fertilidade e aconselhamento personalizado.",
    icone: "fa-solid fa-venus-mars"
  }
];
localStorage.setItem('servicos', JSON.stringify(servicos));


function mostrarUnidadesPublico() {
  const unidades = JSON.parse(localStorage.getItem('unidades')) || [];
  const container = document.getElementById('unidades-publico');
  if (!container) return;

  container.innerHTML = unidades.map(u => `
    <div class="unidade-item">
      <img src="${u.imagem}" alt="${u.nome}">
      <div class="morada-overlay">
        <p><strong>${u.nome}</strong><br>${u.morada}<br>${u.telefone}<br><a href="mailto:${u.email}">${u.email}</a></p>
      </div>
    </div>
  `).join('');
}

const unidades = [
  {
    nome: "HealthTech Solutions",
    morada: "Rua da Saúde 123, 4000-123 Porto",
    telefone: "+351 223 456 789",
    email: "central@lebioom.pt",
    imagem: "assets/img/unidade1.jpg"
  },
  {
    nome: "HealthTech Clinical Labs Norte",
    morada: "Avenida da Boavista 456, 4100-321 Porto",
    telefone: "+351 225 678 901",
    email: "norte@lebioom.pt",
    imagem: "assets/img/unidade2.jpg"
  },
  {
    nome: "HealthTech Clinical Lab Lisboa",
    morada: "Praça do Marquês de Pombal 10, 1250-001 Lisboa",
    telefone: "+351 213 456 789",
    email: "lisboa@lebioom.pt",
    imagem: "assets/img/unidade3.jpg"
  },
  {
    nome: "HealthTech Clinical Lab Coimbra",
    morada: "Rua da Sofia 78, 3000-123 Coimbra",
    telefone: "+351 239 123 456",
    email: "coimbra@lebioom.pt",
    imagem: "assets/img/unidade4.jpg"
  },
  {
    nome: "HealthTech Clinical Lab Algarve",
    morada: "Avenida Tomás Cabreira 12, 8500-321 Portimão",
    telefone: "+351 282 456 789",
    email: "algarve@lebioom.pt",
    imagem: "assets/img/unidade5.jpg"
  }
];
localStorage.setItem('unidades', JSON.stringify(unidades));


mostrarServicosPublico();
mostrarUnidadesPublico();



