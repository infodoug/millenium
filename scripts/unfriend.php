
<?php
include('../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_solicitante = $_POST['id_solicitante'] ?? null;
    $id_solicitado = $_POST['id_solicitado'] ?? null;

    if ($id_solicitante && $id_solicitado) {
        $sql = "DELETE FROM friends 
                WHERE (id_solicitante = '$id_solicitante' AND id_solicitado = '$id_solicitado') 
                OR    (id_solicitante = '$id_solicitado' AND id_solicitado = '$id_solicitante')";
        
        if ($conexao->query($sql) === TRUE) {
            echo "sucesso";
        } else {
            echo "erro: " . $conexao->error;
        }
    }
}
?>