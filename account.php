<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>

</head>
<body>
    <div class="container">
        <h1>Profil de <?php echo htmlspecialchars($username); ?></h1>
        <p>Voici vos informations personnelles.</p>

        <form method="POST" action="logout.php">
            <button type="submit" class="logout">Se déconnecter</button>
        </form>
        <button onclick="window.location.href='/chrome-haven/home.php'">Retour à l'accueil</button>
    </div>
</body>
</html>
