
    function confirmarFoto() {
        var input = document.getElementById('arquivo');
        var pathInput = document.getElementById('foto_path');
        var path = input.value; // Obtém o valor do caminho do arquivo
        pathInput.value = path; // Define o valor do campo oculto com o caminho do arquivo
        document.getElementById('formUpload').submit(); // Submete o formulário após definir o caminho do arquivo
    }

    document.addEventListener("DOMContentLoaded", function() {
        const title = "Millenium";
        const h1 = document.querySelector("h1");
        const login = document.getElementsByClassName("login");
        h1.innerHTML = ""; // Limpa o conteúdo inicial
        let index = 0;
    
        function typeLetter() {
            if (index < title.length) {
                h1.innerHTML += title[index]; // Adiciona a letra ao h1
                index++;
                setTimeout(typeLetter, 150); // Ajuste o tempo para a velocidade da animação
                h1.style.opacity = 1;
                h1.style.animation = 0;
            } else {
                console.log(login);
                h1.style.opacity = 1; // Torna o título visível após completar a digitação
                h1.style.animation = "colorChange 10s infinite";
                /* setTimeout(() => {login[0].classList.add("on")}, 500); */
                login[0].classList.add("on")
            }
        }
    
        // Começa a animação
        typeLetter();
    });
    
    