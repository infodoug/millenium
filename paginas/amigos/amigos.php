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

  <div id="friends-list">
    <?php foreach ($friends as $friend): ?>
      <div class="friend">
        <?php

          if ($friend['isFriend'] == 1) {

            // Aqui você pode buscar o nome e a foto do amigo usando o id do solicitante ou solicitado
            $friend_id = ($friend['id_solicitante'] == $user_id) ? $friend['id_solicitado'] : $friend['id_solicitante'];
            $friend_sql = "SELECT nome, foto FROM usuarios WHERE idusuarios = ?";
            if ($friend_stmt = $conexao->prepare($friend_sql)) {
                $friend_stmt->bind_param("i", $friend_id);
                $friend_stmt->execute();
                $friend_result = $friend_stmt->get_result();
                $friend_data = $friend_result->fetch_assoc();

                // ajusta caminho da imagem conforme sua estrutura (ex.: /millenium/uploads/)
                $photo_path = !empty($friend_data['foto']) 
                    ? '/millenium/' . $friend_data['foto'] 
                    : '/millenium/assets/icons/default-avatar.png';

                echo '<img class="friend-photo" src="'.htmlspecialchars($photo_path).'" alt="'.htmlspecialchars($friend_data['nome']).'"> ';
                echo htmlspecialchars($friend_data['nome']); // Exibe o nome do amigo

                $friend_stmt->close();
            }
            
          }
        ?>
      </div>
    <?php endforeach; ?>
  </div>

  <script src="/millenium/components/header/header.js" defer></script>
  <script src="/millenium/scripts/user-suggestions.php" defer></script>



</body>
</html>