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
    $user_id = (int) $userid;
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $post = new Post();
        $post_result = $post->create_post($userid, $_POST);
        if($post_result != 'Digite algo para postar.<br>')
        {
            $post_query = mysqli_query($conexao, "INSERT INTO posts(postid,userid,post,image) VALUES ('$post_result[0]','$userid','$post_result[1]','$path')");
        }
    }

    // Falta fazer a postagem não precisar de imagem

/*      if(isset($_POST['submit'])) {
        $result = mysqli_query($conexao, "INSERT INTO posts(image) VALUES ('$path')");
      }  */


    // busca todas as linhas na tabela friends onde o usuário é solicitante ou solicitado
    if ($stmt = $conexao->prepare("SELECT * FROM friends WHERE (id_solicitante = ? OR id_solicitado = ?) AND isFriend = 1")) {
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


    // posts dos amigos — buscar todos os posts dos amigos em uma query ordenada (mais recentes primeiro)
    $post_data = array();
    // coletar ids dos amigos
    $friend_ids = array();
    foreach ($friends as $f) {
        $fid = ($f['id_solicitante'] == $user_id) ? (int)$f['id_solicitado'] : (int)$f['id_solicitante'];
        $friend_ids[] = $fid;
    }

    if (!empty($friend_ids)) {
        // montar lista segura de inteiros
        $friend_ids = array_map('intval', $friend_ids);
        $ids_list = implode(',', $friend_ids);

        // buscar posts com dados do autor, ordenados por postid desc (mais novo primeiro)
        $sql_posts = "SELECT p.*, u.nome, u.foto FROM posts p JOIN usuarios u ON p.userid = u.idusuarios WHERE p.userid IN (" . $ids_list . ") ORDER BY p.postid DESC";
        if ($res_posts = $conexao->query($sql_posts)) {
            while ($row = $res_posts->fetch_assoc()) {
                $post_data[] = $row;
            }
        }
    }


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
    <div id="header-container"></div>
    <main>
        <div class="container">
            <div class="mini-perfil">
                <div class="foto">
                    <img height="125" width="125" src='../<?php echo $user_data['foto']; ?>' alt='erro na imagem'></img>
                    
                </div>
                <div class="nome">
                    <?php
                        echo $user_data['nome'];
                    ?>
                </div>
            </div>

            <div class="center">
                <div id="novo-post"></div>
                <div class="timeline">
                    <div class="posts-amigos">
                        <?php
                            foreach (array_reverse($post_data) as $linhapost) {
                                $author_photo = !empty($linhapost['foto']) ? '../' . $linhapost['foto'] : '../assets/icons/default-avatar.png';
                                $author_name = htmlspecialchars($linhapost['nome'] ?? 'Usuário');
                                $post_text = htmlspecialchars($linhapost['post'] ?? '');
                                $post_image = $linhapost['image'] ?? '';

                                echo '<div class="post">';
                                echo '<div class="post-header">';
                                echo '<img height="20" width="20" src="' . htmlspecialchars($author_photo) . '" alt="erro na imagem"></img>';
                                echo '<p>' . $author_name . '</p>';
                                echo '</div>';

                                echo '<div class="text-content">' . $post_text . '</div>';

                                if (!empty($post_image)) {
                                    echo '<div class="arquivos"><img height="300px" src="' . htmlspecialchars($post_image) . '" alt="erro na imagem"></img></div>';
                                } else {
                                    echo '<div class="arquivos"></div>';
                                }

                                echo '</div><hr>';
                            }
                        ?>
                    </div>
                </div>

            </div>
 
            <div class="social">
                
            </div>
        </div>
    </main>
    
    <script>
        // Espera o DOM carregar completamente antes de executar o script
        document.addEventListener("DOMContentLoaded", function() {
            // Carrega o header.html no container apropriado
            fetch('../components/new-post/new-post.html')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('novo-post').innerHTML = data;
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Carrega o header.html no container apropriado
            fetch('../components/header/header.html')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('header-container').innerHTML = data;

                    // Força a rolagem para o topo após carregar o header
                    window.scrollTo(0, 0); // Rola para o topo da página

            // Inclui o script de configurações após o carregamento do header
            const scriptConfig = document.createElement("script");
            scriptConfig.src = "../components/header/header.js";
            scriptConfig.defer = true;
            document.body.appendChild(scriptConfig);

            // Inclui o script de sugestões após o carregamento do header
            const script = document.createElement("script");
            script.src = "../scripts/user-suggestions.php";
            script.defer = true;
            document.body.appendChild(script);
            })
            .catch(error => console.error('Erro ao carregar header:', error));
        });
    </script>
</body>
</html>