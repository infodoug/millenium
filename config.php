<?php

     $dbHost = 'localhost';
    $dbUsername = 'root';
    $dbPassword = 'rl2002';
    $dbName = 'millenium';

    $conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Cria tabela de notificações caso não exista
$createNotifications = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_id INT DEFAULT NULL,
    type VARCHAR(50) DEFAULT NULL,
    link VARCHAR(255) DEFAULT NULL,
    text TEXT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@$conexao->query($createNotifications);


/*     if($conexao->connect_errno)
    {
        echo "Erro";
    }
    else
    {
        echo "Conectado!";
    } */
?>

<?php
/*     $pdo = new PDO(
        "mysql:host=localhost;dbname=millenium;charset=utf8",
        "root",
        "rl2002"
    ); */
?>