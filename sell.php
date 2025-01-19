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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $quantity = $_POST['quantity'] ?? 1; 
    $user_id = $_SESSION['user_id'] ?? null; 

    if (empty($name) || empty($description) || empty($price) || !$user_id || empty($quantity)) {
        die("Tous les champs sont requis !");
    }

    $query = "INSERT INTO Article (name, description, price, author_id) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        die("Erreur dans la préparation de la requête : " . $mysqli->error);
    }

    $stmt->bind_param("ssdi", $name, $description, $price, $user_id);

    if ($stmt->execute()) {
        echo "Article ajouté avec succès !";
     
        $article_id = $stmt->insert_id;

        $stock_query = "INSERT INTO Stock (article_id, quantity) VALUES (?, ?)";
        $stock_stmt = $mysqli->prepare($stock_query);
        
        if ($stock_stmt) {
            $stock_stmt->bind_param("ii", $article_id, $quantity);
            if ($stock_stmt->execute()) {
                echo " Quantité ajoutée au stock avec succès.";
            } else {
                echo "Erreur lors de l'ajout de la quantité au stock : " . $stock_stmt->error;
            }
        } else {
            echo "Erreur dans la préparation de la requête de stock : " . $mysqli->error;
        }
    } else {
        echo "Erreur lors de l'ajout de l'article : " . $stmt->error;
    }

    $stmt->close();
}
?>

<html>
<head>
    <title>Vendre un article</title>
</head>
<body>
    <h1>Vendre un article</h1>
    <form method="POST" action="sell.php">
        <label for="name">Nom :</label>
        <input type="text" name="name" id="name" required><br><br>

        <label for="description">Description :</label>
        <textarea name="description" id="description" required></textarea><br><br>

        <label for="price">Prix :</label>
        <input type="number" step="0.01" name="price" id="price" required><br><br>

        <label for="quantity">Quantité :</label>
        <input type="number" name="quantity" id="quantity" required min="1"><br><br>

        <button type="submit">Ajouter</button>
    </form>
</body>
</html>
