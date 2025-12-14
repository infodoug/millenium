<?php

    session_start();
    include_once('../../search_logic.php');
    include('../../classes/post.php');

    // print_r($_SESSION);
    if((!isset($_SESSION['email']) == true) and (!isset($_SESSION['senha']) == true))
    {
        header('Location: index.php');
    }
    $logado = $_SESSION['email'];

    include('../../config.php');

    // dados usuario logado
    $sql = "SELECT idusuarios, nome, foto FROM usuarios WHERE email='$logado'";
    $result = $conexao->query($sql);
    ($user_data = mysqli_fetch_assoc($result));

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../components/header/header.css">
  <link rel="stylesheet" href="../../pages.css">
  <link rel="stylesheet" href="../../../perfil.css">
  <link rel="stylesheet" href="../../components/header/header.css">
  <link rel="stylesheet" href="../../components/friend-button.css">
  <title>Millenium - <?php echo $user_data['nome'] ?></title>
</head>
<body>
  <!-- <div id="header-container"></div> -->
  <?php 
        include('../../components/header/header.html'); 
    ?>

  <script src="/millenium/components/header/header.js" defer></script>
  <script src="/millenium/scripts/user-suggestions.php" defer></script>
</body>
</html>