<?php

    $dbHost = 'Localhost';
    $dbUsername = 'root';
    $dbPassword = '';
    $dbName = 'contas-millenium';

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