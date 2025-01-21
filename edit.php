<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de la connexion : " . $mysqli->connect_error);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit;
}

$article_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'article
$query = "SELECT * FROM article WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Article non trouvé.");
}

$article = $result->fetch_assoc();
$stmt->close();

// Récupérer la quantité de l'article dans la table stock
$queryStock = "SELECT quantity FROM stock WHERE article_id = ?";
$stmtStock = $mysqli->prepare($queryStock);
$stmtStock->bind_param("i", $article_id);
$stmtStock->execute();
$resultStock = $stmtStock->get_result();

if ($resultStock->num_rows === 0) {
    die("Quantité de stock non trouvée.");
}

$stock = $resultStock->fetch_assoc();
$quantity = $stock['quantity'];
$stmtStock->close();

// Vérifier si l'utilisateur est l'auteur de l'article ou admin
$queryUser = "SELECT role FROM user WHERE id = ?";
$stmtUser = $mysqli->prepare($queryUser);
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();
$is_admin = ($user['role'] === 'admin');
$can_edit = $is_admin || ($article['author_id'] == $user_id);

if (!$can_edit) {
    header("Location: home.php");
    exit;
}

// Mise à jour des informations de l'article
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;

    // Vérifier que tous les champs sont remplis correctement
    if (!empty($name) && !empty($description) && !empty($price) && isset($quantity)) {
        // Mettre à jour l'article dans la table article
        $updateArticleQuery = "UPDATE article SET name = ?, description = ?, price = ? WHERE id = ?";
        $stmtUpdateArticle = $mysqli->prepare($updateArticleQuery);
        $stmtUpdateArticle->bind_param("ssdi", $name, $description, $price, $article_id);
        $stmtUpdateArticle->execute();

        // Mettre à jour la quantité dans la table stock
        $updateStockQuery = "UPDATE stock SET quantity = ? WHERE article_id = ?";
        $stmtUpdateStock = $mysqli->prepare($updateStockQuery);
        $stmtUpdateStock->bind_param("ii", $quantity, $article_id);
        $stmtUpdateStock->execute();

        // Confirmation
        echo "<div class='success'>Article mis à jour avec succès!</div>";
    } else {
        echo "<div class='error'>Veuillez remplir tous les champs correctement.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'article</title>
    <link rel="stylesheet" href="/static/edit.css">
</head>
<body>
    <div class="container">
        <h1>Modifier l'article</h1>

        <form method="POST" action="edit.php?id=<?= $article['id'] ?>" class="form">
            <label for="name">Nom de l'article:</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($article['name']) ?>" required>

            <label for="description">Description:</label>
            <textarea name="description" id="description" required><?= htmlspecialchars($article['description']) ?></textarea>

            <label for="price">Prix:</label>
            <input type="number" name="price" id="price" value="<?= htmlspecialchars($article['price']) ?>" required>

            <label for="quantity">Quantité en stock:</label>
            <input type="number" name="quantity" id="quantity" value="<?= htmlspecialchars($quantity) ?>" required>

            <button type="submit" class="button">Mettre à jour l'article</button>
        </form>

        <a href="home.php" class="button">Retour à l'accueil</a>
    </div>
</body>
</html>
