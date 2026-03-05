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