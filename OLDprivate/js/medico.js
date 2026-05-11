// medico/js/medico.js
// Funções específicas do Médico

document.addEventListener("DOMContentLoaded", function () {
    // Inicializar dados do médico
    inicializarDadosMedico();
    
    // Configurar gráficos do dashboard
    configurarGraficosMedico();
    
    // Configurar gestão de prescrições
    configurarPrescricoesMedico();
    
    // Configurar gestão de pacientes
    configurarPacientesMedico();
});

function inicializarDadosMedico() {
    // Inicializar dados de prescrições se não existirem
    if (!localStorage.getItem('prescricao')) {
        const prescricoesPadrao = [
            { ID_Prescricao: "PR-001", ID_Utente: "1", ID_Medico: "1", Data_Prescricao: "2026-03-12", Data_Validade: "2026-06-12", Tipo_Prescricao: "SNS", Prioridade: "Média", Observacoes: "Plano de reabilitação" }
        ];
        localStorage.setItem('prescricao', JSON.stringify(prescricoesPadrao));
    }
    
    // Inicializar dados de pacientes
    if (!localStorage.getItem('pacientes_medico')) {
        const pacientesPadrao = [
            { id: "1", nome: "Ana Ferreira", idade: 38, sexo: "F", contacto: "912345678", email: "ana.ferreira@email.com", ultimaConsulta: "2026-03-13", estado: "ativo" }
        ];
        localStorage.setItem('pacientes_medico', JSON.stringify(pacientesPadrao));
    }
}

function configurarGraficosMedico() {
    // Gráfico de atividade semanal
    const graficoAtividade = document.getElementById('activityChart');
    if (graficoAtividade) {
        new Chart(graficoAtividade, {
            type: 'line',
            data: {
                labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Consultas',
                    data: [4, 6, 5, 8, 7, 2, 1],
                    borderColor: '#1e7b4b',
                    backgroundColor: 'rgba(30,123,75,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            }
        });
    }
}

function configurarPrescricoesMedico() {
    // Configurar formulário de nova prescrição
    const formPrescricao = document.getElementById('formPrescricao');
    if (formPrescricao) {
        formPrescricao.addEventListener('submit', function(e) {
            e.preventDefault();
            const titulo = document.getElementById('servicoTitulo')?.value;
            const icone = document.getElementById('servicoIcone')?.value;
            const descricao = document.getElementById('servicoDescricao')?.value;
            
            if (titulo && icone && descricao) {
                const novaPrescricao = { titulo, icone, descricao };
                let lista = JSON.parse(localStorage.getItem('prescricoes_medico') || '[]');
                lista.push(novaPrescricao);
                localStorage.setItem('prescricoes_medico', JSON.stringify(lista));
                alert('Prescrição criada com sucesso!');
                formPrescricao.reset();
            }
        });
    }
}

function configurarPacientesMedico() {
    // Configurar filtro de pacientes
    const filtroTexto = document.getElementById('filtroUtentesTexto');
    if (filtroTexto) {
        filtroTexto.addEventListener('input', function() {
            const termo = this.value.toLowerCase();
            const linhas = document.querySelectorAll('#tabelaPacientes tbody tr');
            linhas.forEach(linha => {
                const texto = linha.innerText.toLowerCase();
                linha.style.display = texto.includes(termo) ? '' : 'none';
            });
        });
    }
}

// Funções auxiliares
function novaPrescricao() {
    window.location.href = 'prescricoes/nova_prescricao.html';
}

function verPrescricao(id) {
    window.location.href = `prescricoes/detalhes_prescricao.html?id=${id}`;
}

function editarPrescricao(id) {
    window.location.href = `prescricoes/editar_prescricao.html?id=${id}`;
}

function verPaciente(id) {
    window.location.href = `pacientes/perfil_paciente.html?id=${id}`;
}

function agendarConsulta(id) {
    window.location.href = `consultas/consultas.html?paciente=${id}`;
}