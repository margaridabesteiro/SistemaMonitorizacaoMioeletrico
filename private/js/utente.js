
$(document).ready(function () {
    // Inicializar DataTable
    $('#tabela_sessoes').DataTable({
        pageLength: 5,
        pagingType: "full_numbers",
        language: {
            decimal: "",
            emptyTable: "Sem sessões disponíveis",
            info: "Mostrando _START_ até _END_ de _TOTAL_ sessões",
            infoEmpty: "Mostrando 0 até 0 de 0 sessões",
            infoFiltered: "(filtrado de _MAX_ sessões)",
            lengthMenu: "Mostrar _MENU_ sessões",
            loadingRecords: "A carregar...",
            processing: "A processar...",
            search: "Filtrar:",
            zeroRecords: "Nenhuma sessão encontrada",
            paginate: {
                first: "Primeira",
                last: "Última",
                next: "Seguinte",
                previous: "Anterior"
            }
        }
    });

    // Chatbot functionality
    $('#chatbot-button').click(function () {
        $('#chatbot').removeClass('d-none');
        $(this).hide();
    });

    $('#close-chat').click(function () {
        $('#chatbot').addClass('d-none');
        $('#chatbot-button').show();
    });

    // FAQ buttons functionality
    $('.faq-btn').click(function () {
        const question = $(this).text();
        alert('Funcionalidade de chat em desenvolvimento. Pergunta: ' + question);
    });
});
