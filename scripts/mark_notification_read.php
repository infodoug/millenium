<?php
include('../config.php');
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) exit;
// Deleta a notificação para que ela desapareça definitivamente após o clique
$conexao->query("DELETE FROM notifications WHERE id='" . $id . "'");
?>