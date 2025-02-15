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

$error = "";
$success = "";

$query = "SELECT cart.id AS cart_id, article.id AS article_id, article.name AS article_name, article.price, cart.quantity 
          FROM cart 
          JOIN article ON cart.article_id = article.id 
          WHERE cart.user_id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la préparation de la requête pour le panier : " . $mysqli->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$totalAmount = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $totalAmount += $row['price'] * $row['quantity'];
}

$query = "SELECT balance FROM user WHERE id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la préparation de la requête pour le solde : " . $mysqli->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$balance = $user['balance'];

if ($balance < $totalAmount) {
    $error = "Vous n'avez pas assez de solde pour valider cette commande.";
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $billing_address = $_POST['billing_address'] ?? '';
        $billing_city = $_POST['billing_city'] ?? '';
        $billing_postal_code = $_POST['billing_postal_code'] ?? ''; 

        if (empty($billing_address) || empty($billing_city) || empty($billing_postal_code)) {
            $error = "Veuillez remplir toutes les informations de facturation.";
        } else {
            $newBalance = $balance - $totalAmount;
            $updateQuery = "UPDATE user SET balance = ? WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);

            if (!$updateStmt) {
                die("Erreur dans la mise à jour du solde : " . $mysqli->error);
            }
            $updateStmt->bind_param("di", $newBalance, $user_id);
            $updateStmt->execute();

            $insertInvoiceQuery = "INSERT INTO invoice (user_id, transaction_date, amount, billing_address, billing_city, billing_postal_code) 
                                   VALUES (?, NOW(), ?, ?, ?, ?)";
            $invoiceStmt = $mysqli->prepare($insertInvoiceQuery);

            if (!$invoiceStmt) {
                die("Erreur dans l'ajout de la facture : " . $mysqli->error);
            }

            $invoiceStmt->bind_param("idsss", $user_id, $totalAmount, $billing_address, $billing_city, $billing_postal_code);
            $invoiceStmt->execute();

            foreach ($cartItems as $item) {
                $article_id = $item['article_id'];
                $quantity_purchased = $item['quantity'];
                $queryStockCheck = "SELECT quantity FROM stock WHERE article_id = ?";
                $stmtStockCheck = $mysqli->prepare($queryStockCheck);
                $stmtStockCheck->bind_param("i", $article_id);
                $stmtStockCheck->execute();
                $resultStockCheck = $stmtStockCheck->get_result();
                $stock = $resultStockCheck->fetch_assoc();
                if ($stock && $stock['quantity'] >= $quantity_purchased) {

                    $newStockQuantity = $stock['quantity'] - $quantity_purchased;
                    $updateStockQuery = "UPDATE stock SET quantity = ? WHERE article_id = ?";
                    $updateStockStmt = $mysqli->prepare($updateStockQuery);
                    $updateStockStmt->bind_param("ii", $newStockQuantity, $article_id);
                    $updateStockStmt->execute();
                } else {
                    $error = "Stock insuffisant pour l'article " . htmlspecialchars($item['article_name']) . ".";
                    break;
                }
            }
            if (empty($error)) {
                $deleteCartQuery = "DELETE FROM cart WHERE user_id = ?";
                $deleteCartStmt = $mysqli->prepare($deleteCartQuery);

                if (!$deleteCartStmt) {
                    die("Erreur dans la suppression du panier : " . $mysqli->error);
                }

                $deleteCartStmt->bind_param("i", $user_id);
                $deleteCartStmt->execute();

                $success = "Votre commande a été validée avec succès.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation du Panier</title>
    <link rel="stylesheet" href="static/cart-validate.css">
</head>
<body>
    <div class="container">
        <h1>Validation du Panier</h1>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif (!empty($success)): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
            <a href="home.php" class="button">Retour à l'accueil</a>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Quantité</th>
                        <th>Prix Unitaire</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['article_name']) ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                            <td><?= htmlspecialchars(number_format($item['price'], 2)) ?> €</td>
                            <td><?= htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)) ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="total-label">Total :</td>
                        <td class="total-value"><?= htmlspecialchars(number_format($totalAmount, 2)) ?> €</td>
                    </tr>
                </tfoot>
            </table>

            <form method="post" class="form">
                <label for="billing_address">Adresse de facturation :</label>
                <input type="text" id="billing_address" name="billing_address" required>

                <label for="billing_city">Ville de facturation :</label>
                <input type="text" id="billing_city" name="billing_city" required>

                <label for="billing_postal_code">Code postal :</label>
                <input type="text" id="billing_postal_code" name="billing_postal_code" required>

                <button type="submit" class="button">Valider la commande</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
