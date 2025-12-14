<?php
    session_start();
    include_once('../search_logic.php');
?>


const searchInput = document.getElementById('searchInput');
const suggestionsList = document.getElementById('suggestions');
const users = <?php echo json_encode($search_user_data); ?>; // Convertendo dados PHP para JSON
const user_logado = <?php echo json_encode($user_data); ?>; // Convertendo dados PHP para JSON

searchInput.addEventListener('input', function () {
    const inputValue = this.value.toLowerCase();
    let suggestions = [];
    if (inputValue.length > 0) {
        suggestions = users.filter(user =>
            user['nome'].toLowerCase().includes(inputValue)
        );
        displaySuggestions(suggestions);
    } else {
        suggestionsList.innerHTML = ''; // Limpa as sugestões se não houver entrada
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
                    </form>`
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
