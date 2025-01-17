const configOptions = document.querySelector('.config-options');
const configButton = document.querySelector('.config-button');
const configImg = document.querySelector('.img-config');

configButton.addEventListener('click', function() {
    console.log("aaaaaaaaaa");
    configOptions.classList.toggle('active');
    configImg.classList.toggle('rotated');
});



/* configButton.addEventListener('click', function() {
    console.log("aaaaaaaaaa");
    configOptions.classList.toggle('active');
}); */