<?php

    $dbHost = 'localhost';
    $dbUsername = 'root';
    $dbPassword = 'rl2002';
    $dbName = 'millenium';

    $conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

/*     if($conexao->connect_errno)
    {
        echo "Erro";
    }
    else
    {
        echo "Conectado!";
    } */
?>