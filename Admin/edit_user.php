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

$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM User WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Utilisateur introuvable.");
}

$user = $result->fetch_assoc();
if ($user['role'] !== 'admin') {
    header("Location: home.php");
    exit;
}
$message = "";

if (isset($_GET['id'])) {
    $target_user_id = $_GET['id'];

    $userQuery = "SELECT * FROM User WHERE id = ?";
    $userStmt = $mysqli->prepare($userQuery);
    $userStmt->bind_param("i", $target_user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows === 0) {
        die("Utilisateur introuvable.");
    }

    $targetUser = $userResult->fetch_assoc();

    $username_value = isset($_POST['username']) ? $_POST['username'] : $targetUser['username'];
    $email_value = isset($_POST['email']) ? $_POST['email'] : $targetUser['email'];
    $role_value = isset($_POST['role']) ? $_POST['role'] : $targetUser['role'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];

        $usernameQuery = "SELECT id FROM User WHERE username = ? AND id != ?";
        $stmt = $mysqli->prepare($usernameQuery);
        $stmt->bind_param("si", $username, $target_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Le nom d'utilisateur est déjà pris.";
        } else {
            $emailQuery = "SELECT id FROM User WHERE email = ? AND id != ?";
            $stmt = $mysqli->prepare($emailQuery);
            $stmt->bind_param("si", $email, $target_user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $message = "L'email est déjà utilisé.";
            } else {
                $updateQuery = "UPDATE User SET username = ?, email = ?, role = ? WHERE id = ?";
                $stmt = $mysqli->prepare($updateQuery);
                $stmt->bind_param("sssi", $username, $email, $role, $target_user_id);
                if ($stmt->execute()) {
                    $message = "Utilisateur mis à jour avec succès.";
                } else {
                    $message = "Erreur lors de la mise à jour de l'utilisateur.";
                }
            }
        }
    }
} else {
    die("ID de l'utilisateur manquant.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'utilisateur</title>
    <link rel="stylesheet" href="../static/edit_user.css">
</head>
<body>
    <h1>Modifier l'utilisateur</h1>

    <?php if (!empty($message)): ?>
        <p style="color: <?= (strpos($message, 'succès') !== false) ? 'green' : 'red'; ?>;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="username">Nom d'utilisateur :</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username_value) ?>" required><br><br>

        <label for="email">Email :</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email_value) ?>" required><br><br>

        <label for="role">Rôle :</label>
        <select name="role" id="role">
            <option value="admin" <?= $role_value === 'admin' ? 'selected' : '' ?>>Administrateur</option>
            <option value="user" <?= $role_value === 'user' ? 'selected' : '' ?>>Utilisateur</option>
        </select><br><br>

        <button type="submit">Mettre à jour</button>
    </form>

    <br>
    <a href="admin.php">Retour à l'administration</a>
</body>
</html>
