<?php
    session_start();
    header('Content-Type: application/json');
    include('../config.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete-comment') {
        
        $comment_id = $_POST['comment_id'] ?? '';
        $user_id = $_SESSION['user_id'] ?? 0;

        // Validações
        if (empty($comment_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Comment ID inválido']);
            exit;
        }

        // Verificar se o comentário pertence ao usuário
        $stmt = $conexao->prepare("
            SELECT userid 
            FROM comentarios 
            WHERE idcomentarios = ?
        ");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro na preparação da query']);
            exit;
        }
        
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $comment = $result->fetch_assoc();
        $stmt->close();

        if (!$comment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Comentário não encontrado']);
            exit;
        }

        if ((int) $comment['userid'] !== (int) $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para deletar este comentário']);
            exit;
        }

        // Marcar como deletado (alterar texto para "comentário excluído")
        $deleted_message = '<i class="comment-deleted-message" style="color: gray;"> [comentário excluído] </i>';
        $stmt = $conexao->prepare("
            UPDATE comentarios 
            SET comentario = ? 
            WHERE idcomentarios = ?
        ");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro na preparação da query de atualização']);
            exit;
        }

        $stmt->bind_param("si", $deleted_message, $comment_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Comentário deletado com sucesso']);
        } else {
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao deletar o comentário']);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
?>
