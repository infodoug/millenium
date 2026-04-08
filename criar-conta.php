<?php

  include('config.php');
  
  // REMOVIDO: include('./configs/arquivo-config.php'); 
  // O código abaixo já faz tudo o que precisamos para validar a foto.

  if(isset($_POST['submit'])) {

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $path = 'assets/icons/default-avatar.png';

    // Só faz a checagem de foto se o usuário realmente enviou uma
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $arquivo = $_FILES['arquivo'];
        
        if($arquivo['size'] > 2097152 * 25) {
            die("Arquivo muito grande! Max: 50MB");
        }
        
        $pasta = "arquivos/";
        $nomeDoArquivo = $arquivo['name'];
        $novoNomeDoArquivo = uniqid();
        $extensao = strtolower(pathinfo($nomeDoArquivo,PATHINFO_EXTENSION));

        if($extensao != 'jpg' && $extensao != 'png') {
            die('Tipo de arquivo inválido!');
        }

        // Validar proporção 1:1 (quadrada)
        $img_info = getimagesize($arquivo['tmp_name']);
        if ($img_info !== false) {
            $largura = $img_info[0];
            $altura = $img_info[1];
            
            if ($largura != $altura) {
                die('A imagem deve ter proporção 1:1 (quadrada)! Envie uma foto de tamanho igual (ex: 500x500).');
            }
        }

        $path_temp = $pasta . $novoNomeDoArquivo . '.' . $extensao;
        $deu_certo = move_uploaded_file($arquivo['tmp_name'], $path_temp);
        
        if ($deu_certo) {
            $path = $path_temp; // Salva o caminho para mandar pro banco
        }
    }

    // AGORA SIM: Tenta salvar no banco de dados
    $result = mysqli_query($conexao, "INSERT INTO usuarios(nome, email, senha, foto) VALUES ('$nome', '$email', '$senha', '$path')");

    // Redireciona APENAS se a conta for criada com sucesso
    if ($result) {
        header('Location: index.php');
        exit(); // O exit garante que o PHP pare de rodar aqui após mudar de página
    } else {
        // Se der algum erro (ex: email duplicado), agora ele vai te mostrar!
        die("Erro ao criar conta no banco: " . mysqli_error($conexao));
    }

  }

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
  <title>Millenium - Criar Conta</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <link rel="stylesheet" type="text/css" href="criar-conta.css">
  <link href="script.js">
</head>
<body>
<!--   <h1>Criar Conta</h1> -->




  
  <form action="" enctype="multipart/form-data" method="POST">
    <!-- <label for="">Foto de Perfil:</label><br> -->
    


    <div class="cadastro">
      <label for="inputImage">
        <div id="userCard">
          
        </div>
      </label>
      <input id="inputImage" name="arquivo" type="file" style="display: none;"><br>
      <!-- <label for="nome">Nome de Usuário:</label><br> -->
      <input placeholder="Digite seu nome" type="text" name="nome" id="nome" class="inputUser" required><br><br>

      <!-- <label for="email">E-mail:</label><br> -->
      <input placeholder="Digite seu e-mail" type="email" name="email" id="email" class="inputUser" required><br><br>

      <!-- <label for="senha">Senha:</label><br> -->
      <input placeholder="Digite sua senha" type="password" name="senha" id="senha" class="inputUser" required><br><br>

      <input type="submit" name="submit" id="submit" class="inputSubmit">
    </div>
  </form>

  <script>
      const inputImage = document.getElementById('inputImage');
      const userCard = document.getElementById('userCard');

      inputImage.addEventListener('change', function() {
          const file = this.files[0]; // Pega o arquivo selecionado

          if (file) {
              const reader = new FileReader();

              reader.onload = function(e) {
                  // Limpa o conteúdo atual do card (caso tenha ícones ou texto)
                  userCard.innerHTML = ''; 
                  
                  // Cria o elemento de imagem
                  const img = document.createElement('img');
                  img.src = e.target.result;
                  img.style.width = '100%';
                  img.style.height = '100%';
                  img.style.borderRadius = '50%'; // Para ficar redondo igual ao perfil
                  img.style.objectFit = 'cover';
                  
                  userCard.appendChild(img);
              }

              reader.readAsDataURL(file);
          }
      });
  </script>

</body>
</html>
