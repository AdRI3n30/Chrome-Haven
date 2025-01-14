<?php
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");
if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $mysqli->real_escape_string($_POST['username']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $checkQuery = "SELECT * FROM user WHERE username='$username' OR email='$email'";
    $result = $mysqli->query($checkQuery);

    if ($result->num_rows > 0) {
        echo "<p class='error'>Le nom d'utilisateur ou l'adresse email existe déjà.</p>";
    } else {
        $insertQuery = "INSERT INTO user (username, email, password, role, balance) 
                        VALUES ('$username', '$email', '$password', 'user', 0)";
        if ($mysqli->query($insertQuery)) {
            echo "<p class='success'>Inscription réussie. Vous pouvez maintenant vous connecter.</p>";
            header("Location: /chrome-haven/login.php");
            exit();
        } else {
            echo "<p class='error'>Erreur : " . $mysqli->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="/static/register.css">
</head>
<body>
    <div class="container">
        <form method="POST" action="">
            <h2>Sign Up</h2>
            <input type="text" id="username" name="username" placeholder="Nom d'utilisateur..." required>
            <input type="email" id="email" name="email" placeholder="Adresse email..." required>
            <input type="password" id="password" name="password" placeholder="Mot de passe..." required>
            <button type="submit">S'inscrire →</button>
            <p>Déjà un compte ? <a href="/chrome-haven/login.php">Connectez-vous ici!</a></p>
        </form>
    </div>
</body>
</html>
