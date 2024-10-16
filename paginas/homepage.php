<?php

    session_start();

    include('../classes/post.php');

    // print_r($_SESSION);
    if((!isset($_SESSION['email']) == true) and (!isset($_SESSION['senha']) == true))
    {
        header('Location: index.php');
    }
    $logado = $_SESSION['email'];

    include('../config.php');
    include('../configs/arquivo-config.php');
    
    $sql = "SELECT idusuarios, nome, foto FROM usuarios WHERE email='$logado'";
    $sql_nome = "SELECT nome FROM usuarios WHERE email='$logado'";
    $result = $conexao->query($sql);
    ($user_data = mysqli_fetch_assoc($result));

    // postagem
    $userid = $user_data['idusuarios'];
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $post = new Post();
        $post_result = $post->create_post($userid, $_POST);
        if($post_result != 'Digite algo para postar.<br>')
        {
            $post_query = mysqli_query($conexao, "INSERT INTO posts(postid,userid,post,image) VALUES ('$post_result[0]','$userid','$post_result[1]','$path')");
        }
    }



/*      if(isset($_POST['submit'])) {
        $result = mysqli_query($conexao, "INSERT INTO posts(image) VALUES ('$path')");
      }  */
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../pages.css">
    <title>Millenium - Início</title>
</head>
<body>
    <header>
        <a href="#"><h1 class="mil">Millenium</h1></a>
        <navbar>
            <nav><a href="#">Página Inicial</a></nav>
            <nav><a href="constelacoes.php">Constelações</a></nav>
            <nav><a href="#">Amigos</a></nav>
            <nav><a href="perfil.php">Perfil</a></nav>
        </navbar>
    </header>
    <main>
        <div class="container">
            <div class="mini-perfil">
                <div class="foto">
                    <img height="180" width="180" src='../<?php echo $user_data['foto']; ?>' alt='erro na imagem'></img>
                    
                </div>
                <div class="nome">
                    <?php
                        echo $user_data['nome'];
                    ?>
                </div>
            </div>
            <div class="timeline">
            
                <div class="novo-post">
                    <form enctype="multipart/form-data" method="POST">
                        <input name="arquivo" type="file">
                        <input name="post" type="text">
                        <button type="submit">Lançar</button>
                    </form>
                </div>

            </div>
            <div class="social">
                
            </div>
        </div>
    </main>
    
    
</body>
</html>