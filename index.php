<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Millenium - Login</title>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
  <h1>Mi<span class="Ll">ll</span>enium</h1>

  <div class="login" name="login">
    <form action="testLogin.php" method="POST">
      <div class="form-fields">
        <input type="text" placeholder ="email" name="email" required>
      </div>
      <div class="form-fields">
        <input type="password" placeholder="senha" name="senha" required>
      </div>
      <input class="inputSubmit" type="submit" name="submit" value="Entrar">
      <p class="forgot-password"><a href="#">Esqueceu a senha? Clique aqui</a></p>
      <p class="nova-conta">Ainda não tem conta? <a href="criar-conta.php">Cadastre-se!</a></p>
    </form>
  </div>

  <script src="script.js"></script>
</body>
</html>
