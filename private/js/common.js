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



document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formQuemSomos");
    const dados = JSON.parse(localStorage.getItem("quemSomos")) || {};

    if (dados.titulo) document.getElementById("titulo").value = dados.titulo;
    if (dados.subtitulo) document.getElementById("subtitulo").value = dados.subtitulo;
    if (dados.descricao) document.getElementById("descricao").value = dados.descricao;
    if (dados.imagemURL) document.getElementById("imagemURL").value = dados.imagemURL;
    if (dados.valores) document.getElementById("valores").value = dados.valores.join(";");
    if (dados.textoBotao) document.getElementById("textoBotao").value = dados.textoBotao;
    if (dados.linkBotao) document.getElementById("linkBotao").value = dados.linkBotao;

    form.addEventListener("submit", (e) => {
        e.preventDefault();
        const novosDados = {
            titulo: document.getElementById("titulo").value,
            subtitulo: document.getElementById("subtitulo").value,
            descricao: document.getElementById("descricao").value,
            imagemURL: document.getElementById("imagemURL").value,
            valores: document.getElementById("valores").value.split(";").map(v => v.trim()),
            textoBotao: document.getElementById("textoBotao").value,
            linkBotao: document.getElementById("linkBotao").value
        };
        localStorage.setItem("quemSomos", JSON.stringify(novosDados));
        alert("Alterações guardadas com sucesso.");
    });
});

//  Simulação de sessão (ID do médico logado)
sessionStorage.setItem("ID_Medico", "1");
const utentesSimulados = [
    {
        ID_Utente: "1",
        Nome: "João Manuel da Silva",
        Data_Nascimento: "1990-04-12",
        NIF: "123456789",
        Sexo: "M",
        Contacto: "913456789",
        Email: "joao.silva90@gmail.com",
        Morada: "Rua Augusta, 100, 1100-053 Lisboa",
        Numero_SNS: "123456789012",
        Subsistema_Saude: "SNS"
    },
    {
        ID_Utente: "2",
        Nome: "Ana Maria Costa",
        Data_Nascimento: "1987-11-30",
        NIF: "987654321",
        Sexo: "F",
        Contacto: "925432109",
        Email: "ana.costa87@hotmail.com",
        Morada: "Avenida da Liberdade, n°200, 4000-054 Porto",
        Numero_SNS: "234567890123",
        Subsistema_Saude: "ADSE"
    },
    {
        ID_Utente: "3",
        Nome: "Carlos Eduardo Mendes",
        Data_Nascimento: "1975-09-22",
        NIF: "456789123",
        Sexo: "M",
        Contacto: "912345678",
        Email: "carlos.mendes75@sapo.pt",
        Morada: "Praça da República, n°50, 4710-249 Braga",
        Numero_SNS: "345678901234",
        Subsistema_Saude: "SNS"
    },
    {
        ID_Utente: "4",
        Nome: "Filipa Joana Rocha",
        Data_Nascimento: "1995-06-18",
        NIF: "789123456",
        Sexo: "F",
        Contacto: "932456789",
        Email: "filipa.rocha95@gmail.com",
        Morada: "Rua da Sofia, n°25, 3000-389 Coimbra",
        Numero_SNS: "456789012345",
        Subsistema_Saude: "SAMS"
    },
    {
        ID_Utente: "5",
        Nome: "Ricardo Jorge Lopes",
        Data_Nascimento: "1982-02-07",
        NIF: "321654987",
        Sexo: "M",
        Contacto: "961234567",
        Email: "ricardo.lopes82@outlook.pt",
        Morada: "Rua da Liberdade, n°10, 8000-117 Faro",
        Numero_SNS: "567890123456",
        Subsistema_Saude: "SNS"
    }
];

const medicosSimulados = [
    { ID_Medico: "1", Nome: "Dr. António Ribeiro", Especialidade: "Clínica Geral", Instituicao: "Hospital de Santa Maria, Lisboa", Contacto: "913450001" },
    { ID_Medico: "2", Nome: "Dra. Marta Fernandes", Especialidade: "Pediatria", Instituicao: "Hospital de São João, Porto", Contacto: "926450002" },
    { ID_Medico: "3", Nome: "Dr. Ricardo Silva", Especialidade: "Endocrinologia", Instituicao: "Hospital Lusíadas, Braga", Contacto: "913450003" },
    { ID_Medico: "4", Nome: "Dra. Ana Almeida", Especialidade: "Ginecologia", Instituicao: "Hospital CUF Descobertas, Lisboa", Contacto: "932450004" },
    { ID_Medico: "5", Nome: "Dr. João Lopes", Especialidade: "Cardiologia", Instituicao: "Hospital da Luz, Lisboa", Contacto: "916450005" }
];

const examesSimulados = [
    { ID_Exame: "6", Nome: "Glicose", Grupo: "Bioquímica", Tipo: "Clínico", idInstrucao: "501" },
    { ID_Exame: "7", Nome: "Colesterol Total", Grupo: "Bioquímica", Tipo: "Clínico", idInstrucao: "503" },
    { ID_Exame: "8", Nome: "Hemograma Completo", Grupo: "Hematologia", Tipo: "Clínico", idInstrucao: "NULL" },
    { ID_Exame: "9", Nome: "Creatinina", Grupo: "Bioquímica", Tipo: "Clínico", idInstrucao: "515" },
    { ID_Exame: "10", Nome: "TSH (Hormona Tireoidiana)", Grupo: "Hormonal", Tipo: "Clínico", idInstrucao: "511" }
];

const instrucoesSimuladas = [
    { idInstrucao: "501", ID_Exame: "6" },
    { idInstrucao: "509", ID_Exame: "6" },
    { idInstrucao: "503", ID_Exame: "7" },
    { idInstrucao: "515", ID_Exame: "9" },
    { idInstrucao: "518", ID_Exame: "8" },
    { idInstrucao: "519", ID_Exame: "8" },
    { idInstrucao: "502", ID_Exame: "9" },
    { idInstrucao: "509", ID_Exame: "10" },
    { idInstrucao: "502", ID_Exame: "10" },
    { idInstrucao: "511", ID_Exame: "10" }
];

const instrucoesTexto = [
    { idInstrucao: "501", tipoInstrucao: "Realize jejum obrigatório de 4 horas antes da colheita" },
    { idInstrucao: "502", tipoInstrucao: "Realize jejum obrigatório de 8 horas antes da colheita" },
    { idInstrucao: "503", tipoInstrucao: "Não fazer uso de medicamentos no dia do exame" },
    { idInstrucao: "504", tipoInstrucao: "Evite esforços físicos antes da colheita" },
    { idInstrucao: "505", tipoInstrucao: "Informar o técnico sobre medicamentos em uso" },
    { idInstrucao: "509", tipoInstrucao: "Apenas pode realizar a colheita até às 10h00 da manhã" },
    { idInstrucao: "511", tipoInstrucao: "Colheita preferencialmente em jejum" },
    { idInstrucao: "515", tipoInstrucao: "Beber muita água antes do exame" },
    { idInstrucao: "518", tipoInstrucao: "Evitar consumo de carne 24h antes" },
    { idInstrucao: "519", tipoInstrucao: "Recolher urina das primeiras 24h" }
];

