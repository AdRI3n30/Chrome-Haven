<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

// Récupérer l'ID de l'utilisateur
$user_id = $_SESSION['user_id'];

// Initialiser les variables d'erreur et de succès
$error = "";
$success = "";

// Récupérer les articles du panier
$query = "SELECT cart.id AS cart_id, article.id AS article_id, article.price, cart.quantity 
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

// Vérifier le solde de l'utilisateur
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

// Vérifier si l'utilisateur a suffisamment de solde
if ($balance < $totalAmount) {
    $error = "Vous n'avez pas assez de solde pour valider cette commande.";
} else {
    // Traiter le formulaire lorsque celui-ci est soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupérer les informations de facturation
        $billing_address = $_POST['billing_address'] ?? '';
        $billing_city = $_POST['billing_city'] ?? '';
        $billing_postal_code = $_POST['billing_postal_code'] ?? ''; 

        // Vérifier si tous les champs de facturation sont remplis
        if (empty($billing_address) || empty($billing_city) || empty($billing_postal_code)) {
            $error = "Veuillez remplir toutes les informations de facturation.";
        } else {
            // Mettre à jour le solde de l'utilisateur
            $newBalance = $balance - $totalAmount;
            $updateQuery = "UPDATE user SET balance = ? WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);

            if (!$updateStmt) {
                die("Erreur dans la mise à jour du solde : " . $mysqli->error);
            }

            $updateStmt->bind_param("di", $newBalance, $user_id);
            $updateStmt->execute();

            // Ajouter une facture
            $insertInvoiceQuery = "INSERT INTO invoice (user_id, transaction_date, amount, billing_address, billing_city, billing_postal_code) 
                                   VALUES (?, NOW(), ?, ?, ?, ?)";
            $invoiceStmt = $mysqli->prepare($insertInvoiceQuery);

            if (!$invoiceStmt) {
                die("Erreur dans l'ajout de la facture : " . $mysqli->error);
            }

            $invoiceStmt->bind_param("idsss", $user_id, $totalAmount, $billing_address, $billing_city, $billing_postal_code);
            $invoiceStmt->execute();

            // Mettre à jour le stock et supprimer les articles du panier
            foreach ($cartItems as $item) {
                $article_id = $item['article_id'];
                $quantity_purchased = $item['quantity'];

                // Vérifier la quantité en stock
                $queryStockCheck = "SELECT quantity FROM stock WHERE article_id = ?";
                $stmtStockCheck = $mysqli->prepare($queryStockCheck);
                $stmtStockCheck->bind_param("i", $article_id);
                $stmtStockCheck->execute();
                $resultStockCheck = $stmtStockCheck->get_result();
                $stock = $resultStockCheck->fetch_assoc();

                if ($stock && $stock['quantity'] >= $quantity_purchased) {
                    // Mettre à jour le stock
                    $newStockQuantity = $stock['quantity'] - $quantity_purchased;
                    $updateStockQuery = "UPDATE stock SET quantity = ? WHERE article_id = ?";
                    $updateStockStmt = $mysqli->prepare($updateStockQuery);
                    $updateStockStmt->bind_param("ii", $newStockQuantity, $article_id);
                    $updateStockStmt->execute();
                } else {
                    $error = "Stock insuffisant pour l'article ID $article_id.";
                    break;
                }
            }

            // Si tout est validé, supprimer les articles du panier
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
    <link rel="stylesheet" href="/static/cart-validate.css">
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
            <form method="post" class="form">
                <label for="billing_address">Adresse de facturation :</label>
                <input type="text" id="billing_address" name="billing_address" 
                       value="<?= isset($billing_address) ? htmlspecialchars($billing_address) : '' ?>" required>

                <label for="billing_city">Ville de facturation :</label>
                <input type="text" id="billing_city" name="billing_city" 
                       value="<?= isset($billing_city) ? htmlspecialchars($billing_city) : '' ?>" required>

                <label for="billing_postal_code">Code postal :</label>
                <input type="text" id="billing_postal_code" name="billing_postal_code" 
                       value="<?= isset($billing_postal_code) ? htmlspecialchars($billing_postal_code) : '' ?>" required>

                <button type="submit" class="button">Valider la commande</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

