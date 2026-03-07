// Inicializar listeners quando o script for carregado
// (não usar DOMContentLoaded pois o script é carregado dinamicamente)

console.log('✓ post-menu.js carregado com sucesso');

// Verificar se há elementos do menu na página
setTimeout(() => {
    const menuButtons = document.querySelectorAll('.post-menu-btn');
    console.log(`Found ${menuButtons.length} menu buttons`);
}, 100);

// Abrir/fechar menu de contexto do post
document.addEventListener('click', function(e) {
    // Fechar todos os menus abertos quando clicar fora
    if (!e.target.closest('.post-menu-container')) {
        document.querySelectorAll('.post-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });
    }

    // Abrir/fechar menu ao clicar no botão
    if (e.target.closest('.post-menu-btn')) {
        console.log('➤ Menu button clicked');
        const btn = e.target.closest('.post-menu-btn');
        const container = btn.closest('.post-menu-container');
        const menu = container ? container.querySelector('.post-menu') : null;
        
        console.log('Menu element:', menu);
        
        if (!menu) {
            console.error('Menu element not found!');
            return;
        }
        
        // Fechar outros menus
        document.querySelectorAll('.post-menu.active').forEach(m => {
            if (m !== menu) {
                m.classList.remove('active');
            }
        });
        
        menu.classList.toggle('active');
        console.log('Menu is now:', menu.classList.contains('active') ? 'OPEN' : 'CLOSED');
        e.stopPropagation();
    }
});

// Botão de editar
document.addEventListener('click', function(e) {
    if (e.target.closest('.post-menu-item.edit')) {
        const menuItem = e.target.closest('.post-menu-item.edit');
        const postId = menuItem.dataset.postId;
        const postContainer = menuItem.closest('.post');
        const postText = postContainer.querySelector('.text-content').textContent.trim();
        
        // Fechar o menu
        menuItem.closest('.post-menu').classList.remove('active');
        
        // Abrir modal de edição
        openEditModal(postId, postText, postContainer);
        e.stopPropagation();
    }
});

// Botão de deletar
document.addEventListener('click', function(e) {
    if (e.target.closest('.post-menu-item.delete')) {
        const menuItem = e.target.closest('.post-menu-item.delete');
        const postId = menuItem.dataset.postId;
        const postContainer = menuItem.closest('.post');
        
        if (confirm('Tem certeza que deseja deletar este post? Os comentários serão mantidos.')) {
            deletePost(postId, postContainer);
        }
        e.stopPropagation();
    }
});

// Fechar modal ao clicar fora
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('edit-post-modal')) {
        closeEditModal();
    }
});

// Fechar modal ao clicar no botão cancelar
document.addEventListener('click', function(e) {
    if (e.target.closest('.cancel-btn')) {
        closeEditModal();
        e.stopPropagation();
    }
});

// Enviar edição
document.addEventListener('click', function(e) {
    if (e.target.closest('.edit-post-btn')) {
        submitEditPost(e);
        e.stopPropagation();
    }
});

function openEditModal(postId, postText, postContainer) {
    let modal = document.getElementById('editPostModal');
    
    if (!modal) {
        modal = createEditModal();
    }

    document.getElementById('editPostId').value = postId;
    document.getElementById('editPostText').value = postText;
    
    modal.classList.add('active');
    document.getElementById('editPostText').focus();
}

function closeEditModal() {
    const modal = document.getElementById('editPostModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function createEditModal() {
    const modal = document.createElement('div');
    modal.id = 'editPostModal';
    modal.className = 'edit-post-modal';
    modal.innerHTML = `
        <div class="edit-post-modal-content">
            <div class="edit-post-modal-header">Editar Postagem</div>
            <div class="edit-post-modal-body">
                <textarea id="editPostText" class="edit-post-textarea" placeholder="Digite o novo texto da postagem..."></textarea>
                
                <div class="time-limit-warning" style="display: none;"></div>
                <input type="hidden" id="editPostId">
            </div>
            <div class="edit-post-modal-footer">
                <button class="cancel-btn">Cancelar</button>
                <button class="edit-post-btn">Salvar Alterações</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    return modal;
}

function submitEditPost(e) {
    const postId = document.getElementById('editPostId').value;
    const newText = document.getElementById('editPostText').value.trim();

    if (!newText) {
        alert('O texto do post não pode estar vazio!');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'edit-post');
    formData.append('post_id', postId);
    formData.append('new_text', newText);

    // Determinar o caminho correto para o script
    const baseUrl = window.location.pathname.includes('/paginas/') ? '../' : '';
    const scriptUrl = baseUrl + 'scripts/editPost.php';

    fetch(scriptUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Erro ao atualizar o post');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Post atualizado com sucesso!');
            closeEditModal();
            // Recarregar a página
            window.location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar o post: ' + error.message);
    });
}

function deletePost(postId, postContainer) {
    const formData = new FormData();
    formData.append('action', 'delete-post');
    formData.append('post_id', postId);

    // Determinar o caminho correto para o script
    const baseUrl = window.location.pathname.includes('/paginas/') ? '../' : '';
    const scriptUrl = baseUrl + 'scripts/deletePost.php';

    fetch(scriptUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Erro ao deletar o post');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Post deletado com sucesso!');
            // Recarregar a página
            window.location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao deletar o post: ' + error.message);
    });
}