localStorage.setItem("colheitas", JSON.stringify([
    { ID_Colheita: "1", Data_Colheita: "2025-06-03", ID_Prescricao: "6", Condicoes_colheita_OK: "OK" },
    { ID_Colheita: "2", Data_Colheita: "2025-06-03", ID_Prescricao: "7", Condicoes_colheita_OK: "OK" },
    { ID_Colheita: "3", Data_Colheita: "2025-06-04", ID_Prescricao: "8", Condicoes_colheita_OK: "OK" },
    { ID_Colheita: "4", Data_Colheita: "2025-06-05", ID_Prescricao: "9", Condicoes_colheita_OK: "NOK" },
    { ID_Colheita: "5", Data_Colheita: "2025-06-05", ID_Prescricao: "10", Condicoes_colheita_OK: "OK" },
    { ID_Colheita: "6", Data_Colheita: "2025-06-06", ID_Prescricao: "11", Condicoes_colheita_OK: "OK" },
    { ID_Colheita: "7", Data_Colheita: "2025-06-06", ID_Prescricao: "12", Condicoes_colheita_OK: "OK" },
    { ID_Colheita: "8", Data_Colheita: "2025-06-06", ID_Prescricao: "13", Condicoes_colheita_OK: "OK" },
    { ID_Colheita: "9", Data_Colheita: "2025-06-07", ID_Prescricao: "14", Condicoes_colheita_OK: "OK" },
    { ID_Colheita: "10", Data_Colheita: "2025-06-08", ID_Prescricao: "15", Condicoes_colheita_OK: "OK" },
    { ID_Colheita: "11", Data_Colheita: "2025-06-08", ID_Prescricao: "16", Condicoes_colheita_OK: "NOK" },
    { ID_Colheita: "12", Data_Colheita: "2025-06-09", ID_Prescricao: "17", Condicoes_colheita_OK: "OK" }
]));
// Função para inicializar dados no localStorage 
function inicializarDados() {
    if (!localStorage.getItem('utentes')) localStorage.setItem('utentes', JSON.stringify(utentesSimulados));
    if (!localStorage.getItem('medicos')) localStorage.setItem('medicos', JSON.stringify(medicosSimulados));
    if (!localStorage.getItem('exames')) localStorage.setItem('exames', JSON.stringify(examesSimulados));
    if (!localStorage.getItem('instrucao_exame')) localStorage.setItem('instrucao_exame', JSON.stringify(instrucoesSimuladas));
    if (!localStorage.getItem('prescricao_exame')) localStorage.setItem('prescricao_exame', JSON.stringify([]));
    if (!localStorage.getItem('instrucao_completa')) {
        localStorage.setItem('instrucao_completa', JSON.stringify(instrucoesTexto));
    }
    if (!localStorage.getItem('prescricao')) {
        localStorage.setItem('prescricao', JSON.stringify([
            { ID_Prescricao: "6", ID_Utente: "1", ID_Medico: "1", Data_Prescricao: "2025-05-01", Tipo_Prescricao: "SNS", Num_Requisicao: "198456789012345", Cod_Acesso: "AOP103", Cod_Prestacao: "XYZ416", Observacoes: "Exames de rotina anual", Prioridade: "Normal", Data_Validade: "2025-06-01" },
            { ID_Prescricao: "7", ID_Utente: "2", ID_Medico: "2", Data_Prescricao: "2025-05-02", Tipo_Prescricao: "Privado", Num_Requisicao: "245789123456789", Cod_Acesso: "DMF274", Cod_Prestacao: "UVW617", Observacoes: "Check-up geral privado", Prioridade: "Normal", Data_Validade: "2025-06-02" },
            { ID_Prescricao: "8", ID_Utente: "3", ID_Medico: "3", Data_Prescricao: "2025-05-03", Tipo_Prescricao: "Particular", Num_Requisicao: "28754321987654", Cod_Acesso: "GYT645", Cod_Prestacao: "RST673", Observacoes: "Pré-operatório urgente", Prioridade: "Urgente", Data_Validade: "2025-05-17" },
            { ID_Prescricao: "9", ID_Utente: "4", ID_Medico: "4", Data_Prescricao: "2025-05-04", Tipo_Prescricao: "SNS", Num_Requisicao: "239876541231654", Cod_Acesso: "JPL456", Cod_Prestacao: "QP078", Observacoes: "Controlo de diabetes", Prioridade: "Normal", Data_Validade: "2025-06-04" },
            { ID_Prescricao: "10", ID_Utente: "5", ID_Medico: "5", Data_Prescricao: "2025-05-05", Tipo_Prescricao: "Privado", Num_Requisicao: "27896541212654987", Cod_Acesso: "MSO567", Cod_Prestacao: "NML190", Observacoes: "Controlo de colesterol", Prioridade: "Normal", Data_Validade: "2025-06-05" }
        ]));
    }
}
// Função que carrega utentes da página "Nova Prescrição"
function carregarUtentes() {
    const selectUtente = document.getElementById('ID_Utente');
    if (!selectUtente) return;

    selectUtente.innerHTML = '<option value="">Selecione o utente</option>';
    const utentes = JSON.parse(localStorage.getItem('utentes')) || [];
    utentes.forEach(utente => {
        selectUtente.innerHTML += `<option value="${utente.ID_Utente}">${utente.Nome}</option>`;
    });
}
// Função que popula a tabela de Listagem de Utentes
function carregarListaUtentes() {
    const tabelaBody = document.querySelector('#listaUtentes tbody');
    if (!tabelaBody) return;

    tabelaBody.innerHTML = '';

    const utentes = JSON.parse(localStorage.getItem('utentes')) || [];
    utentes.forEach(utente => {
        const subsLower = utente.Subsistema_Saude.toLowerCase();
        tabelaBody.innerHTML += `
      <tr data-subsistema="${subsLower}">
        <td>${utente.Nome}</td>
        <td>${utente.Data_Nascimento}</td>
        <td>${utente.NIF}</td>
        <td>${utente.Sexo}</td>
        <td>${utente.Contacto}</td>
        <td>${utente.Email}</td>
        <td>${utente.Morada}</td>
        <td>${utente.Numero_SNS}</td>
        <td>${utente.Subsistema_Saude}</td>
        <td class="text-center">
          <a href="../../utente/detalhes.html?idUtente=${utente.ID_Utente}" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-eye"></i>
          </a>
        </td>
      </tr>`;
    });
}
document.addEventListener("DOMContentLoaded", function () {
    const inputFiltro = document.getElementById("filtroNomeUtente");
    const linhas = document.querySelectorAll("table tbody tr");

    if (inputFiltro) {
        inputFiltro.addEventListener("keyup", function () {
            const termo = inputFiltro.value.toLowerCase();

            linhas.forEach((linha) => {
                const textoLinha = linha.textContent.toLowerCase();
                linha.style.display = textoLinha.includes(termo) ? "" : "none";
            });
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const inputTexto = document.getElementById("filtroTexto");
    const filtroTubo = document.getElementById("filtroTubo");
    const filtroMaterial = document.getElementById("filtroMaterial");
    // Só continua se pelo menos um dos filtros existir
    if (!inputTexto && !filtroTubo && !filtroMaterial) return;

    const linhas = document.querySelectorAll("table tbody tr");

    function aplicarFiltros() {
        const texto = inputTexto?.value.toLowerCase() || "";
        const tubo = filtroTubo?.value || "";
        const material = filtroMaterial?.value || "";

        linhas.forEach((linha) => {
            const textoLinha = linha.textContent.toLowerCase();
            const colTubo = linha.cells[3].textContent;
            const colMaterial = linha.cells[4].textContent;

            const correspondeTexto = textoLinha.includes(texto);
            const correspondeTubo = tubo === "" || colTubo === tubo;
            const correspondeMaterial = material === "" || colMaterial === material;

            linha.style.display = (correspondeTexto && correspondeTubo && correspondeMaterial) ? "" : "none";
        });
    }
    if (inputTexto) inputTexto.addEventListener("input", aplicarFiltros);
    if (filtroTubo) filtroTubo.addEventListener("change", aplicarFiltros);
    if (filtroMaterial) filtroMaterial.addEventListener("change", aplicarFiltros);
});
// Filtrar “Listagem de Utentes” por texto (nome, email, NIF etc.)
function configurarFiltroUtenteTexto() {
    const filtroTexto = document.getElementById('filtroUtentesTexto');
    if (!filtroTexto) return;

    filtroTexto.addEventListener('keyup', function () {
        const termo = this.value.toLowerCase();
        const linhas = document.querySelectorAll('#listaUtentes tbody tr');
        linhas.forEach(linha => {
            const textoLinha = linha.textContent.toLowerCase();
            linha.style.display = textoLinha.includes(termo) ? '' : 'none';
        });
    });
}
// Filtrar “Listagem de Utentes” por subsistema de saúde
function configurarFiltroUtenteSubsistema() {
    const filtroSubs = document.getElementById('filtroUtentesSubsistema');
    if (!filtroSubs) return;

    filtroSubs.addEventListener('change', function () {
        const selecionado = this.value.toLowerCase();
        const linhas = document.querySelectorAll('#listaUtentes tbody tr');
        linhas.forEach(linha => {
            const subs = linha.getAttribute('data-subsistema') || '';
            if (!selecionado) {
                // “Todos os subsistemas” → mostra tudo
                linha.style.display = '';
            } else {
                // compara valor selecionado exatamente
                linha.style.display = (subs === selecionado ? '' : 'none');
            }
        });
    });
}
// Função que carrega exames na página "Selecionar Exames"
function carregarExames() {
    const exames = JSON.parse(localStorage.getItem('exames')) || [];
    const container = document.getElementById("listaExames");
    if (!container) return;

    container.innerHTML = '';
    exames.forEach(exame => {
        container.innerHTML += `
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="${exame.ID_Exame}" id="exame${exame.ID_Exame}">
        <label class="form-check-label" for="exame${exame.ID_Exame}">${exame.Nome}</label>
      </div>`;
    });
}
// Função que carrega as informações básicas da prescrição na página "Selecionar Exames"
function carregarDetalhesPrescricao() {
    const idPrescricao = new URLSearchParams(window.location.search).get('idPrescricao');
    const prescricaoInfo = document.getElementById('prescricaoInfo');
    const idPrescricaoSpan = document.getElementById('idPrescricao');
    const nomeUtenteSpan = document.getElementById('nomeUtente');
    const dataPrescricaoSpan = document.getElementById('dataPrescricao');

    // Se faltar algum desses elementos, não executa
    if (!prescricaoInfo || !idPrescricaoSpan || !nomeUtenteSpan || !dataPrescricaoSpan) {
        console.log('Esta página talvez não seja "selecionar_exames.html" ou os elementos não existem.');
        return;
    }

    // Se não veio nada na URL
    if (!idPrescricao) {
        prescricaoInfo.innerHTML = `<div class="alert alert-danger">Erro: ID da prescrição não encontrado na URL.</div>`;
        return;
    }

    // Limpa qualquer conteúdo antigo
    prescricaoInfo.innerHTML = '';
    idPrescricaoSpan.textContent = '';
    nomeUtenteSpan.textContent = '';
    dataPrescricaoSpan.textContent = '';

    // Preenche o ID visível
    idPrescricaoSpan.textContent = idPrescricao;

    // Procura a prescrição no localStorage
    const prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
    const prescricao = prescricoes.find(p => p.ID_Prescricao === idPrescricao);

    if (prescricao) {
        const utentes = JSON.parse(localStorage.getItem('utentes')) || [];
        const utente = utentes.find(u => u.ID_Utente === prescricao.ID_Utente);
        const nomeUtente = utente ? utente.Nome : "Utente Desconhecido";
        nomeUtenteSpan.textContent = nomeUtente;
        dataPrescricaoSpan.textContent = prescricao.Data_Prescricao || "N/A";
    } else {
        prescricaoInfo.innerHTML = `<div class="alert alert-danger">Erro: Dados da prescrição não encontrados.</div>`;
    }
}
// Lógica de submissão de “Selecionar Exames”
function configurarSubmissaoExames() {
    const form = document.getElementById("formExames");
    const mensagem = document.getElementById("mensagem");
    const idPrescricao = new URLSearchParams(window.location.search).get('idPrescricao');

    if (!form || !idPrescricao) {
        if (mensagem) {
            mensagem.innerHTML = `<div class="alert alert-danger">Erro: ID da prescrição não encontrado.</div>`;
        }
        return;
    }

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const examesSelecionados = [];
        document.querySelectorAll('#listaExames input[type="checkbox"]:checked').forEach(cb => {
            examesSelecionados.push({
                ID_Exame: cb.value,
                ID_Prescricao: idPrescricao,
                Estado: "Prescrito"
            });
        });

        if (examesSelecionados.length === 0) {
            mensagem.innerHTML = `<div class="alert alert-warning">Selecione pelo menos um exame.</div>`;
            return;
        }

        // Guarda no localStorage
        let prescricaoExame = JSON.parse(localStorage.getItem('prescricao_exame')) || [];
        prescricaoExame = prescricaoExame.concat(examesSelecionados);
        localStorage.setItem('prescricao_exame', JSON.stringify(prescricaoExame));

        mensagem.innerHTML = `<div class="alert alert-success">Exames associados com sucesso!</div>`;
        setTimeout(() => {
            // Redireciona de volta à lista de prescrições
            window.location.href = "lista_prescricoes.html";
        }, 1500);
    });
}
// Lógica de criação de “Nova Prescrição”
function configurarFormularioPrescricao() {
    const formPrescricao = document.getElementById('formPrescricao');
    if (!formPrescricao) return;

    formPrescricao.addEventListener('submit', function (event) {
        event.preventDefault();

        const requiredFields = formPrescricao.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            alert('Por favor, preencha todos os campos obrigatórios.');
            return;
        }

        const idPrescricao = Date.now().toString();
        const prescricaoData = {
            ID_Prescricao: idPrescricao,
            ID_Utente: document.getElementById('ID_Utente').value,
            ID_Medico: sessionStorage.getItem('ID_Medico'),
            Data_Prescricao: document.getElementById('Data_Prescricao').value,
            Data_Validade: document.getElementById('Data_Validade').value,
            Tipo_Prescricao: document.getElementById('Tipo_Prescricao').value,
            Num_Requisicao: document.getElementById('Num_Requisicao').value,
            Cod_Acesso: document.getElementById('Cod_Acesso').value,
            Cod_Prestacao: document.getElementById('Cod_Prestacao').value,
            Prioridade: document.getElementById('Prioridade').value,
            Observacoes: document.getElementById('Observacoes').value
        };

        let prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
        prescricoes.push(prescricaoData);
        localStorage.setItem('prescricao', JSON.stringify(prescricoes));

        // Redireciona direto para “Selecionar Exames” (para esse ID)
        window.location.href = `selecionar_exames.html?idPrescricao=${idPrescricao}`;
    });
}
function carregarDetalhesPrescricaoDetalhes() {
    const idPrescricaoSpan = document.getElementById('idPrescricao');
    if (!idPrescricaoSpan) return;

    const prescricaoInfo = document.getElementById('prescricaoInfo');
    const nomeUtenteSpan = document.getElementById('nomeUtente');
    const nomeMedicoSpan = document.getElementById('nomeMedico');
    const dataPrescricaoSpan = document.getElementById('dataPrescricao');
    const dataValidadeSpan = document.getElementById('dataValidade');
    const tipoPrescricaoSpan = document.getElementById('tipoPrescricao');
    const numRequisicaoSpan = document.getElementById('numRequisicao');
    const codAcessoSpan = document.getElementById('codAcesso');
    const codPrestacaoSpan = document.getElementById('codPrestacao');
    const prioridadeSpan = document.getElementById('prioridade');
    const observacoesSpan = document.getElementById('observacoes');
    const listaExamesAssoc = document.getElementById('listaExamesAssociados');
    const detalhesContainer = document.getElementById('detalhesContainer');

    const idPrescricao = new URLSearchParams(window.location.search).get('idPrescricao');
    if (!idPrescricao) {
        prescricaoInfo.innerHTML = `<div class="alert alert-danger">Erro: ID da prescrição não foi passado na URL.</div>`;
        return;
    }

    const prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
    const prescricao = prescricoes.find(p => p.ID_Prescricao === idPrescricao);
    if (!prescricao) {
        prescricaoInfo.innerHTML = `<div class="alert alert-danger">Prescrição não encontrada.</div>`;
        return;
    }

    detalhesContainer.style.display = 'block';
    prescricaoInfo.innerHTML = '';

    idPrescricaoSpan.textContent = prescricao.ID_Prescricao;
    dataPrescricaoSpan.textContent = prescricao.Data_Prescricao || 'N/A';
    dataValidadeSpan.textContent = prescricao.Data_Validade || 'N/A';
    tipoPrescricaoSpan.textContent = prescricao.Tipo_Prescricao || 'N/A';
    numRequisicaoSpan.textContent = prescricao.Num_Requisicao || 'N/A';
    codAcessoSpan.textContent = prescricao.Cod_Acesso || 'N/A';
    codPrestacaoSpan.textContent = prescricao.Cod_Prestacao || 'N/A';
    prioridadeSpan.textContent = prescricao.Prioridade || 'N/A';
    observacoesSpan.textContent = prescricao.Observacoes || 'N/A';

    const utentes = JSON.parse(localStorage.getItem('utentes')) || [];
    const utente = utentes.find(u => u.ID_Utente === prescricao.ID_Utente);
    nomeUtenteSpan.textContent = utente ? utente.Nome : 'Desconhecido';

    const medicos = JSON.parse(localStorage.getItem('medicos')) || [];
    const medico = medicos.find(m => m.ID_Medico === prescricao.ID_Medico);
    nomeMedicoSpan.textContent = medico ? medico.Nome : 'Desconhecido';

    const examesAssoc = JSON.parse(localStorage.getItem('prescricao_exame')) || [];
    const listaAssoc = examesAssoc.filter(e => e.ID_Prescricao === idPrescricao);
    listaExamesAssoc.innerHTML = '';

    if (listaAssoc.length === 0) {
        listaExamesAssoc.innerHTML = `<p class="text-muted">Nenhum exame associado a esta prescrição.</p>`;
    } else {
        const exames = JSON.parse(localStorage.getItem('exames')) || [];
        const instrucaoExame = JSON.parse(localStorage.getItem('instrucao_exame')) || [];
        const instrucoesTexto = JSON.parse(localStorage.getItem('instrucao_completa')) || [];

        let htmlListaExames = `
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>ID Exame</th>
            <th>Nome do Exame</th>
            <th>Grupo</th>
            <th>Tipo</th>
            <th class="text-center">Estado</th>
            <th>Instruções</th>
          </tr>
        </thead>
        <tbody>`;

        listaAssoc.forEach(item => {
            const exameInfo = exames.find(x => x.ID_Exame === item.ID_Exame);
            const instrucoesAssoc = instrucaoExame
                .filter(ie => ie.ID_Exame === item.ID_Exame)
                .map(ie => {
                    const instr = instrucoesTexto.find(i => i.idInstrucao === ie.idInstrucao);
                    return instr ? instr.tipoInstrucao : null;
                })
                .filter(Boolean)
                .join("; ");

            htmlListaExames += `
        <tr>
          <td>${item.ID_Exame}</td>
          <td>${exameInfo ? exameInfo.Nome : 'Desconhecido'}</td>
          <td>${exameInfo ? exameInfo.Grupo : '-'}</td>
          <td>${exameInfo ? exameInfo.Tipo : '-'}</td>
          <td class="text-center">${item.Estado || '-'}</td>
          <td>${instrucoesAssoc || '-'}</td>
        </tr>`;
        });

        htmlListaExames += `</tbody></table>`;
        listaExamesAssoc.innerHTML = htmlListaExames;
    }
}
// Filtrar “Listagem de Prescrições” por texto (toda a linha)
function configurarFiltroTexto() {
    const filtroTexto = document.getElementById('filtroTexto');
    if (!filtroTexto) return;

    filtroTexto.addEventListener('keyup', function () {
        const termo = this.value.toLowerCase();
        const linhas = document.querySelectorAll('#listaPrescricoes tbody tr');
        linhas.forEach(linha => {
            const textoLinha = linha.textContent.toLowerCase();
            linha.style.display = textoLinha.includes(termo) ? '' : 'none';
        });
    });
}
// Filtrar “Listagem de Prescrições” por tipo de prescrição
function configurarFiltroSubsistema() {
    const filtroSubsistema = document.getElementById('filtroSubsistema');
    if (!filtroSubsistema) return;

    filtroSubsistema.addEventListener('change', function () {
        const selecionado = this.value.toLowerCase();
        const linhas = document.querySelectorAll('#listaPrescricoes tbody tr');
        linhas.forEach(linha => {
            const tipo = linha.cells[4].textContent.toLowerCase(); // coluna “Tipo_Prescricao”
            if (!selecionado) {
                linha.style.display = '';
            } else {
                linha.style.display = (tipo === selecionado ? '' : 'none');
            }
        });
    });
}
// Botão “Ver apenas prescrições sem exames” 
function configurarBotaoSemExames() {
    const btnSemExames = document.getElementById('btnSemExames');
    if (!btnSemExames) return;

    let filtrando = false;
    btnSemExames.addEventListener('click', function () {
        const linhas = document.querySelectorAll('#listaPrescricoes tbody tr');

        if (!filtrando) {
            // Mostrar apenas linhas com data-tem-exames="false"
            linhas.forEach(linha => {
                const tem = linha.getAttribute('data-tem-exames');
                linha.style.display = (tem === 'false' ? '' : 'none');
            });
            btnSemExames.textContent = 'Mostrar todas as prescrições';
            filtrando = true;
        } else {
            // Mostrar tudo novamente
            linhas.forEach(linha => linha.style.display = '');
            btnSemExames.textContent = 'Ver apenas prescrições sem exames';
            filtrando = false;
        }
    });
}
// Ajusta links dos botões “Apagar” e “Editar” na página de detalhes
function configurarBotoesDetalhes() {
    // Obtém o ID da prescrição da query string
    const idPrescricao = new URLSearchParams(window.location.search).get('idPrescricao');
    if (!idPrescricao) return;

    // Botão de Apagar
    const btnApagarLink = document.getElementById('btnApagarLink');
    if (btnApagarLink) {
        btnApagarLink.setAttribute('href', `apagar_prescricao.html?idPrescricao=${idPrescricao}`);
    }

    // Botão de Editar
    const btnEditarLink = document.getElementById('btnEditarLink');
    if (btnEditarLink) {
        btnEditarLink.setAttribute('href', `editar_prescricao.html?idPrescricao=${idPrescricao}`);
    }
}
// Carrega detalhes básicos na página “apagar_prescricao.html”
function carregarDadosParaApagar() {
    // Obtém elementos do DOM
    const idPrescricaoSpan = document.getElementById('idPrescricao');
    const nomeUtenteSpan = document.getElementById('nomeUtente');
    const prescricaoInfo = document.getElementById('prescricaoInfo');

    // Se não existir o elemento, não estamos nesta página
    if (!idPrescricaoSpan || !nomeUtenteSpan) return;

    // Obtém o ID da prescrição via query string
    const idPrescricao = new URLSearchParams(window.location.search).get('idPrescricao');
    if (!idPrescricao) {
        prescricaoInfo.innerHTML = `<div class="alert alert-danger">Erro: ID da prescrição não foi passado na URL.</div>`;
        return;
    }

    // Busca a prescrição no localStorage
    const prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
    const prescricao = prescricoes.find(p => p.ID_Prescricao === idPrescricao);
    if (!prescricao) {
        prescricaoInfo.innerHTML = `<div class="alert alert-danger">Prescrição com ID ${idPrescricao} não encontrada.</div>`;
        return;
    }

    // Preenche o ID da prescrição no título
    idPrescricaoSpan.textContent = prescricao.ID_Prescricao;

    // Busca o nome do utente para exibir
    const utentes = JSON.parse(localStorage.getItem('utentes')) || [];
    const utente = utentes.find(u => u.ID_Utente === prescricao.ID_Utente);
    nomeUtenteSpan.textContent = utente ? utente.Nome : 'Utente Desconhecido';
}
// Remove a prescrição 
function configurarBotaoApagar() {
    const btnSim = document.querySelector('.btn-danger');
    if (!btnSim) return;

    btnSim.addEventListener('click', function (event) {
        event.preventDefault();

        // Obtém ID da prescrição
        const idPrescricao = new URLSearchParams(window.location.search).get('idPrescricao');
        if (!idPrescricao) return;

        // 1) Remove a prescrição da lista “prescricao”
        let prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
        prescricoes = prescricoes.filter(p => p.ID_Prescricao !== idPrescricao);
        localStorage.setItem('prescricao', JSON.stringify(prescricoes));

        // 2) Remove todos os exames associados a essa prescrição
        let prescExames = JSON.parse(localStorage.getItem('prescricao_exame')) || [];
        prescExames = prescExames.filter(pe => pe.ID_Prescricao !== idPrescricao);
        localStorage.setItem('prescricao_exame', JSON.stringify(prescExames));

        // Redireciona de volta para a lista de prescrições
        window.location.href = "lista_prescricoes.html";
    });
}
//  Função que carrega os dados atuais no formulário de edição
function carregarDadosParaEditar() {
    // Elementos do DOM
    const prescricaoInfo = document.getElementById('prescricaoInfo');
    const formEditarPrescricao = document.getElementById('formEditarPrescricao');
    if (!formEditarPrescricao) return; // não é página de edição
    // Pegar o idPrescricao da query string
    const idPrescricao = new URLSearchParams(window.location.search).get('idPrescricao');
    if (!idPrescricao) {
        prescricaoInfo.innerHTML = `<div class="alert alert-danger">Erro: ID da prescrição não foi passado na URL.</div>`;
        return;
    }
    // Carregar a lista de prescrições do localStorage
    const prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
    const prescricao = prescricoes.find(p => p.ID_Prescricao === idPrescricao);
    if (!prescricao) {
        prescricaoInfo.innerHTML = `<div class="alert alert-danger">Prescrição com ID ${idPrescricao} não encontrada.</div>`;
        return;
    }
    // Se tudo estiver OK, mostra o formulário
    formEditarPrescricao.style.display = 'block';
    prescricaoInfo.innerHTML = ''; // limpa mensagens de erro

    // Preenchendo campos:
    document.getElementById('ID_Prescricao').value = prescricao.ID_Prescricao;
    document.getElementById('Data_Prescricao').value = prescricao.Data_Prescricao || '';
    document.getElementById('Data_Validade').value = prescricao.Data_Validade || '';
    document.getElementById('Tipo_Prescricao').value = prescricao.Tipo_Prescricao || '';
    document.getElementById('Num_Requisicao').value = prescricao.Num_Requisicao || '';
    document.getElementById('Cod_Acesso').value = prescricao.Cod_Acesso || '';
    document.getElementById('Cod_Prestacao').value = prescricao.Cod_Prestacao || '';
    document.getElementById('Prioridade').value = prescricao.Prioridade || '';
    document.getElementById('Observacoes').value = prescricao.Observacoes || '';

    // Carregar lista de utentes no <select> e já deixar o utente correto selecionado
    const selectUtente = document.getElementById('ID_Utente');
    if (selectUtente) {
        // 1) Preenche todas as opções de utentes
        selectUtente.innerHTML = '<option value="">Selecione o utente</option>';
        const utentes = JSON.parse(localStorage.getItem('utentes')) || [];
        utentes.forEach(u => {
            selectUtente.innerHTML += `<option value="${u.ID_Utente}">${u.Nome}</option>`;
        });
        // 2) Marca o utente associado à prescrição
        selectUtente.value = prescricao.ID_Utente;
    }
}
//  Função que processa formulário de edição
function configurarFormularioEditar() {
    const form = document.getElementById('formEditarPrescricao');
    if (!form) return;

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        // Validar campos obrigatórios
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        requiredFields.forEach(field => {
            if (!field.value) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        if (!isValid) {
            alert('Por favor, preencha todos os campos obrigatórios.');
            return;
        }

        // Coletar valores atualizados
        const idPrescricao = document.getElementById('ID_Prescricao').value;
        const novoUtente = document.getElementById('ID_Utente').value;
        const novaDataPresc = document.getElementById('Data_Prescricao').value;
        const novaDataVal = document.getElementById('Data_Validade').value;
        const novoTipo = document.getElementById('Tipo_Prescricao').value;
        const novoNumReq = document.getElementById('Num_Requisicao').value;
        const novoCodAcesso = document.getElementById('Cod_Acesso').value;
        const novoCodPrest = document.getElementById('Cod_Prestacao').value;
        const novaPrioridade = document.getElementById('Prioridade').value;
        const novasObs = document.getElementById('Observacoes').value;

        // Carregar array de prescrições e atualizar o objeto
        let prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
        const index = prescricoes.findIndex(p => p.ID_Prescricao === idPrescricao);
        if (index === -1) {
            alert('Não foi possível encontrar a prescrição para atualizar.');
            return;
        }

        // Substituir apenas os campos que podem ser editados
        prescricoes[index].ID_Utente = novoUtente;
        prescricoes[index].Data_Prescricao = novaDataPresc;
        prescricoes[index].Data_Validade = novaDataVal;
        prescricoes[index].Tipo_Prescricao = novoTipo;
        prescricoes[index].Num_Requisicao = novoNumReq;
        prescricoes[index].Cod_Acesso = novoCodAcesso;
        prescricoes[index].Cod_Prestacao = novoCodPrest;
        prescricoes[index].Prioridade = novaPrioridade;
        prescricoes[index].Observacoes = novasObs;

        // Gravar de volta no localStorage
        localStorage.setItem('prescricao', JSON.stringify(prescricoes));

        // ─── Atualizar associações de exames ───
        // 1) Carrega todas as associações existentes
        let prescExames = JSON.parse(localStorage.getItem('prescricao_exame')) || [];
        // 2) Remove todas as linhas desta prescrição (ID_Prescricao)
        prescExames = prescExames.filter(pe => pe.ID_Prescricao !== idPrescricao);
        // 3) Adiciona cada checkbox que está marcado
        document.querySelectorAll('#listaExamesEdit input[type="checkbox"]:checked').forEach(cb => {
            prescExames.push({
                ID_Prescricao: idPrescricao,
                ID_Exame: cb.value,
                Estado: "Prescrito"
            });
        });
        // 4) Grava de volta
        localStorage.setItem('prescricao_exame', JSON.stringify(prescExames));
        // ────────────────────────────────────────

        // Redireciona de volta para a lista de prescrições
        window.location.href = "lista_prescricoes.html";
    });
}
// Função que carrega detalhes de um UTENTE (utenteDetalhes.html)
function carregarDetalhesUtente() {
    // Verifica se estamos em utenteDetalhes.html conferindo a existência de #idUtente
    const idUtenteSpan = document.getElementById('idUtente');
    if (!idUtenteSpan) return; // se não estiver nesta página, não faz nada

    const utenteInfo = document.getElementById('utenteInfo');
    const nomeUtenteSpan = document.getElementById('nomeUtente');
    const dataNascimentoSpan = document.getElementById('dataNascimento');
    const nifUtenteSpan = document.getElementById('nifUtente');
    const sexoUtenteSpan = document.getElementById('sexoUtente');
    const contactoUtenteSpan = document.getElementById('contactoUtente');
    const emailUtenteSpan = document.getElementById('emailUtente');
    const moradaUtenteSpan = document.getElementById('moradaUtente');
    const snsUtenteSpan = document.getElementById('snsUtente');
    const subsistemaUtenteSpan = document.getElementById('subsistemaUtente');
    const listaPrescricoesUtente = document.getElementById('listaPrescricoesUtente');
    const detalhesContainerUtente = document.getElementById('detalhesContainerUtente');

    // Obtém o idUtente via query string
    const idUtente = new URLSearchParams(window.location.search).get('idUtente');
    if (!idUtente) {
        utenteInfo.innerHTML = `<div class="alert alert-danger">Erro: ID do utente não foi passado na URL.</div>`;
        return;
    }

    // Busca o utente no localStorage
    const utentes = JSON.parse(localStorage.getItem('utentes')) || [];
    const utente = utentes.find(u => u.ID_Utente === idUtente);

    if (!utente) {
        utenteInfo.innerHTML = `<div class="alert alert-danger">Utente não encontrado.</div>`;
        return;
    }

    // Se achou, exibe a área de detalhes
    detalhesContainerUtente.style.display = 'block';
    utenteInfo.innerHTML = ''; // limpa qualquer mensagem de erro anterior

    // Preenche os campos do utente
    idUtenteSpan.textContent = utente.ID_Utente;
    nomeUtenteSpan.textContent = utente.Nome;
    dataNascimentoSpan.textContent = utente.Data_Nascimento;
    nifUtenteSpan.textContent = utente.NIF;
    sexoUtenteSpan.textContent = utente.Sexo;
    contactoUtenteSpan.textContent = utente.Contacto;
    emailUtenteSpan.textContent = utente.Email;
    moradaUtenteSpan.textContent = utente.Morada;
    snsUtenteSpan.textContent = utente.Numero_SNS;
    subsistemaUtenteSpan.textContent = utente.Subsistema_Saude;

    // Agora, carrega as prescrições já feitas por este utente
    const prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
    const prescUtente = prescricoes.filter(p => p.ID_Utente === idUtente);

    // Se não houver prescrições
    if (prescUtente.length === 0) {
        listaPrescricoesUtente.innerHTML = `<p class="text-muted">Este utente ainda não tem prescrições.</p>`;
    } else {
        // Monta uma tabela com todas as prescrições deste utente
        let htmlTabela = `
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>ID Prescrição</th>
            <th>Data Prescrição</th>
            <th>Tipo</th>
            <th>Nº Requisição</th>
            <th class="text-center">Detalhes</th>
          </tr>
        </thead>
        <tbody>`;

        prescUtente.forEach(p => {
            htmlTabela += `
        <tr>
          <td>${p.ID_Prescricao}</td>
          <td>${p.Data_Prescricao || 'N/A'}</td>
          <td>${p.Tipo_Prescricao || 'N/A'}</td>
          <td>${p.Num_Requisicao || 'N/A'}</td>
          <td class="text-center">
            <a href="../medico/modulo1/prescricao/detalhes_prescricao.html?idPrescricao=${p.ID_Prescricao}" class="btn btn-sm btn-outline-primary">
              <i class="fa-solid fa-eye"></i>
            </a>
          </td>
        </tr>`;
        });

        htmlTabela += `</tbody></table>`;
        listaPrescricoesUtente.innerHTML = htmlTabela;
    }
}


