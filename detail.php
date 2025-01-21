<?php
session_start();

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit;
}

$article_id = intval($_GET['id']);

// Vérifier si l'utilisateur est connecté et récupérer ses informations
$is_admin = false;
$user_id = null;
$can_edit = false;

// Si l'utilisateur est connecté, vérifier son rôle
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];


    $queryUser = "SELECT role FROM user WHERE id = ?";
    $stmtUser = $mysqli->prepare($queryUser);
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $user = $resultUser->fetch_assoc();
    $is_admin = ($user['role'] === 'admin');
    $stmtUser->close();
}

// Récupérer les détails de l'article
$query = "SELECT * FROM Article WHERE id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la préparation de la requête : " . $mysqli->error);
}

$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: home.php");
    exit;
}

$article = $result->fetch_assoc();
$stmt->close();

// Récupérer les informations de stock
$stockQuery = "SELECT quantity FROM Stock WHERE article_id = ?";
$stockStmt = $mysqli->prepare($stockQuery);
$stockStmt->bind_param("i", $article_id);
$stockStmt->execute();
$stockResult = $stockStmt->get_result();
$stock = $stockResult->fetch_assoc();
$remainingQuantity = $stock ? $stock['quantity'] : 0;
$stockStmt->close();

// Vérifier si l'utilisateur a l'autorisation de modifier cet article (admin ou auteur)
$can_edit = $is_admin || ($user_id === $article['author_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'article</title>
</head>
<body>
    <h1>Détails de l'article</h1>
    <h2><?php echo htmlspecialchars($article['name']); ?></h2>
    <p><?php echo htmlspecialchars($article['description']); ?></p>
    <p>Prix : <?php echo htmlspecialchars($article['price']); ?> €</p>
    <p><strong>Quantité restante en stock :</strong> <?php echo $remainingQuantity; ?></p>

    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="POST" action="cart.php">
            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
            <label for="quantity">Quantité :</label>
            <input type="number" name="quantity" id="quantity" required min="1" max="<?php echo $remainingQuantity; ?>" value="1"><br><br>
            <button type="submit">Ajouter au panier</button>
        </form>
    <?php else: ?>
        <p>Veuillez vous connecter pour ajouter cet article à votre panier.</p>
    <?php endif; ?>

    <?php if ($can_edit): ?>
        <form action="edit.php" method="get">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($article['id']); ?>">
            <button type="submit">Modifier cet article</button>
        </form>
    <?php endif; ?>

    <a href="home.php"><button>Retour à l'accueil</button></a>
</body>
</html>
