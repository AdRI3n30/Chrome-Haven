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
if (isset($_GET['delete_article_id'])) {
    $article_id = $_GET['delete_article_id'];
    $query = "DELETE FROM Article WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $message = "Article supprimé avec succès.";
    } else {
        $message = "Erreur lors de la suppression de l'article.";
    }
}

if (isset($_POST['update_article'])) {
    $article_id = $_POST['article_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    $query = "UPDATE Article SET name = ?, description = ?, price = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ssdi", $name, $description, $price, $article_id);
        $stmt->execute();
        $message = "Article mis à jour avec succès.";
    } else {
        $message = "Erreur lors de la mise à jour de l'article.";
    }
}


$articlesResult = $mysqli->query("SELECT * FROM Article");

$quantitiesResult = $mysqli->query("SELECT article_id, quantity AS total_quantity FROM Stock");

$usersResult = $mysqli->query("SELECT id, username, email, role FROM User");

$invoicesResult = $mysqli->query("SELECT * FROM Invoice");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../static/admin.css">
</head>
<body>
    <h1>Admin Panel</h1>

    <?php if (!empty($message)): ?>
        <p style="color: white;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>Gestion des articles</h2>
    <h3>Liste des articles</h3>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Description</th>
            <th>Prix</th>
            <th>Quantité</th>
            <th>Actions</th>
        </tr>
        <?php while ($article = $articlesResult->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($article['id']) ?></td>
                <td><?= htmlspecialchars($article['name']) ?></td>
                <td><?= htmlspecialchars($article['description']) ?></td>
                <td><?= number_format($article['price'], 2) ?> €</td>
                <td>
                    <?php
                    $quantity = 0; 
                    $article_id = $article['id'];

                    $quantitiesResult->data_seek(0); 
                    while ($quantityRow = $quantitiesResult->fetch_assoc()) {
                        if ($quantityRow['article_id'] === $article_id) {
                            $quantity = $quantityRow['total_quantity'];
                            break;
                        }
                    }
                    echo $quantity; 
                    ?>
                </td>
                <td>
                    <a href="?delete_article_id=<?= $article['id'] ?>">Supprimer</a> |
                    <a href="edit_article.php?id=<?= $article['id'] ?>">Modifier</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Gestion des utilisateurs</h2>
    <h3>Liste des utilisateurs</h3>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nom d'utilisateur</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Actions</th>
        </tr>
        <?php while ($user = $usersResult->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $user['id'] ?>">Modifier</a> |
                    <a href="delete_user.php?id=<?= $user['id'] ?>">Supprimer</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Gestion des factures</h2>
    <h3>Liste des factures</h3>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>ID Utilisateur</th>
            <th>Date</th>
            <th>Montant (€)</th>
            <th>Adresse</th>
            <th>Ville</th>
            <th>Code Postal</th>
        </tr>
        <?php while ($invoice = $invoicesResult->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($invoice['id']) ?></td>
                <td><?= htmlspecialchars($invoice['user_id']) ?></td>
                <td><?= htmlspecialchars($invoice['transaction_date']) ?></td>
                <td><?= number_format($invoice['amount'], 2) ?></td>
                <td><?= htmlspecialchars($invoice['billing_address']) ?></td>
                <td><?= htmlspecialchars($invoice['billing_city']) ?></td>
                <td><?= htmlspecialchars($invoice['billing_postal_code']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <br>
    <a href="../home.php">Retour à la page d'accueil</a>
</body>
</html>