// --------------------------------------------------------------
//  Carrega todos os exames como checkboxes em editar_prescricao.html
//  e marca os que já estão associados
// --------------------------------------------------------------
function carregarExamesParaEditar() {
    const container = document.getElementById('listaExamesEdit');
    if (!container) return; // não é a página de edição

    // 1) Buscar todos os exames cadastrados
    const exames = JSON.parse(localStorage.getItem('exames')) || [];

    // 2) Buscar associações existentes dessa prescrição
    const idPrescricao = new URLSearchParams(window.location.search).get('idPrescricao');
    const prescExamesAll = JSON.parse(localStorage.getItem('prescricao_exame')) || [];
    const examesAssociados = prescExamesAll
        .filter(pe => pe.ID_Prescricao === idPrescricao)
        .map(pe => pe.ID_Exame);

    // 3) Montar cada checkbox em .row (col‐md‐4)
    let html = '';
    exames.forEach(exame => {
        const isChecked = examesAssociados.includes(exame.ID_Exame) ? 'checked' : '';
        html += `
      <div class="col-md-4">
        <div class="form-check">
          <input 
            class="form-check-input" 
            type="checkbox" 
            value="${exame.ID_Exame}" 
            id="exame${exame.ID_Exame}"
            ${isChecked}
          >
          <label class="form-check-label" for="exame${exame.ID_Exame}">
            ${exame.Nome} &nbsp;(<small>${exame.Grupo}</small>)
          </label>
        </div>
      </div>
    `;
    });

    container.innerHTML = html;
}

