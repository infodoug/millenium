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
                        <img height="170" width="170" src='../<?php echo $user_pesq_data['foto']; ?>' alt='erro na imagem'></img>
                    </div>
                    <div class="infos">
                        <div class="nome">
                        <?php
                            echo $user_pesq_data['nome'];
                        ?>
                        </div>
                        <button class="button-addFriend" onclick="sendFriendRequest()">
                            Adicionar amigo
                        </button>

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
        });


        

        function sendFriendRequest() {
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
                    alert("Solicitação de amizade enviada!");
                    // Opcional: Mudar o texto do botão para "Enviado"
                } else {
                    alert("Erro ao enviar solicitação.");
                    console.log(data);
                }
            })
            .catch(error => console.error('Erro:', error));
        }
        





        
    </script>
</body>
</html>
