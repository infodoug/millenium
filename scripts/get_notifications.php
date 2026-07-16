<?php
include('../config.php');
session_start();
$logado = $_SESSION['email'] ?? null;
if (!$logado) { echo json_encode(['unread'=>0,'notifications'=>[]]); exit; }
$res = $conexao->query("SELECT idusuarios, nome FROM usuarios WHERE email='" . $conexao->real_escape_string($logado) . "' LIMIT 1");
$user = $res ? $res->fetch_assoc() : null;
if (!$user) { echo json_encode(['unread'=>0,'notifications'=>[]]); exit; }
$uid = (int)$user['idusuarios'];

// unread count
$r = $conexao->query("SELECT COUNT(*) as c FROM notifications WHERE user_id='" . $uid . "' AND is_read=0");
$unread = ($r && ($row=$r->fetch_assoc())) ? (int)$row['c'] : 0;

// recent notifications
$stmt = $conexao->prepare("SELECT id, actor_id, type, link, text, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->bind_param('i', $uid);
$stmt->execute();
$resn = $stmt->get_result();
$notifs = [];
while ($n = $resn->fetch_assoc()) {
    $notifs[] = $n;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['unread'=>$unread, 'notifications'=>$notifs]);
?>