<?php
    ini_set("error_reporting", 1);
    header("Cashe-Control: no-cashe, no-store, must-revalidate, max-age-0");
    header("Cashe-Control: pre-check-0, post-check-0", false);
    header("Pragma: no-cashe");

    $current = basename($_SERVER['PHP_SELF']); // gets "test1.php" or "test2.php"

    if ( $_GET["rel"]!="page") {
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test1</title>
    <style>
        a.active { font-weight: bold; color: red; }
    </style>
</head>
<body>
    <div>
        <a href="test1.php" rel="page" class="<?= $current == 'test1.php' ? 'active' : '' ?>">Test1</a>
        <a href="test2.php" rel="page" class="<?= $current == 'test2.php' ? 'active' : '' ?>">Test2</a>
    </div>
    
    <div id="load">

<?php } ?>