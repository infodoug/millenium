
    function confirmarFoto() {
        var input = document.getElementById('arquivo');
        var pathInput = document.getElementById('foto_path');
        var path = input.value; // Obtém o valor do caminho do arquivo
        pathInput.value = path; // Define o valor do campo oculto com o caminho do arquivo
        document.getElementById('formUpload').submit(); // Submete o formulário após definir o caminho do arquivo
    }
