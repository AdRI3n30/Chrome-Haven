<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];


    if (!is_numeric($amount) || $amount <= 0) {
        die("Montant invalide. Veuillez saisir un nombre positif.");
    }


    $user_id = $_SESSION['user_id'];
    $query = "UPDATE user SET balance = balance + ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        die("Erreur dans la préparation de la requête : " . $mysqli->error);
    }

    $stmt->bind_param("di", $amount, $user_id);

    if ($stmt->execute()) {
        echo "Montant ajouté avec succès à votre solde.";
    } else {
        echo "Erreur lors de la mise à jour du solde : " . $stmt->error;
    }

    $stmt->close();
    $mysqli->close();

    header("Location: account.php");
    exit;
} else {
    die("Méthode non autorisée.");
}
?>
