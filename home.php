<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: /chrome-haven/index.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
</head>
<body>
    <div class="container">
        <h1>Bienvenue, <?php echo htmlspecialchars($username); ?> !</h1>
        <p>Vous êtes connecté avec succès.</p>
        <button onclick="window.location.href='/chrome-haven/account.php'">Voir mon profil</button>
    </div>
</body>
</html>
