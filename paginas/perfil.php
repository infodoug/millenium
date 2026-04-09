<?php
    session_start();
    include_once('../search_logic.php');
    include('../config.php');
    include('../classes/post.php');
    include('../configs/arquivo-config.php');

    // dados do usuário logado
    $logado = $_SESSION['email'];
    $result = $conexao->query("SELECT idusuarios, nome, foto, bio, emocao_emoji, emocao_texto, capa FROM usuarios WHERE email='$logado'");
    $user_data = mysqli_fetch_assoc($result);
    $user_id = (int) $user_data['idusuarios'];
    
    // Armazenar user_id na sessão se não estiver lá
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $user_id;
    }

    // id do perfil exibido (GET id ou próprio usuário)
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $num_id = (int) $_GET['id'];
    } else {
        $num_id = $user_id;
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST') {

        if (isset($_POST['action']) && $_POST['action'] == 'update-emocao') {
            $emoji = mysqli_real_escape_string($conexao, $_POST['emocao-emoji']);
            $texto = mysqli_real_escape_string($conexao, $_POST['emocao-texto']);
            
            // Limita a 20 caracteres por segurança no servidor também
            $texto = mb_substr($texto, 0, 20);

            mysqli_query($conexao, "UPDATE usuarios SET emocao_emoji='$emoji', emocao_texto='$texto' WHERE idusuarios='$user_id'");
            header("Location: perfil.php?id=" . $num_id);
            exit();
        }

        // Lógica para atualizar a BIO
        if (isset($_POST['action']) && $_POST['action'] == 'update-bio') {
            $nova_bio = mysqli_real_escape_string($conexao, $_POST['bio-text']);
            
            // Atualiza no banco
            mysqli_query($conexao, "UPDATE usuarios SET bio='$nova_bio' WHERE idusuarios='$user_id'");
            
            // Redireciona para atualizar a página
            header("Location: perfil.php?id=" . $num_id);
            exit();
        }
        
        // Se veio imagem recortada via cropper (base64)
        if (!empty($_POST['foto_perfil_data']) && $user_id === $num_id) {
            $imgData = $_POST['foto_perfil_data'];
            if (preg_match('/^data:(image\/png|image\/jpeg);base64,(.*)$/', $imgData, $m)) {
                $mime = $m[1]; $data = base64_decode($m[2]); if ($data === false) die('Falha ao decodificar imagem!');
                $ext = ($mime === 'image/png') ? 'png' : 'jpg';
                $pasta = "../arquivos/"; $novoNomeDoArquivo = uniqid(); $path = $pasta . $novoNomeDoArquivo . '.' . $ext;
                $saved = file_put_contents($path, $data);
                if ($saved === false) die('Falha ao salvar arquivo!');
                $path_db = 'arquivos/' . $novoNomeDoArquivo . '.' . $ext;
                // remove previous profile image file if it was stored in the arquivos/ folder
                if (!empty($user_data['foto']) && strpos($user_data['foto'], 'arquivos/') === 0) {
                    $oldFull = __DIR__ . '/../' . $user_data['foto'];
                    if (file_exists($oldFull)) @unlink($oldFull);
                }
                mysqli_query($conexao, "UPDATE usuarios SET foto='$path_db' WHERE idusuarios='$user_id'"); header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $num_id); exit();
            }
        }

        // Se veio imagem de CAPA recortada via cropper (base64)
        if (!empty($_POST['foto_capa_data']) && $user_id === $num_id) {
            $imgData = $_POST['foto_capa_data'];
            if (preg_match('/^data:(image\/png|image\/jpeg);base64,(.*)$/', $imgData, $m)) {
                $mime = $m[1]; $data = base64_decode($m[2]); if ($data === false) die('Falha ao decodificar imagem!');
                $ext = ($mime === 'image/png') ? 'png' : 'jpg';
                $pasta = "../arquivos/"; $novoNomeDoArquivo = uniqid('capa_'); $path = $pasta . $novoNomeDoArquivo . '.' . $ext;
                $saved = file_put_contents($path, $data);
                if ($saved === false) die('Falha ao salvar arquivo!');
                $path_db = 'arquivos/' . $novoNomeDoArquivo . '.' . $ext;
                // remove previous cover image file if it was stored in the arquivos/ folder
                if (!empty($user_data['capa']) && strpos($user_data['capa'], 'arquivos/') === 0) {
                    $oldFull = __DIR__ . '/../' . $user_data['capa'];
                    if (file_exists($oldFull)) @unlink($oldFull);
                }
                mysqli_query($conexao, "UPDATE usuarios SET capa='$path_db' WHERE idusuarios='$user_id'"); header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $num_id); exit();
            }
        }

        // Fallback: upload clássico para capa (suporta `foto-capa-file`)
        if (isset($_FILES['foto-capa-file']) && $user_id === $num_id) {
            $arquivo = $_FILES['foto-capa-file'];
            if($arquivo['size'] > 2097152 * 25)
                die("Arquivo muito grande! Max: 50MB");
            if($arquivo['error'])
                die("Falha ao enviar arquivo!");

            $pasta = "../arquivos/";
            $nomeDoArquivo = $arquivo['name'];
            $novoNomeDoArquivo = uniqid('capa_');
            $extensao = strtolower(pathinfo($nomeDoArquivo, PATHINFO_EXTENSION));

            if($extensao != 'jpg' && $extensao != 'png')
                die('Tipo de arquivo inválido! Use JPG ou PNG.');

            $path = $pasta . $novoNomeDoArquivo . '.' . $extensao;
            $deu_certo = move_uploaded_file($arquivo['tmp_name'], $path);

            if ($deu_certo) {
                $path_db = "arquivos/" . $novoNomeDoArquivo . '.' . $extensao;
                // remove previous cover image file if it was stored in the arquivos/ folder
                if (!empty($user_data['capa']) && strpos($user_data['capa'], 'arquivos/') === 0) {
                    $oldFull = __DIR__ . '/../' . $user_data['capa'];
                    if (file_exists($oldFull)) @unlink($oldFull);
                }
                mysqli_query($conexao, "UPDATE usuarios SET capa='$path_db' WHERE idusuarios='$user_id'");
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $num_id);
                exit();
            }
        }

        // Fallback: upload clássico (suporta `foto-perfil` antigo ou `foto-perfil-file`)
        if ((isset($_FILES['foto-perfil']) && $user_id === $num_id) || (isset($_FILES['foto-perfil-file']) && $user_id === $num_id)) {
            $arquivo = isset($_FILES['foto-perfil-file']) ? $_FILES['foto-perfil-file'] : $_FILES['foto-perfil'];
            if($arquivo['size'] > 2097152 * 25)
                die("Arquivo muito grande! Max: 50MB");
            if($arquivo['error'])
                die("Falha ao enviar arquivo!");
            
            $pasta = "../arquivos/";
            $nomeDoArquivo = $arquivo['name'];
            $novoNomeDoArquivo = uniqid();
            $extensao = strtolower(pathinfo($nomeDoArquivo, PATHINFO_EXTENSION));

            if($extensao != 'jpg' && $extensao != 'png')
                die('Tipo de arquivo inválido! Use JPG ou PNG.');

            $path = $pasta . $novoNomeDoArquivo . '.' . $extensao;
            $deu_certo = move_uploaded_file($arquivo['tmp_name'], $path);
            
            if ($deu_certo) {
                // Atualizar foto no banco de dados
                $path_db = "arquivos/" . $novoNomeDoArquivo . '.' . $extensao;
                // remove previous profile image file if it was stored in the arquivos/ folder
                if (!empty($user_data['foto']) && strpos($user_data['foto'], 'arquivos/') === 0) {
                    $oldFull = __DIR__ . '/../' . $user_data['foto'];
                    if (file_exists($oldFull)) @unlink($oldFull);
                }
                mysqli_query($conexao, "UPDATE usuarios SET foto='$path_db' WHERE idusuarios='$user_id'");
                
                // Redirecionar para recarregar a página com a nova foto
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $num_id);
                exit();
            }
        }
        
        // Se o clique veio do botão de COMENTÁRIO
        if (isset($_POST['action']) && $_POST['action'] == 'send-comment') {
            $post_id_comentado = $_POST['post_id'];
            $texto_comentario = $_POST['comment-text'];
            

            if (!empty($texto_comentario)) {

            // Aqui você insere na sua tabela de comentários (exemplo):
            mysqli_query($conexao, "INSERT INTO comentarios (postid, userid, comentario) VALUES ('$post_id_comentado', '$user_id', '$texto_comentario')");
            
            echo "<script>alert('$texto_comentario');</script>";

            // REDIRECIONAR APÓS SUCESSO
            header("Location: perfil.php?id=" . $num_id);
            exit();

            } else {
                echo "<script>alert('Digite algo para comentar!');</script>";
            }
        } 
        // Se o clique veio do formulário de NOVA POSTAGEM (new-post.html)
        // Certifique-se que o botão lá tenha name="action" e value="make_post"
        elseif (isset($_POST['action']) && $_POST['action'] == 'make_post') {
                    $post = new Post();
                    // Corrigido de $userid para $user_id
                    $post_result = $post->create_post($user_id, $_POST);
                    if($post_result != 'Digite algo para postar.<br>') {
                        // Corrigido de $userid para $user_id
                        mysqli_query($conexao, "INSERT INTO posts(userid,post,image,created_at) VALUES ('$user_id','$post_result[1]','$path',CURRENT_TIMESTAMP)");
                    }

                    // REDIRECIONAR APÓS SUCESSO
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
        }

        // Lógica para postar no Mural
        if (isset($_POST['action']) && $_POST['action'] == 'post-mural') {
            $mensagem_mural = mysqli_real_escape_string($conexao, $_POST['mural-text']);
            $perfil_destino = (int)$_POST['perfil_id']; // O ID do dono do perfil (num_id)

            if (!empty($mensagem_mural)) {
                $sql_mural = "INSERT INTO mural (autor_id, perfil_id, mensagem) VALUES ('$user_id', '$perfil_destino', '$mensagem_mural')";
                mysqli_query($conexao, $sql_mural);
                
                // Redireciona para evitar reenvio de formulário
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $perfil_destino);
                exit();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=0.1">
    <link rel="stylesheet" href="../pages.css">
    <link rel="stylesheet" href="perfil.css">
    <link rel="stylesheet" href="../components/header/header.css">
    <link rel="stylesheet" href="../components/post-menu/post-menu.css">
    <link rel="stylesheet" href="../components/comment-menu/comment-menu.css">
    <link rel="stylesheet" href="../components/new-post/emoji-tab.css" />
    <title>Millenium - <?php echo $user_data['nome'] ?></title>
</head>
<body>
    <div id="header-container"></div>
    <main>
        <div class="container-perfil">
            <div class="center-perfil">
                <div class="header-perfil">
                    <div class="capa" id="capa-div" style="background-image: url('../<?php echo isset($user_data['capa']) && $user_data['capa'] ? $user_data['capa'] : '' ?>');"></div>
                    <div  style="cursor: pointer;" onclick="document.getElementById('foto-perfil-input').click();">
                        <img class="foto" height="170" width="170" src='../<?php echo $user_data['foto']; ?>' alt='Foto de perfil' id="main-profile-img">
                    </div>
                            <button id="edit-emocao" class="edit-emocao" title="Alterar Emoção" type="button">
                                <?php if(!empty($user_data['emocao_emoji'])): ?>
                                    <img src="<?php echo $user_data['emocao_emoji']; ?>" alt="Emoção atual">
                                <?php else: ?>
                                    <img src="../assets/icons/emoticons/smiling.png" alt="Definir Emoção">
                                <?php endif; ?>
                                <?php if (!empty($user_data['emocao_texto'])): ?>
                                    <span class="status-text">
                                        <?php echo htmlspecialchars($user_data['emocao_texto']); ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                    <?php if ($user_id === $num_id): ?>


                            <form id="foto-form" method="POST" enctype="multipart/form-data" style="display: none;">
                                <input type="file" name="foto-perfil-file" id="foto-perfil-input" accept="image/jpeg,image/png">
                                <input type="hidden" name="foto_perfil_data" id="foto_perfil_data">
                            </form>

                            <form id="capa-form" method="POST" enctype="multipart/form-data" style="display: none;">
                                <input type="file" name="foto-capa-file" id="foto-capa-input" accept="image/jpeg,image/png">
                                <input type="hidden" name="foto_capa_data" id="foto_capa_data">
                            </form>

                            <div id="emocao-modal" style="display:none; position: absolute; z-index: 100; background: white; border: 1px solid #ccc; padding: 10px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                                <form method="POST">
                                    <p style="font-size: 12px; margin-bottom: 5px;">Escolha um emoji e status:</p>
                                    <div id="emoji-emocoes"></div>
                                    <input type="hidden" name="emocao-emoji" id="input-emocao-emoji" value="<?php echo $user_data['emocao_emoji']; ?>">
                                    <input type="text" name="emocao-texto" maxlength="20" placeholder="Estou me sentindo..." value="<?php echo $user_data['emocao_texto']; ?>" style="width: 100%; margin-bottom: 5px;">
                                    <button type="submit" name="action" value="update-emocao" style="width: 100%;">Salvar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php
                        // Mostrar botão de editar foto apenas para o próprio perfil
/*                         if ($user_id === $num_id) {
                            echo '<button id="edit-foto-button" class="edit-foto-btn" title="Trocar foto de perfil">
                                <img src="../assets/icons/emoticons/smiling.png" alt="Trocar foto de perfil">
                            </button>';
                            echo '<form id="foto-form" method="POST" enctype="multipart/form-data" style="display: none;">';
                            echo '<input type="file" name="foto-perfil" id="foto-perfil-input" accept="image/jpeg,image/png" required>';
                            echo '</form>';
                        } */
                    ?>
                    <div class="infos">
                        <div class="nome">
                            <?php echo $user_data['nome']; ?>
                        </div>
                        
                        <div class="bio-container" style="margin: 10px 0;">
                            <?php 
                                // Pega a bio do banco de dados (se não existir, mostra vazio)
                                $bio_atual = !empty($user_data['bio']) ? strip_tags($user_data['bio'], '<img>') : "Nenhuma biografia definida."; 
                            ?>
                            
                            <div class="bio-display" id="bio-display">
                                <p style="margin: 0; font-size: 14px;"><?php echo strip_tags($user_data['bio'], '<img><br>'); ?></p>
                                <?php if ($user_id === $num_id): ?>
                                    <button id="edit-bio-btn" style="background: none; border: none; cursor: pointer; color: #888; font-size: 12px; margin-top: 5px; padding: 0;">✏️ Editar Bio</button>
                                <?php endif; ?>
                            </div>

                            <?php if ($user_id === $num_id): ?>
                            <div class="bio-form-container" id="bio-form-container" style="display: none;">
                                <form method="POST" id="bio-form">
                                    <div contenteditable="true" class="bio-input" style="min-height: 40px; border: 1px solid #ccc; padding: 5px; background: #fff; width: 100%; max-width: 300px; font-size: 14px;"><?php echo $user_data['bio']; ?></div>
                                    <input type="hidden" name="bio-text" class="hidden-bio-input">
                                    
                                    <div class="bio-actions" style="display: flex; gap: 10px; align-items: center; margin-top: 5px;">
                                        <div style="position: relative; display: inline-block;">
                                            <button class="bio-emoji-btn" type="button" style="background: none; border: none; cursor: pointer;">
                                                <img src="../assets/icons/emoticons/smiling.png" width="20" alt="Emojis">
                                            </button>
                                            <div class="bio-emoji-picker" style="display: none; position: absolute; top: 100%; left: 0; z-index: 10; background: white; border: 1px solid #ccc; padding: 5px;"></div>
                                        </div>
                                        
                                        <button type="submit" name="action" value="update-bio" style="padding: 2px 8px; font-size: 12px;">Salvar</button>
                                        <button type="button" id="cancel-bio-btn" style="padding: 2px 8px; font-size: 12px; background: #ccc; border: none; cursor:pointer;">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button id="criarPost-button">
                            <img src="../assets/icons/plusSymbol.png" alt="">
                            Criar postagem
                        </button>
                    </div>


                </div>
                <div id="novo-post"></div>
                <div class="timeline">
                    <div class="posts-perfil">
                        <?php
                            foreach (array_reverse($post_data) as $linhapost) {
                            $post_image = $linhapost['image'] ?? '';
                            $post_created_at = $linhapost['created_at'] ?? date('Y-m-d H:i:s');

                            echo '<div class="post" data-created-at="' . htmlspecialchars($post_created_at) . '">';
                            echo '<div class="post-header">';
                            echo '<img height="20" width="20" src=../' . $user_data['foto'] .' alt="erro na imagem"></img>';
                            echo '<p>' . $user_data['nome'] . '</p>';
                            
                            // Mostrar menu apenas para posts do usuário logado
                            if ((int)$linhapost['userid'] === $user_id && ($linhapost['is_deleted'] == 0)) {
                                echo '<div class="post-actions">';
                                echo '<div class="post-menu-container">';
                                echo '<button class="post-menu-btn" title="Opções do post">';
                                echo '<img src="../assets/icons/arrow-down.png" alt="Menu">';
                                echo '</button>';
                                echo '<div class="post-menu">';
                                echo '<button class="post-menu-item edit" data-post-id="' . htmlspecialchars($linhapost['postid']) . '">';
                                echo '<span>✏️ Editar</span>';
                                echo '</button>';
                                echo '<button class="post-menu-item delete" data-post-id="' . htmlspecialchars($linhapost['postid']) . '">';
                                echo '<span>🗑️ Deletar</span>';
                                echo '</button>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                            if (!empty($post_image)) {
                                echo '<div class="arquivos"><img height="300px" src="' . htmlspecialchars($post_image) . '" alt="erro na imagem"></img></div>';
                            } else {
                                echo '<div class="arquivos"></div>';
                            }
                            echo '<div class="text-content">';
                            echo '<br>' .
                            $linhapost["post"] . 
                            '</div>' .
                            '</div>';
                            echo '<div class="comment-area">';
                                echo '<div class="my-comment">';
                                    echo '<form method="POST" action="" class="comment-form"> ';
                                        echo '<div contenteditable="true" class="comment-input" style="min-height: 40px; border: 1px solid #ccc; padding: 5px;" placeholder="Digite um comentário..."></div>';
                                        echo '<input type="hidden" name="comment-text" class="hidden-comment-input">';
                                        echo '<div class="comment-actions">';
                                            echo '<div style="position: relative; display: inline-block;">';
                                                echo '<button class="comment-emoji-btn" popovertarget="emoji-tab" type="button" style="background: none; border: none; cursor: pointer;">';
                                                    echo '<img src="../assets/icons/emoticons/smiling.png" width="20" alt="Emojis">';
                                                echo '</button>';
                                                echo '<div class="comment-emoji-picker" style="display: none; position: absolute; bottom: 100%; left: 0; z-index: 10; background: white; border: 1px solid #ccc; padding: 5px;"></div>';
                                            echo '</div>';
                                        

                                            echo '<input type="hidden" name="post_id" value="' . $linhapost['postid'] . '">';
                                                
                                            echo '<button class="send-comment" type="submit" name="action" value="send-comment">Comentar</button>';

                                        echo '</div>';
                                    echo '</form>';
                                echo '</div>';
                                


                            $stmt_c = $conexao->prepare("
                                SELECT c.idcomentarios, c.comentario, c.userid, u.nome, u.foto
                                FROM comentarios c
                                JOIN usuarios u ON c.userid = u.idusuarios
                                WHERE c.postid = ?
                                ORDER BY c.idcomentarios DESC
                            ");
                            $stmt_c->bind_param("i", $linhapost['postid']);
                            $stmt_c->execute();
                            $res_c = $stmt_c->get_result();

                            while ($c = $res_c->fetch_assoc()){

                                echo '<div class="comment">';
                                    echo '<div class="comment-header">';
                                    echo '<img width="20" src="../' . $c['foto'] . '">';
                                    echo '<strong>' . htmlspecialchars($c['nome']) . '</strong>';
                                    
                                    // Mostrar menu apenas para comentários do usuário logado
                                    if ((int)$c['userid'] === $user_id) {
                                        echo '<div class="comment-menu-container">';
                                        echo '<button class="comment-menu-btn" title="Opções do comentário">';
                                        echo '<img src="../assets/icons/arrow-down.png" alt="Menu">';
                                        echo '</button>';
                                        echo '<div class="comment-menu">';
                                        echo '<button class="comment-menu-item delete" data-comment-id="' . htmlspecialchars($c['idcomentarios']) . '">';
                                        echo '<span>🗑️ Deletar</span>';
                                        echo '</button>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                    
                                    echo '</div>';
                                    
                                    // Exibir comentário - não escapar se for um comentário excluído
                                    if (strpos($c['comentario'], '[comentário excluído]') !== false) {
                                        echo '<p class="comment-text">' . $c['comentario'] . '</p>';
                                    } else {
                                        // Permite apenas a tag <img> para renderizar os emojis
                                        $comentario_seguro = strip_tags($c['comentario'], '<img>');
                                        echo '<p class="comment-text">' . $comentario_seguro . '</p>';
                                    }
                                    echo '<hr>';
                                echo '</div>';
                            }

                            $stmt_c->close();


                            echo '</div>';
                            '<hr>';                      
                            }
                        ?>
                    </div>

                </div>
            </div>
            <div class="side-perfil">
                <!-- <p>excluir\/</p> -->
                <div class="options" style="display: none">
                    <?php
                        echo "<a href='../contas-options/deletar-conta.php?idusuarios=$user_data[idusuarios]'>Excluir Conta</a>";
                    ?>
                </div>
            </div>
            <section class="mural-container">
                <h3>Mural de Recados</h3>
                
                <div class="mural-form">
                    <form method="POST" class="mural-form-action">
                        <div contenteditable="true" class="mural-input" style="min-height: 60px; border: 1px solid #ccc; padding: 5px; background: #fff; width: 100%;" placeholder="Escreva algo no mural..."></div>
                        
                        <input type="hidden" name="mural-text" class="hidden-mural-input">
                        <input type="hidden" name="perfil_id" value="<?php echo $num_id; ?>">

                        <div class="mural-actions" style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                            <div style="position: relative; display: inline-block;">
                                <button class="mural-emoji-btn" type="button" style="background: none; border: none; cursor: pointer;">
                                    <img src="../assets/icons/emoticons/smiling.png" width="20" alt="Emojis">
                                </button>
                                <div class="mural-emoji-picker" style="display: none; position: absolute; bottom: 100%; left: 0; z-index: 10; background: white; border: 1px solid #ccc; padding: 5px;"></div>
                            </div>
                            
                            <button type="submit" name="action" value="post-mural">Fixar no Mural</button>
                        </div>
                    </form>
                </div>

                <div class="mural-list">
                    <?php
                        // Busca as mensagens do mural deste perfil específico
                        $query_mural = "SELECT m.*, u.nome, u.foto 
                                        FROM mural m 
                                        JOIN usuarios u ON m.autor_id = u.idusuarios 
                                        WHERE m.perfil_id = ? 
                                        ORDER BY m.data_postagem DESC";
                        
                        $stmt_m = $conexao->prepare($query_mural);
                        $stmt_m->bind_param("i", $num_id);
                        $stmt_m->execute();
                        $res_mural = $stmt_m->get_result();

                        if ($res_mural->num_rows > 0) {
                            while ($recado = $res_mural->fetch_assoc()) {
                                echo '<div class="mural-item">';
                                    echo '<div class="mural-item-header">';
                                        echo '<img src="../' . $recado['foto'] . '" width="30" height="30">';
                                        echo '<strong>' . htmlspecialchars($recado['nome']) . '</strong>';
                                        /* echo '<span class="mural-date">' . date('d/m/H:i', strtotime($recado['data_postagem'])) . '</span>'; */
                                    echo '</div>';
                                    // Usa o strip_tags para liberar APENAS a tag <img>, igual aos comentários
                                    $mensagem_segura = strip_tags($recado['mensagem'], '<img>');
                                    echo '<p>' . nl2br($mensagem_segura) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="empty-mural">Nenhum recado ainda. Seja o primeiro!</p>';
                        }
                        $stmt_m->close();
                    ?>
                </div>
            </section>
        </div>
    </main>
    <link rel="stylesheet" href="../scripts/image-cropper.css">
    <script src="../scripts/image-cropper.js"></script>
    <script>
        // bind profile file input to cropper and auto-submit foto-form
        document.addEventListener('DOMContentLoaded', function(){
            const fileInput = document.getElementById('foto-perfil-input');
            const hidden = document.getElementById('foto_perfil_data');
            if (fileInput && hidden) {
                bindInputToCropper(fileInput, hidden, document.getElementById('main-profile-img'), 'foto-form');
            }
            // bind cover input to cropper (rectangular)
            const capaInput = document.getElementById('foto-capa-input');
            const capaHidden = document.getElementById('foto_capa_data');
            const capaPreview = document.getElementById('capa-div');
            if (capaInput && capaHidden && capaPreview) {
                // For cover images, open cropper with target output 850x400
                bindInputToCropper(capaInput, capaHidden, capaPreview, 'capa-form', { stageWidth: 850, stageHeight: 400 });
                // allow owner to click the cover area to change it
                <?php if ($user_id === $num_id): ?>
                capaPreview.addEventListener('click', function(){
                    capaInput.click();
                });
                <?php endif; ?>
            }
        });
    </script>
    <script>
        // Espera o DOM carregar completamente antes de executar o script
        document.addEventListener("DOMContentLoaded", function() {
            // Lógica para editar foto de perfil
            const editFotoButton = document.getElementById('edit-foto-button');
            const fotoForm = document.getElementById('foto-form');
            const fotoPerfılInput = document.getElementById('foto-perfil-input');

            if (editFotoButton && fotoForm && fotoPerfılInput) {
                editFotoButton.addEventListener('click', function() {
                    fotoPerfılInput.click();
                });

                fotoPerfılInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        fotoForm.submit();
                    }
                });
            }

            // Carrega o header.html no container apropriado
            fetch('../components/header/header.html')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('header-container').innerHTML = data;

                    // Força a rolagem para o topo após carregar o header
                    window.scrollTo(0, 0); // Rola para o topo da página

            // Inclui o script de configurações após o carregamento do header
            const scriptConfig = document.createElement("script");
            scriptConfig.src = "../components/header/header.js";
            scriptConfig.defer = true;
            document.body.appendChild(scriptConfig);

            // Inclui o script de sugestões após o carregamento do header
            const script = document.createElement("script");
            script.src = "../scripts/user-suggestions.php";
            script.defer = true;
            document.body.appendChild(script);

            // Inclui o script do menu de posts
            const scriptMenu = document.createElement("script");
            scriptMenu.src = "../components/post-menu/post-menu.js";
            scriptMenu.defer = true;
            document.body.appendChild(scriptMenu);

            // Inclui o script do menu de comentários
            const scriptCommentMenu = document.createElement("script");
            scriptCommentMenu.src = "../components/comment-menu/comment-menu.js";
            scriptCommentMenu.defer = true;
            document.body.appendChild(scriptCommentMenu);
            })
        });


        // componente de novo post
        document.addEventListener("DOMContentLoaded", function() {
            // Carrega o new-post.html
            fetch('../components/new-post/new-post.html')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('novo-post').innerHTML = data;
                    let newPostDiv = document.querySelector('.newpostdiv');
                    console.log(newPostDiv);
/*                     if (newPostDiv) {
                        newPostDiv.style.display = 'none';
                    } */
                    



                    // lógica de arquivo de imagem do new-post.html
                    const arquivo = document.getElementById('arquivo');
                    console.log("Arquivo carregado via fetch:", arquivo);

                    let imagePreview = document.getElementById('imagePreview');
                    let previewBox = document.getElementById('previewBox');

                    let removeImageButton = document.querySelector('.removeImage-button');

                    let imagePath = "";


                    
                    if(arquivo) {
                        arquivo.addEventListener('change', function () {
                            if (this.files.length > 0) {
                                previewBox.style.display = 'block';
                                console.log("Arquivo selecionado!");
                                // link temporário da imagem
                                let blobUrl = URL.createObjectURL(this.files[0]);
                                //imagePath = this.files[0].name;
                                //console.log(imagePath);  
                                imagePreview.src = blobUrl;
                            }
                        
                        });

                        
                        removeImageButton.addEventListener('click', () => {
                            arquivo.value = '';
                            blobUrl = '';
                            imagePreview.src = '';
                            previewBox.style.display = 'none';
                            
                        });
                        
                    }

                    if (newPostDiv) {
                        const criarPostButton = document.getElementById('criarPost-button');

                        criarPostButton.addEventListener('click', function() {
                            newPostDiv.classList.toggle('active');
                        });
                    }

                    // Adiciona a escuta de submit apenas no formulário de novo post
                    document.querySelector('#novo-post form').addEventListener('submit', function(e) {
                        const editorDiv = document.getElementById('editor-postagem');
                        
                        // Validamos usando innerHTML para considerar imagens (emojis) também
                        if (editorDiv.innerHTML.trim() === '') {
                            e.preventDefault();
                            alert('Digite algo para postar!');
                            return;
                        }
                        
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'post';
                        
                        // innerHTML vai capturar as tags <img> dos emojis corretamente
                        hiddenInput.value = editorDiv.innerHTML; 
                        this.appendChild(hiddenInput);
                    });
 
                    const emojiBtn = document.querySelector('.emojiButton');
                    const emojiContainer = document.getElementById('emoji-picker-container');

                    emojiBtn.addEventListener('click', function(e) {
                        e.preventDefault(); // Evita qualquer comportamento estranho no form

                        // Se o container estiver vazio, carrega o conteúdo via Fetch
                        if (emojiContainer.innerHTML === "") {
                            fetch('../components/new-post/emoji-tab.html') // Ajuste o caminho se necessário
                                .then(res => res.text())
                                .then(html => {
                                    // Remove a tag <link> ou tags <body> extras se houver
                                    emojiContainer.innerHTML = html;
                                    
                                    // Adicionar o emoji ao clicar nele
                                    emojiContainer.addEventListener('click', (event) => {
                                        if(event.target.tagName === 'IMG') {
                                            const editor = document.querySelector('.input-text');
                                            
                                            // Garante que o editor está focado para inserir no lugar certo
                                            editor.focus(); 
                                            
                                            // Pega o caminho da imagem que foi clicada na aba de emojis
                                            const imgSrc = event.target.src;
                                            const imgAlt = event.target.alt;
                                            
                                            // Monta a tag HTML da imagem (com um tamanho reduzido para parecer texto)
                                            const imgTag = `<img src="${imgSrc}" alt="${imgAlt}" style=" vertical-align: middle; margin: 0 2px;">`;
                                            
                                            // Insere a tag HTML da imagem onde o cursor do usuário está
                                            document.execCommand('insertHTML', false, imgTag);
                                        }
                                    });
                                });
                        }

                        // Toggle de visibilidade
                        if (emojiContainer.style.display === 'none') {
                            emojiContainer.style.display = 'block';
                        } else {
                            emojiContainer.style.display = 'none';
                        }
                    });

                    // Fechar ao clicar fora
                    document.addEventListener('click', function(event) {
                        if (!emojiBtn.contains(event.target) && !emojiContainer.contains(event.target)) {
                            emojiContainer.style.display = 'none';
                        }
                    });

                });

        // ==========================================
            // LÓGICA DE EMOJIS PARA OS COMENTÁRIOS
            // ==========================================
            
            const commentEmojiBtns = document.querySelectorAll('.comment-emoji-btn');

            commentEmojiBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    // O container do picker está logo depois do botão no HTML
                    const pickerContainer = this.nextElementSibling; 
                    // O editor do comentário está no mesmo formulário
                    const form = this.closest('.comment-form');
                    const editorDiv = form.querySelector('.comment-input');

                    // Se o picker estiver vazio, carrega o HTML
                    if (pickerContainer.innerHTML === "") {
                        fetch('../components/new-post/emoji-tab.html')
                            .then(res => res.text())
                            .then(html => {
                                pickerContainer.innerHTML = html;
                                
                                // Adiciona o evento de clique nos emojis
                                pickerContainer.addEventListener('click', (event) => {
                                    if(event.target.tagName === 'IMG') {
                                        editorDiv.focus();
                                        
                                        const imgSrc = event.target.src;
                                        const imgAlt = event.target.alt;
                                        // Tag da imagem com largura fixa para não ficar gigante no comentário
                                        const imgTag = `<img src="${imgSrc}" alt="${imgAlt}" style="vertical-align: middle; margin: 0 2px;">`;
                                        
                                        document.execCommand('insertHTML', false, imgTag);
                                    }
                                });
                            });
                    }

                    // Alternar a visibilidade
                    if (pickerContainer.style.display === 'none' || pickerContainer.style.display === '') {
                        // Fecha todos os outros pickers abertos antes de abrir este (opcional, mas evita bagunça)
                        document.querySelectorAll('.comment-emoji-picker').forEach(p => p.style.display = 'none');
                        pickerContainer.style.display = 'block';
                    } else {
                        pickerContainer.style.display = 'none';
                    }
                });
            });

            // Fechar os pickers de comentário ao clicar em qualquer lugar fora deles
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.comment-emoji-btn') && !event.target.closest('.comment-emoji-picker')) {
                    document.querySelectorAll('.comment-emoji-picker').forEach(picker => {
                        picker.style.display = 'none';
                    });
                }
            });

            // Atualizar o input hidden antes de enviar o formulário de comentário
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const editorDiv = this.querySelector('.comment-input');
                    const hiddenInput = this.querySelector('.hidden-comment-input');

                    // Se os elementos existirem (evita erros em formulários antigos)
                    if (editorDiv && hiddenInput) {
                        if (editorDiv.innerHTML.trim() === '') {
                            e.preventDefault();
                            alert('Digite algo para comentar!');
                            return;
                        }
                        // Passa o HTML completo (texto + imagens de emoji) para o input oculto
                        hiddenInput.value = editorDiv.innerHTML;
                    }
                });
            });

            // ==========================================
            // LÓGICA DE EMOJIS PARA O MURAL
            // ==========================================
            const muralEmojiBtn = document.querySelector('.mural-emoji-btn');
            const muralPickerContainer = document.querySelector('.mural-emoji-picker');
            const muralEditorDiv = document.querySelector('.mural-input');
            const muralForm = document.querySelector('.mural-form-action');
            const hiddenMuralInput = document.querySelector('.hidden-mural-input');

            if (muralEmojiBtn) {
                // Abrir e fechar aba de emojis
                muralEmojiBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    if (muralPickerContainer.innerHTML === "") {
                        fetch('../components/new-post/emoji-tab.html')
                            .then(res => res.text())
                            .then(html => {
                                muralPickerContainer.innerHTML = html;
                                
                                // Inserir emoji ao clicar
                                muralPickerContainer.addEventListener('click', (event) => {
                                    if(event.target.tagName === 'IMG') {
                                        muralEditorDiv.focus();
                                        
                                        const imgSrc = event.target.src;
                                        const imgAlt = event.target.alt;
                                        const imgTag = `<img src="${imgSrc}" alt="${imgAlt}" style="vertical-align: middle; margin: 0 2px;">`;
                                        
                                        document.execCommand('insertHTML', false, imgTag);
                                    }
                                });
                            });
                    }

                    // Alterna visibilidade do picker do mural
                    if (muralPickerContainer.style.display === 'none' || muralPickerContainer.style.display === '') {
                        // Esconde pickers de comentários se estiverem abertos
                        document.querySelectorAll('.comment-emoji-picker').forEach(p => p.style.display = 'none');
                        muralPickerContainer.style.display = 'block';
                    } else {
                        muralPickerContainer.style.display = 'none';
                    }
                });

                // Fechar picker ao clicar fora
                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.mural-emoji-btn') && !event.target.closest('.mural-emoji-picker')) {
                        if (muralPickerContainer) muralPickerContainer.style.display = 'none';
                    }
                });

                // Enviar os dados da div editável para o input hidden antes do submit
                if (muralForm) {
                    muralForm.addEventListener('submit', function(e) {
                        if (muralEditorDiv.innerHTML.trim() === '') {
                            e.preventDefault();
                            alert('Digite algo para fixar no mural!');
                            return;
                        }
                        hiddenMuralInput.value = muralEditorDiv.innerHTML;
                    });
                }
            }

        // ==========================================
                // LÓGICA DE EDIÇÃO E EMOJIS PARA A BIO
                // ==========================================
                const editBioBtn = document.getElementById('edit-bio-btn');
                const cancelBioBtn = document.getElementById('cancel-bio-btn');
                const bioDisplay = document.getElementById('bio-display');
                const bioFormContainer = document.getElementById('bio-form-container');
                const bioForm = document.getElementById('bio-form');
                const bioEditorDiv = document.querySelector('.bio-input');
                const hiddenBioInput = document.querySelector('.hidden-bio-input');
                const bioEmojiBtn = document.querySelector('.bio-emoji-btn');
                const bioPickerContainer = document.querySelector('.bio-emoji-picker');

                if (editBioBtn && bioFormContainer) {
                    // Abrir modo de edição
                    editBioBtn.addEventListener('click', () => {
                        bioDisplay.style.display = 'none';
                        bioFormContainer.style.display = 'block';
                        bioEditorDiv.focus(); // Já deixa o cursor piscando
                    });

                    // Cancelar modo de edição
                    cancelBioBtn.addEventListener('click', () => {
                        bioFormContainer.style.display = 'none';
                        bioDisplay.style.display = 'block';
                    });

                    // Abrir e fechar aba de emojis da Bio
                    bioEmojiBtn.addEventListener('click', function(e) {
                        e.preventDefault();

                        if (bioPickerContainer.innerHTML === "") {
                            fetch('../components/new-post/emoji-tab.html')
                                .then(res => res.text())
                                .then(html => {
                                    bioPickerContainer.innerHTML = html;
                                    
                                    // Inserir emoji ao clicar
                                    bioPickerContainer.addEventListener('click', (event) => {
                                        if(event.target.tagName === 'IMG') {
                                            bioEditorDiv.focus();
                                            const imgSrc = event.target.src;
                                            const imgAlt = event.target.alt;
                                            const imgTag = `<img src="${imgSrc}" alt="${imgAlt}" style="vertical-align: middle; margin: 0 2px;">`;
                                            document.execCommand('insertHTML', false, imgTag);
                                        }
                                    });
                                });
                        }

                        // Alterna visibilidade do picker da bio
                        if (bioPickerContainer.style.display === 'none' || bioPickerContainer.style.display === '') {
                            // Esconde pickers de outras áreas se estiverem abertos
                            document.querySelectorAll('.comment-emoji-picker, .mural-emoji-picker').forEach(p => p.style.display = 'none');
                            bioPickerContainer.style.display = 'block';
                        } else {
                            bioPickerContainer.style.display = 'none';
                        }
                    });

                    // Fechar picker ao clicar fora dele
                    document.addEventListener('click', function(event) {
                        if (!event.target.closest('.bio-emoji-btn') && !event.target.closest('.bio-emoji-picker')) {
                            if (bioPickerContainer) bioPickerContainer.style.display = 'none';
                        }
                    });

                    // Enviar os dados da div editável para o input hidden antes de salvar
                    bioForm.addEventListener('submit', function() {
                        // Ao contrário das outras opções, não colocamos verificação de vazio,
                        // porque o usuário pode querer apagar a bio inteira.
                        hiddenBioInput.value = bioEditorDiv.innerHTML;
                    });
                }

            const MAX_CHARS = 300; // Defina o limite aqui

            // Impede colar texto muito grande ou digitar além do limite
            bioEditorDiv.addEventListener('input', function() {
                if (this.innerText.length > MAX_CHARS) {
                    this.innerText = this.innerText.substring(0, MAX_CHARS);
                    // Move o cursor para o final após o corte
                    const range = document.createRange();
                    const sel = window.getSelection();
                    range.selectNodeContents(this);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);
                    alert("A bio pode ter no máximo " + MAX_CHARS + " caracteres.");
                }
            });

            // No momento do Submit, também limpamos o excesso
            bioForm.addEventListener('submit', function(e) {
                if (bioEditorDiv.innerText.length > MAX_CHARS) {
                    e.preventDefault();
                    alert("Bio muito longa!");
                    return;
                }
                hiddenBioInput.value = bioEditorDiv.innerHTML;
            });




        });

            // Abrir modal de emoção
            fetch('../components/new-post/emoji-tab.html')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('emoji-emocoes').innerHTML = data;
                    const btnEmocao = document.getElementById('edit-emocao');
                    const modalEmocao = document.getElementById('emocao-modal');

                    if(btnEmocao) {
                        btnEmocao.addEventListener('click', (e) => {
                            modalEmocao.style.display = modalEmocao.style.display === 'none' ? 'block' : 'none';
                            modalEmocao.style.top = (e.pageY + 10) + 'px';
                            modalEmocao.style.left = (e.pageX - 100) + 'px';
                        });
                    }

                    // Seleção de emoji no modal
                    document.querySelectorAll('.emoji-opt').forEach(img => {
                        img.addEventListener('click', function() {
                            document.getElementById('input-emocao-emoji').value = this.src;
                            document.querySelectorAll('.emoji-opt').forEach(i => i.style.border = "none");
                            this.style.border = "2px solid blue";
                        });
                    });
            });





        
    </script>
</body>
</html>
