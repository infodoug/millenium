<?php
    session_start();
    header('Content-Type: application/json');
    include('../config.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit-post') {
        
        $post_id = $_POST['post_id'] ?? '';
        $new_text = $_POST['new_text'] ?? '';
        $user_id = $_SESSION['user_id'] ?? 0;

        // Validações
        if (empty($post_id) || empty($new_text)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Post ID ou texto inválido']);
            exit;
        }

        // Verificar se o post pertence ao usuário
        $stmt = $conexao->prepare("
            SELECT p.userid, p.created_at, p.image
            FROM posts p 
            WHERE p.postid = ?
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
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para editar este post']);
            exit;
        }

        // Sanitizar o texto
        $new_text = addslashes($new_text);
        
        // Processar imagem
        $new_image = $post['image']; // Manter imagem anterior por padrão
        
        // Verificar se quer remover a imagem
        if (isset($_POST['remove_image']) && $_POST['remove_image'] === 'true') {
            // Deletar imagem se solicitado
            if ($post['image'] && file_exists('../' . $post['image'])) {
                @unlink('../' . $post['image']);
            }
            $new_image = '';
        } elseif (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
            // Fazer upload de nova imagem
            $file = $_FILES['post_image'];
            
            // Validar tamanho (máx 5MB)
            if ($file['size'] > 5242880) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Arquivo muito grande (máximo 5MB)']);
                exit;
            }
            
            // Deletar imagem anterior se existir
            if ($post['image'] && file_exists('../' . $post['image'])) {
                @unlink('../' . $post['image']);
            }
            
            // Criar diretório se não existir
            $upload_dir = '../arquivos/';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
            
            $filename = uniqid('post_') . '_' . time() . '_' . basename($file['name']);
            $filepath = $upload_dir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da imagem']);
                exit;
            }
            
            $new_image = 'arquivos/' . $filename;
        }

        // Atualizar o post
        $stmt = $conexao->prepare("
            UPDATE posts 
            SET post = ?, image = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE postid = ?
        ");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro na preparação da query de atualização']);
            exit;
        }

        $stmt->bind_param("sss", $new_text, $new_image, $post_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Post atualizado com sucesso']);
        } else {
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o post']);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
?>
