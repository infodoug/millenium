<?php

  include('config.php');
  
  // REMOVIDO: include('./configs/arquivo-config.php'); 
  // O código abaixo já faz tudo o que precisamos para validar a foto.

  if(isset($_POST['submit'])) {

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $path = 'assets/icons/default-avatar.png';

    // Se o frontend enviou imagem já recortada (base64)
    if (!empty($_POST['arquivo_data'])) {
      $imgData = $_POST['arquivo_data'];
      if (preg_match('/^data:(image\/png|image\/jpeg);base64,(.*)$/', $imgData, $m)) {
        $mime = $m[1]; $data = base64_decode($m[2]); if ($data === false) die('Falha ao decodificar imagem.');
        $ext = ($mime === 'image/png') ? 'png' : 'jpg';
        $novoNomeDoArquivo = uniqid(); $pasta = 'arquivos/'; $path_temp = $pasta . $novoNomeDoArquivo . '.' . $ext; file_put_contents($path_temp, $data); $path = $path_temp;
      } else { die('Formato de imagem inválido.'); }
    }

    // Fallback: upload clássico (suporta `arquivo` ou `arquivo_file`)
    if ((isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) || (isset($_FILES['arquivo_file']) && $_FILES['arquivo_file']['error'] === UPLOAD_ERR_OK)) {
      $arquivo = isset($_FILES['arquivo_file']) ? $_FILES['arquivo_file'] : $_FILES['arquivo'];
      if($arquivo['size'] > 2097152 * 25) { die("Arquivo muito grande! Max: 50MB"); }
      $pasta = "arquivos/"; $nomeDoArquivo = $arquivo['name']; $novoNomeDoArquivo = uniqid(); $extensao = strtolower(pathinfo($nomeDoArquivo,PATHINFO_EXTENSION));
      if($extensao != 'jpg' && $extensao != 'png') { die('Tipo de arquivo inválido!'); }
      $path_temp = $pasta . $novoNomeDoArquivo . '.' . $extensao; $deu_certo = move_uploaded_file($arquivo['tmp_name'], $path_temp);
      if ($deu_certo) { $path = $path_temp; }
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
      <input id="inputImage" name="arquivo_file" type="file" accept="image/*" style="display: none;"><br>
      <input type="hidden" name="arquivo_data" id="arquivo_data">
      <!-- <label for="nome">Nome de Usuário:</label><br> -->
      <input placeholder="Digite seu nome" type="text" name="nome" id="nome" class="inputUser" required><br><br>

      <!-- <label for="email">E-mail:</label><br> -->
      <input placeholder="Digite seu e-mail" type="email" name="email" id="email" class="inputUser" required><br><br>

      <!-- <label for="senha">Senha:</label><br> -->
      <input placeholder="Digite sua senha" type="password" name="senha" id="senha" class="inputUser" required><br><br>

      <input type="submit" name="submit" id="submit" class="inputSubmit">
    </div>
  </form>

    <link rel="stylesheet" href="scripts/image-cropper.css">
    <script src="scripts/image-cropper.js"></script>
    <script>
      const inputImage = document.getElementById('inputImage');
      const userCard = document.getElementById('userCard');
      const arquivoHidden = document.getElementById('arquivo_data');
      // bind input to cropper (no auto-submit here)
      bindInputToCropper(document.getElementById('inputImage'), arquivoHidden, userCard);
    </script>

</body>
</html>
