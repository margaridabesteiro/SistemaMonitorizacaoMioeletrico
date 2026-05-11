// admin/js/admin.js
// Funções específicas do Administrador

document.addEventListener("DOMContentLoaded", function () {
    // Inicializar dados do administrador
    inicializarDadosAdmin();
    
    // Configurar gráficos do dashboard
    configurarGraficosAdmin();
    
    // Configurar filtros de tabelas
    configurarFiltrosAdmin();
    
    // Configurar modais e ações
    configurarModaisAdmin();
});

function inicializarDadosAdmin() {
    // Inicializar dados de profissionais de saúde se não existirem
    if (!localStorage.getItem('profissionais_saude')) {
        const profissionaisPadrao = [
            { id: 1, nome: "Dr. António Ribeiro", cargo: "Médico", especialidade: "Cardiologia", instituicao: "Unidade Central Porto", contacto: "912345678", email: "antonio.ribeiro@rehablink.pt", ativo: true },
            { id: 2, nome: "Dra. Ana Almeida", cargo: "Médico", especialidade: "Pediatria", instituicao: "Clínica do Norte – Famalicão", contacto: "916789432", email: "ana.almeida@rehablink.pt", ativo: true },
            { id: 3, nome: "Ana Silva", cargo: "Técnico", especialidade: "-", instituicao: "Posto de Braga", contacto: "912111111", email: "ana.silva@rehablink.pt", ativo: true },
            { id: 4, nome: "Bruno Ferreira", cargo: "Técnico", especialidade: "-", instituicao: "Unidade de Viana do Castelo", contacto: "913222222", email: "bruno.ferreira@rehablink.pt", ativo: true },
            { id: 5, nome: "Dra. Carla Santos", cargo: "Fisioterapeuta", especialidade: "Reabilitação", instituicao: "Clínica RehabLink - Porto", contacto: "934567890", email: "carla.santos@rehablink.pt", ativo: true }
        ];
        localStorage.setItem('profissionais_saude', JSON.stringify(profissionaisPadrao));
    }
    
    // Inicializar dados de dispositivos
    if (!localStorage.getItem('dispositivos')) {
        const dispositivosPadrao = [
            { id: "PS-1024", paciente: "Ana Ferreira", estado: "online", ultimaLigacao: "20 Mar 2026 10:28", tipo: "Força de pinça" },
            { id: "PS-1032", paciente: "João Rodrigues", estado: "offline", ultimaLigacao: "19 Mar 2026 08:15", tipo: "Precisão" },
            { id: "PS-1045", paciente: "Carlos Monteiro", estado: "online", ultimaLigacao: "20 Mar 2026 09:45", tipo: "Resistência" }
        ];
        localStorage.setItem('dispositivos', JSON.stringify(dispositivosPadrao));
    }
}

function configurarGraficosAdmin() {
    // Gráfico de faturação mensal
    const graficoFaturacao = document.getElementById('faturacaoMensalChart');
    if (graficoFaturacao) {
        new Chart(graficoFaturacao, {
            type: 'line',
            data: {
                labels: ['Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez', 'Jan', 'Fev', 'Mar'],
                datasets: [{
                    label: 'Faturação (€)',
                    data: [8500, 9200, 8800, 9500, 10200, 10800, 11200, 11800, 12100, 12450, 12800, 12450],
                    borderColor: '#8B0000',
                    backgroundColor: 'rgba(139,0,0,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            }
        });
    }
    
    // Gráfico de distribuição por serviço
    const graficoServico = document.getElementById('distribuicaoServicoChart');
    if (graficoServico) {
        new Chart(graficoServico, {
            type: 'doughnut',
            data: {
                labels: ['Sessões de Treino', 'Avaliações', 'Jogos', 'Consultas Médicas'],
                datasets: [{
                    data: [65, 15, 12, 8],
                    backgroundColor: ['#8B0000', '#A52A2A', '#B22222', '#CD5C5C']
                }]
            }
        });
    }
    
    // Gráfico de atividade do sistema
    const graficoAtividade = document.getElementById('atividadeSistemaChart');
    if (graficoAtividade) {
        new Chart(graficoAtividade, {
            type: 'line',
            data: {
                labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Acessos',
                    data: [142, 156, 148, 165, 172, 98, 45],
                    borderColor: '#8B0000',
                    backgroundColor: 'rgba(139,0,0,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            }
        });
    }
}

function configurarFiltrosAdmin() {
    // Filtro para tabela de profissionais
    const filtroCargo = document.getElementById('filtroCargo');
    const filtroInstituicao = document.getElementById('filtroInstituicao');
    const pesquisa = document.getElementById('pesquisa');
    
    if (filtroCargo || filtroInstituicao || pesquisa) {
        aplicarFiltrosProfissionais();
    }
}

function aplicarFiltrosProfissionais() {
    const filtroCargo = document.getElementById('filtroCargo');
    const filtroInstituicao = document.getElementById('filtroInstituicao');
    const pesquisa = document.getElementById('pesquisa');
    
    if (!filtroCargo) return;
    
    const cargo = filtroCargo.value;
    const instituicao = filtroInstituicao ? filtroInstituicao.value : 'todos';
    const textoPesquisa = pesquisa ? pesquisa.value.toLowerCase() : '';
    
    const linhas = document.querySelectorAll('#tabelaPS tbody tr');
    linhas.forEach(linha => {
        let mostrar = true;
        
        if (cargo !== 'todos') {
            const cargoLinha = linha.querySelector('.badge-cargo')?.innerText.toLowerCase() || '';
            if (cargoLinha !== cargo) mostrar = false;
        }
        
        if (instituicao !== 'todos' && mostrar) {
            const instituicaoLinha = linha.cells[3]?.innerText.toLowerCase() || '';
            if (instituicaoLinha !== instituicao.toLowerCase()) mostrar = false;
        }
        
        if (textoPesquisa && mostrar) {
            const textoLinha = linha.innerText.toLowerCase();
            if (!textoLinha.includes(textoPesquisa)) mostrar = false;
        }
        
        linha.style.display = mostrar ? '' : 'none';
    });
}

function configurarModaisAdmin() {
    // Modal de confirmação para eliminação
    const botoesEliminar = document.querySelectorAll('.btn-eliminar-fatura, .btn-desativar');
    botoesEliminar.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que pretende realizar esta ação?')) {
                e.preventDefault();
            }
        });
    });
}

// Funções auxiliares
function exportarDados(tipo) {
    alert(`A exportar dados de ${tipo}...`);
}

function limparFiltros() {
    document.querySelectorAll('.filter-section select, .filter-section input').forEach(campo => {
        if (campo.tagName === 'SELECT') campo.value = 'todos';
        if (campo.tagName === 'INPUT') campo.value = '';
    });
    aplicarFiltrosProfissionais();
}