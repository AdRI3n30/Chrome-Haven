<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID d'article non spécifié.");
}

$article_id = intval($_GET['id']);

// Récupérer les informations de l'article
$query = "SELECT * FROM Article WHERE id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la préparation de la requête : " . $mysqli->error);
}

$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Article non trouvé.");
}

$article = $result->fetch_assoc();
$stmt->close();

// Récupérer la quantité restante dans le stock
$stockQuery = "SELECT quantity FROM Stock WHERE article_id = ?";
$stockStmt = $mysqli->prepare($stockQuery);
$stockStmt->bind_param("i", $article_id);
$stockStmt->execute();
$stockResult = $stockStmt->get_result();
$stock = $stockResult->fetch_assoc();
$remainingQuantity = $stock ? $stock['quantity'] : 0;
$stockStmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Détails de l'article</title>
</head>
<body>
    <h1>Détails de l'article</h1>
    <h2><?php echo htmlspecialchars($article['name']); ?></h2>
    <p><?php echo htmlspecialchars($article['description']); ?></p>
    <p>Prix : <?php echo htmlspecialchars($article['price']); ?> €</p>
    
    <!-- Afficher la quantité restante en stock -->
    <p><strong>Quantité restante en stock : </strong> <?php echo $remainingQuantity; ?></p>

    <!-- Formulaire pour ajouter au panier avec une quantité -->
    <form method="POST" action="cart.php">
        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
        
        <!-- Champ de quantité -->
        <label for="quantity">Quantité :</label>
        <input type="number" name="quantity" id="quantity" required min="1" max="<?php echo $remainingQuantity; ?>" value="1"><br><br>
        
        <button type="submit">Ajouter au panier</button>
    </form>

    <a href="home.php"><button>Retour à l'accueil</button></a>
</body>
</html>
