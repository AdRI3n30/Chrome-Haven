<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "chrome-haven");
if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $query = "SELECT * FROM User WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $delete_query = "DELETE FROM User WHERE id = ?";
        $delete_stmt = $mysqli->prepare($delete_query);
        $delete_stmt->bind_param("i", $user_id);

        if ($delete_stmt->execute()) {
            $message = "Utilisateur supprimé avec succès.";
        } else {
            $message = "Erreur lors de la suppression de l'utilisateur.";
        }
    } else {
        $message = "Utilisateur non trouvé.";
    }
} else {
    $message = "ID d'utilisateur non spécifié.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer un utilisateur</title>
    <link rel="stylesheet" href="../static/delete_user.css"> 
</head>
<body>
    <h1>Suppression d'un utilisateur</h1>

    <p><?= htmlspecialchars($message) ?></p>

    <a href="admin.php">Retour à l'administration</a>
</body>
</html>
