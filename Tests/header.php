<?php
    ini_set("error_reporting", 1);
    header("Cashe-Control: no-cashe, no-store, must-revalidate, max-age-0");
    header("Cashe-Control: pre-check-0, post-check-0", false);
    header("Pragma: no-cashe");

    if ( $_GET["rel"]!="page") {

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test1</title>
</head>
<body>
    <div>
        <a href="test1.php" rel="page">Test1</a>
        <a href="test2.php" rel="page">Test2</a>
    </div>
    
    <div id="load">

<?php } ?>