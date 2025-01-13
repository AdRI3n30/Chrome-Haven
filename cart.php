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

// Récupération des articles dans le panier
$query = "SELECT Cart.id AS cart_id, Article.name, Article.price, Cart.quantity 
          FROM Cart 
          JOIN Article ON Cart.article_id = Article.id 
          WHERE Cart.user_id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la préparation de la requête : " . $mysqli->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
}

// Suppression d'un article du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cart_id'])) {
    $delete_cart_id = $_POST['delete_cart_id'];
    $deleteQuery = "DELETE FROM Cart WHERE id = ?";
    $deleteStmt = $mysqli->prepare($deleteQuery);

    if (!$deleteStmt) {
        die("Erreur dans la préparation de la requête de suppression : " . $mysqli->error);
    }

    $deleteStmt->bind_param("i", $delete_cart_id);
    $deleteStmt->execute();
    header("Location: cart.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Votre Panier</h1>

    <?php if (empty($cartItems)) : ?>
        <p>Votre panier est vide.</p>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Prix</th>
                    <th>Quantité</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item) : ?>
                    <tr>
                        <td><?= htmlspecialchars($item['title']) ?></td>
                        <td><?= number_format($item['price'], 2) ?> €</td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="delete_cart_id" value="<?= htmlspecialchars($item['cart_id']) ?>">
                                <button type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="checkout.php">Passer à la commande</a>
    <?php endif; ?>
</body>
</html>
