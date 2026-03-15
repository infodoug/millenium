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
    
    // Armazenar user_id na sessão se não estiver lá
    $_SESSION['user_id'] = $user_id;


    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        // Se o clique veio do botão de COMENTÁRIO
        if (isset($_POST['action']) && $_POST['action'] == 'send-comment') {
            $post_id_comentado = $_POST['post_id'];
            $texto_comentario = $_POST['comment-text'];
            

            if (!empty($texto_comentario)) {

            // Aqui você insere na sua tabela de comentários (exemplo):
            mysqli_query($conexao, "INSERT INTO comentarios (postid, userid, comentario) VALUES ('$post_id_comentado', '$user_id', '$texto_comentario')");
            
            echo "<script>alert('$texto_comentario');</script>";

            // REDIRECIONAR APÓS SUCESSO
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();

            } else {
                echo "<script>alert('Digite algo para comentar!');</script>";
            }
        }
        
        // Se o clique veio do formulário de NOVA POSTAGEM (new-post.html)
        // Certifique-se que o botão lá tenha name="action" e value="make_post"
        elseif (isset($_POST['action']) && $_POST['action'] == 'make_post') {
            $post = new Post();
            $post_result = $post->create_post($userid, $_POST);
            if($post_result != 'Digite algo para postar.<br>') {
                mysqli_query($conexao, "INSERT INTO posts(userid,post,image,created_at) VALUES ('$userid','$post_result[1]','$path',CURRENT_TIMESTAMP)");
            }

            // REDIRECIONAR APÓS SUCESSO
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
/*         $post = new Post();
        $post_result = $post->create_post($userid, $_POST);
        if($post_result != 'Digite algo para postar.<br>')
        {
            $post_query = mysqli_query($conexao, "INSERT INTO posts(postid,userid,post,image) VALUES ('$post_result[0]','$userid','$post_result[1]','$path')");
        } */
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

    // posts dos amigos e do próprio usuário
    $post_data = array();
    // Iniciar a lista de IDs já incluindo o usuário logado
    $all_relevant_ids = array($user_id); 

    foreach ($friends as $f) {
        $fid = ($f['id_solicitante'] == $user_id) ? (int)$f['id_solicitado'] : (int)$f['id_solicitante'];
        $all_relevant_ids[] = $fid;
    }

    if (!empty($all_relevant_ids)) {
        // montar lista segura de inteiros
        $all_relevant_ids = array_map('intval', $all_relevant_ids);
        $ids_list = implode(',', $all_relevant_ids);

        // buscar posts com dados do autor, ordenados por postid DESC (mais novo primeiro)
        // Usamos p.created_at ou p.postid para garantir a ordem cronológica
        $sql_posts = "SELECT p.*, u.nome, u.foto 
                    FROM posts p 
                    JOIN usuarios u ON p.userid = u.idusuarios 
                    WHERE p.userid IN (" . $ids_list . ") 
                    ORDER BY p.created_at DESC"; // Alterado para ordenar pela data de criação
        
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
    <meta name="viewport" content="width=device-width, initial-scale=0.1">
    <link rel="stylesheet" href="../pages.css">
    <link rel="stylesheet" href="../components/header/header.css">
    <link rel="stylesheet" href="../components/post-menu/post-menu.css">
    <link rel="stylesheet" href="../components/comment-menu/comment-menu.css">
    <link rel="stylesheet" href="../components/new-post/emoji-tab.css" />
    <title>Millenium - Início</title>
</head>
<body>
    <div id="header-container"></div>
    <main>
        <div class="container">
            <div class="mini-perfil">
                <svg class="card-shape" viewBox="0 0 100 120" preserveAspectRatio="none">
                    <path d="M 0,10 
                            Q 50,-5 100,10 
                            L 100,110 
                            Q 50,125 0,110 
                            Z" 
                        fill="white" />
                </svg>
                <div class="miniperfilcontent">
                    <div class="foto">
                        <img height="125" width="125" src='../<?php echo $user_data['foto']; ?>' alt='erro na imagem'></img>
                        
                    </div>
                    <div class="nome">
                        <?php
                            echo $user_data['nome'];
                            /* echo $user_id; */
                        ?>
                    </div>
                </div>
            </div>

            <div class="center">
                <div id="novo-post"></div>
                <div class="timeline">
                    <div class="posts-amigos">
                        <?php
                            foreach ($post_data as $linhapost) {
                                $author_photo = !empty($linhapost['foto']) ? '../' . $linhapost['foto'] : '../assets/icons/default-avatar.png';
                                $author_name = htmlspecialchars($linhapost['nome'] ?? 'Usuário');
                                $post_text = $linhapost['post'] ?? '';
                                $post_image = $linhapost['image'] ?? '';
                                $post_created_at = $linhapost['created_at'] ?? date('Y-m-d H:i:s');

                                echo '<div class="post" data-created-at="' . htmlspecialchars($post_created_at) . '">';
                                echo '<div class="post-header">';
                                echo '<img height="20" width="20" src="' . htmlspecialchars($author_photo) . '" alt="erro na imagem"></img>';
                                echo '<p>' . $author_name . '</p>';
                                
                                // Mostrar menu apenas para posts do usuário logado
                                if ((int)$linhapost['userid'] == (int)$user_id && ($linhapost['is_deleted'] == 0)) {
                                    echo '<div class="post-actions">';
                                    echo '<div class="post-menu-container">';
                                    echo '<button class="post-menu-btn" title="Opções do post">';
                                    echo '<img src="../assets/icons/arrow-down.png" alt="Menu">';
                                    echo '</button>';
                                    echo '<div class="post-menu">';
                                    echo '<button class="post-menu-item edit" data-post-id="' . htmlspecialchars($linhapost['postid']) . '">';
                                    echo '<span>✏️ Editar</span>';
                                    echo '</button>';
                                    echo '<button class="post-menu-item delete" data-post-id="' . htmlspecialchars($linhapost['postid']) . '">';
                                    echo '<span>🗑️ Deletar</span>';
                                    echo '</button>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                
                                echo '</div>';


                                if (!empty($post_image)) {
                                    echo '<div class="arquivos"><img height="300px" src="' . htmlspecialchars($post_image) . '" alt="erro na imagem"></img></div>';
                                } else {
                                    echo '<div class="arquivos"></div>';
                                }

                                echo '<div class="text-content">' . $post_text . '</div>';



                                echo '<div class="comment-area">';
                                    echo '<div class="my-comment">';
                                        // Adicione a tag <form>
                                        echo '<form method="POST" action="" class="comment-form">'; 
                                            echo '<textarea name="comment-text" class="comment-input" placeholder="Digite um comentário..."></textarea>';
                                            
                                            // Input escondido para o PHP saber qual post está sendo comentado
                                            echo '<input type="hidden" name="post_id" value="' . $linhapost['postid'] . '">';
                                            
                                            // O botão com type="submit" e o name "action"
                                            echo '<button class="send-comment" type="submit" name="action" value="send-comment">Comentar</button>';
                                        echo '</form>';
                                    echo '</div>';
                                    
                                    


                                $stmt_c = $conexao->prepare("
                                    SELECT c.idcomentarios, c.comentario, c.userid, u.nome, u.foto
                                    FROM comentarios c
                                    JOIN usuarios u ON c.userid = u.idusuarios
                                    WHERE c.postid = ?
                                    ORDER BY c.idcomentarios ASC
                                ");
                                $stmt_c->bind_param("i", $linhapost['postid']);
                                $stmt_c->execute();
                                $res_c = $stmt_c->get_result();

                                while ($c = $res_c->fetch_assoc()){

                                    echo '<div class="comment">';
                                        echo '<div class="comment-header">';
                                        echo '<img width="20" src="../' . $c['foto'] . '">';
                                        echo '<strong>' . htmlspecialchars($c['nome']) . '</strong>';
                                        
                                        // Mostrar menu apenas para comentários do usuário logado
                                        if ((int)$c['userid'] === $user_id) {
                                            echo '<div class="comment-menu-container">';
                                            echo '<button class="comment-menu-btn" title="Opções do comentário">';
                                            echo '<img src="../assets/icons/arrow-down.png" alt="Menu">';
                                            echo '</button>';
                                            echo '<div class="comment-menu">';
                                            echo '<button class="comment-menu-item delete" data-comment-id="' . htmlspecialchars($c['idcomentarios']) . '">';
                                            echo '<span>🗑️ Deletar</span>';
                                            echo '</button>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        
                                        echo '</div>';
                                        
                                        // Exibir comentário - não escapar se for um comentário excluído
                                        if (strpos($c['comentario'], '[comentário excluído]') !== false) {
                                            echo '<p>' . $c['comentario'] . '</p>';
                                        } else {
                                            echo '<p>' . htmlspecialchars($c['comentario']) . '</p>';
                                        }
                                        echo '<hr>';
                                    echo '</div>';

                                    
                                }

                                $stmt_c->close();


                                echo '</div>';
                                                                
                                echo '</div>';
                                echo '<hr class="post-separator">';
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

            // Inclui o script do menu de posts
            const scriptMenu = document.createElement("script");
            scriptMenu.src = "../components/post-menu/post-menu.js";
            scriptMenu.defer = true;
            document.body.appendChild(scriptMenu);

            // Inclui o script do menu de comentários
            const scriptCommentMenu = document.createElement("script");
            scriptCommentMenu.src = "../components/comment-menu/comment-menu.js";
            scriptCommentMenu.defer = true;
            document.body.appendChild(scriptCommentMenu);
            })
            .catch(error => console.error('Erro ao carregar header:', error));

            // Carrega o new-post.html
            fetch('../components/new-post/new-post.html')
                .then(response => response.text())
                .then(data => {

                    document.getElementById('novo-post').innerHTML = data;
                    let newPostDiv = document.querySelector('.newpostdiv');
                    newPostDiv.classList.add('active');

                    // lógica de arquivo de imagem do new-post.html
                    const arquivo = document.getElementById('arquivo');
                    console.log("Arquivo carregado via fetch:", arquivo);

                    let imagePreview = document.getElementById('imagePreview');
                    let previewBox = document.getElementById('previewBox');

                    let removeImageButton = document.querySelector('.removeImage-button');

                    let imagePath = "";


                    
                    if(arquivo) {
                        arquivo.addEventListener('change', function () {
                            if (this.files.length > 0) {
                                previewBox.style.display = 'block';
                                console.log("Arquivo selecionado!");
                                // link temporário da imagem
                                let blobUrl = URL.createObjectURL(this.files[0]);
                                //imagePath = this.files[0].name;
                                //console.log(imagePath);  
                                imagePreview.src = blobUrl;
                            }
                        
                        });

                        
                        removeImageButton.addEventListener('click', () => {
                            arquivo.value = '';
                            blobUrl = '';
                            imagePreview.src = '';
                            previewBox.style.display = 'none';
                            
                        });
                        
                    }


                    document.querySelector('form').addEventListener('submit', function(e) {
                        const editorDiv = document.querySelector('[contenteditable="true"]');
                        
                        // Agora validamos usando innerHTML para considerar imagens também
                        if (editorDiv.innerHTML.trim() === '') {
                            e.preventDefault();
                            alert('Digite algo para postar!');
                            return;
                        }
                        
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'post';
                        
                        // innerHTML vai capturar as tags <img> dos emojis
                        hiddenInput.value = editorDiv.innerHTML; 
                        this.appendChild(hiddenInput);
                    });

                    const emojiBtn = document.querySelector('.emojiButton');
                    const emojiContainer = document.getElementById('emoji-picker-container');

                    emojiBtn.addEventListener('click', function(e) {
                        e.preventDefault(); // Evita qualquer comportamento estranho no form

                        // Se o container estiver vazio, carrega o conteúdo via Fetch
                        if (emojiContainer.innerHTML === "") {
                            fetch('../components/new-post/emoji-tab.html') // Ajuste o caminho se necessário
                                .then(res => res.text())
                                .then(html => {
                                    // Remove a tag <link> ou tags <body> extras se houver
                                    emojiContainer.innerHTML = html;
                                    
                                    // Adicionar o emoji ao clicar nele
                                    emojiContainer.addEventListener('click', (event) => {
                                        if(event.target.tagName === 'IMG') {
                                            const editor = document.querySelector('.input-text');
                                            
                                            // Garante que o editor está focado para inserir no lugar certo
                                            editor.focus(); 
                                            
                                            // Pega o caminho da imagem que foi clicada na aba de emojis
                                            const imgSrc = event.target.src;
                                            const imgAlt = event.target.alt;
                                            
                                            // Monta a tag HTML da imagem (com um tamanho reduzido para parecer texto)
                                            const imgTag = `<img src="${imgSrc}" alt="${imgAlt}" style=" vertical-align: middle; margin: 0 2px;">`;
                                            
                                            // Insere a tag HTML da imagem onde o cursor do usuário está
                                            document.execCommand('insertHTML', false, imgTag);
                                        }
                                    });
                                });
                        }

                        // Toggle de visibilidade
                        if (emojiContainer.style.display === 'none') {
                            emojiContainer.style.display = 'block';
                        } else {
                            emojiContainer.style.display = 'none';
                        }
                    });

                    // Fechar ao clicar fora
                    document.addEventListener('click', function(event) {
                        if (!emojiBtn.contains(event.target) && !emojiContainer.contains(event.target)) {
                            emojiContainer.style.display = 'none';
                        }
                    });
                });


        });

    </script>
</body>
</html>