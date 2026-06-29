<?php

$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= h(url_path('/assets/css/styles.css')) ?>">
  </head>
  <body>
