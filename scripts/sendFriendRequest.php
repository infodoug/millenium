<?php
session_start();
include('../config.php');

if(isset($_POST['id_solicitante']) && isset($_POST['id_solicitado'])){
    
    $id_user = mysqli_real_escape_string($conexao, $_POST['id_solicitante']);
    $id_friend = mysqli_real_escape_string($conexao, $_POST['id_solicitado']);


    // if !isFriend && not friend request found in friends table {
    //$sql = "INSERT INTO friends (id_solicitante, id_solicitado) VALUES ('$id_solicitante', '$id_solicitado')";

    $request_sent = "SELECT * FROM friends
                            WHERE (id_solicitante = '$id_user' AND id_solicitado = '$id_friend')
                            OR (id_solicitante = '$id_friend' AND id_solicitado = '$id_user')";

    $check_request_sent = $conexao->query($request_sent);

    if ($check_request_sent->num_rows == 0) {
        $sql = "INSERT INTO friends (id_solicitante, id_solicitado) VALUES ('$id_user', '$id_friend')";
    } else {
        $sql = "DELETE FROM friends 
                    WHERE (id_solicitante = '$id_user' AND id_solicitado = '$id_friend') 
                    OR    (id_solicitante = '$id_friend' AND id_solicitado = '$id_user')";
    }
    


    
    // else if !isFriend && friend request found in friends table {
    // sql DELETE row WHERE friend request found


 
    if($conexao->query($sql) === TRUE) {
        echo "sucesso";
    } else {
        echo "erro";
    } 

    if($check_request_sent) {
        echo "sucesso";
    } else {
        echo "erro";
    }
}
?>