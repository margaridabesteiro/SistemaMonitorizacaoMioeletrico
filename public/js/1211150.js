
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formRegistarUtente');
    const alertErro = document.getElementById('alertErro');
    const selectSubsistema = document.getElementById('selectSubsistema');
    const inputSNS = document.getElementById('inputSNS');
    const textoAjudaSNS = document.getElementById('textoAjudaSNS');
    const textoAjuda1 = document.getElementById('textoAjudaSNS1'); 

    // Função para atualizar estado do campo "Número SNS"
    function atualizarCampoSNS() {
      if (selectSubsistema.value === 'SNS') {
        inputSNS.disabled = false;
        inputSNS.required = true;
        textoAjudaSNS.classList.remove('d-none');
      } else {
        inputSNS.disabled = true;
        inputSNS.required = false;
        textoAjudaSNS.classList.add('d-none');
        inputSNS.value = ''; // limpa se não for SNS
      }
      esconderTextoAjuda1(); 
    }

   
    function esconderTextoAjuda1() {
      if (selectSubsistema.value !== '') {
        textoAjuda1.classList.add('d-none');
      } else {
        textoAjuda1.classList.remove('d-none');
      }
    }

    // Atualiza ao mudar o select
    selectSubsistema.addEventListener('change', atualizarCampoSNS);

    // Chama uma vez no carregamento para ajustar o estado inicial
    atualizarCampoSNS();

    form.addEventListener('submit', (e) => {
      e.preventDefault(); // Impede envio real
      alertErro.classList.add('d-none'); // Esconde alerta antes de validar

      // Captura valores
      const nome = document.getElementById('inputNome').value.trim();
      const morada = document.getElementById('inputMorada').value.trim();
      const codPostal = document.getElementById('inputCodPostal').value.trim();
      const cidade = document.getElementById('inputCidade').value.trim();
      const contacto = document.getElementById('inputContacto').value.trim();
      const email = document.getElementById('inputEmail').value.trim();
      const dataNasc = document.getElementById('inputDataNasc').value;
      const nif = document.getElementById('inputNIF').value.trim();
      const numeroSNS = inputSNS.value.trim();
      const subsistema = selectSubsistema.value;

      // Verifica campos obrigatórios (morada pode ficar em branco)
      if (
        !nome ||
        !codPostal ||
        !cidade ||
        !contacto ||
        !email ||
        !dataNasc ||
        !nif ||
        !subsistema
      ) {
        alertErro.textContent = 'Preencha todos os campos obrigatórios.';
        alertErro.classList.remove('d-none');
        return;
      }

      // Se subsistema for 'SNS', númeroSNS torna-se obrigatório
      if (subsistema === 'SNS') {
        if (!numeroSNS) {
          alertErro.textContent =
            'Campo "Número SNS" é obrigatório quando subsistema for SNS.';
          alertErro.classList.remove('d-none');
          return;
        }

        // Validação de formato de número SNS: apenas dígitos
        const regexSNS = /^\d+$/;
        if (!regexSNS.test(numeroSNS)) {
          alertErro.textContent = 'Número SNS inválido. Use apenas dígitos.';
          alertErro.classList.remove('d-none');
          return;
        }
      }

      // Valida Código Postal (formato 1234-567)
      const regexCP = /^\d{4}-\d{3}$/;
      if (!regexCP.test(codPostal)) {
        alertErro.textContent = 'Código Postal inválido. Use formato 1234-567.';
        alertErro.classList.remove('d-none');
        return;
      }

      // Valida NIF (9 dígitos numéricos)
      const regexNIF = /^\d{9}$/;
      if (!regexNIF.test(nif)) {
        alertErro.textContent = 'NIF inválido. Deve ter 9 dígitos numéricos.';
        alertErro.classList.remove('d-none');
        return;
      }

      // Valida Email (básico)
      const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!regexEmail.test(email)) {
        alertErro.textContent = 'Email inválido.';
        alertErro.classList.remove('d-none');
        return;
      }

      // Se tudo estiver correto:
      alert('Utente registado (placeholder) com sucesso!');
      form.reset();
      // Reajusta campo SNS e texto de ajuda após limpar o form
      atualizarCampoSNS();
      esconderTextoAjuda1();
    });
  });
