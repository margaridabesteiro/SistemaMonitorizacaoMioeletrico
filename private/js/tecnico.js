// tecnico/js/tecnico.js
// Funções específicas do Técnico

document.addEventListener("DOMContentLoaded", function () {
    // Inicializar dados do técnico
    inicializarDadosTecnico();
    
    // Configurar tabelas e gestão de amostras
    configurarTabelasTecnico();
    
    // Configurar gestão de reagentes
    configurarReagentesTecnico();
    
    // Configurar resultados
    configurarResultadosTecnico();
});

function inicializarDadosTecnico() {
    // Inicializar dados de amostras
    if (!localStorage.getItem('amostras')) {
        const amostrasPadrao = [
            { id: "A001", tipo: "Sangue", paciente: "João Silva", dataColheita: "2026-03-20", status: "Pendente" },
            { id: "A002", tipo: "Urina", paciente: "Maria Costa", dataColheita: "2026-03-19", status: "Processado" }
        ];
        localStorage.setItem('amostras', JSON.stringify(amostrasPadrao));
    }
    
    // Inicializar dados de reagentes
    if (!localStorage.getItem('reagentes')) {
        const reagentesPadrao = [
            { id: "R001", nome: "Glicose", quantidade: 150, unidade: "ml", limite: 50 },
            { id: "R002", nome: "Hemoglobina", quantidade: 75, unidade: "ml", limite: 30 }
        ];
        localStorage.setItem('reagentes', JSON.stringify(reagentesPadrao));
    }
}

function configurarTabelasTecnico() {
    // Configurar DataTable para equipamentos
    const tabelaEquipamentos = document.getElementById('tabelasTecnico');
    if (tabelaEquipamentos) {
        $(tabelaEquipamentos).DataTable({
            pageLength: 5,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
            },
            dom: '<"top mb-3"f>rt<"bottom d-flex justify-content-between mt-3"ip>'
        });
    }
    
    // Configurar DataTable para amostras especiais
    const tabelaEspecial = document.getElementById('tabelasTecnicoespecial');
    if (tabelaEspecial) {
        $(tabelaEspecial).DataTable({
            pageLength: 5,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
            }
        });
    }
}

function configurarReagentesTecnico() {
    // Configurar ações para reagentes
    const tabelaReagentes = document.getElementById('tabela-reagentes');
    if (tabelaReagentes) {
        $(tabelaReagentes).on('click', '.adicionar', function() {
            const row = $(this).closest('tr');
            const cell = row.find('td').eq(2);
            let quantidade = parseInt(cell.text(), 10);
            if (!isNaN(quantidade)) cell.text(++quantidade);
        });
        
        $(tabelaReagentes).on('click', '.subtrair', function() {
            const row = $(this).closest('tr');
            const cell = row.find('td').eq(2);
            let quantidade = parseInt(cell.text(), 10);
            if (!isNaN(quantidade) && quantidade > 0) cell.text(--quantidade);
        });
        
        $(tabelaReagentes).on('click', '.eliminar', function() {
            const row = $(this).closest('tr');
            const nome = row.find('td').eq(1).text();
            if (confirm(`Tem a certeza que pretende eliminar o reagente "${nome}"?`)) {
                row.remove();
            }
        });
    }
}

function configurarResultadosTecnico() {
    // Configurar edição de resultados
    const botoesEditar = document.querySelectorAll('.btn-editar-teste, .btn-editar-exame');
    botoesEditar.forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const modal = new bootstrap.Modal(document.getElementById('modalEditar'));
            // Preencher modal com dados da linha
            modal.show();
        });
    });
    
    // Configurar eliminação de resultados
    const botoesEliminar = document.querySelectorAll('.btn-eliminar-teste, .btn-outline-danger');
    botoesEliminar.forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            if (confirm('Tem certeza que pretende eliminar este resultado?')) {
                row.remove();
            }
        });
    });
}

// Funções auxiliares
function verAmostras(paciente) {
    window.location.href = `amostras.html?paciente=${paciente}`;
}

function processarAmostra(id) {
    window.location.href = `processar_amostra.html?id=${id}`;
}

function validarResultado(id) {
    window.location.href = `validar_resultado.html?id=${id}`;
}

function gerarRelatorioResultado(id) {
    window.location.href = `relatorio_resultado.html?id=${id}`;
}