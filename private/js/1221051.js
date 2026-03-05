document.addEventListener("DOMContentLoaded", () => {
  const tipoSelect = document.getElementById("tipoProfissional");
  const campoEspecialidade = document.getElementById("campoEspecialidade");

  if (tipoSelect && campoEspecialidade) {
    tipoSelect.addEventListener("change", () => {
      if (tipoSelect.value === "medico") {
        campoEspecialidade.style.display = "block";
      } else {
        campoEspecialidade.style.display = "none";
        document.getElementById("especialidade").value = "";
      }
    });

    if (tipoSelect.value === "medico") {
      campoEspecialidade.style.display = "block";
    } else {
      campoEspecialidade.style.display = "none";
    }
  }
});

// Reagentes e Consumíveis
document.addEventListener("DOMContentLoaded", () => {
  const filtroInput = document.getElementById("filtroReagente");
  const tabela = document.getElementById("tabelaReagentes");
  const corpo = tabela?.getElementsByTagName("tbody")[0];

  if (!corpo) return;

  const atualizarEstados = () => {
    const linhas = corpo.getElementsByTagName("tr");

    for (let linha of linhas) {
      const qtd = parseInt(linha.cells[1].textContent.trim());
      const estadoCell = linha.cells[3];

      // Limpa classes anteriores
      linha.classList.remove("bg-success-subtle", "bg-warning-subtle", "bg-danger-subtle");

      if (isNaN(qtd)) {
        estadoCell.textContent = "Erro";
        continue;
      }

      if (qtd >= 150) {
        estadoCell.innerHTML = '<span class="badge bg-success">OK</span>';
        linha.classList.add("bg-success-subtle");
      } else if (qtd >= 75) {
        estadoCell.innerHTML = '<span class="badge bg-warning text-dark">Baixo</span>';
        linha.classList.add("bg-warning-subtle");
      } else {
        estadoCell.innerHTML = '<span class="badge bg-danger">Reposição urgente</span>';
        linha.classList.add("bg-danger-subtle");
      }
    }
  };
  atualizarEstados();
});

document.addEventListener("DOMContentLoaded", () => {
  const filtroInput = document.getElementById("filtroFaturacao");
  const tabela = document.getElementById("tabelaFaturacao");
  const corpo = tabela?.getElementsByTagName("tbody")[0];

  if (!corpo) return;

  const atualizarEstadoFaturas = () => {
    const linhas = corpo.getElementsByTagName("tr");

    for (let linha of linhas) {
      const estadoCell = linha.cells[8];
      const estadoTexto = estadoCell.textContent.trim().toLowerCase();

      // Limpa classes anteriores
      linha.classList.remove("bg-success-subtle", "bg-warning-subtle", "bg-danger-subtle");

      // Limpa e volta a escrever o badge, aplica cor na linha
      if (estadoTexto === "pago") {
        estadoCell.innerHTML = '<span class="badge bg-success">Pago</span>';
        linha.classList.add("bg-success-subtle");
      } else if (estadoTexto === "pendente") {
        estadoCell.innerHTML = '<span class="badge bg-warning text-dark">Pendente</span>';
        linha.classList.add("bg-warning-subtle");
      } else {
        estadoCell.innerHTML = '<span class="badge bg-danger">Cancelado</span>';
        linha.classList.add("bg-danger-subtle");
      }
    }
  };

  atualizarEstadoFaturas();
});

// Esconde botão "Ver Fatura" se não for estado "Pago"
function faturacaoOcultarVerFatura() {
  const tabela = document.getElementById("tabelaFaturacao");
  const corpo = tabela?.getElementsByTagName("tbody")[0];

  if (!corpo) return;

  const linhas = corpo.getElementsByTagName("tr");

  for (let linha of linhas) {
    const estadoTexto = linha.cells[8]?.textContent.trim().toLowerCase();
    const botaoVer = linha.querySelector(".btn-ver-fatura");

    if (estadoTexto !== "Pago" && botaoVer) {
      botaoVer.style.display = "none";
    }
  }
}

// Faturação
function guardarFatura(botao) {
  const linha = botao.closest("tr");
  const dados = {
    utente: linha.cells[0].innerText,
    NIF: linha.cells[1].innerText,
    seguradora: linha.cells[2].innerText,
    exame: linha.cells[3].innerText,
    testeEspecial: linha.cells[4].innerText,
    valor: linha.cells[5].innerText,
    data: linha.cells[6].innerText,
    metodo: linha.cells[7].innerText,
    estado: linha.cells[8].innerText
  };
  localStorage.setItem("faturaVisualizada", JSON.stringify(dados));
}

