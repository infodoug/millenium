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

    // dados do usuário pesquisado (aceita GET 'id' ou POST 'id-user-pesquisado')
    $num_id = null;
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $num_id = (int) $_GET['id'];
    } elseif (isset($_POST['id-user-pesquisado']) && is_numeric($_POST['id-user-pesquisado'])) {
        $num_id = (int) $_POST['id-user-pesquisado'];
    }

    if (empty($num_id)) {
        die('ID inválido.');
    }

    // busca usuário pesquisado com prepared statement
    $sql = "SELECT idusuarios, nome, foto FROM usuarios WHERE idusuarios = ?";
    if ($stmtp = $conexao->prepare($sql)) {
        $stmtp->bind_param("i", $num_id);
        $stmtp->execute();
        $resultado_pesquisado = $stmtp->get_result();
        $user_pesq_data = $resultado_pesquisado->fetch_assoc();
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
