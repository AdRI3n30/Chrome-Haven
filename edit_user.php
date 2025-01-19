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

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];


    $query = "SELECT * FROM User WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if (!$user) {
        die("Utilisateur non trouvé.");
    }
} else {
    die("ID d'utilisateur non spécifié.");
}

$message = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $query = "UPDATE User SET username = ?, email = ?, role = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sssi", $username, $email, $role, $user_id);
    if ($stmt->execute()) {
        $message = "Utilisateur mis à jour avec succès.";
    } else {
        $message = "Erreur lors de la mise à jour de l'utilisateur.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'utilisateur</title>
</head>
<body>
    <h1>Modifier l'utilisateur</h1>

    <?php if (!empty($message)): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="username">Nom d'utilisateur :</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required><br><br>

        <label for="email">Email :</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br><br>

        <label for="role">Rôle :</label>
        <select name="role" id="role">
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Utilisateur</option>
        </select><br><br>

        <button type="submit">Mettre à jour</button>
    </form>

    <br>
    <a href="admin.php">Retour à l'administration</a>
</body>
</html>
