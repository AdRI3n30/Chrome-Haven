<?php
// Informations de connexion
$host = "localhost";
$username = "root";
$password = "";
$database = "chrome-haven";

// Création de la connexion
$mysqli = new mysqli($host, $username, $password, $database);

// Vérification de la connexion
if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

echo "Connexion réussie à la base de données.";
?>