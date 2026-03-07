<?php
    session_start();
    header('Content-Type: application/json');
    include('../config.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete-post') {
        
        $post_id = $_POST['post_id'] ?? '';
        $user_id = $_SESSION['user_id'] ?? 0;

        // Validações
        if (empty($post_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Post ID inválido']);
            exit;
        }

        // Verificar se o post pertence ao usuário
        $stmt = $conexao->prepare("
            SELECT userid, image 
            FROM posts 
            WHERE postid = ?
        ");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro na preparação da query']);
            exit;
        }
        
        $stmt->bind_param("s", $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        $stmt->close();

        if (!$post) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post não encontrado']);
            exit;
        }

        if ((int) $post['userid'] !== (int) $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para deletar este post']);
            exit;
        }

        // Deletar arquivo de imagem se existir
        if (!empty($post['image']) && file_exists('../' . $post['image'])) {
            unlink('../' . $post['image']);
        }

        // Marcar como deletado (deixar o texto com mensagem de deletado e limpar imagem)
        $deleted_message = '<i class="post-deleted-message" style="color: gray;"> [postagem excluída] </i>';
        $stmt = $conexao->prepare("
            UPDATE posts 
            SET post = ?, image = '', is_deleted = 1, deleted_at = CURRENT_TIMESTAMP 
            WHERE postid = ?
        ");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro na preparação da query de atualização']);
            exit;
        }

        $stmt->bind_param("ss", $deleted_message, $post_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Post deletado com sucesso']);
        } else {
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao deletar o post']);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
?>