document.addEventListener("DOMContentLoaded", function () {
    const tabelaBody = document.querySelector("#listaColheitas tbody");
    if (tabelaBody) {
        tabelaBody.querySelectorAll("tr").forEach(tr => {
            const condOK = tr.cells[6].textContent.trim(); // Condicoes_colheita_OK
            const garrote = tr.cells[8].textContent.trim(); // Garrote_Excedido

            if (condOK === "NOK") {
                tr.classList.add("table-danger");
            } else if (garrote === "1") {
                tr.classList.add("table-warning");
            }
        });
    }



    const filtroTexto = document.getElementById("filtroTexto");
    if (filtroTexto) {
        filtroTexto.addEventListener("keyup", function () {
            const termo = this.value.toLowerCase();
            tabelaBody.querySelectorAll("tr").forEach(tr => {
                const textoLinha = tr.textContent.toLowerCase();
                tr.style.display = textoLinha.includes(termo) ? "" : "none";
            });
        });
    }

    const filtroCond = document.getElementById("filtroSubsistema");
    if (filtroCond) {
        filtroCond.addEventListener("change", function () {
            const valor = this.value; // ou "1" ou "0"
            tabelaBody.querySelectorAll("tr").forEach(tr => {
                if (valor === "") {
                    tr.style.display = "";
                } else {
                    const celulaOK = tr.cells[6].textContent.trim();
                    tr.style.display = celulaOK === valor ? "" : "none";
                }
            });
        });
    }
});


