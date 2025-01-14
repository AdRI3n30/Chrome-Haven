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

// Récupérer les informations du panier
$query = "SELECT Cart.id AS cart_id, Article.id AS article_id, Article.price, Cart.quantity 
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
$totalAmount = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $totalAmount += $row['price'] * $row['quantity'];
}

// Validation du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $billing_address = $_POST['billing_address'] ?? '';
    $billing_city = $_POST['billing_city'] ?? '';
    $billing_zip = $_POST['billing_zip'] ?? '';

    if (empty($billing_address) || empty($billing_city) || empty($billing_zip)) {
        $error = "Veuillez remplir toutes les informations de facturation.";
    } else {
        // Vérifier si l'utilisateur a assez de solde
        $query = "SELECT balance FROM User WHERE id = ?";
        $stmt = $mysqli->prepare($query);

        if (!$stmt) {
            die("Erreur dans la préparation de la requête pour le solde : " . $mysqli->error);
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user['balance'] < $totalAmount) {
            $error = "Vous n'avez pas assez de solde pour valider cette commande.";
        } else {
            // Déduire le montant du solde de l'utilisateur
            $newBalance = $user['balance'] - $totalAmount;
            $updateQuery = "UPDATE User SET balance = ? WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);

            if (!$updateStmt) {
                die("Erreur dans la préparation de la mise à jour du solde : " . $mysqli->error);
            }

            $updateStmt->bind_param("di", $newBalance, $user_id);
            $updateStmt->execute();

            // Ajouter la facture
            $insertInvoiceQuery = "INSERT INTO Invoice (user_id, transaction_date, amount, billing_address, billing_city, billing_zip) 
                                   VALUES (?, NOW(), ?, ?, ?, ?)";
            $invoiceStmt = $mysqli->prepare($insertInvoiceQuery);

            if (!$invoiceStmt) {
                die("Erreur dans la préparation de l'ajout de la facture : " . $mysqli->error);
            }

            $invoiceStmt->bind_param("idsss", $user_id, $totalAmount, $billing_address, $billing_city, $billing_zip);
            $invoiceStmt->execute();

            // Supprimer les articles du panier
            $deleteCartQuery = "DELETE FROM Cart WHERE user_id = ?";
            $deleteCartStmt = $mysqli->prepare($deleteCartQuery);

            if (!$deleteCartStmt) {
                die("Erreur dans la préparation de la suppression du panier : " . $mysqli->error);
            }

            $deleteCartStmt->bind_param("i", $user_id);
            $deleteCartStmt->execute();

            $success = "Votre commande a été validée avec succès.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation du Panier</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Validation du Panier</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($success)): ?>
        <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <a href="home.php">Retour à la page d'accueil</a>
    <?php else: ?>
        <p>Total à payer : <?= number_format($totalAmount, 2) ?> €</p>

        <form method="post">
            <label for="billing_address">Adresse de facturation :</label>
            <input type="text" id="billing_address" name="billing_address" required><br>

            <label for="billing_city">Ville de facturation :</label>
            <input type="text" id="billing_city" name="billing_city" required><br>

            <label for="billing_zip">Code postal :</label>
            <input type="text" id="billing_zip" name="billing_zip" required><br>

            <button type="submit">Valider la commande</button>
        </form>
    <?php endif; ?>
</body>
</html>
