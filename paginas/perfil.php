<?php
    session_start();
    include_once('../search_logic.php');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../pages.css">
    <link rel="stylesheet" href="perfil.css">
    <title>Millenium - <?php echo $user_data['nome'] ?></title>
</head>
<body>
    <header>
        <a href="#"><h1 class="mil">Millenium</h1></a>
        <navbar>
            <nav><a href="homepage.php">Página Inicial</a></nav>
            <!-- <nav><a href="constelacoes.php">Constelações</a></nav> -->
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
                        <div class="options">
                            <?php
                                echo "<a href='../contas-options/deletar-conta.php?idusuarios=$user_data[idusuarios]'>Excluir Conta</a>";
                            ?>
                            <a href="../logout.php">Sair</a>
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
                                '<img height="20" width="20" src=../' . $user_data['foto'] .' alt="erro na imagem"></img>' .
                                '<p>' . $user_data['nome'] . '</p>' .
                                '</div>' .
                                '<div class="text-content">' .
                                $linhapost["post"] . 
                                '</div>' .
                                '<div class="arquivos">' .
                                '<img height="300px" src=' . $linhapost["image"] . ' alt="erro na imagem"></img>' .
                                '</div>' .
                                '</div>' .
                                '<hr>';                        
                            }
                        ?>
                    </div>

                </div>
            </div>
            <div class="side-perfil">
                
            </div>
        </div>
    </main>
    <script src="../scripts/user-suggestions.php"></script>
</body>
</html>