document.addEventListener("DOMContentLoaded", function () {

    inicializarDados();
    configurarFiltroPrescricoesPendentes();
});

function configurarFiltroPrescricoesPendentes() {
    const filtroTexto = document.getElementById("filtroTextoPresc");
    const filtroTipo = document.getElementById("filtroTipoPresc");
    const btnVigentes = document.getElementById("btnSomenteVigentes");

    if (filtroTexto) {
        filtroTexto.addEventListener("keyup", function () {
            const termo = this.value.toLowerCase();
            document.querySelectorAll("#listaPrescricoesPendentes tbody tr").forEach(row => {
                const textoRow = row.textContent.toLowerCase();
                row.style.display = textoRow.includes(termo) ? "" : "none";
            });
        });
    }

    if (filtroTipo) {
        filtroTipo.addEventListener("change", function () {
            const selecionado = this.value.toLowerCase();
            document.querySelectorAll("#listaPrescricoesPendentes tbody tr").forEach(row => {
                const tipo = row.getAttribute("data-tipo");
                row.style.display = (!selecionado || tipo === selecionado) ? "" : "none";
            });
            if (!tipo) {
                row.style.display = (tipo)
            }
        });
    }

    if (btnVigentes) {
        let filtrandoVigentes = false;
        btnVigentes.addEventListener("click", function () {
            const hoje = new Date().toISOString().split("T")[0];
            document.querySelectorAll("#listaPrescricoesPendentes tbody tr").forEach(row => {
                const idPresc = row.children[0].textContent;
                const prescricoes = JSON.parse(localStorage.getItem("prescricao")) || [];
                const p = prescricoes.find(pr => pr.ID_Prescricao === idPresc);

                if (!filtrandoVigentes) {
                    row.style.display = (p && p.Data_Validade >= hoje) ? "" : "none";
                } else {
                    row.style.display = "";
                }
            });

            filtrandoVigentes = !filtrandoVigentes;
            btnVigentes.textContent = filtrandoVigentes
                ? "Mostrar Todas"
                : "Somente Válidas";
        });
    }
}

function verAmostras(utente) {
    const nomeUtenteSpan = document.getElementById("nomeUtente");
    const tabela = document.getElementById("tabelaAmostras");
    const container = document.getElementById("amostrasUtente");

    tabela.innerHTML = "";

    if (utente === "joao") {
        nomeUtenteSpan.textContent = "João Silva";
        tabela.innerHTML += `
          <tr>
            <td>A001</td>
            <td>Glicose</td>
            <td>Soro</td>
            <td>5 ml</td>
            <td>2025-06-06</td>
            <td>Jejum cumprido</td>
          </tr>`;
    } else if (utente === "maria") {
        nomeUtenteSpan.textContent = "Maria Costa";
        tabela.innerHTML += `
          <tr>
            <td>A002</td>
            <td>Colesterol Total</td>
            <td>EDTA</td>
            <td>4,5 ml</td>
            <td>2025-06-07</td>
            <td>N/A</td>
          </tr>`;
    }

    container.style.display = "block";
}

document.addEventListener("DOMContentLoaded", function () {
    carregarDetalhesPrescricaoDetalhes();
});

document.addEventListener("DOMContentLoaded", function () {
    const filtroTexto = document.getElementById("filtroTextoPresc");
    const filtroTipo = document.getElementById("filtroTipoPresc");
    const filtroPosto = document.getElementById("filtroPosto");
    const filtroPrioridade = document.getElementById("filtroPrioridade");
    const btnVigentes = document.getElementById("btnSomenteVigentes");


    let filtrarVigentes = false;

    function aplicarFiltros() {
        const termo = filtroTexto?.value.toLowerCase() || "";
        const tipoSelecionado = filtroTipo?.value || "";
        const postoSelecionado = filtroPosto?.value || "";
        const prioridadeSelecionada = filtroPrioridade?.value.toLowerCase() || "";
        const hoje = new Date().toISOString().split("T")[0];

        document.querySelectorAll("#listaPrescricoesPendentes tbody tr").forEach(row => {
            const textoLinha = row.textContent.toLowerCase();
            const tipo = row.getAttribute("data-tipo");
            const posto = row.getAttribute("data-id-posto");
            const data = row.getAttribute("data-data");
            const hoje = new Date().toISOString().split("T")[0];
            const expirado = data < hoje;
            const btnAcao = row.querySelector(".btn-acao");
            const prioridade = row.cells[5]?.textContent.trim().toLowerCase() || "";

            const coincideTexto = textoLinha.includes(termo);
            const coincideTipo = !tipoSelecionado || tipo === tipoSelecionado;
            const coincidePosto = !postoSelecionado || posto === postoSelecionado;
            const coincidePrioridade = !prioridadeSelecionada || prioridade === prioridadeSelecionada;
            const eVigente = !filtrarVigentes || data >= hoje;

            const mostrar = (
                coincideTexto &&
                coincideTipo &&
                coincidePosto &&
                coincidePrioridade &&
                eVigente
            );
            row.style.display = mostrar ? "" : "none";

            // Limpa destaque anterior
            row.classList.remove("table-danger");
            if (mostrar && prioridade === "urgente") {
                row.classList.add("table-danger");
            }
            if (expirado) {
                // Desativa o botão
                if (btnAcao) {
                    btnAcao.classList.add("disabled");
                    btnAcao.setAttribute("title", "Prescrição expirada");
                    btnAcao.style.pointerEvents = "none";
                    btnAcao.style.opacity = "0.5";
                }

                // Estiliza a linha opaca
                row.style.opacity = "0.5";
                row.style.backgroundColor = "#f2f2f2";
            } else {
                // Caso contrário, garante que está normal
                if (btnAcao) {
                    btnAcao.classList.remove("disabled");
                    btnAcao.removeAttribute("title");
                    btnAcao.style.pointerEvents = "auto";
                    btnAcao.style.opacity = "1";
                }

                row.style.opacity = "1";
                row.style.backgroundColor = ""; // limpa estilo extra
            }
        });
    }

    if (filtroTexto) filtroTexto.addEventListener("input", aplicarFiltros);
    if (filtroTipo) filtroTipo.addEventListener("change", aplicarFiltros);
    if (filtroPosto) filtroPosto.addEventListener("change", aplicarFiltros);
    if (filtroPrioridade) filtroPrioridade.addEventListener("change", aplicarFiltros);

    if (btnVigentes) {
        btnVigentes.addEventListener("click", function () {
            filtrarVigentes = !filtrarVigentes;
            aplicarFiltros();
            btnVigentes.textContent = filtrarVigentes
                ? "Mostrar Todas"
                : "Somente Válidas";
        });
    }

    aplicarFiltros();
});

