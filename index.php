<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Millenium - Login</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <link href="script.js">
</head>
<body>
  <h1>Millenium</h1>

  <div class="container">
    <form action="testLogin.php" method="POST">
      <div class="form-fields">
        <label for="email">E-mail:</label>
        <input type="text" name="email" required>
      </div>
      <div class="form-fields">
        <label for="senha">Senha:</label>
        <input type="password" name="senha" required>
      </div>
      <input class="inputSubmit" type="submit" name="submit" value="Entrar">
      <p class="forgot-password"><a href="#">Esqueceu a senha? Clique aqui</a></p>
      <p class="nova-conta">Ainda não tem conta?<a href="criar-conta.php">Crie uma conta!</a></p>
    </form>
  </div>

</body>
</html>
