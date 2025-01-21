<?php
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");
if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $mysqli->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM user WHERE username='$username'";
    $result = $mysqli->query($query);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: /chrome-haven/home.php");
            exit();
        } else {
            echo "<p class='error'>Mot de passe incorrect.</p>";
        }
    } else {
        echo "<p class='error'>Utilisateur non trouvé.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="static/login.css">
</head>
<body>
    <div class="container">
        <form method="POST" action="">
            <h2>Log in</h2>
            <input type="text" id="username" name="username" placeholder="Username..." required>
            <input type="password" id="password" name="password" placeholder="Password..." required>
            <button type="submit">Log in →</button>
            <p>Aucun compte, <a href="register.php">créez-vous-en un!</a></p>
        </form>
    </div>
</body>
</html>