document.getElementById("btnExportarPDF")?.addEventListener("click", function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const idPrescricao = new URLSearchParams(window.location.search).get('idPrescricao');
    const prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
    const presc = prescricoes.find(p => p.ID_Prescricao === idPrescricao);
    const utentes = JSON.parse(localStorage.getItem('utentes')) || [];
    const medicos = JSON.parse(localStorage.getItem('medicos')) || [];
    const medico = medicos.find(m => m.ID_Medico === presc.ID_Medico);

    const examesAssoc = JSON.parse(localStorage.getItem('prescricao_exame')) || [];
    const exames = JSON.parse(localStorage.getItem('exames')) || [];
    const instrucaoExame = JSON.parse(localStorage.getItem('instrucao_exame')) || [];
    const instrucoesTexto = JSON.parse(localStorage.getItem('instrucao_completa')) || [];


    const utente = utentes.find(u => u.ID_Utente === presc.ID_Utente);
    const examesDaPrescricao = examesAssoc.filter(e => e.ID_Prescricao === idPrescricao);

    let y = 20;

    doc.setFontSize(14);
    doc.text("Prescrição Clínica", 105, y, { align: "center" });
    y += 10;

    doc.setFontSize(10);
    doc.text(`ID Prescrição: ${presc.ID_Prescricao}`, 20, y); y += 6;
    doc.text(`Data: ${presc.Data_Prescricao}`, 20, y); y += 6;
    doc.text(`Validade: ${presc.Data_Validade}`, 20, y); y += 10;

    doc.text(`Utente: ${utente.Nome}`, 20, y); y += 6;
    doc.text(`Médico: ${medico?.Nome || '-'}`, 20, y); y += 6;
    doc.text(`Posto: ${medico?.Instituicao || '-'}`, 20, y); y += 10;


    doc.text(`Observações: ${presc.Observacoes || '-'}`, 20, y); y += 10;

    doc.setFontSize(12);
    doc.text("Exames Associados:", 20, y); y += 8;
    doc.setFontSize(10);

    examesDaPrescricao.forEach((e, i) => {
        const info = exames.find(ex => ex.ID_Exame === e.ID_Exame);
        const instrucoesIDs = instrucaoExame
            .filter(ie => ie.ID_Exame === e.ID_Exame)
            .map(ie => ie.idInstrucao);
        const instrucoes = instrucoesIDs.map(id => {
            const instr = instrucoesTexto.find(t => t.idInstrucao === id);
            return instr?.tipoInstrucao || '';
        }).filter(Boolean).join("; ");

        doc.text(`${i + 1}) ${info?.Nome || 'Exame Desconhecido'}`, 25, y); y += 5;
        doc.text(`Grupo: ${info?.Grupo || '-'}`, 30, y); y += 5;
        doc.text(`Tipo: ${info?.Tipo || '-'}`, 30, y); y += 5;
        doc.text(`Instruções: ${instrucoes || '-'}`, 30, y); y += 8;
    });

    doc.save(`prescricao_${presc.ID_Prescricao}.pdf`);
});

function carregarPrescricoes() {
    const tabelaBody = document.querySelector('#listaPrescricoes tbody');
    if (!tabelaBody) return;

    tabelaBody.innerHTML = '';

    const prescricoes = JSON.parse(localStorage.getItem('prescricao')) || [];
    const utentes = JSON.parse(localStorage.getItem('utentes')) || [];
    const examesAssoc = JSON.parse(localStorage.getItem('prescricao_exame')) || [];

    prescricoes.forEach(prescricao => {
        const utente = utentes.find(u => u.ID_Utente === prescricao.ID_Utente);
        const temExames = examesAssoc.some(e => e.ID_Prescricao === prescricao.ID_Prescricao);

        tabelaBody.innerHTML += `
      <tr data-tem-exames="${temExames}" data-tipo="${prescricao.Tipo_Prescricao.toLowerCase()}">
        <td>${prescricao.ID_Prescricao}</td>
        <td>${utente ? utente.Nome : 'Desconhecido'}</td>
        <td>${prescricao.Data_Prescricao}</td>
        <td>${prescricao.Data_Validade}</td>
        <td>${prescricao.Tipo_Prescricao}</td>
        <td>${prescricao.Prioridade}</td>
        <td class="text-center">
          <a href="detalhes_prescricao.html?idPrescricao=${prescricao.ID_Prescricao}" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-eye"></i>
          </a>
        </td>
      </tr>`;
    });
}

function gerarDashboardPrescricao() {
    const prescricoes = JSON.parse(localStorage.getItem("prescricao")) || [];

    const total = prescricoes.length;
    const urgentes = prescricoes.filter(p => p.Prioridade === "Urgente").length;
    const sns = prescricoes.filter(p => p.Tipo_Prescricao === "SNS").length;
    const privado = prescricoes.filter(p => p.Tipo_Prescricao === "Privado").length;
    const particular = prescricoes.filter(p => p.Tipo_Prescricao === "Particular").length;

    // Atualiza os números nos cartões
    document.getElementById("totalPrescricoes").textContent = total;
    document.getElementById("urgentesPrescricoes").textContent = urgentes;
    document.getElementById("snsPrescricoes").textContent = sns;
    document.getElementById("privadoPrescricoes").textContent = privado;

    // Gráfico
    const ctx = document.getElementById('graficoPrescricoes').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['SNS', 'Privado', 'Particular'],
            datasets: [{
                label: 'Nº de Prescrições',
                data: [sns, privado, particular],
                backgroundColor: ['#198754', '#ffc107', '#0dcaf0']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    stepSize: 1
                }
            }
        }
    });
}
function gerarGraficoColheitasSemana() {
    const colheitas = JSON.parse(localStorage.getItem("colheitas")) || [];
    const diasSemana = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
    const hoje = new Date();

    // Últimos 7 dias
    const diasLabels = [];
    const diasContagem = [];

    for (let i = 6; i >= 0; i--) {
        const d = new Date(hoje);
        d.setDate(d.getDate() - i);
        const iso = d.toISOString().split("T")[0];
        diasLabels.push(diasSemana[d.getDay()]);
        diasContagem.push(
            colheitas.filter(c => c.Data_Colheita === iso).length
        );
    }

    const ctx = document.getElementById("graficoColheitasSemana")?.getContext("2d");
    if (!ctx) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: diasLabels,
            datasets: [{
                label: "Colheitas por Dia (Últimos 7 Dias)",
                data: diasContagem,
                backgroundColor: "rgba(155, 213, 252, 0.6)",
                borderColor: "rgb(8, 93, 150)",
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: "Colheitas nos Últimos 7 Dias",
                    font: { size: 18 }
                },
                tooltip: {
                    enabled: true
                },
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: Math.round,
                    font: { weight: 'bold' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        },
        plugins: [ChartDataLabels] // Atenção aqui
    });
}
// --------------------------------------------------------------
// Chama todas as funções quando o DOM estiver carregado
// --------------------------------------------------------------
document.addEventListener("DOMContentLoaded", function () {
    inicializarDados();
    configurarBotaoApagar();
    carregarDadosParaApagar();

    // Se estivermos na página "lista_prescricoes.html"
    if (document.getElementById('listaPrescricoes')) {
        carregarPrescricoes();
        configurarFiltroTexto();
        configurarFiltroSubsistema();
        configurarBotaoSemExames();
    }

    // Se estivermos na página "detalhes_prescricao.html"
    if (document.getElementById('idPrescricao') && document.getElementById('detalhesContainer')) {
        carregarDetalhesPrescricaoDetalhes();
        configurarBotoesDetalhes();
    }

    // Se estivermos na página "apagar_prescricao.html"
    if (document.getElementById('idPrescricao') && document.getElementById('nomeUtente') && document.getElementById('formEditarPrescricao') === null) {
        carregarDadosParaApagar();
    }

    // Se estivermos na página "editar_prescricao.html"
    if (document.getElementById('formEditarPrescricao')) {
        carregarDadosParaEditar();
        carregarExamesParaEditar();
        configurarFormularioEditar();
    }

    // Se estivermos na página “utentDetalhes.html”
    if (document.getElementById('idUtente')) {
        carregarDetalhesUtente();
    }

    if (document.getElementById('graficoPrescricoes')) {
        gerarDashboardPrescricao();
    }
    if (document.getElementById('graficoColheitasSemana')) { gerarGraficoColheitasSemana(); }
    // Se estivermos na página “nova_prescricao.html”
    if (document.getElementById('formPrescricao')) { carregarUtentes(); configurarFormularioPrescricao(); }
    // Se estivermos na página “selecionar_exames.html”
    if (document.getElementById('formExames')) { carregarExames(); carregarDetalhesPrescricao(); configurarSubmissaoExames(); }
    // Se estivermos na página "gestaoUtente.html"
    if (document.querySelector('#listaUtentes')) { carregarListaUtentes(); configurarFiltroUtenteTexto(); configurarFiltroUtenteSubsistema(); }
});

//Tabela Equipamento
document.addEventListener("DOMContentLoaded", function () {
    const tabela = $('#tabelasTecnico').DataTable({
        pageLength: 5,
        lengthChange: false,
        dom: '<"top mb-3"f>rt<"bottom d-flex justify-content-between mt-3"ip>',
        language: {
            search: "", // Remove o "Pesquisar:" padrão
            zeroRecords: "Nenhum registo encontrado",
            info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            infoEmpty: "Sem registos disponíveis",
            infoFiltered: "(filtrado de _MAX_ no total)",
            paginate: {
                first: "Primeiro",
                last: "Último",
                next: "Seguinte",
                previous: "Anterior"
            }
        }
    });

    // Customizar a caixa de pesquisa
    const filtro = document.querySelector('#tabelasTecnico_filter');
    const input = filtro?.querySelector('input');
    if (input) {
        filtro.classList.add('w-100');          // Ocupa toda a largura
        filtro.classList.remove('dataTables_filter');
        filtro.classList.add('row');

        input.classList.add('form-control', 'form-control-lg', 'col-12');
        input.placeholder = "Escreve o que pretendes pesquisar.";
    }
});

