// Script para menu de exclusão de comentários
console.log('✓ comment-menu.js carregado com sucesso');

// Abrir/fechar menu de comentário
document.addEventListener('click', function(e) {
    // Fechar todos os menus abertos quando clicar fora
    if (!e.target.closest('.comment-menu-container')) {
        document.querySelectorAll('.comment-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });
    }

    // Abrir/fechar menu ao clicar no botão
    if (e.target.closest('.comment-menu-btn')) {
        console.log('➤ Comment menu button clicked');
        const btn = e.target.closest('.comment-menu-btn');
        const container = btn.closest('.comment-menu-container');
        const menu = container ? container.querySelector('.comment-menu') : null;
        
        if (!menu) {
            console.error('Comment menu element not found!');
            return;
        }
        
        // Fechar outros menus
        document.querySelectorAll('.comment-menu.active').forEach(m => {
            if (m !== menu) {
                m.classList.remove('active');
            }
        });
        
        menu.classList.toggle('active');
        console.log('Comment menu is now:', menu.classList.contains('active') ? 'OPEN' : 'CLOSED');
        e.stopPropagation();
    }
});

// Botão de deletar comentário
document.addEventListener('click', function(e) {
    if (e.target.closest('.comment-menu-item.delete')) {
        const menuItem = e.target.closest('.comment-menu-item.delete');
        const commentId = menuItem.dataset.commentId;
        const commentContainer = menuItem.closest('.comment');
        
        if (confirm('Tem certeza que deseja deletar este comentário?')) {
            deleteComment(commentId, commentContainer);
        }
        e.stopPropagation();
    }
});

function deleteComment(commentId, commentContainer) {
    const formData = new FormData();
    formData.append('action', 'delete-comment');
    formData.append('comment_id', commentId);

    // Determinar o caminho correto para o script
    const baseUrl = window.location.pathname.includes('/paginas/') ? '../' : '';
    const scriptUrl = baseUrl + 'scripts/deleteComment.php';

    fetch(scriptUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Erro ao deletar o comentário');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Recarregar a página
            window.location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao deletar o comentário: ' + error.message);
    });
}
