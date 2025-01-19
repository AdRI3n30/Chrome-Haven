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
$query = "SELECT balance FROM User WHERE id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la préparation de la requête pour le solde : " . $mysqli->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$balance = $user['balance'];

if (isset($_POST['clear_cart'])) {
    $query = "DELETE FROM Cart WHERE user_id = ?";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        die("Erreur dans la préparation de la requête pour vider le panier : " . $mysqli->error);
    }

    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo "<p>Votre panier a été vidé avec succès.</p>";
    } else {
        echo "<p>Erreur lors du vidage du panier : " . $stmt->error . "</p>";
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['article_id'])) {
    $article_id = $_POST['article_id'];
    $quantity = $_POST['quantity'] ?? 1; 


    if (!$article_id) {
        die("Erreur : article invalide.");
    }


    $query = "SELECT * FROM Cart WHERE user_id = ? AND article_id = ?";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        die("Erreur dans la préparation de la requête : " . $mysqli->error);
    }

    $stmt->bind_param("ii", $user_id, $article_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $query = "UPDATE Cart SET quantity = quantity + ? WHERE user_id = ? AND article_id = ?";
        $stmt = $mysqli->prepare($query);

        if (!$stmt) {
            die("Erreur dans la préparation de la requête d'update : " . $mysqli->error);
        }

        $stmt->bind_param("iii", $quantity, $user_id, $article_id);

        if ($stmt->execute()) {
            echo "Quantité mise à jour dans le panier.";
        } else {
            echo "Erreur lors de la mise à jour de la quantité : " . $stmt->error;
        }
    } else {
        $query = "INSERT INTO Cart (user_id, article_id, quantity) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($query);

        if (!$stmt) {
            die("Erreur dans la préparation de la requête d'insertion : " . $mysqli->error);
        }

        $stmt->bind_param("iii", $user_id, $article_id, $quantity);

        if ($stmt->execute()) {
            echo "Article ajouté au panier avec succès.";
        } else {
            echo "Erreur lors de l'ajout de l'article au panier : " . $stmt->error;
        }
    }

    $stmt->close();
}

$query = "SELECT Article.name, Article.price, Cart.quantity FROM Cart 
          INNER JOIN Article ON Cart.article_id = Article.id 
          WHERE Cart.user_id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la préparation de la requête : " . $mysqli->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Votre panier</h2>";
echo "<p>Votre solde : " . number_format($balance, 2) . " €</p>"; 

$totalAmount = 0;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<p>" . htmlspecialchars($row['name']) . " - " . htmlspecialchars($row['price']) . " € - Quantité : " . htmlspecialchars($row['quantity']) . "</p>";
        $totalAmount += $row['price'] * $row['quantity'];
    }

    echo "<p>Total à payer : " . number_format($totalAmount, 2) . " €</p>";

    if ($balance >= $totalAmount) {
        echo "<a href='cart-validate.php'><button>Valider la commande</button></a>";
    } else {
        echo "<p>Vous n'avez pas assez de solde pour valider cette commande.</p>";
    }

    echo '<form method="POST" style="margin-top: 20px;">
            <button type="submit" name="clear_cart">Vider le panier</button>
          </form>';
} else {
    echo "<p>Votre panier est vide.</p>";
}

echo '<a href="home.php" style="display: inline-block; margin-top: 20px;">
        <button>Retour à l\'accueil</button>
      </a>';

$stmt->close();
?>
