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
        echo "Le nom d'utilisateur ou l'email existe déjà.";
    } else {
    
        $insertQuery = "INSERT INTO user (username, email, password, role, balance) 
                        VALUES ('$username', '$email', '$password', 'user', 0)";
        if ($mysqli->query($insertQuery)) {
            echo "Inscription réussie. Vous pouvez maintenant vous connecter.";
            header("Location: /chrome-haven/home.php");
            exit();
        } else {
            echo "Erreur : " . $mysqli->error;
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
</head>
<body>
    <h1>Inscription</h1>
    <form method="POST" action="">
        <label for="username">Nom d'utilisateur :</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="email">Adresse email :</label>
        <input type="email" id="email" name="email" required>
        <br>
        <label for="password">Mot de passe :</label>
        <input type="password" id="password" name="password" required>
        <br>
        <button type="submit">S'inscrire</button>
    </form>
</body>
</html>
