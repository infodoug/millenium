<?php

    session_start();

    // print_r($_SESSION);
    if((!isset($_SESSION['email']) == true) and (!isset($_SESSION['senha']) == true))
    {
        header('Location: index.php');
    }
    $logado = $_SESSION['email'];

    include_once('../config.php');
    //$sql = "SELECT * FROM usuarios ORDER BY idusuarios DESC";
    $sql = "SELECT idusuarios, nome, foto FROM usuarios WHERE email='$logado'";
    $sql_nome = "SELECT nome FROM usuarios WHERE email='$logado'";
    $result = $conexao->query($sql);
    ($user_data = mysqli_fetch_assoc($result));

    $userid = $user_data['idusuarios'];
    $sql_post = "SELECT * FROM posts WHERE userid='$userid'";
    $resultpost = $conexao->query($sql_post);
    $post_data = array(); // Inicializa um array para armazenar todas as linhas

    while ($row = mysqli_fetch_assoc($resultpost)) {
        // Adiciona cada linha ao array $post_data
        $post_data[] = $row;
    }

    $search_user = $conexao->query("SELECT * FROM usuarios");
    $search_user_data = array();

    while ($user_row = mysqli_fetch_assoc($search_user)) {
        $search_user_data[] = $user_row;
    }
    
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../pages.css">
    <title>Millenium - <?php echo $user_data['nome'] ?></title>
</head>
<body>
    <header>
        <a href="#"><h1 class="mil">Millenium</h1></a>
        <navbar>
            <nav><a href="homepage.php">Página Inicial</a></nav>
            <nav><a href="constelacoes.php">Constelações</a></nav>
            <nav><a href="#">Amigos</a></nav>
            <nav><a href="#">Perfil</a></nav>
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
                    <img height="180" width="180" src='../<?php echo $user_data['foto']; ?>' alt='erro na imagem'></img>
                </div>
                <div class="nome">
                    <?php
                        echo $user_data['nome'];
                    ?>
                </div>
                <div class="options">
                    <?php
                        echo "<a href='deletar-conta.php?idusuarios=$user_data[idusuarios]'>Excluir Conta</a>";
                    ?>
                    <a href="../logout.php">Sair</a>
                </div>
            </div>
            <div class="timeline">
                <div class="novo-post">
                    <form enctype="multipart/form-data" method="POST">
                        <input name="post" type="text">
                        <button type="submit">Lançar</button>
                    </form>
                </div>

                <div class="posts-perfil">
                    <?php
                        foreach (array_reverse($post_data) as $linhapost) {
                            echo
                            '<div class="post">' .
                            '<div class="post-header">' .
                            '<img height="20" width="20" src=../' . $user_data['foto'] .' alt="erro na imagem"></img>' .
                            '<p>' . $user_data['nome'] . '</p>' .
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
    </main>
    
    <script>
        const searchInput = document.getElementById('searchInput');
        const suggestionsList = document.getElementById('suggestions');
        const users = <?php echo json_encode($search_user_data); ?>; // Convertendo dados PHP para JSON
        const user_logado = <?php echo json_encode($user_data); ?>; // Convertendo dados PHP para JSON

        searchInput.addEventListener('input', function () {
            const inputValue = this.value.toLowerCase();
            let suggestions = [];
            if (inputValue.length > 0) {
                suggestions = users.filter(user =>
                    user['nome'].toLowerCase().includes(inputValue)
                );
                displaySuggestions(suggestions);
            } else {
                suggestionsList.innerHTML = ''; // Limpa as sugestões se não houver entrada
            }
        });

        function displaySuggestions(suggestions) {
            const link = document.getElementById('link-perfil');
            const html = suggestions.map(user => {
                if (user.idusuarios != user_logado['idusuarios']) {
                    return `<form action='perfil-pesquisado.php' method='post'>
                                <input name='id-user-pesquisado' value='${user.idusuarios}' type='hidden'>
                                <button type='submit' name='entrar'>

                                        <img height='30px' width='30px' src="../${user.foto}">
                                        ${user.nome}

                                </button>
                            </form>`
                    /* return `<a href='perfil-pesquisado.php?id=${user.idusuarios}'>

                    </a>`; */
                } else {
                    return `<a href='#.php'>
                        <li>
                            <img height='30px' width='30px' src="../${user.foto}">
                            ${user.nome}
                        </li>
                    </a>`;
                }
            }).join('');
            suggestionsList.innerHTML = html;
            const userid_pesquisado = suggestions.map(user => user.idusuarios)
            console.log(userid_pesquisado);

            suggestionsList.querySelectorAll('li').forEach(li => {
                li.addEventListener('click', function() {

                    searchInput.value = this.textContent;
                    suggestionsList.innerHTML = ''; // Limpa as sugestões ao selecionar
                    searchInput.value = '';
                });
            });
        }
    </script>
</body>
</html>
