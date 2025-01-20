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

// Vérification du solde avant de valider la commande
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
        $billing_postal_code = $_POST['billing_zip'] ?? ''; // Corrigé ici

        if (empty($billing_address) || empty($billing_city) || empty($billing_postal_code)) {
            $error = "Veuillez remplir toutes les informations de facturation.";
        } else {
            // Déduire le montant du solde de l'utilisateur
            $newBalance = $balance - $totalAmount;
            $updateQuery = "UPDATE user SET balance = ? WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);

            if (!$updateStmt) {
                die("Erreur dans la mise à jour du solde : " . $mysqli->error);
            }

            $updateStmt->bind_param("di", $newBalance, $user_id);
            $updateStmt->execute();

            // Ajouter la facture
            $insertInvoiceQuery = "INSERT INTO invoice (user_id, transaction_date, amount, billing_address, billing_city, billing_postal_code) 
                                   VALUES (?, NOW(), ?, ?, ?, ?)";
            $invoiceStmt = $mysqli->prepare($insertInvoiceQuery);

            if (!$invoiceStmt) {
                die("Erreur dans l'ajout de la facture : " . $mysqli->error);
            }

            $invoiceStmt->bind_param("idsss", $user_id, $totalAmount, $billing_address, $billing_city, $billing_postal_code);
            $invoiceStmt->execute();

            // Supprimer les articles du panier
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation du Panier</title>
</head>
<body>
    <h1>Validation du Panier</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($success)): ?>
        <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <a href="home.php">Retour à l'accueil</a>
    <?php else: ?>
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
