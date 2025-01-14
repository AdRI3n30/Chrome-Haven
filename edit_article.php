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

// Vérifier le rôle de l'utilisateur dans la base de données
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

// Initialisation des variables
$message = "";

// Récupérer les informations de l'article
if (isset($_GET['id'])) {
    $article_id = $_GET['id'];
    $query = "SELECT * FROM Article WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $articleResult = $stmt->get_result();

    if ($articleResult->num_rows === 0) {
        die("Article introuvable.");
    }

    $article = $articleResult->fetch_assoc();

    // Récupérer la quantité actuelle de l'article dans le stock
    $stockQuery = "SELECT quantity FROM Stock WHERE article_id = ?";
    $stockStmt = $mysqli->prepare($stockQuery);
    $stockStmt->bind_param("i", $article_id);
    $stockStmt->execute();
    $stockResult = $stockStmt->get_result();
    $stock = $stockResult->fetch_assoc();
    $currentQuantity = $stock ? $stock['quantity'] : 0;
}

// Modifier l'article et la quantité dans le stock
if (isset($_POST['update_article'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    // Mettre à jour les informations de l'article
    $updateQuery = "UPDATE Article SET name = ?, description = ?, price = ? WHERE id = ?";
    $stmt = $mysqli->prepare($updateQuery);
    $stmt->bind_param("ssdi", $name, $description, $price, $article_id);
    if ($stmt->execute()) {
        // Mettre à jour la quantité dans la table Stock si nécessaire
        $stockQuery = "UPDATE Stock SET quantity = ? WHERE article_id = ?";
        $stockStmt = $mysqli->prepare($stockQuery);
        if ($stockStmt) {
            $stockStmt->bind_param("ii", $quantity, $article_id);
            $stockStmt->execute();
        }
        
        // Rediriger vers la page admin après la mise à jour
        header("Location: admin.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'article</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Modifier l'article</h1>

    <?php if (!empty($message)): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form action="edit_article.php?id=<?= $article_id ?>" method="POST">
        <div>
            <label for="name">Nom de l'article</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($article['name']) ?>" required>
        </div>
        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($article['description']) ?></textarea>
        </div>
        <div>
            <label for="price">Prix</label>
            <input type="number" step="0.01" id="price" name="price" value="<?= number_format($article['price'], 2) ?>" required>
        </div>
        <div>
            <label for="quantity">Quantité</label>
            <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($currentQuantity) ?>" required>
        </div>
        <div>
            <button type="submit" name="update_article">Mettre à jour</button>
        </div>
    </form>

    <br>
    <a href="admin.php">Retour à la gestion des articles</a>
</body>
</html>
