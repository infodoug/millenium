
<?php
include('../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_solicitante = $_POST['id_solicitante'] ?? null;
    $id_solicitado = $_POST['id_solicitado'] ?? null;

    if ($id_solicitante && $id_solicitado) {
        $sql = "UPDATE friends 
                SET isFriend = '1'
                WHERE (id_solicitante = '$id_solicitante' AND id_solicitado = '$id_solicitado') 
                OR    (id_solicitante = '$id_solicitado' AND id_solicitado = '$id_solicitante')";
        
        if ($conexao->query($sql) === TRUE) {
            // Insere notificação para quem pediu amizade
            if ($id_solicitante && $id_solicitado && $id_solicitante != $id_solicitado) {
                // busca nome do usuário que aceitou
                $res = $conexao->query("SELECT nome FROM usuarios WHERE idusuarios = '" . $conexao->real_escape_string($id_solicitado) . "' LIMIT 1");
                $row = $res ? $res->fetch_assoc() : null;
                $nomeAceitou = $row['nome'] ?? 'Um usuário';
                $texto = $conexao->real_escape_string($nomeAceitou . ' aceitou seu pedido de amizade.');
                $link = '/millenium/paginas/perfil-pesquisado.php?id=' . intval($id_solicitado);
                $conexao->query("INSERT INTO notifications (user_id, actor_id, type, link, text) VALUES ('" . intval($id_solicitante) . "', '" . intval($id_solicitado) . "', 'friend_accept', '" . $conexao->real_escape_string($link) . "', '" . $texto . "')");
            }
            echo "sucesso";
        } else {
            echo "erro: " . $conexao->error;
        }
    }
}
?>