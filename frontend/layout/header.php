<?php

$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="<?= h(url_path('/assets/img/logo-fazmastone-favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= h(url_path('/assets/img/logo-fazmastone-favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(url_path('/assets/css/styles.css')) ?>">
  </head>
  <body>