//Tabela Amostra Especial
document.addEventListener("DOMContentLoaded", function () {
    const tabela = $('#tabelasTecnicoespecial').DataTable({
        pageLength: 5,
        lengthChange: false,
        dom: '<"top mb-3"f>rt<"bottom d-flex justify-content-between mt-3"ip>',
        language: {
            search: "", // Remove o "Pesquisar:" padrão
            zeroRecords: "Nenhum registo encontrado",
            info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            infoEmpty: "Sem registos disponíveis",
            infoFiltered: "(filtrado de _MAX_ no total)",
            paginate: {
                first: "Primeiro",
                last: "Último",
                next: "Seguinte",
                previous: "Anterior"
            }
        }
    });

    // Customizar a caixa de pesquisa
    const filtro = document.querySelector('#tabelasTecnicoespecial_filter');
    const input = filtro?.querySelector('input');
    if (input) {
        filtro.classList.add('w-100');          // Ocupa toda a largura
        filtro.classList.remove('dataTables_filter');
        filtro.classList.add('row');

        input.classList.add('form-control', 'form-control-lg', 'col-12');
        input.placeholder = "Escreve o que pretendes pesquisar.";
    }
});

// Tabelas para Validação Amostra
document.addEventListener("DOMContentLoaded", function () {
    const modal = new bootstrap.Modal(document.getElementById('modalEscolha'));
    modal.show();

    modal.show();

    document.getElementById('btnNormal').addEventListener('click', function () {
        document.getElementById('tabelaNormal').classList.remove('d-none');
        document.getElementById('tabelaEspecial').classList.add('d-none');
        modal.hide();
    });

    document.getElementById('btnEspecial').addEventListener('click', function () {
        document.getElementById('tabelaEspecial').classList.remove('d-none');
        document.getElementById('tabelaNormal').classList.add('d-none');
        modal.hide();
    });
});

// Tabela de Resultados
$(document).ready(function () {
    $('#tabela-resultados').DataTable({
        pageLength: 5,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    // Se a página contém os elementos de detalhes
    if (document.getElementById("det-idResultado")) {
        const params = new URLSearchParams(window.location.search);

        const mapCampos = {
            "det-idResultado": "id",
            "det-idAmostra": "amostra",
            "det-valorObtido": "valor",
            "det-grupo": "grupo",
            "det-fatorRh": "rh",
            "det-descricao": "descricao",
            "det-infecao": "infecao",
            "det-status": "status",
            "det-observacoes": "observacoes",
            "det-processamento": "processamento",
            "det-criacao": "criacao"
        };

        for (const [idCampo, param] of Object.entries(mapCampos)) {
            const el = document.getElementById(idCampo);
            if (el) el.textContent = params.get(param) || "";
        }
    }
});

// Função utilitária para formatar data para input datetime-local
function formatarDataParaInput(textoData) {
    const [data, hora] = textoData.split(' ');
    const [ano, mes, dia] = data.split('-');
    const [horas, minutos] = hora ? hora.split(':') : ['00', '00'];
    const dataISO = new Date(`${ano}-${mes}-${dia}T${horas}:${minutos}`);
    return !isNaN(dataISO.getTime()) ? dataISO.toISOString().slice(0, 16) : '';
}

// Variáveis para controlar linhas selecionadas
let linhaSelecionadaExames = null;
let linhaSelecionadaTesteSaude = null;
const modalConfirmacao = new bootstrap.Modal(document.getElementById("modalConfirmacao"));

// Eventos para editar e eliminar resultados de exames
$('#tabelasTecnicoespecial').on('click', '.btn-outline-danger', function () {
    linhaSelecionadaExames = $(this).closest('tr');
    modalConfirmacao.show();
});

$('#tabelasTecnicoespecial').on('click', '.btn-outline-primary', function () {
    const row = $(this).closest('tr');
    linhaSelecionadaExames = row;

    $('#editarId').val(row.find('td:eq(0)').text());
    $('#editarPaciente').val(row.find('td:eq(1)').text());
    $('#editarExame').val(row.find('td:eq(2)').text());
    $('#editarValor').val(row.find('td:eq(3)').text());
    $('#editarReferencia').val(row.find('td:eq(4)').text());
    $('#editarInterpretacao').val(row.find('td:eq(5) span').text());
    $('#editarStatus').val(row.find('td:eq(6) span').text());
    $('#editarData').val(formatarDataParaInput(row.find('td:eq(7)').text()));

    new bootstrap.Modal(document.getElementById('modalEditar')).show();
});

$('#btnGuardarAlteracoes').on('click', function () {
    if (!linhaSelecionadaExames) return;

    linhaSelecionadaExames.find('td:eq(2)').text($('#editarExame').val());
    linhaSelecionadaExames.find('td:eq(3)').text($('#editarValor').val());
    linhaSelecionadaExames.find('td:eq(4)').text($('#editarReferencia').val());

    const interpretacao = $('#editarInterpretacao').val();
    const status = $('#editarStatus').val();

    linhaSelecionadaExames.find('td:eq(5)').html(`<span class="badge ${interpretacao.includes('normal') ? 'bg-success' : 'bg-danger'}">${interpretacao}</span>`);
    linhaSelecionadaExames.find('td:eq(6)').html(`<span class="badge ${status === 'Confirmado' ? 'bg-success' : 'bg-danger'}">${status}</span>`);

    const data = new Date($('#editarData').val());
    if (!isNaN(data)) {
        linhaSelecionadaExames.find('td:eq(7)').text(
            data.toLocaleDateString('pt-PT') + ' ' + data.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' })
        );
    }

    bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide();
    linhaSelecionadaExames = null;
});

// Eventos para editar e eliminar resultados de teste de saúde
$('#tabelasTecnico').on('click', '.btn-eliminar-teste', function () {
    linhaSelecionadaTesteSaude = $(this).closest('tr');
    modalConfirmacao.show();
});

$('#tabelasTecnico').on('click', '.btn-editar-teste', function () {
    const row = $(this).closest('tr');
    linhaSelecionadaTesteSaude = row;

    $('#editarIdTeste').val(row.find('td:eq(0)').text());
    $('#editarValorObtido').val(row.find('td:eq(1)').text());
    $('#editarGrupoSanguineo').val(row.find('td:eq(2)').text());
    $('#editarDescricao').val(row.find('td:eq(3)').text());
    $('#editarFatorRH').val(row.find('td:eq(4)').text());
    $('#editarInfecoes').val(row.find('td:eq(5)').text());
    $('#editarStatusTeste').val(row.find('td:eq(6)').text());
    $('#editarObservacoes').val(row.find('td:eq(7)').text());
    $('#editarDataProcessamento').val(row.find('td:eq(8)').text());

    new bootstrap.Modal(document.getElementById('modalEditarTesteSaude')).show();
});

$('#btnGuardarAlteracoesTesteSaude').on('click', function () {
    if (!linhaSelecionadaTesteSaude) return;

    linhaSelecionadaTesteSaude.find('td:eq(1)').text($('#editarValorObtido').val());
    linhaSelecionadaTesteSaude.find('td:eq(2)').text($('#editarGrupoSanguineo').val());
    linhaSelecionadaTesteSaude.find('td:eq(3)').text($('#editarDescricao').val());
    linhaSelecionadaTesteSaude.find('td:eq(4)').text($('#editarFatorRH').val());
    linhaSelecionadaTesteSaude.find('td:eq(5)').text($('#editarInfecoes').val());
    linhaSelecionadaTesteSaude.find('td:eq(6)').text($('#editarStatusTeste').val());
    linhaSelecionadaTesteSaude.find('td:eq(7)').text($('#editarObservacoes').val());
    linhaSelecionadaTesteSaude.find('td:eq(8)').text($('#editarDataProcessamento').val());

    bootstrap.Modal.getInstance(document.getElementById('modalEditarTesteSaude')).hide();
    linhaSelecionadaTesteSaude = null;
});

// Confirmar eliminação comum
document.getElementById('btnConfirmarEliminar')?.addEventListener('click', function () {
    if (linhaSelecionadaExames) {
        linhaSelecionadaExames.remove();
        linhaSelecionadaExames = null;
    } else if (linhaSelecionadaTesteSaude) {
        linhaSelecionadaTesteSaude.remove();
        linhaSelecionadaTesteSaude = null;
    }
    modalConfirmacao.hide();
});


//Cards metodologia

function toggleDescricao(imgContainer) {
    const cardClicado = imgContainer.closest(".card");
    const descricaoClicada = cardClicado.querySelector(".descricao-metodologia");

    // Esconde todas as outras descrições
    document.querySelectorAll(".descricao-metodologia").forEach(desc => {
        if (desc !== descricaoClicada) {
            desc.classList.add("d-none");
        }
    });

    // Mostra a descrição apenas se estiver oculta
    descricaoClicada.classList.toggle("d-none");
}

//Detalhes normais
document.addEventListener("DOMContentLoaded", function () {
    const params = new URLSearchParams(window.location.search);

    const set = (id, value) => {
        document.getElementById(id).textContent = value || "(não disponível)";
    };

    set("det-idResultado", params.get("id"));
    set("det-idItemSolicitado", params.get("item"));
    set("det-valor", params.get("valor"));
    set("det-dataProcessamento", params.get("processamento"));
    set("det-observacoes", params.get("observacoes"));
    set("det-assinaturaResponsavel", params.get("assinaturaResponsavel"));
    set("det-metodologiaUtilizada", params.get("metodologia"));
    set("det-statusResultado", params.get("status"));
    set("det-dataCriacao", params.get("criacao"));
    set("det-dataConfirmacao", params.get("confirmacao"));
    set("det-assinaturaDigital", params.get("assinaturaDigital"));
});

//Dashboard dos Resultados 

document.addEventListener("DOMContentLoaded", function () {
    const resultados = [
        { id: 1, valor: 5.2, data: '2025-06-01', status: 'confirmado' },
        { id: 2, valor: 13.5, data: '2025-06-02', status: 'pré-eliminar' },
        { id: 3, valor: 210, data: '2025-06-03', status: 'rejeitado' },
        { id: 4, valor: 4.8, data: '2025-06-04', status: 'confirmado' }
    ];

    const contagem = { confirmado: 0, 'pré-eliminar': 0, rejeitado: 0 };
    const dadosPorData = {};

    resultados.forEach(r => {
        contagem[r.status]++;
        if (!dadosPorData[r.data]) dadosPorData[r.data] = 0;
        dadosPorData[r.data] += r.valor;
    });

    new Chart(document.getElementById('graficoStatus'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(contagem),
            datasets: [{
                data: Object.values(contagem),
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
            }]
        },
        options: { responsive: true }
    });

    new Chart(document.getElementById('graficoEvolucao'), {
        type: 'line',
        data: {
            labels: Object.keys(dadosPorData),
            datasets: [{
                label: 'Soma dos Valores',
                data: Object.values(dadosPorData),
                borderColor: '#007bff',
                borderWidth: 2,
                fill: false
            }]
        },
        options: { responsive: true }
    });
});

$(document).ready(function () {
    const tabela = $('#tabela-reagentes').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
        }
    });

    $('#tabela-reagentes tbody').on('click', '.adicionar', function () {
        const row = $(this).closest('tr');
        const cell = row.find('td').eq(2);
        let quantidade = parseInt(cell.text(), 10);
        cell.text(++quantidade);
    });

    $('#tabela-reagentes tbody').on('click', '.subtrair', function () {
        const row = $(this).closest('tr');
        const cell = row.find('td').eq(2);
        let quantidade = parseInt(cell.text(), 10);
        if (quantidade > 0) cell.text(--quantidade);
    });

    $('#tabela-reagentes tbody').on('click', '.eliminar', function () {
        const row = $(this).closest('tr');
        const nome = row.find('td').eq(1).text(); // Nome do reagente

        const confirmar = confirm(`Tem a certeza que pretende eliminar o reagente "${nome}"?`);
        if (confirmar) {
            tabela.row(row).remove().draw();
        }
    });
});




