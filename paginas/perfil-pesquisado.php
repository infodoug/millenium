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
    $sql = "SELECT idusuarios, nome, foto FROM usuarios WHERE idusuarios = ?";
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
                
                // Redireciona garantindo que o ?id= apareça na URL
                header("Location: perfil-pesquisado.php?id=" . $perfil_destino);
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
    <title>Millenium - <?php echo $user_data['nome'] ?></title>
</head>
<body>
    <div id="header-container"></div>
    <main>
        <div class="container-perfil">
            <div class="center-perfil">
                <div class="header-perfil">
                    <div class="foto">
                        <img height="170" width="170" src='../<?php echo $user_pesq_data['foto']; ?>' alt='erro na imagem'></img>
                    </div>
                    <div class="infos">
                        <div class="nome">
                        <?php
                            echo $user_pesq_data['nome'];
                        ?>
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
                                    // Adicione a tag <form>
                                        echo '<form method="POST" action="" class="comment-form">'; 
                                            echo '<textarea name="comment-text" class="comment-input" placeholder="Digite um comentário..."></textarea>';
                                            
                                            // Input escondido para o PHP saber qual post está sendo comentado
                                            echo '<input type="hidden" name="post_id" value="' . $linhapost['postid'] . '">';
                                            echo '<input type="hidden" name="id-user-pesquisado" value="' . $num_id . '">';
                                            // O botão com type="submit" e o name "action"
                                            echo '<button class="send-comment" type="submit" name="action" value="send-comment">Comentar</button>';
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
                                        echo '<div class="comment-header">';
                                        echo '<img width="20" src="../' . $c['foto'] . '">';
                                        echo '<strong>' . htmlspecialchars($c['nome']) . '</strong>';
                                        echo '</div>';
                                        echo '<p>' . htmlspecialchars($c['comentario']) . '</p>';
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
                    <form method="POST">
                        <textarea class="mural-textarea" name="mural-text" placeholder="Escreva algo no mural..." required></textarea>
                        <input type="hidden" name="perfil_id" value="<?php echo $num_id; ?>">
                        <button type="submit" name="action" value="post-mural">Fixar no Mural</button>
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
                                        echo '<strong>' . htmlspecialchars($recado['nome']) . '</strong>';
                                        echo '<span class="mural-date">' . date('d/m/H:i', strtotime($recado['data_postagem'])) . '</span>';
                                    echo '</div>';
                                    echo '<p>' . nl2br(htmlspecialchars($recado['mensagem'])) . '</p>';
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
</body>
</html>
