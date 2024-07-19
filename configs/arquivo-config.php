<?php

  include('../config.php');
  var_dump($_FILES);
  if (isset($_FILES['arquivo'])) {
    $arquivo = $_FILES['arquivo'];
    if($arquivo['size'] > 2097152 * 25)
      die("Arquivo muito grande! Max: 2MB");
    if($arquivo['error'])
      die("Falha ao enviar arquivo!");
    
    $pasta = "../arquivos/";
    $nomeDoArquivo = $arquivo['name'];
    $novoNomeDoArquivo = uniqid();
    $extensao = strtolower(pathinfo($nomeDoArquivo,PATHINFO_EXTENSION));

    if($extensao != 'jpg' && $extensao != 'png')
      die('Tipo de arquivo inválido!');

    $path = $pasta . $novoNomeDoArquivo . '.' . $extensao;
    $deu_certo = move_uploaded_file($arquivo['tmp_name'], $path);
/*     $deu_certo = move_uploaded_file($arquivo['tmp_name'], $path);
    if ($deu_certo)
      echo "<p> Arquivo: <a href='arquivos/$novoNomeDoArquivo.$extensao'>clique aqui</a>"; */
  }
?>