<?php

  include('config.php');
  include('./configs/arquivo-config.php');

  if (isset($_FILES['arquivo'])) {
    $arquivo = $_FILES['arquivo'];
    if($arquivo['size'] > 2097152 * 25)
      die("Arquivo muito grande! Max: 2MB");
    if($arquivo['error'])
      die("Falha ao enviar arquivo!");
    
    $pasta = "arquivos/";
    $nomeDoArquivo = $arquivo['name'];
    $novoNomeDoArquivo = uniqid();
    $extensao = strtolower(pathinfo($nomeDoArquivo,PATHINFO_EXTENSION));

    if($extensao != 'jpg' && $extensao != 'png')
      die('Tipo de arquivo inválido!');

    $path = $pasta . $novoNomeDoArquivo . '.' . $extensao;
    $deu_certo = move_uploaded_file($arquivo['tmp_name'], $path);
    if ($deu_certo)
      echo "<p> Arquivo: <a href='arquivos/$novoNomeDoArquivo.$extensao'>clique aqui</a>";
  }

  if(isset($_POST['submit'])) {

    

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    

    

    header('Location: index.php');

    $result = mysqli_query($conexao, "INSERT INTO usuarios(nome,email,senha,foto) VALUES ('$nome', '$email', '$senha', '$path')");

  }

  

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
  <title>Millenium - Criar Conta</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <link href="script.js">
</head>
<body>
  <h1>Criar Conta</h1>




  
  <form action="" enctype="multipart/form-data" method="POST">
    <label for="">Foto de Perfil:</label><br>
    <input name="arquivo" type="file"><br>


    <label for="nome">Nome de Usuário:</label><br>
    <input type="text" name="nome" id="nome" class="inputUser" required><br><br>

    <label for="email">E-mail:</label><br>
    <input type="email" name="email" id="email" class="inputUser" required><br><br>

    <label for="senha">Senha:</label><br>
    <input type="password" name="senha" id="senha" class="inputUser" required><br><br>

    <input type="submit" name="submit" id="submit">
  </form>

</body>
</html>
