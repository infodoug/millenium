<?php
    session_start();
    include_once('../search_logic.php');
?>


const searchInput = document.getElementById('searchInput');
const suggestionsList = document.getElementById('suggestions');
const users = <?php echo json_encode($search_user_data); ?>; // Convertendo dados PHP para JSON
const user_logado = <?php echo json_encode($user_data); ?>; // Convertendo dados PHP para JSON

// Normaliza strings removendo compatibilidades/unicode decorativo e diacríticos
function normalizeString(str) {
    if (!str) return '';
    try {
        // NFKD faz decomposição compatível (ex: 𝓗 -> H)
        return str.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    } catch (e) {
        // Caso o ambiente não suporte normalize, cai para fallback simples
        return str.replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }
}

searchInput.addEventListener('input', function () {
    const inputValue = normalizeString(this.value);
    let suggestions = [];
    if (inputValue.length > 0) {
        suggestions = users.filter(user =>
            normalizeString(user['nome']).includes(inputValue)
        );
        displaySuggestions(suggestions);
    } else {
        suggestionsList.innerHTML = '';<!-- Limpa as sugestões se não houver entrada -->
    }
});

function displaySuggestions(suggestions) {
    const link = document.getElementById('link-perfil');
    const html = suggestions.map(user => {
        if (user.idusuarios != user_logado['idusuarios']) {
            return `<form action='/millenium/paginas/perfil-pesquisado.php' method='post'>
                        <input name='id-user-pesquisado' value='${user.idusuarios}' type='hidden'>
                        <button type='submit' name='entrar'>

                                <img height='30px' width='30px' src="/millenium/${user.foto}">
                                ${user.nome}

                        </button>
                    </form>
                    <hr>
                    `
            /* return `<a href='/millenium/paginas/perfil-pesquisado.php?id=${user.idusuarios}'>

            </a>`; */
        } else {
            return `<a href='/millenium/paginas/perfil.php'>
                <li>
                    <img height='30px' width='30px' src="/millenium/${user.foto}">
                    ${user.nome}
                </li>
            </a>`;
        }
    }).join('');
    suggestionsList.innerHTML = html;
    const userid_pesquisado = suggestions.map(user => user.idusuarios)
    console.log(userid_pesquisado);

    suggestionsList.querySelectorAll('li').forEach(li => {
        li.addEventListener('click', function() {

            searchInput.value = this.textContent;
            suggestionsList.innerHTML = ''; // Limpa as sugestões ao selecionar
            searchInput.value = '';
        });
    });
}
