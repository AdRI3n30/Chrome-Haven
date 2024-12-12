<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "chrome-haven";

$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

header("Location: /chrome-haven/login.php");
exit();
?>