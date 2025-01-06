<?php

    session_start();
    include_once('../search_logic.php');
    include('../classes/post.php');

    // print_r($_SESSION);
    if((!isset($_SESSION['email']) == true) and (!isset($_SESSION['senha']) == true))
    {
        header('Location: index.php');
    }
    $logado = $_SESSION['email'];

    include('../config.php');

    // dados usuario logado
    $sql = "SELECT idusuarios, nome, foto FROM usuarios WHERE email='$logado'";
    $result = $conexao->query($sql);
    ($user_data = mysqli_fetch_assoc($result));

    // dados do usuário pesquisado
    $num_id = $_POST['id-user-pesquisado'];
    $sql = "SELECT idusuarios, nome, foto FROM usuarios WHERE idusuarios='$num_id'";
    $resultado_pesquisado = $conexao->query($sql);
    ($user_pesq_data = mysqli_fetch_assoc($resultado_pesquisado));

    // posts do usuário pesquisado
    $sql_post = "SELECT * FROM posts WHERE userid='$num_id'";
    $resultpost = $conexao->query($sql_post);
    $post_data = array(); // Inicializa um array para armazenar todas as linhas

    while ($row = mysqli_fetch_assoc($resultpost)) {
        // Adiciona cada linha ao array $post_data
        $post_data[] = $row;
    }


?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../pages.css">
    <title>Millenium</title>
</head>
<body>
    <header>
        <a href="#"><h1 class="mil">Millenium</h1></a>
        <navbar>
            <nav><a href="homepage.php">Página Inicial</a></nav>
            <!-- <nav><a href="constelacoes.php">Constelações</a></nav> -->
            <nav><a href="#">Amigos</a></nav>
            <nav><a href="perfil.php">Perfil</a></nav>
            <nav>
                <div class="pesquisa">
                    <input type="text" id="searchInput" placeholder="Digite para buscar...">
                    <ul id="suggestions"></ul>
                </div>                
            </nav>
        </navbar>
    </header>
    <main>
        <div class="container">
            <div class="mini-perfil">
                <div class="foto">
                    <img height="180" width="180" src='../<?php echo $user_pesq_data['foto']; ?>' alt='erro na imagem'></img>
                    
                </div>
                <div class="nome">
                    <?php
                        echo $user_pesq_data['nome'];
                    ?>
                </div>
            </div>
            <div class="timeline">
                <div class="posts-perfil">
                    <?php
                        foreach (array_reverse($post_data) as $linhapost) {
                            echo
                            '<div class="post">' .
                            '<div class="post-header">' .
                            '<img height="20" width="20" src=../' . $user_pesq_data['foto'] .' alt="erro na imagem"></img>' .
                            '<p>' . $user_pesq_data['nome'] . '</p>' .
                            '</div>' .
                            '<div class="text-content">' .
                            $linhapost["post"] . 
                            '</div>' .
                            '</div>' .
                            '<hr>';                        
                        }
                    ?>
                </div>
            </div>
            <div class="social">
                
            </div>
        </div>
    </main>
    <script src="../scripts/user-suggestions.php"></script>
</body>
</html>