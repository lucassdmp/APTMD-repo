button.addEventListener('click', (e) => {
    e.preventDefault();
    const aluno = document.createElement('div');
    aluno.className = 'aluno';
    aluno.innerHTML = `
                    <p class='titulo'>Formando</p>
                    <label for='aluno_name[]'>Nome Formando:</label>
                    <input type='text' name='aluno_name[]' required>
                    <label for='aluno_email[]'>Email Formando:</label>
                    <input type='email' name='aluno_email[]' required>
                    <label for='data_aniversario[]'>Data De Nascimento do Formando:</label>
                    <input type='date' name='data_aniversario[]' required>
                    <button class='remover_aluno'>Remover Formando</button>
                `;
    const remover_aluno = aluno.querySelector('.remover_aluno');
    remover_aluno.addEventListener('click', (e) => {
        e.preventDefault();
        container.removeChild(aluno);
    });
    const dateInput = aluno.querySelector(`input[type='date']`);
    dateInput.addEventListener('change', (e) => {
        const inputDate = new Date(e.target.value);
        console.log(inputDate);
        const today = new Date();
        const age = today.getFullYear() - inputDate.getFullYear();
        const check = aluno.querySelector(`input[type='checkbox']`);
        if (age < 18 && !check) {
            const label = document.createElement('label');
            label.innerHTML = 'Os responsáveis do formando assinaram o termo de responsabilidade?';
            label.className = 'responsavel'
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'responsavel[]';
            checkbox.required = true;
            aluno.appendChild(label);
            aluno.appendChild(checkbox);
        } else if (age >= 18) {
            const checkbox = aluno.querySelector(`input[type='checkbox']`);
            const label = aluno.querySelector('label.responsavel');
            if (checkbox) {
                aluno.removeChild(checkbox);
                aluno.removeChild(label);
            }
        }
    });

    const email = aluno.querySelector(`input[type='email']`);
    email.addEventListener('change', (e) => {
        const inputEmail = e.target.value;
        if (inputEmail == userEmail) {
            const errorMessage = document.createElement('div');
            errorMessage.className = 'errorEmail';
            errorMessage.innerHTML = 'O Email não pode ser igual ao teu e não será gerado!';
            errorMessage.style.color = 'red';
            errorMessage.style.fontSize = '14px';
            email.after(errorMessage);
        } else {
            const errorMessage = document.querySelector('.errorEmail');
            if (errorMessage) {
                errorMessage.remove();
            }
        }
    });

    container.appendChild(aluno);
});