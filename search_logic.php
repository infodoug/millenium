<?php

include_once(__DIR__ . '/config.php');

if ((!isset($_SESSION['email']) || !isset($_SESSION['senha']))) {
    header('Location: index.php');
    exit;
}

$logado = $_SESSION['email'];

$sql = "SELECT idusuarios, nome, foto FROM usuarios WHERE email='$logado'";
$result = $conexao->query($sql);
$user_data = mysqli_fetch_assoc($result);

$userid = $user_data['idusuarios'];
$sql_post = "SELECT * FROM posts WHERE userid='$userid'";
$resultpost = $conexao->query($sql_post);
$post_data = [];

while ($row = mysqli_fetch_assoc($resultpost)) {
    $post_data[] = $row;
}

$search_user = $conexao->query("SELECT * FROM usuarios");
$search_user_data = [];

while ($user_row = mysqli_fetch_assoc($search_user)) {
    $search_user_data[] = $user_row;
}
?>