document.addEventListener("DOMContentLoaded", () => {
  const fatura = JSON.parse(localStorage.getItem("faturaVisualizada"));
  if (!fatura) return;

  document.getElementById("fatura-utente").textContent = fatura.utente;
  document.getElementById("fatura-NIF").textContent = fatura.NIF;
  document.getElementById("fatura-seguradora").textContent = fatura.seguradora;
  document.getElementById("fatura-exame").textContent = fatura.exame;
  document.getElementById("fatura-testeEspecial").textContent = fatura.testeEspecial;
  document.getElementById("fatura-valor").textContent = fatura.valor;
  document.getElementById("fatura-data").textContent = fatura.data;
  document.getElementById("fatura-metodo").textContent = fatura.metodo;
});

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("#tabelaFaturacao tbody tr").forEach((linha) => {
    const estado = linha.cells[8]?.innerText?.trim();
    const botaoVer = linha.querySelector(".btn-ver-fatura");

    if (estado !== "Pago" && botaoVer) {
      botaoVer.style.display = "none";
    }
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const fatura = JSON.parse(localStorage.getItem("faturaVisualizada"));
  const utenteEl = document.getElementById("fatura-utente");
  const btnExportar = document.getElementById("btnExportarFatura");

  // Só executa se estivermos na página da fatura
  if (fatura && utenteEl) {
    document.getElementById("fatura-utente").textContent = fatura.utente;
    document.getElementById("fatura-NIF").textContent = fatura.NIF;
    document.getElementById("fatura-seguradora").textContent = fatura.seguradora;
    document.getElementById("fatura-exame").textContent = fatura.exame;
    document.getElementById("fatura-testeEspecial").textContent = fatura.testeEspecial;
    document.getElementById("fatura-valor").textContent = fatura.valor;
    document.getElementById("fatura-data").textContent = fatura.data;
    document.getElementById("fatura-metodo").textContent = fatura.metodo;
  }

  if (btnExportar) {
    btnExportar.addEventListener("click", () => {
      if (!fatura) return;
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      doc.setFont("Helvetica", "bold");
      doc.setFontSize(18);
      doc.text("Fatura - HealthTech Clinical Labs", 20, 20);

      doc.setFontSize(12);
      doc.setFont("Helvetica", "normal");

      let y = 40;
      doc.text(`Utente: ${fatura.utente}`, 20, y);
      doc.text(`Seguradora/SNS: ${fatura.seguradora}`, 20, y += 10);
      doc.text(`Exame: ${fatura.exame}`, 20, y += 10);
      doc.text(`Teste Especial: ${fatura.testeEspecial}`, 20, y += 10);
      doc.text(`Valor: ${fatura.valor} €`, 20, y += 10);
      doc.text(`Data: ${fatura.data}`, 20, y += 10);
      doc.text(`Método de Pagamento: ${fatura.metodo}`, 20, y += 10);
      doc.text(`Estado: ${fatura.estado}`, 20, y += 10);

      doc.setFontSize(10);
      doc.text("Obrigado pela sua preferência.", 20, y + 30);

      doc.save("fatura.pdf");
    });
  }
});

document.addEventListener("DOMContentLoaded", () => {
  atualizarEstadoFaturas();
  faturacaoOcultarVerFatura();
});


document.addEventListener("DOMContentLoaded", () => {
  const subsistema = document.getElementById("subsistema");
  const requisitosSNS = document.getElementById("requisitosSNS");
  const pagamento = document.getElementById("pagamento");

  subsistema.addEventListener("change", () => {
    const valor = subsistema.value;

    if (valor === "SNS") {
      requisitosSNS.classList.remove("d-none");
      atualizarPagamento(["Subsistema"]);
    } else if (valor === "Privado" || valor === "Particular") {
      requisitosSNS.classList.add("d-none");
      atualizarPagamento(["Dinheiro", "Cartão bancário", "MbWay", "Cheque"]);
    } else {
      requisitosSNS.classList.add("d-none");
      atualizarPagamento(["Dinheiro", "Cartão bancário", "MbWay", "Cheque", "Subsistema"]);
    }
  });

  function atualizarPagamento(opcoes) {
    pagamento.innerHTML = '<option value="">Escolha...</option>';
    opcoes.forEach(opcao => {
      const opt = document.createElement("option");
      opt.value = opcao;
      opt.textContent = opcao;
      pagamento.appendChild(opt);
    });
  }
});

