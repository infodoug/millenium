<?php
    session_start();
    include_once('../search_logic.php');
    include('../config.php');

    // dados do usuário logado
    $logado = $_SESSION['email'];
    $result = $conexao->query("SELECT idusuarios, nome, foto FROM usuarios WHERE email='$logado'");
    $user_data = mysqli_fetch_assoc($result);
    $user_id = (int) $user_data['idusuarios'];

    // id do perfil exibido (GET id ou próprio usuário)
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $num_id = (int) $_GET['id'];
    } else {
        $num_id = $user_id;
    }

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
            header("Location: perfil.php?id=" . $num_id);
            exit();

            } else {
                echo "<script>alert('Digite algo para comentar!');</script>";
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
    <link rel="stylesheet" href="perfil.css">
    <link rel="stylesheet" href="../components/header/header.css">
    <title>Millenium - <?php echo $user_data['nome'] ?></title>
</head>
<body>
    <div id="header-container"></div>
    <main>
        <div class="container-perfil">
            <div class="center-perfil">
                <div class="header-perfil">
                    <div class="foto">
                        <img height="170" width="170" src='../<?php echo $user_data['foto']; ?>' alt='erro na imagem'></img>
                    </div>
                    <div class="infos">
                        <div class="nome">
                            <?php
                                echo $user_data['nome'];
                            ?>
                        </div>
                        <button id="criarPost-button">
                            <img src="../assets/icons/plusSymbol.png" alt="">
                            Criar postagem
                        </button>
                    </div>


                </div>
                <div id="novo-post"></div>
                <div class="timeline">
                    <div class="posts-perfil">
                        <?php
                            foreach (array_reverse($post_data) as $linhapost) {
                            echo '<div class="post">';
                            echo '<div class="post-header">';
                            echo '<img height="20" width="20" src=../' . $user_data['foto'] .' alt="erro na imagem"></img>';
                            echo '<p>' . $user_data['nome'] . '</p>';
                            echo '</div>';
                            echo '<div class="text-content">';
                            echo '<img height="300px" src=' . $linhapost['image'] . '>';
                            echo '<br>' .
                            $linhapost["post"] . 
                            '</div>' .
                            '</div>';
                            echo '<div class="comment-area">';
                                echo '<div class="my-comment">';
                                    // Adicione a tag <form>
                                    echo '<form method="POST" action="">'; 
                                        echo '<textarea name="comment-text" class="comment-input"></textarea>';
                                        
                                        // Input escondido para o PHP saber qual post está sendo comentado
                                        echo '<input type="hidden" name="post_id" value="' . $linhapost['postid'] . '">';
                                        
                                        // O botão com type="submit" e o name "action"
                                        echo '<button class="send-comment" type="submit" name="action" value="send-comment">Enviar</button>';
                                        
                                        echo '<input type="hidden" name="id-user" value="' . $num_id . '">';

                                    echo '</form>';
                                echo '</div>';
                                


                            $stmt_c = $conexao->prepare("
                                SELECT c.comentario, u.nome, u.foto
                                FROM comentarios c
                                JOIN usuarios u ON c.userid = u.idusuarios
                                WHERE c.postid = ?
                                ORDER BY c.postid ASC
                            ");
                            $stmt_c->bind_param("i", $linhapost['postid']);
                            $stmt_c->execute();
                            $res_c = $stmt_c->get_result();

                            while ($c = $res_c->fetch_assoc()){

                                echo '<div class="comment">';
                                    echo '<img width="20" src="../' . $c['foto'] . '">';
                                    echo '<strong>' . htmlspecialchars($c['nome']) . '</strong>';
                                    echo '<p>' . htmlspecialchars($c['comentario']) . '</p>';
                                echo '</div>';
                            }

                            $stmt_c->close();


                            echo '</div>';
                            '<hr>';                      
                            }
                        ?>
                    </div>

                </div>
            </div>
            <div class="side-perfil">
                <!-- <p>excluir\/</p> -->
                <div class="options" style="display: none">
                    <?php
                        echo "<a href='../contas-options/deletar-conta.php?idusuarios=$user_data[idusuarios]'>Excluir Conta</a>";
                    ?>
                </div>
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
            })
            .catch(error => console.error('Erro ao carregar header:', error));
        });


        // componente de novo post
        document.addEventListener("DOMContentLoaded", function() {
            // Carrega o new-post.html
            fetch('../components/new-post/new-post.html')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('novo-post').innerHTML = data;
                    let newPostDiv = document.querySelector('.newpostdiv');
                    console.log(newPostDiv);
/*                     if (newPostDiv) {
                        newPostDiv.style.display = 'none';
                    } */
                    



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

                    if (newPostDiv) {
                        const criarPostButton = document.getElementById('criarPost-button');

                        criarPostButton.addEventListener('click', function() {
                            newPostDiv.classList.toggle('active');
                        });
                    }

                });
        });





        
    </script>
</body>
</html>
