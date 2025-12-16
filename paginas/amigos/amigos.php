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

    $user_id = (int) $user_data['idusuarios'];

    // busca todas as linhas na tabela friends onde o usuário é solicitante ou solicitado
    if ($stmt = $conexao->prepare("SELECT * FROM friends WHERE id_solicitante = ? OR id_solicitado = ? AND isFriend = 1")) {
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $friends = [];
        while ($row = $res->fetch_assoc()) {
            $friends[] = $row;
        }

        $stmt->close();
    } else {
        // erro na preparação da query
        $friends = [];
    }



?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo:ital,wght@0,100..900;1,100..900&family=Lilita+One&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../../pages.css">
  <link rel="stylesheet" href="../../paginas/perfil.css">
  <link rel="stylesheet" href="../../components/friend-button.css">
  <link rel="stylesheet" href="friends-page.css">
  <title>Millenium - <?php echo $user_data['nome'] ?></title>
</head>
<body>
  <!-- <div id="header-container"></div> -->
  <?php 
        include('../../components/header/header.html'); 
    ?>

  <h1 class="friends-title">Meus amigos</h1>

  <hr>

  <div id="friends-list">
    <?php foreach ($friends as $friend):
      $friend_id = ($friend['id_solicitante'] == $user_id) ? $friend['id_solicitado'] : $friend['id_solicitante'];

      // só exibe se amizade confirmada
      if ((int)$friend['isFriend'] !== 1) continue;

      // busca nome e foto do amigo
      $friend_sql = "SELECT nome, foto FROM usuarios WHERE idusuarios = ?";
      $friend_data = ['nome' => 'Usuário', 'foto' => ''];
      if ($friend_stmt = $conexao->prepare($friend_sql)) {
          $friend_stmt->bind_param("i", $friend_id);
          $friend_stmt->execute();
          $friend_result = $friend_stmt->get_result();
          if ($rowf = $friend_result->fetch_assoc()) {
              $friend_data = $rowf;
          }
          $friend_stmt->close();
      }

      $photo_path = !empty($friend_data['foto'])
          ? '/millenium/' . $friend_data['foto']
          : '/millenium/assets/icons/default-avatar.png';
    ?>
      <a class="friend" href="/millenium/paginas/perfil-pesquisado.php?id=<?php echo (int)$friend_id; ?>">
        <div class="container-friend-photo">
          <img class="friend-photo" src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($friend_data['nome']); ?>">
        </div>
        <div class="container-friend-info">
          <p><?php echo htmlspecialchars($friend_data['nome']); ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <script src="/millenium/components/header/header.js" defer></script>
  <script src="/millenium/scripts/user-suggestions.php" defer></script>



</body>
</html>