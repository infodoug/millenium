<?php

    session_start();

    // print_r($_SESSION);
    if((!isset($_SESSION['email']) == true) and (!isset($_SESSION['senha']) == true))
    {
        header('Location: index.php');
    }
    $logado = $_SESSION['email'];

    include_once('config.php');
    //$sql = "SELECT * FROM usuarios ORDER BY idusuarios DESC";
    $sql = "SELECT idusuarios, nome FROM usuarios WHERE email='$logado'";
    $sql_nome = "SELECT nome FROM usuarios WHERE email='$logado'";
    $result = $conexao->query($sql);
    ($user_data = mysqli_fetch_assoc($result));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="pages.css">
    <title>Millenium</title>
</head>
<body>
    <header>
        <a href="#"><h1 class="mil">Millenium</h1></a>
        <navbar>
            <nav><a href="#">Página Inicial</a></nav>
            <nav><a href="#">Constelações</a></nav>
            <nav><a href="#">Amigos</a></nav>
            <nav><a href="#">Perfil</a></nav>
            <?php
                echo "<nav><a href='#'>$user_data[nome]</a></nav>";
            ?>
            <nav><a href="logout.php">Sair</a></nav>
            <?php
                echo "<nav><a href='deletar-conta.php?idusuarios=$user_data[idusuarios]'>Excluir Conta</a></nav>";
            ?>
            
        </navbar>
    </header>
    <main>
        <div class="container">
            
        </div>
    </main>
    
    
</body>
</html>