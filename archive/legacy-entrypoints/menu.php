<?php
    include "config.php";

    $accessToken = $_GET["access_token"];
    $session = $_GET["session"];
    $host = $_GET["host"];

    echo "<div>Connected to Accurate Online API</div>";
    echo "<div>Access Token: $accessToken</div>";
    echo "<div>Session: $session</div>";
    echo "<ul>";
    echo "<li><a href=\"read-item.php?access_token=$accessToken&session=$session&host=$host\">Read Item</a></li>";
    echo "<li><a href=\"create-item.php?access_token=$accessToken&session=$session&host=$host\">Create Item</a></li>";
    echo "<li><a href=\"create-customer.php?access_token=$accessToken&session=$session&host=$host\">Create Customer</a></li>";
    echo "<li><a href=\"create-sales-invoice.php?access_token=$accessToken&session=$session&host=$host\">Create Sales Invoice</a></li>";
    echo "</ul>";
?>