window.descarregarPDF = async function () {
  const { jsPDF } = window.jspdf;
  const texto = document.getElementById("instrucoes").innerText.trim();

  if (!texto || texto === "------") {
    alert("Nenhuma instrução disponível para descarregar.");
    return;
  }

  const doc = new jsPDF();
  doc.setFont("Helvetica", "normal");
  doc.setFontSize(12);
  doc.text("Instruções para o exame/teste:", 10, 20);
  doc.text(texto, 10, 30);

  doc.save("instrucoes_exame.pdf");
};

window.addEventListener("DOMContentLoaded", () => {
  const selectVerificacao = document.getElementById('verificacao');
  if (!selectVerificacao) return;

  selectVerificacao.addEventListener('change', function () {
    const exameRow = document.getElementById('exameRow');
    if (!exameRow) return;

    if (this.value === 'sim') {
      exameRow.style.display = 'flex';
      document.getElementById('exame').setAttribute('required', 'required');
    } else {
      exameRow.style.display = 'none';
      document.getElementById('exame').removeAttribute('required');
      document.getElementById('exame').value = '';
    }
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const chatbotBtn = document.getElementById('chatbot-button');
  const chatbotContainer = document.getElementById('chatbot');
  const chatMessages = document.getElementById('chat-messages');
  const closeBtn = document.getElementById('close-chat');
  const respostas = {
    1: "Nem todos os exames exigem jejum. Confirme nas <a href='previsao.html' target='_self'>intruções</a> do seu exame ou contacte o laboratório.",
    2: "Pode marcar <a href='agendamento.html' target='_self'>online</a>, por telefone ou presencialmente no nosso laboratório.",
    3: "Os <a href='marcacoes.html' target='_self'>resultados</a> normalmente ficam prontos em 24 a 72 horas. Para casos especiais, consulte o laboratório.",
    4: "Pode aceder ao <a href='marcacoes.html' target='_self'>portal</a> do paciente ou levantar presencialmente mediante identificação.",
    5: "Sim, contacte o laboratório ou clique <a href='previsao.html' target='_self'>aqui</a> para reagendar a sua marcação.",
    6: "Aceitamos dinheiro, cartão de débito/crédito, MBWay e subsistemas de saúde como o SNS e ADSE.",
    7: "Tem a possibilidade de agendar uma <a href='agendamento.html' target='_self'>marcacão prévia</a> para validar a adaptabilidade do exame que pretende fazer ao seu estado de saúde.",
    8: "Pode marcar <a href='agendamento.html' target='_self'>online</a>, por telefone ou presencialmente no nosso laboratório.",
    9: "Se o resultado for 'não adequado', fica impossibilitado de efetuar o agendamento do exame pretendido. Verifique <a href='previsao.html' target='_self'>aqui</a> o seu resultado e contacte o seu médico para avaliação e orientação clínica."
  };

  function addMessage(type, text) {
    const wrapper = document.createElement("div");

    if (type === "bot") {
      wrapper.classList.add("bot-message");

      const avatar = document.createElement("div");
      avatar.classList.add("avatar");
      avatar.textContent = "🤖";

      const bubble = document.createElement("div");
      bubble.classList.add("message");
      bubble.innerHTML = text;


      wrapper.appendChild(avatar);
      wrapper.appendChild(bubble);
    } else {
      wrapper.classList.add("user-message");
      wrapper.textContent = text;
    }

    chatMessages.appendChild(wrapper);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  if (chatbotBtn && chatbotContainer && chatMessages) {
    chatbotBtn.addEventListener('click', () => {
      chatbotContainer.classList.remove('d-none');
      chatbotBtn.style.display = 'none';
      chatMessages.innerHTML = '';
      addMessage('bot', 'Olá 👋 Sou o seu assistente virtual, como posso ajudar?');
    });

    closeBtn?.addEventListener('click', () => {
      chatbotContainer.classList.add('d-none');
      chatbotBtn.style.display = 'flex';
      chatMessages.innerHTML = '';
      addMessage('bot', 'Olá 👋 Sou o seu assistente virtual, como posso ajudar?');
    });

    document.querySelectorAll('.faq-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const pergunta = btn.innerText;
        const resposta = respostas[id];

        addMessage('user', pergunta);

        const typing = document.createElement('div');
        typing.classList.add('bot-message');
        typing.innerHTML = `
          <div class="avatar">🤖</div>
          <div class="message typing">...</div>
        `;
        chatMessages.appendChild(typing);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        setTimeout(() => {
          typing.remove();
          addMessage('bot', resposta);
        }, 1000);
      });
    });

    // Carrossel
    const scrollContainer = document.querySelector('.suggestions');
    const leftBtn = document.querySelector('.left-btn');
    const rightBtn = document.querySelector('.right-btn');
    const scrollAmount = 200;

    function updateArrowVisibility() {
      if (!scrollContainer || !leftBtn || !rightBtn) return;
      const scrollLeft = scrollContainer.scrollLeft;
      const maxScrollLeft = scrollContainer.scrollWidth - scrollContainer.clientWidth;

      leftBtn.style.visibility = scrollLeft <= 0 ? 'hidden' : 'visible';
      rightBtn.style.visibility = scrollLeft >= maxScrollLeft - 5 ? 'hidden' : 'visible';
    }

    leftBtn?.addEventListener('click', () => {
      scrollContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });

    rightBtn?.addEventListener('click', () => {
      scrollContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });

    scrollContainer?.addEventListener('scroll', updateArrowVisibility);
    updateArrowVisibility();
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const tipoSelect = document.getElementById('tipo');
  const marcarExames = document.getElementById('marcar-exame');
  const marcarTestes = document.getElementById('marcar-teste');

  tipoSelect.addEventListener('change', () => {
    const tipo = tipoSelect.value;
    if (tipo === 'exames') {
      marcarExames.style.display = 'block';
      marcarTestes.style.display = 'none';
    } else {
      marcarExames.style.display = 'none';
      marcarTestes.style.display = 'block';
    }
  })
});

document.addEventListener("DOMContentLoaded", () => {
  const selectVerificacao = document.getElementById('verificacao');
  const exameRow = document.getElementById('exameRow');
  const exameSelect = document.getElementById('exame');

  if (!selectVerificacao || !exameRow || !exameSelect) return;

  selectVerificacao.addEventListener('change', function () {
    if (this.value === 'sim') {
      exameRow.style.display = 'flex';
      exameSelect.setAttribute('required', 'required');
    } else {
      exameRow.style.display = 'none';
      exameSelect.removeAttribute('required');
      exameSelect.value = '';
    }
  });
});

window.descarregarPDF = async function () {
  const { jsPDF } = window.jspdf;

  const isExameVisible = document.getElementById("marcar-exame").style.display !== "none";
  const instrucoesEl = isExameVisible
    ? document.getElementById("instrucoes-exame")
    : document.getElementById("instrucoes-teste");

  if (!instrucoesEl) {
    alert("Elemento de instruções não encontrado.");
    return;
  }

  const texto = instrucoesEl.innerText.trim();

  if (!texto || texto === "------") {
    alert("Nenhuma instrução disponível para descarregar.");
    return;
  }

  const doc = new jsPDF();
  doc.setFont("Helvetica", "normal");
  doc.setFontSize(12);
  doc.text("Instruções para o exame/teste:", 10, 20);
  doc.text(texto, 10, 30);

  doc.save("instrucoes_exame_teste.pdf");
};

document.addEventListener("DOMContentLoaded", () => {
  const tipoSelect = document.getElementById('tipo');
  const historicoExames = document.getElementById('historico-exames');
  const historicoTestes = document.getElementById('historico-testes');

  tipoSelect.addEventListener('change', () => {
    const tipo = tipoSelect.value;
    if (tipo === 'exames') {
      historicoExames.style.display = 'block';
      historicoTestes.style.display = 'none';
    } else {
      historicoExames.style.display = 'none';
      historicoTestes.style.display = 'block';
    }
  })
});

document.addEventListener("DOMContentLoaded", () => {
  const tipoSelect = document.getElementById('tipo');
  const previsaoExames = document.getElementById('previsao_exame');
  const previsaoTestes = document.getElementById('previsao_teste');

  tipoSelect.addEventListener('change', () => {
    const tipo = tipoSelect.value;
    if (tipo === 'exames') {
      previsaoExames.style.display = 'block';
      previsaoTestes.style.display = 'none';
    } else {
      previsaoExames.style.display = 'none';
      previsaoTestes.style.display = 'block';
    }
  })
});
document.addEventListener("DOMContentLoaded", function () {
  let modoEdicao = null;
  let listaSeguros = [];

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

  const form = document.getElementById('form-seguro');
  const tabela = document.querySelector('#tabela-seguros tbody');

  function renderTabela() {
    tabela.innerHTML = '';
    listaSeguros.forEach((s, i) => {
      const linha = document.createElement('tr');
      linha.innerHTML = `
        <td class="td-icone"><i class="${s.icone} fa-2x"></i></td>
        <td class="td-titulo">${s.titulo}</td>
        <td class="td-descricao">${s.descricao}</td>
        <td class="text-center">
          <button class="btn btn-warning btn-sm editar" data-index="${i}"><i class="fa-solid fa-pen"></i></button>
          <button class="btn btn-danger btn-sm apagar" data-index="${i}"><i class="fa-solid fa-trash"></i></button>
        </td>`;
      tabela.appendChild(linha);
    });
    localStorage.setItem('seguros', JSON.stringify(listaSeguros));
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const icone = document.getElementById('icone').value;
    const titulo = document.getElementById('titulo').value;
    const descricao = document.getElementById('descricao').value;
    if (!modoEdicao) {
      const jaExiste = listaSeguros.some(s => s.titulo === titulo);
      if (jaExiste) {
        alert("Já existe um acordo com esse título.");
        return;
      }
    }

    if (modoEdicao !== null) {
      listaSeguros[modoEdicao] = { icone, titulo, descricao };
      modoEdicao = null;
    } else {
      listaSeguros.push({ icone, titulo, descricao });
    }

    form.reset();
    renderTabela();
  });

  tabela.addEventListener('click', function (e) {
    const btn = e.target.closest('button');
    const index = parseInt(btn.dataset.index);

    if (btn.classList.contains('editar')) {
      const s = listaSeguros[index];
      document.getElementById('icone').value = s.icone;
      document.getElementById('titulo').value = s.titulo;
      document.getElementById('descricao').value = s.descricao;
      modoEdicao = index;
    } else if (btn.classList.contains('apagar')) {
      listaSeguros.splice(index, 1);
      renderTabela();
    }
  });

  const dadosGuardados = localStorage.getItem('seguros');

  if (dadosGuardados) {
    listaSeguros = JSON.parse(dadosGuardados);
  } else {

    listaSeguros = [...segurosDefault];
    localStorage.setItem('seguros', JSON.stringify(listaSeguros));
  }


  renderTabela();
});
window.descarregarInstrucao = async function (btn) {
  const instrucao = btn.getAttribute('data-instrucao');
  const { jsPDF } = window.jspdf;

  if (!instrucao || instrucao === '------') {
    alert('Nenhuma instrução disponível.');
    return;
  }

  const doc = new jsPDF();
  doc.setFont("Helvetica", "normal");
  doc.setFontSize(12);

  doc.text("Instruções para o Exame/Teste:", 10, 20);

  doc.text(instrucao, 10, 30, {
    maxWidth: 180
  });

  doc.save("instrucao_exame_teste.pdf");
};

