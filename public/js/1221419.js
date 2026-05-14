document.addEventListener("DOMContentLoaded", () => {

  const medContainer = document.querySelector("#nossa-equipa .equipa-container");
  const medicosStorage = JSON.parse(localStorage.getItem("medicos") || "[]");
  const defaultMedicos = [
    { foto: "assets/img/medico.jpg", nome: "Dr. António Ribeiro", cargo: "Clínica Geral", descricao: "Especialista em Clínica Geral com foco em medicina preventiva, acompanhamento de doenças crónicas e promoção da saúde global." },
    { foto: "assets/img/medico4.jpg", nome: "Dra. Marta Fernandes", cargo: "Pediatria", descricao: "Médica pediatra dedicada ao acompanhamento do desenvolvimento infantil e à saúde preventiva desde o nascimento." },
    { foto: "assets/img/medico2.jpg", nome: "Dr. Ricardo Silva", cargo: "Endocrinologia", descricao: "Experiente em distúrbios hormonais, focado no tratamento de diabetes, obesidade e doenças da tiroide." },
    { foto: "assets/img/medico3.jpg", nome: "Dra. Ana Almeida", cargo: "Ginecologia", descricao: "Ginecologista com experiência em saúde da mulher, planeamento familiar, fertilidade e cuidados obstétricos completos." },
    { foto: "assets/img/medico5.jpg", nome: "Dr. João Lopes", cargo: "Cardiologia", descricao: "Cardiologista especializado em diagnóstico, reabilitação e prevenção de doenças cardiovasculares e hipertensão." }
  ];
  const medicos = medicosStorage.length ? medicosStorage : defaultMedicos;
  medContainer.innerHTML = "";
  medicos.forEach(m => {
    medContainer.innerHTML += `
      <div class="membro-equipa">
        <div class="membro-imagem"><img src="${m.foto}" alt="${m.nome}"></div>
        <div class="membro-info">
          <h3>${m.nome}</h3>
          <p class="cargo">${m.cargo}</p>
          <p class="descricao">${m.descricao}</p>
        </div>
      </div>`;
  });

  //
  // === TÉCNICOS (página pública) ===

  const tecContainer = document.querySelector(".equipa-tecnica-grid");
  const tecnicosStorage = JSON.parse(localStorage.getItem("tecnicos") || "[]");
  const defaultTecnicos = [
    { foto: "assets/img/tecnica1.png", nome: "Ana Silva", funcao: "Técnica Laboratorial", unidade: "Laboratório Central" },
    { foto: "assets/img/tecnico1.jpeg", nome: "Bruno Ferreira", funcao: "Técnico Laboratorial", unidade: "Laboratório Norte" },
    { foto: "assets/img/tecnica2.webp", nome: "Carla Santos", funcao: "Técnica de Laboratório", unidade: "Laboratório Sul" },
    { foto: "assets/img/tecnico2.jpg", nome: "Daniel Costa", funcao: "Técnico de Apoio Laboratorial", unidade: "Laboratório Central" },
    { foto: "assets/img/tecnica3.jpg", nome: "Eduarda Martins", funcao: "Técnica de Laboratório", unidade: "Laboratório Coimbra" },
    { foto: "assets/img/tecnico3.avif", nome: "Filipe Gomes", funcao: "Técnico Laboratorial", unidade: "Laboratório Lisboa" },
    { foto: "assets/img/tecnica4.jpg", nome: "Gabriela Rocha", funcao: "Técnica de Laboratório", unidade: "Laboratório Central" },
    { foto: "assets/img/tecnico4.jpg", nome: "Hugo Pereira", funcao: "Técnico de Apoio Laboratorial", unidade: "Unidade Norte" },
    { foto: "assets/img/tecnica5.jpg", nome: "Inês Almeida", funcao: "Técnica Laboratorial", unidade: "Laboratório Algarve" },
    { foto: "assets/img/tecnico5.webp", nome: "João Rodrigues", funcao: "Técnico Laboratorial", unidade: "Laboratório Central" }
  ];
  const tecnicos = tecnicosStorage.length && tecnicosStorage[0] && tecnicosStorage[0].unidade
    ? tecnicosStorage
    : defaultTecnicos;

  if (tecContainer) {
    tecContainer.innerHTML = "";
    tecnicos.forEach(t => {
      tecContainer.innerHTML += `
          <div class="tecnico-card">
            <div class="tecnico-imagem"><img src="${t.foto}" alt="${t.nome}"></div>
            <div class="tecnico-info">
              <h4>${t.nome}</h4>
              <p>${t.funcao}</p>
              <p class="laboratorio">${t.unidade}</p>
            </div>
          </div>`;
    });
  } else {
    console.warn("Não existe nenhum elemento .equipa-tecnica-grid na página!");
  }
});