//Dashboards
document.addEventListener("DOMContentLoaded", function () {
    // Dados simulados para os diferentes gráficos
    const exames = ['Glicose', 'Hemoglobina', 'Colesterol', 'Creatinina'];
    const frequencia = [80, 65, 50, 45];
    const referencia = [90, 70, 60, 50];

    const testes = ['Teste A', 'Teste B', 'Teste C'];
    const prevalencia = [120, 90, 60];

    const motivos = ['Amostra inadequada', 'Equipamento com falha', 'Erro técnico'];
    const rejeicoes = [5, 3, 2];

    const equipamentos = ['Centrífuga', 'Microscópio', 'Espectrofotómetro', 'PCR'];
    const utilizacao = [75, 60, 80, 90];
    const calibracao = [3, 2, 4, 1];

    // Gráfico: Frequência vs Referência
    new Chart(document.getElementById('graficoFrequencia'), {
        type: 'bar',
        data: {
            labels: exames,
            datasets: [
                {
                    label: 'Frequência',
                    data: frequencia,
                    backgroundColor: '#0d6efd'
                },
                {
                    label: 'Valor de Referência',
                    data: referencia,
                    backgroundColor: '#6c757d'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } }
        }
    });

    // Gráfico: Prevalência dos Testes
    new Chart(document.getElementById('graficoPrevalencia'), {
        type: 'pie',
        data: {
            labels: testes,
            datasets: [{
                data: prevalencia,
                backgroundColor: ['#198754', '#ffc107', '#dc3545']
            }]
        },
        options: { responsive: true }
    });

    // Gráfico: Rejeições
    new Chart(document.getElementById('graficoRejeicao'), {
        type: 'bar',
        data: {
            labels: motivos,
            datasets: [{
                label: 'Casos',
                data: rejeicoes,
                backgroundColor: '#dc3545'
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: { legend: { display: false } }
        }
    });

    // Gráfico: Equipamentos
    new Chart(document.getElementById('graficoEquipamentos'), {
        type: 'bar',
        data: {
            labels: equipamentos,
            datasets: [
                {
                    label: 'Utilização (%)',
                    data: utilizacao,
                    backgroundColor: '#0d6efd'
                },
                {
                    label: 'Calibrações (mês)',
                    data: calibracao,
                    backgroundColor: '#20c997'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } }
        }
    });
});



// ==== SERVIÇOS ====

function obterServicos() {
    return JSON.parse(localStorage.getItem('servicos')) || [];
}

function guardarServicos(servicos) {
    localStorage.setItem('servicos', JSON.stringify(servicos));
}

function mostrarGestaoServicos() {
    const container = document.getElementById('gestao-servicos');
    if (!container) return;

    container.classList.toggle('d-none');

    if (!container.classList.contains('d-none')) {
        const servicos = obterServicos();
        container.innerHTML = servicos.map((s, i) => `
      <div class="col-md-4">
        <div class="card h-100 p-3 bg-light">
          <div class="card-body text-center">
            <i class="${s.icone} fs-3 mb-2"></i>
            <h5 class="card-title">${s.titulo}</h5>
            <p class="card-text">${s.descricao}</p>
            <button class="btn btn-sm btn-danger mt-2" onclick="removerServico(${i})">Remover</button>
          </div>
        </div>
      </div>
    `).join('');
    }
}

function removerServico(index) {
    const servicos = obterServicos();
    servicos.splice(index, 1);
    guardarServicos(servicos);
    mostrarGestaoServicos();
}

document.getElementById('form-servico')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const novo = {
        titulo: form.titulo.value,
        icone: form.icone.value,
        descricao: form.descricao.value
    };
    const lista = obterServicos();
    lista.push(novo);
    guardarServicos(lista);
    form.reset();
});

// Pré-carregar serviços se ainda não estiverem guardados
if (!localStorage.getItem('servicos')) {
    guardarServicos([
        {
            titulo: "Check-up Geral",
            descricao: "Avaliação completa de saúde com exames laboratoriais e aconselhamento médico.",
            icone: "bi bi-clipboard2-pulse"
        },
        {
            titulo: "Análises Clínicas",
            descricao: "Exames laboratoriais de sangue, urina, fezes e outros materiais biológicos.",
            icone: "bi bi-droplet"
        },
        {
            titulo: "Testes Genéticos",
            descricao: "Deteção de predisposições genéticas e doenças hereditárias através do ADN.",
            icone: "bi bi-shield-plus"
        },
        {
            titulo: "Exames Cardiológicos",
            descricao: "Eletrocardiogramas, provas de esforço e outros exames ao coração.",
            icone: "bi bi-heart-pulse"
        },
        {
            titulo: "Consulta de Especialidade",
            descricao: "Consultas com médicos especialistas para diagnóstico e acompanhamento clínico.",
            icone: "bi bi-person-vcard"
        },
        {
            titulo: "Vacinação",
            descricao: "Administração de vacinas obrigatórias e opcionais com registo clínico.",
            icone: "bi bi-capsule"
        },
        {
            titulo: "Saúde Ocupacional",
            descricao: "Exames e relatórios médicos para empresas e colaboradores.",
            icone: "bi bi-briefcase-medical"
        },
        {
            titulo: "Realização de Testes de Saúde",
            descricao: "Testes rápidos e laboratoriais para diversas condições e indicadores.",
            icone: "bi bi-file-medical"
        },
        {
            titulo: "Aconselhamento Clínico",
            descricao: "Sessões de esclarecimento e orientação clínica com profissionais de saúde.",
            icone: "bi bi-chat-dots"
        }
    ]);
}

// ==== UNIDADES ====

function obterUnidades() {
    return JSON.parse(localStorage.getItem('unidades')) || [];
}

function guardarUnidades(unidades) {
    localStorage.setItem('unidades', JSON.stringify(unidades));
}

function mostrarGestaoUnidades() {
    const container = document.getElementById('gestao-unidades');
    if (!container) return;

    container.classList.toggle('d-none');

    if (!container.classList.contains('d-none')) {
        const unidades = obterUnidades();
        container.innerHTML = unidades.map((u, i) => `
      <div class="col-md-4">
        <div class="card h-100 p-3">
          <img src="${u.imagem}" class="card-img-top" style="height: 180px; object-fit: cover;">
          <div class="card-body text-center">
            <h5 class="card-title">${u.nome}</h5>
            <p class="card-text">${u.morada}<br>${u.telefone}<br><a href="mailto:${u.email}">${u.email}</a></p>
            <button class="btn btn-sm btn-danger mt-2" onclick="removerUnidade(${i})">Remover</button>
          </div>
        </div>
      </div>
    `).join('');
    }
}

function removerUnidade(index) {
    if (confirm("Remover esta unidade?")) {
        const unidades = obterUnidades();
        unidades.splice(index, 1);
        guardarUnidades(unidades);
        mostrarGestaoUnidades();
        mostrarGestaoUnidades(); // Atualiza visualmente
    }
}

document.getElementById('form-unidade')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const file = form.imagem.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function () {
        const novaUnidade = {
            nome: form.nome.value,
            morada: form.morada.value,
            telefone: form.telefone.value,
            email: form.email.value,
            imagem: reader.result
        };
        const lista = obterUnidades();
        lista.push(novaUnidade);
        guardarUnidades(lista);
        form.reset();
    };
    reader.readAsDataURL(file);
});

localStorage.setItem('unidades', JSON.stringify([
    {
        nome: "Unidade Porto",
        morada: "Rua da Saúde 123, 4000-123 Porto",
        telefone: "+351 223 456 789",
        email: "porto@labs.pt",
        imagem: "../../assets/img/unidade1.jpg"
    },
    {
        nome: "Unidade Lisboa",
        morada: "Av. da Liberdade 250, 1250-149 Lisboa",
        telefone: "+351 213 987 654",
        email: "lisboa@labs.pt",
        imagem: "../../assets/img/unidade2.jpg"
    },
    {
        nome: "Unidade Braga",
        morada: "Largo São João do Souto, 4700-307 Braga",
        telefone: "+351 253 123 456",
        email: "braga@labs.pt",
        imagem: "../../assets/img/unidade3.jpg"
    },
    {
        nome: "Unidade Coimbra",
        morada: "Rua dos Hospitais 89, 3000-123 Coimbra",
        telefone: "+351 239 765 432",
        email: "coimbra@labs.pt",
        imagem: "../../assets/img/unidade4.jpg"
    },
    {
        nome: "Unidade Faro",
        morada: "Rua do Sol, 8000-000 Faro",
        telefone: "+351 289 654 321",
        email: "faro@labs.pt",
        imagem: "../../assets/img/unidade5.jpg"
    }
]));