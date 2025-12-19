<?php
session_start();
header('Content-Type: application/json');
include('../config.php');

if (!isset($_SESSION['email'])) {
    echo json_encode(['pending' => 0]);
    exit;
}

$email = $_SESSION['email'];

$user_id = null;
if ($stmt = $conexao->prepare("SELECT idusuarios FROM usuarios WHERE email = ?")) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_id = (int) $row['idusuarios'];
    }
    $stmt->close();
}

if (!$user_id) {
    echo json_encode(['pending' => 0]);
    exit;
}

$count = 0;
if ($stmt = $conexao->prepare("SELECT COUNT(*) AS cnt FROM friends WHERE id_solicitado = ? AND isFriend = 0")) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $count = (int) $row['cnt'];
    }
    $stmt->close();
}

echo json_encode(['pending' => $count]);

?>
