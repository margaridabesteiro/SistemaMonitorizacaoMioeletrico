document.addEventListener("DOMContentLoaded", function () {
  const container = document.querySelector("#seguros .seguros-container");

  const segurosDefault = [
    {
      icone: "fa-solid fa-notes-medical",
      titulo: "SNS",
      descricao: "Realizamos exames convencionados com o SNS, mediante prescrição médica e requisição válida."
    },
    {
      icone: "fa-solid fa-briefcase-medical",
      titulo: "ADSE",
      descricao: "Atendimento a beneficiários da ADSE com preços comparticipados. Basta apresentar o cartão ADSE."
    },
    {
      icone: "fa-solid fa-hand-holding-heart",
      titulo: "SAMS",
      descricao: "Colaboramos com SAMS Quadros e SAMS Norte. Exames cobertos mediante plano contratado."
    },
    {
      icone: "fa-solid fa-user-shield",
      titulo: "Seguradoras Privadas",
      descricao: "Protocolos com seguradoras como Médis, Advancecare, Multicare, Allianz, entre outras."
    }
  ];

  const seguros = localStorage.getItem("seguros");
  const lista = seguros ? JSON.parse(seguros) : segurosDefault;

  lista.forEach(s => {
    container.innerHTML += `
      <div class="seguro">
        <i class="${s.icone} fa-2x"></i>
        <h3>${s.titulo}</h3>
        <hr>
        <p>${s.descricao}</p>
      </div>`;
  });
});