const elSemanal = document.getElementById('chartSemanal');
if (elSemanal) {
  new Chart(elSemanal, {
    type: 'bar',
    data: {
      labels: ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
      datasets: [{
        label: '2025-05',
        data: [120, 180, 140, 232],
        backgroundColor: '#4e73df'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: true, // Garante proporção
      aspectRatio: 1.6,
      plugins: { legend: { display: false } },
      layout: { padding: 10 }
    }
  })
};

const elForma = document.getElementById('chartForma');
if (elForma) {
  new Chart(elForma, {
    type: 'pie',
    data: {
      labels: ['Online', 'Telefone', 'Presencial'],
      datasets: [{
        data: [78, 54, 78],
        backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true, // Garante proporção
      aspectRatio: 1.6, // Reduz altura, mais largura
      plugins: {
        legend: {
          display: true,
          position: 'right', // Só no lado direito
          align: 'center',
          labels: {
            boxWidth: 20,
            padding: 15
          }
        }
      },
      layout: {
        padding: 10
      }
    }
  })
};

const elPosto = document.getElementById('chartPosto');
if (elPosto) {
  new Chart(elPosto, {
    type: 'doughnut',
    data: {
      labels: ['Unidade Central Porto', 'Unidade Gaia', 'Posto de Braga',
        'Clínica Matosinhos', 'Posto Aveiro', 'Unidade de Viana do Castelo',
        'Unidade Saúde Coimbra', 'Clínica do Norte – Famalicão',
        'Posto do São João', 'Posto de Colheita Lisboa - Oriente'],
      datasets: [{
        data: [130, 102, 130, 40, 78, 5, 63, 124, 100, 67],
        backgroundColor: [
          '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
          '#6f42c1', '#fd7e14', '#394047', '#17a2b8', '#ff6384'
        ]
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true, // Garante proporção
      aspectRatio: 1.6, // Reduz altura, mais largura
      plugins: {
        legend: {
          display: true,
          position: 'right', // Só no lado direito
          align: 'center',
          labels: {
            boxWidth: 20,
            padding: 15
          }
        }
      },
      layout: {
        padding: 10
      }
    }

  })
};

const receitaChart = new Chart(document.getElementById('chartReceita'), {
  type: 'bar',
  data: {
    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai'],
    datasets: [{
      label: 'Receita (€)',
      data: [4200, 5600, 3900, 6100, 7200],
      backgroundColor: '#20c997'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true }
    }
  }
});

const acessosChart = new Chart(document.getElementById('chartAcessos'), {
  type: 'line',
  data: {
    labels: ['01/05', '02/05', '03/05', '04/05', '05/05'],
    datasets: [
      {
        label: 'Medico 1',
        data: [3, 4, 2, 5, 3],
        borderColor: '#4e73df',
        fill: false
      },
      {
        label: 'Tecnico 1',
        data: [2, 3, 1, 4, 2],
        borderColor: '#e74a3b',
        fill: false
      },
      {
        label: 'Utente 1',
        data: [1, 4, 6, 4, 3],
        borderColor: '#36b9cc',
        fill: false
      },
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top' }
    },
    scales: {
      y: { beginAtZero: true }
    }
  }
});

async function gerarPDFRelatorioExemplo(botao) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();

  const linha = botao.closest('tr');
  const celulas = linha.querySelectorAll('td');

  const tipo = celulas[0].innerText;           // Tipo de Exame
  const data = celulas[1].innerText;           // Data do exame
  const hora = celulas[2].innerText;           // Hora
  const posto = celulas[3].innerText;          // Posto
  const dataHoje = new Date().toLocaleDateString('pt-PT');

  // Dados simulados
  const nomeUtente = "João Manuel da Silva";
  const dataNascimento = "1985-08-12";
  const numRegisto = "UT123456";
  const valorResultado = "12,5";
  const statusResultado = "Confirmado";
  const observacoes = "Hemoglobina normal";
  const laboratorioNome = "HealthTech Clinical Labs";
  const laboratorioMorada = "Av. Central 100, 4710-229 Braga";
  const responsavel = "Dr. João Almeida";

  // Cabeçalho
  doc.setFontSize(16);
  doc.text(laboratorioNome, 105, 20, { align: 'center' });
  doc.setFontSize(12);
  doc.text('Relatório de Resultados Laboratoriais', 105, 30, { align: 'center' });

  // Identificação do paciente
  doc.setFontSize(10);
  doc.text(`Nome do Utente: ${nomeUtente}`, 20, 45);
  doc.text(`Data de Nascimento: ${dataNascimento}`, 20, 52);
  doc.text(`Nº de Registo: ${numRegisto}`, 20, 59);

  // Identificação do laboratório
  doc.text(`Laboratório: ${laboratorioNome}`, 20, 70);
  doc.text(`Morada: ${laboratorioMorada}`, 20, 77);
  doc.text(`Responsável Técnico: ${responsavel}`, 20, 84);

  // Tipo e dados do exame
  doc.setFontSize(10);
  doc.text(`Tipo de Exame: ${tipo}`, 20, 94);
  doc.text(`Data do Exame: ${data}`, 20, 101);
  doc.text(`Hora: ${hora}`, 20, 108);
  doc.text(`Posto de Colheita: ${posto}`, 20, 115);

  // Linha separadora
  doc.line(20, 119, 190, 119);

  // Resultados laboratoriais
  doc.autoTable({
    startY: 124,
    head: [['Parâmetro', 'Valor Obtido', 'Referência', 'Unidade', 'Interpretação', 'Estado']],
    body: [
      [tipo, valorResultado, '70 - 99', 'mg/dL', observacoes, statusResultado]
    ],
    styles: { fontSize: 9 },
    headStyles: { fillColor: [41, 128, 185] }
  });

  // Observações laboratoriais
  const finalY = doc.lastAutoTable.finalY + 10;
  doc.setFontSize(10);
  doc.text('Observações laboratoriais:', 20, finalY);
  doc.setFontSize(9);
  doc.text('Sem interferências na amostra. Resultados dentro dos valores de referência.', 20, finalY + 8);

  // Rodapé e assinatura
  doc.setFontSize(10);
  doc.text(`Responsável Técnico: ${responsavel}`, 20, doc.internal.pageSize.height - 30);
  doc.text(`Assinatura Digital: ${responsavel}`, 20, doc.internal.pageSize.height - 24);
  doc.text(`Data de emissão: ${dataHoje}`, 20, doc.internal.pageSize.height - 18);

  doc.save(`Resultados_${tipo.replace(/\s/g, '_')}_${data}.pdf`);
}

async function gerarPDFRelatorio(botao) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();

  const linha = botao.closest('tr');
  const celulas = linha.querySelectorAll('td');

  const tipo = celulas[0].innerText;           // Tipo de Exame
  const data = celulas[1].innerText;           // Data do exame
  const hora = celulas[2].innerText;           // Hora
  const posto = celulas[3].innerText;          // Posto
  const dataHoje = new Date().toLocaleDateString('pt-PT');

  // Dados simulados
  const nomeUtente = "---------";
  const dataNascimento = "---------";;
  const numRegisto = "---------";
  const valorResultado = "---------";
  const statusResultado = "---------";
  const observacoes = "---------";
  const laboratorioNome = "HealthTech Clinical Labs";
  const laboratorioMorada = "---------";
  const responsavel = "---------";

  // Cabeçalho
  doc.setFontSize(16);
  doc.text(laboratorioNome, 105, 20, { align: 'center' });
  doc.setFontSize(12);
  doc.text('Relatório de Resultados Laboratoriais', 105, 30, { align: 'center' });

  // Identificação do paciente
  doc.setFontSize(10);
  doc.text(`Nome do Utente: ${nomeUtente}`, 20, 45);
  doc.text(`Data de Nascimento: ${dataNascimento}`, 20, 52);
  doc.text(`Nº de Registo: ${numRegisto}`, 20, 59);

  // Identificação do laboratório
  doc.text(`Laboratório: ${laboratorioNome}`, 20, 70);
  doc.text(`Morada: ${laboratorioMorada}`, 20, 77);
  doc.text(`Responsável Técnico: ${responsavel}`, 20, 84);

  // Tipo e dados do exame
  doc.setFontSize(10);
  doc.text(`Tipo de Exame: ${tipo}`, 20, 94);
  doc.text(`Data do Exame: ${data}`, 20, 101);
  doc.text(`Hora: ${hora}`, 20, 108);
  doc.text(`Posto de Colheita: ${posto}`, 20, 115);

  // Linha separadora
  doc.line(20, 119, 190, 119);

  // Resultados laboratoriais
  doc.autoTable({
    startY: 124,
    head: [['Parâmetro', 'Valor Obtido', 'Referência', 'Unidade', 'Interpretação', 'Estado']],
    body: [
      [tipo, valorResultado, '---------', '---------', observacoes, statusResultado]
    ],
    styles: { fontSize: 9 },
    headStyles: { fillColor: [41, 128, 185] }
  });

  // Observações laboratoriais
  const finalY = doc.lastAutoTable.finalY + 10;
  doc.setFontSize(10);
  doc.text('Observações laboratoriais:', 20, finalY);
  doc.setFontSize(9);
  doc.text('------------------------', 20, finalY + 8);

  // Rodapé e assinatura
  doc.setFontSize(10);
  doc.text(`Responsável Técnico: ${responsavel}`, 20, doc.internal.pageSize.height - 30);
  doc.text(`Assinatura Digital: ${responsavel}`, 20, doc.internal.pageSize.height - 24);
  doc.text(`Data de emissão: ${dataHoje}`, 20, doc.internal.pageSize.height - 18);

  doc.save(`Resultados_${tipo.replace(/\s/g, '_')}_${data}.pdf`);
}