<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_article_id'])) {
    $article_id = $_POST['remove_article_id'];

    $query = "DELETE FROM cart WHERE user_id = ? AND article_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ii", $user_id, $article_id);
    $stmt->execute();

    echo "Article supprimé du panier.";
    $stmt->close();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['article_id']) && isset($_POST['quantity'])) {
    $article_id = $_POST['article_id'];
    $quantity = $_POST['quantity'];

    $stockQuery = "SELECT quantity FROM stock WHERE article_id = ?";
    $stockStmt = $mysqli->prepare($stockQuery);
    $stockStmt->bind_param("i", $article_id);
    $stockStmt->execute();
    $stockResult = $stockStmt->get_result();
    $stock = $stockResult->fetch_assoc();
    $remainingQuantity = $stock ? $stock['quantity'] : 0;
    $stockStmt->close();

    if ($quantity > $remainingQuantity) {
        echo "La quantité demandée dépasse le stock disponible.";
    } else {
        $query = "SELECT * FROM cart WHERE user_id = ? AND article_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $user_id, $article_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $query = "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND article_id = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("iii", $quantity, $user_id, $article_id);
            $stmt->execute();
            echo "Quantité mise à jour dans le panier.";
        } else {
            $query = "INSERT INTO cart (user_id, article_id, quantity) VALUES (?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("iii", $user_id, $article_id, $quantity);
            $stmt->execute();
            echo "Article ajouté au panier.";
        }

        $query = "UPDATE stock SET quantity = quantity - ? WHERE article_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $quantity, $article_id);
        $stmt->execute();
        $stmt->close();
    }
}
if (isset($_POST['clear_cart'])) {
    $query = "SELECT article_id, quantity FROM cart WHERE user_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $article_id = $row['article_id'];
        $quantity = $row['quantity'];

        $queryStock = "UPDATE stock SET quantity = quantity + ? WHERE article_id = ?";
        $stmtStock = $mysqli->prepare($queryStock);
        $stmtStock->bind_param("ii", $quantity, $article_id);
        $stmtStock->execute();
        $stmtStock->close();
    }

    $queryDelete = "DELETE FROM cart WHERE user_id = ?";
    $stmtDelete = $mysqli->prepare($queryDelete);
    $stmtDelete->bind_param("i", $user_id);
    $stmtDelete->execute();

    echo "Votre panier a été vidé avec succès.";
    $stmt->close();
    $stmtDelete->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier</title>
    <link rel="stylesheet" href="static/cart.css">
</head>
<body>
    <div class="container">
        <h1>Mon Panier</h1>
        
        <?php
        $query = "SELECT a.id, a.name, a.price, c.quantity, s.quantity AS stock_quantity
                  FROM cart c
                  JOIN article a ON c.article_id = a.id
                  JOIN stock s ON a.id = s.article_id
                  WHERE c.user_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0): 
        ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Nom de l'article</th>
                        <th>Prix</th>
                        <th>Quantité</th>
                        <th>Total</th>
                        <th>Supprimer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    while ($row = $result->fetch_assoc()):
                        $total += $row['price'] * $row['quantity'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['price']); ?> €</td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['price'] * $row['quantity']); ?> €</td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="remove_article_id" value="<?php echo $row['id']; ?>" style="color: red; border: none; background: none; cursor: pointer;">
                                    <strong>❌</strong>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="cart-total">
                <p>Total: <span><?php echo $total; ?> €</span></p>
            </div>

            <div class="cart-actions">
                <a href="home.php"><button>Continuer vos achats</button></a>

                <form method="POST" style="display: inline;">
                    <button type="submit" name="clear_cart">Vider le panier</button>
                </form>

                <a href='cart-validate.php'><button>Valider la commande</button></a>
            </div>

        <?php else: ?>
            <p>Aucun article dans votre panier.</p>

            <a href="home.php"><button>Retour à l'accueil</button></a>

        <?php endif; ?>
    </div>
</body>
</html>

<?php
$stmt->close();
$mysqli->close();
?>
