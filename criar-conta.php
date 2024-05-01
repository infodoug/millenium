<?php
  if(isset($_POST['submit'])) {
  //  print_r($_POST['nome']);
  //  print_r($_POST['email']);
  //  print_r($_POST['senha']);

  include_once('config.php');

  $nome = $_POST['nome'];
  $email = $_POST['email'];
  $senha = $_POST['senha'];

  $result = mysqli_query($conexao, "INSERT INTO usuarios(nome,email,senha) VALUES ('$nome', '$email', '$senha')");

  header('Location: index.php');
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
  <form action="criar-conta.php" method="POST">
    <label for="nome">Nome de Usuário:</label>
    <input type="text" name="nome" id="nome" class="inputUser" required><br><br>

    <label for="email">E-mail:</label>
    <input type="email" name="email" id="email" class="inputUser" required><br><br>

    <label for="senha">Senha:</label>
    <input type="password" name="senha" id="senha" class="inputUser" required><br><br>

    <input type="submit" name="submit" id="submit">
  </form>

</body>
</html>
