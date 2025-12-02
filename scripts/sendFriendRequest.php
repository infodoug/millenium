<?php
session_start();
include('../config.php'); // Ajuste o caminho para conectar no banco

if(isset($_POST['id_solicitante']) && isset($_POST['id_solicitado'])){
    
    $id_solicitante = mysqli_real_escape_string($conexao, $_POST['id_solicitante']);
    $id_solicitado = mysqli_real_escape_string($conexao, $_POST['id_solicitado']);

    // Verifica se já não são amigos ou se já tem solicitação (Opcional, mas recomendado)
    // ... lógica de verificação aqui ...

    $sql = "INSERT INTO friends (id_solicitante, id_solicitado) VALUES ('$id_solicitante', '$id_solicitado')";
    
    if($conexao->query($sql) === TRUE) {
        echo "sucesso";
    } else {
        echo "erro";
    }
}
?>