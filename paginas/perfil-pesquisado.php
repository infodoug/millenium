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

    // define o user_id usado nos INSERTs
    $userid = $user_data['idusuarios'];
    $user_id = (int) $userid;

    // dados do usuário pesquisado (aceita GET 'id' ou POST 'id-user-pesquisado')

    $num_id = null;

    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $num_id = (int) $_GET['id'];
    } elseif (isset($_POST['id-user-pesquisado']) && is_numeric($_POST['id-user-pesquisado'])) {
        $num_id = (int) $_POST['id-user-pesquisado'];
    } elseif (isset($_POST['perfil_id']) && is_numeric($_POST['perfil_id'])) { 
        // Adicione esta condição para capturar o ID vindo do mural
        $num_id = (int) $_POST['perfil_id'];
    }

    // Se ainda for nulo, algo deu errado na navegação
    if (!$num_id) {
        die('ID do usuário não especificado.');
    }



    // busca usuário pesquisado com prepared statement
    $sql = "SELECT idusuarios, nome, foto, bio, emocao_emoji, emocao_texto, capa FROM usuarios WHERE idusuarios = ?";
    if ($stmtp = $conexao->prepare($sql)) {
        $stmtp->bind_param("i", $num_id);
        $stmtp->execute();
        $resultado_pesquisado = $stmtp->get_result();
        $user_pesq_data = $resultado_pesquisado->fetch_assoc();

        if (empty($num_id)) {
            $num_id = $user_pesq_data['idusuarios'];
        } 

        $stmtp->close();
    } else {
        die('Erro na consulta ao usuário.');
    }

    if (empty($user_pesq_data)) {
        die('Usuário não encontrado.');
    }

    // posts do usuário pesquisado (prepared)
    $post_data = array();
    $sql_post = "SELECT * FROM posts WHERE userid = ?";
    if ($stmtpost = $conexao->prepare($sql_post)) {
        $stmtpost->bind_param("i", $num_id);
        $stmtpost->execute();
        $resultpost = $stmtpost->get_result();
        while ($row = $resultpost->fetch_assoc()) {
            $post_data[] = $row;
        }
        $stmtpost->close();
    }


    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        // Se o clique veio do botão de COMENTÁRIO
        if (isset($_POST['action']) && $_POST['action'] == 'send-comment') {
            $post_id_comentado = $_POST['post_id'];
            $texto_comentario = $_POST['comment-text'];
            

            if (!empty($texto_comentario)) {

            // Aqui você insere na sua tabela de comentários (exemplo):
            mysqli_query($conexao, "INSERT INTO comentarios (postid, userid, comentario) VALUES ('$post_id_comentado', '$user_id', '$texto_comentario')");
            // notificação para o dono do post quando outro usuário comentar
            $res_owner = $conexao->query("SELECT userid FROM posts WHERE postid='" . intval($post_id_comentado) . "' LIMIT 1");
            if ($res_owner && ($row_owner = $res_owner->fetch_assoc())) {
                $post_owner = (int)$row_owner['userid'];
                if ($post_owner !== (int)$user_id) {
                    $autor_nome = $user_data['nome'];
                    $link = ($post_owner === $user_id) ? '/millenium/paginas/perfil.php#post-' . intval($post_id_comentado) : '/millenium/paginas/perfil-pesquisado.php?id=' . $post_owner . '#post-' . intval($post_id_comentado);
                    $texto_not = $conexao->real_escape_string($autor_nome . ' comentou no seu post.');
                    $conexao->query("INSERT INTO notifications (user_id, actor_id, type, link, text) VALUES ('" . $post_owner . "', '" . $user_id . "', 'comment_post', '" . $conexao->real_escape_string($link) . "', '" . $texto_not . "')");
                }
            }
            
            echo "<script>alert('$texto_comentario');</script>";

            // REDIRECIONAR APÓS SUCESSO
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $num_id);
            exit();

            } else {
                echo "<script>alert('Digite algo para comentar!');</script>";
            }
        }

        // Lógica para postar no Mural
        if (isset($_POST['action']) && $_POST['action'] == 'post-mural') {
            $mensagem_mural = mysqli_real_escape_string($conexao, $_POST['mural-text']);
            $perfil_destino = (int)$_POST['perfil_id'];

            if (!empty($mensagem_mural)) {
                $sql_mural = "INSERT INTO mural (autor_id, perfil_id, mensagem) VALUES ('$user_id', '$perfil_destino', '$mensagem_mural')";
                mysqli_query($conexao, $sql_mural);

                // Insere notificação para dono do perfil (se não for o próprio autor)
                if ($perfil_destino !== $user_id) {
                    $autor_nome = $user_data['nome'];
                    $link = ($perfil_destino === $user_id) ? '/millenium/paginas/perfil.php' : '/millenium/paginas/perfil-pesquisado.php?id=' . $perfil_destino;
                    $texto = $conexao->real_escape_string($autor_nome . ' escreveu no seu mural.');
                    $conexao->query("INSERT INTO notifications (user_id, actor_id, type, link, text) VALUES ('$perfil_destino', '$user_id', 'mural_post', '$link', '$texto')");
                }

                // Redireciona para perfil.php se o destino for o usuário logado, caso contrário para perfil-pesquisado
                if ($perfil_destino === $user_id) {
                    header("Location: perfil.php");
                } else {
                    header("Location: perfil-pesquisado.php?id=" . $perfil_destino);
                }
                exit();
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
    <link rel="stylesheet" href="perfil.css">
    <link rel="stylesheet" href="../components/header/header.css">
    <link rel="stylesheet" href="../components/friend-button.css">
    <link rel="stylesheet" href="../components/comment-menu/comment-menu.css">
    <title>Millenium - <?php echo $user_data['nome'] ?></title>
</head>
<body>
    <div id="header-container"></div>
    <script>window.currentUserId = <?php echo (int)$user_id; ?>;</script>
    <main>
        <div class="container-perfil">
            <div class="center-perfil">
                <div class="header-perfil">
                    <div class="capa" id="capa-div" style="background-image: url('../<?php echo isset($user_pesq_data['capa']) && $user_pesq_data['capa'] ? $user_pesq_data['capa'] : '' ?>');"></div>
                    <div class="foto">
                        <img height="170" width="170" src='../<?php echo $user_pesq_data['foto']; ?>' alt='erro na imagem'></img>
                    </div>
                    <div id="edit-emocao">
                        <?php if (!empty($user_pesq_data['emocao_emoji'])): ?>
                            <div class="user-status-display">
                                <img src="<?php echo $user_pesq_data['emocao_emoji']; ?>" alt="Emoji de Status" style="width: 25px; height: 25px;">
                                
                                <?php if (!empty($user_pesq_data['emocao_texto'])): ?>
                                    <span class="status-text">
                                        <?php echo htmlspecialchars($user_pesq_data['emocao_texto']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="infos">
                        <div class="nome">
                        <?php
                            echo $user_pesq_data['nome'];
                        ?>
                    <div class="bio-container" style="margin: 10px 0;">
                            <?php 
                                // Pega a bio do banco de dados (se não existir, mostra vazio)
                                $bio_atual = !empty($user_pesq_data['bio']) ? strip_tags($user_pesq_data['bio'], '<img>') : "Nenhuma biografia definida."; 
                            ?>
                            
                            <div class="bio-display" id="bio-display">
                                <?php 
                                    // 1. Pegamos o que está no banco de dados
                                    $texto_bio = $user_pesq_data['bio']; 

                                    // 2. Se o texto NÃO estiver vazio, limpamos as tags e mostramos
                                    if (!empty($texto_bio)) {
                                        echo '<p style="margin: 0; font-size: 14px;">' . strip_tags($texto_bio, "<img><br>") . '</p>';
                                    } else {
                                        // 3. Se estiver realmente vazio, mostra a frase padrão
                                        echo '<p style="margin: 0; font-size: 14px; color: #888;">Nenhuma biografia definida.</p>';
                                    }
                                ?>
                                
                                <?php if ($user_id === $num_id): // Só mostra o botão de editar se for o dono ?>
                                    <button id="edit-bio-btn" style="...">✏️ Editar Bio</button>
                                <?php endif; ?>
                            </div>
                    </div>
                        </div>
                        <button class="friend-button" onclick="sendFriendRequest()">
                            + Adicionar amigo
                        </button>
                        <button class="friend-button unsend" onclick="sendFriendRequest()">
                            × Remover pedido de amizade
                        </button>

                        <div class="unfriendArea">
                            <button class="friend-button isFriend" style="display: none;">
                                ✓ Amigo
                            </button>

                            <button class="friend-button unfriend" onclick="unfriend()" style="display: none;">
                                × Desfazer amizade
                            </button>
                        </div>



                        <div class="decide-friendship">
                            <button class="friend-button accept" onclick="acceptFriendship()">
                                Aceitar pedido de amizade
                            </button>
                            <button class="friend-button decline" onclick="unfriend()">
                                × Recusar 
                            </button>
                        </div>

                    </div>

                </div>
                <div class="timeline">
                    <div class="posts-perfil">
                    <?php
                        foreach (array_reverse($post_data) as $linhapost) {
                            
                            echo '<div class="post">';
                            echo '<div class="post-header">';
                            echo '<img height="20" width="20" src=../' . $user_pesq_data['foto'] .' alt="erro na imagem"></img>';
                            echo '<p>' . $user_pesq_data['nome'] . '</p>';
                            echo '</div>';
                            if (!empty($linhapost['image'])) {
                                echo '<div class="arquivos"><img height="300px" src="' . htmlspecialchars($linhapost['image']) . '" alt="erro na imagem"></img></div>';
                            } else {
                                echo '<div class="arquivos"></div>';
                            }
                            echo '<div class="text-content">';
                            echo '<br>' .
                            $linhapost["post"] . 
                            '</div>' .
                            '</div>';
                            echo '<div class="comment-area">';
                                echo '<div class="my-comment">';
                                    echo '<form method="POST" action="" class="comment-form"> ';
                                        echo '<div contenteditable="true" class="comment-input" style="min-height: 40px; border: 1px solid #ccc; padding: 5px;" placeholder="Digite um comentário..."></div>';
                                        echo '<input type="hidden" name="comment-text" class="hidden-comment-input">';
                                        echo '<div class="comment-actions">';
                                            echo '<div style="position: relative; display: inline-block;">';
                                                echo '<button class="comment-emoji-btn" popovertarget="emoji-tab" type="button" style="background: none; border: none; cursor: pointer;">';
                                                    echo '<img src="../assets/icons/emoticons/smiling.png" width="20" alt="Emojis">';
                                                echo '</button>';
                                                echo '<div class="comment-emoji-picker" style="display: none; position: absolute; bottom: 100%; left: 0; z-index: 10; background: white; border: 1px solid #ccc; padding: 5px;"></div>';
                                            echo '</div>';

                                            echo '<input type="hidden" name="post_id" value="' . $linhapost['postid'] . '">';
                                                
                                            echo '<input type="hidden" name="id-user-pesquisado" value="' . $num_id . '">';
                                            echo '<button class="send-comment" type="submit" name="action" value="send-comment">Comentar</button>';
                                        echo '</div>';
                                    echo '</form>';
                                echo '</div>';
                                


                                $stmt_c = $conexao->prepare("
                                    SELECT c.idcomentarios, c.comentario, c.userid, u.nome, u.foto
                                    FROM comentarios c
                                    JOIN usuarios u ON c.userid = u.idusuarios
                                    WHERE c.postid = ?
                                    ORDER BY c.idcomentarios DESC
                                ");
                                $stmt_c->bind_param("i", $linhapost['postid']);
                                $stmt_c->execute();
                                $res_c = $stmt_c->get_result();

                                while ($c = $res_c->fetch_assoc()){

                                    echo '<div class="comment">';
                                        echo '<div class="comment-header">';
                                        echo '<img width="20" src="../' . $c['foto'] . '">';
                                        $cUserId = intval($c['userid']);
                                        $cHref = ($cUserId === (int)$user_id) ? '/millenium/paginas/perfil.php' : '/millenium/paginas/perfil-pesquisado.php?id=' . $cUserId;
                                        echo '<a class="comment-author" href="' . $cHref . '"><strong>' . htmlspecialchars($c['nome']) . '</strong></a>';
                                        
                                        // AQUI ESTÁ A MÁGICA: Mostrar menu apenas para comentários do usuário logado
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
                                        
                                        // Verifica se foi excluído e processa os emojis usando strip_tags
                                        if (strpos($c['comentario'], '[comentário excluído]') !== false) {
                                            echo '<p>' . $c['comentario'] . '</p>';
                                        } else {
                                            $comentario_seguro = strip_tags($c['comentario'], '<img>');
                                            echo '<p class="comment-text">' . $comentario_seguro . '</p>';
                                        }

                                        echo '<hr>';
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
            <section class="mural-container">
                <h3>Mural de Recados</h3>
                
                <div class="mural-form">
                    <form method="POST" class="mural-form-action">
                        <div contenteditable="true" class="mural-input" style="min-height: 60px; border: 1px solid #ccc; padding: 5px; background: #fff; width: 100%;" placeholder="Escreva algo no mural..."></div>
                        
                        <input type="hidden" name="mural-text" class="hidden-mural-input">
                        <input type="hidden" name="perfil_id" value="<?php echo $num_id; ?>">

                        <div class="mural-actions" style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                            <div style="position: relative; display: inline-block;">
                                <button class="mural-emoji-btn" type="button" style="background: none; border: none; cursor: pointer;">
                                    <img src="../assets/icons/emoticons/smiling.png" width="20" alt="Emojis">
                                </button>
                                <div class="mural-emoji-picker" style="display: none; position: absolute; bottom: 100%; left: 0; z-index: 10; background: white; border: 1px solid #ccc; padding: 5px;"></div>
                            </div>
                            
                            <button type="submit" name="action" value="post-mural">Fixar no Mural</button>
                        </div>
                    </form>
                </div>

                <div class="mural-list">
                    <?php
                        // Busca as mensagens do mural deste perfil específico
                        $query_mural = "SELECT m.*, u.nome, u.foto 
                                        FROM mural m 
                                        JOIN usuarios u ON m.autor_id = u.idusuarios 
                                        WHERE m.perfil_id = ? 
                                        ORDER BY m.data_postagem DESC";
                        
                        $stmt_m = $conexao->prepare($query_mural);
                        $stmt_m->bind_param("i", $num_id);
                        $stmt_m->execute();
                        $res_mural = $stmt_m->get_result();

                        if ($res_mural->num_rows > 0) {
                            while ($recado = $res_mural->fetch_assoc()) {
                                echo '<div class="mural-item">';
                                    echo '<div class="mural-item-header">';
                                        echo '<img src="../' . $recado['foto'] . '" width="30" height="30">';
                                        $ra = intval($recado['autor_id']);
                                        $rHref = ($ra === (int)$user_id) ? '/millenium/paginas/perfil.php' : '/millenium/paginas/perfil-pesquisado.php?id=' . $ra;
                                        echo '<a class="mural-author" href="' . $rHref . '"><strong>' . htmlspecialchars($recado['nome']) . '</strong></a>';
                                        /* echo '<span class="mural-date">' . date('d/m/H:i', strtotime($recado['data_postagem'])) . '</span>'; */
                                    echo '</div>';
                                    // Usa o strip_tags para liberar APENAS a tag <img>, igual aos comentários
                                    $mensagem_segura = strip_tags($recado['mensagem'], '<img>');
                                    echo '<p>' . nl2br($mensagem_segura) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="empty-mural">Nenhum recado ainda. Seja o primeiro!</p>';
                        }
                        $stmt_m->close();
                    ?>
                </div>
            </section>
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


            let decideFriendship = document.querySelector('.decide-friendship');
            let friendButton = document.querySelector('.friend-button');
            let friendButtonUnsend = document.querySelector('.friend-button.unsend');
            decideFriendship.style.display = 'none';
            friendButton.style.display = 'none';
            friendButtonUnsend.style.display = 'none';
        });


        <?php
            $id_user = $user_data['idusuarios'];
            $id_friend = $user_pesq_data['idusuarios'];
            $isFriend = "SELECT * FROM friends
                                    WHERE (id_solicitante = '$id_user' AND id_solicitado = '$id_friend' AND isFriend = 1)
                                    OR (id_solicitante = '$id_friend' AND id_solicitado = '$id_user' AND isFriend = 1)";

            $check_isFriend = (($conexao->query($isFriend))->num_rows > 0) ? true : false;

        ?>

        let isFriend = <?php echo json_encode($check_isFriend);?>;

        document.addEventListener('DOMContentLoaded', function() {
            if (isFriend) {
                // Quando já são amigos, esconder todos os botões de amizade
                // exceto o botão de 'unfriend' (remover amizade).
                let allFriendButtons = document.querySelectorAll('.friend-button');
                let unfriendArea = document.querySelector('.unfriendArea');
                let decideFriendship = document.querySelector('.decide-friendship');
                allFriendButtons.forEach(btn => {
                    if (btn.classList.contains('unfriend') || btn.classList.contains('isFriend')) {
                        btn.style.display = 'block';
                    } else {
                        btn.style.display = 'none';
                    }
                });
                if (decideFriendship) decideFriendship.style.display = 'none';

                if (unfriendArea) unfriendArea.style.display = 'flex';
            }
        });

        // > Botão de amizade na visão do SOLICITADO
        <?php
            $id_user = $user_data['idusuarios'];
            $id_friend = $user_pesq_data['idusuarios'];
            $request_received = "SELECT * FROM friends
                                    WHERE (id_solicitante = '$id_friend' AND id_solicitado = '$id_user')";
                                    

            $check_request_received = (($conexao->query($request_received))->num_rows > 0) ? true : false;
        ?>

        let receivedFriendship = <?php echo json_encode($check_request_received);?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            let decideFriendship = document.querySelector('.decide-friendship');
            //let declineButton = document.querySelector('.friend-button.decline');

            

            //if (decideFriendship && friendButtonUnsend) {
            if (decideFriendship) {
                if (!receivedFriendship) {
                    decideFriendship.style.display = 'none';
                } else {
                    decideFriendship.style.display = 'flex';
                }
            }
        });
        
        // <
        

        function acceptFriendship() {
            let idSolicitante = "<?php echo $user_data['idusuarios']; ?>";
            let idSolicitado = "<?php echo $user_pesq_data['idusuarios']; ?>";

            let formData = new FormData();
            formData.append('id_solicitante', idSolicitante);
            formData.append('id_solicitado', idSolicitado);

            fetch('../scripts/acceptFriendRequest.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if(data.includes("sucesso")) {
                    console.log("Amizade aceita!");
                    // Recarrega a página ou atualiza a interface
                    location.reload();
                } else {
                    console.error(data);
                }
            })
            .catch(error => console.error('Erro:', error));
        }


        // > Botão de amizade na visão do SOLICITANTE
        <?php
            $id_user = $user_data['idusuarios'];
            $id_friend = $user_pesq_data['idusuarios'];
            $request_sent = "SELECT * FROM friends
                                    WHERE (id_solicitante = '$id_user' AND id_solicitado = '$id_friend')";
                                    

            $check_request_sent = (($conexao->query($request_sent))->num_rows > 0) ? true : false;
        ?>


        let askedFriendship = <?php echo json_encode($check_request_sent);?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            let friendButton = document.querySelector('.friend-button');
            let friendButtonUnsend = document.querySelector('.friend-button.unsend');

            if (friendButton && friendButtonUnsend && !receivedFriendship) {
                if (!askedFriendship) {
                    friendButton.style.display = 'block';
                    friendButtonUnsend.style.display = 'none';
                } else if (askedFriendship && !isFriend){
                    friendButton.style.display = 'none';
                    friendButtonUnsend.style.display = 'block';
                }
            } else if (receivedFriendship) {
                    friendButton.style.display = 'none';
                    friendButtonUnsend.style.display = 'none';
            }


            // > alternacao entre isFriend e unfriend
            let unfriendArea = document.querySelector('.unfriendArea');
            let isFriendButton = document.querySelector('.isFriend');
            let unfriendButton = document.querySelector('.unfriend');

            if (unfriendArea) {
                if (isFriendButton) isFriendButton.style.display = 'block';
                if (unfriendButton) unfriendButton.style.display = 'none';


                unfriendArea.addEventListener('mouseenter', () => {
                    if (isFriendButton) isFriendButton.style.display = 'none';
                    if (unfriendButton) unfriendButton.style.display = 'block';
                });

                unfriendArea.addEventListener('mouseleave', () => {
                    if (isFriendButton) isFriendButton.style.display = 'block';
                    if (unfriendButton)  unfriendButton.style.display = 'none';
                });
            }

            if (!isFriend) {
                    if (isFriendButton) isFriendButton.style.display = 'none';
                    if (unfriendButton)  unfriendButton.style.display = 'none';
                    
            }
            // <

        // ==========================================
            // LÓGICA DE EMOJIS PARA OS COMENTÁRIOS
            // ==========================================
            
            const commentEmojiBtns = document.querySelectorAll('.comment-emoji-btn');

            commentEmojiBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    // O container do picker está logo depois do botão no HTML
                    const pickerContainer = this.nextElementSibling; 
                    // O editor do comentário está no mesmo formulário
                    const form = this.closest('.comment-form');
                    const editorDiv = form.querySelector('.comment-input');

                    // Se o picker estiver vazio, carrega o HTML
                    if (pickerContainer.innerHTML === "") {
                        fetch('../components/new-post/emoji-tab.html')
                            .then(res => res.text())
                            .then(html => {
                                pickerContainer.innerHTML = html;
                                
                                // Adiciona o evento de clique nos emojis
                                pickerContainer.addEventListener('click', (event) => {
                                    if(event.target.tagName === 'IMG') {
                                        editorDiv.focus();
                                        
                                        const imgSrc = event.target.src;
                                        const imgAlt = event.target.alt;
                                        // Tag da imagem com largura fixa para não ficar gigante no comentário
                                        const imgTag = `<img src="${imgSrc}" alt="${imgAlt}" style="height:30px; width:auto; vertical-align: middle; margin: 0 2px;">`;
                                        
                                        document.execCommand('insertHTML', false, imgTag);
                                    }
                                });
                            });
                    }

                    // Alternar a visibilidade
                    if (pickerContainer.style.display === 'none' || pickerContainer.style.display === '') {
                        // Fecha todos os outros pickers abertos antes de abrir este (opcional, mas evita bagunça)
                        document.querySelectorAll('.comment-emoji-picker').forEach(p => p.style.display = 'none');
                        pickerContainer.style.display = 'block';
                    } else {
                        pickerContainer.style.display = 'none';
                    }
                });
            });

            // Fechar os pickers de comentário ao clicar em qualquer lugar fora deles
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.comment-emoji-btn') && !event.target.closest('.comment-emoji-picker')) {
                    document.querySelectorAll('.comment-emoji-picker').forEach(picker => {
                        picker.style.display = 'none';
                    });
                }
            });

            // Atualizar o input hidden antes de enviar o formulário de comentário
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const editorDiv = this.querySelector('.comment-input');
                    const hiddenInput = this.querySelector('.hidden-comment-input');

                    // Se os elementos existirem (evita erros em formulários antigos)
                    if (editorDiv && hiddenInput) {
                        if (editorDiv.innerHTML.trim() === '') {
                            e.preventDefault();
                            alert('Digite algo para comentar!');
                            return;
                        }
                        // Passa o HTML completo (texto + imagens de emoji) para o input oculto
                        hiddenInput.value = editorDiv.innerHTML;
                    }
                });
            });

            // ==========================================
            // LÓGICA DE EMOJIS PARA O MURAL
            // ==========================================
            const muralEmojiBtn = document.querySelector('.mural-emoji-btn');
            const muralPickerContainer = document.querySelector('.mural-emoji-picker');
            const muralEditorDiv = document.querySelector('.mural-input');
            const muralForm = document.querySelector('.mural-form-action');
            const hiddenMuralInput = document.querySelector('.hidden-mural-input');

            if (muralEmojiBtn) {
                // Abrir e fechar aba de emojis
                muralEmojiBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    if (muralPickerContainer.innerHTML === "") {
                        fetch('../components/new-post/emoji-tab.html')
                            .then(res => res.text())
                            .then(html => {
                                muralPickerContainer.innerHTML = html;
                                
                                // Inserir emoji ao clicar
                                muralPickerContainer.addEventListener('click', (event) => {
                                    if(event.target.tagName === 'IMG') {
                                        muralEditorDiv.focus();
                                        
                                        const imgSrc = event.target.src;
                                        const imgAlt = event.target.alt;
                                        const imgTag = `<img src="${imgSrc}" alt="${imgAlt}" style="height:20px; width:auto; vertical-align: middle; margin: 0 2px;">`;
                                        
                                        document.execCommand('insertHTML', false, imgTag);
                                    }
                                });
                            });
                    }

                    // Alterna visibilidade do picker do mural
                    if (muralPickerContainer.style.display === 'none' || muralPickerContainer.style.display === '') {
                        // Esconde pickers de comentários se estiverem abertos
                        document.querySelectorAll('.comment-emoji-picker').forEach(p => p.style.display = 'none');
                        muralPickerContainer.style.display = 'block';
                    } else {
                        muralPickerContainer.style.display = 'none';
                    }
                });

                // Fechar picker ao clicar fora
                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.mural-emoji-btn') && !event.target.closest('.mural-emoji-picker')) {
                        if (muralPickerContainer) muralPickerContainer.style.display = 'none';
                    }
                });

                // Enviar os dados da div editável para o input hidden antes do submit
                if (muralForm) {
                    muralForm.addEventListener('submit', function(e) {
                        if (muralEditorDiv.innerHTML.trim() === '') {
                            e.preventDefault();
                            alert('Digite algo para fixar no mural!');
                            return;
                        }
                        hiddenMuralInput.value = muralEditorDiv.innerHTML;
                    });
                }
            }


        });


        function sendFriendRequest() {
            
            askedFriendship = !askedFriendship;
            let friendButton = document.querySelector('.friend-button');
            let friendButtonUnsend = document.querySelector('.friend-button.unsend');
            if (!askedFriendship) {
                friendButton.style.display = 'block';
                friendButtonUnsend.style.display = 'none';
            } else {
                friendButton.style.display = 'none';
                friendButtonUnsend.style.display = 'block';
            }
            

            //friendButton.classList.toggle('unsend');
            // Pegamos os IDs gerados pelo PHP e guardamos em variáveis JS
            let idSolicitante = "<?php echo $user_data['idusuarios']; ?>";
            let idSolicitado = "<?php echo $user_pesq_data['idusuarios']; ?>";

            // Cria os dados para enviar
            let formData = new FormData();
            formData.append('id_solicitante', idSolicitante);
            formData.append('id_solicitado', idSolicitado);

            // Envia para o arquivo PHP separado
            fetch('../scripts/sendFriendRequest.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if(data.includes("sucesso")) {
                    //alert("Solicitação de amizade enviada!");
                    // Opcional: Mudar o texto do botão para "Enviado"
                } else {
                    //alert("Erro ao enviar solicitação.");
                    console.log(data);
                }
            })
            .catch(error => console.error('Erro:', error));
            
        }
        
        function unfriend() {
            // Atualiza apenas a interface. A ação no servidor deve ser implementada separadamente.
            if (isFriend){
                if (!confirm('Tem certeza que deseja remover esta amizade?')) return;
            }

            let friendButton = document.querySelector('.friend-button');
            let friendButtonUnsend = document.querySelector('.friend-button.unsend');
            let unfriendButton = document.querySelector('.friend-button.unfriend');

            if (unfriendButton) unfriendButton.style.display = 'none';
            if (friendButton) friendButton.style.display = 'block';
            if (friendButtonUnsend) friendButtonUnsend.style.display = 'none';


            let unfriendArea = document.querySelector('.unfriendArea');
            if (unfriendArea) unfriendArea.style.display = 'none';
            

            // TODO: implementar chamada ao servidor para remover amizade.
            console.log('unfriend clicked — implementar remoção no servidor.');

            let idSolicitante = "<?php echo $user_data['idusuarios']; ?>";
            let idSolicitado = "<?php echo $user_pesq_data['idusuarios']; ?>";

            // Cria os dados para enviar
            let formData = new FormData();
            formData.append('id_solicitante', idSolicitante);
            formData.append('id_solicitado', idSolicitado);

            // Envia para o arquivo PHP separado
            fetch('../scripts/unfriend.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if(data.includes("sucesso")) {
                    location.reload();
                } else {
                    //alert("Erro ao enviar solicitação.");
                    console.log(data);
                }
            })
            .catch(error => console.error('Erro:', error));

            
        }
        // <




        
    </script>
    <script src="../components/comment-menu/comment-menu.js"></script>
</body>
</html>
