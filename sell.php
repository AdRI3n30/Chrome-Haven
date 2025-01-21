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

    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_name = basename($_FILES['image']['name']);
        $image_type = pathinfo($image_name, PATHINFO_EXTENSION);
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array(strtolower($image_type), $allowed_types)) {

            $upload_dir = 'uploads/';
            $image_url = $upload_dir . uniqid() . '.' . $image_type; 

            if (move_uploaded_file($image_tmp_name, $image_url)) {

            } else {
                die("Erreur lors du téléchargement de l'image.");
            }
        } else {
            die("Type de fichier non autorisé. Seules les images JPG, JPEG, PNG, et GIF sont autorisées.");
        }
    }

  
    $query = "INSERT INTO Article (name, description, price, author_id, image_url) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        die("Erreur dans la préparation de la requête : " . $mysqli->error);
    }

    $stmt->bind_param("ssdis", $name, $description, $price, $user_id, $image_url);

    if ($stmt->execute()) {
      
        $article_id = $stmt->insert_id;

        $stock_query = "INSERT INTO Stock (article_id, quantity) VALUES (?, ?)";
        $stock_stmt = $mysqli->prepare($stock_query);
        
        if ($stock_stmt) {
            $stock_stmt->bind_param("ii", $article_id, $quantity);
            if ($stock_stmt->execute()) {
                echo "Article ajouté avec succès et quantité ajoutée au stock.";
                header("Location: home.php");
                exit;
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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendre un article</title>
    <link rel="stylesheet" href="static/sell.css">
</head>
<body>
    <div class="container">
        <h1 class="page-title">Vendre un article</h1>
        <form class="sell-form" method="POST" action="sell.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Nom :</label>
                <input type="text" name="name" id="name" required>
            </div>

            <div class="form-group">
                <label for="description">Description :</label>
                <textarea name="description" id="description" required></textarea>
            </div>

            <div class="form-group">
                <label for="price">Prix :</label>
                <input type="number" step="0.01" name="price" id="price" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantité :</label>
                <input type="number" name="quantity" id="quantity" required min="1">
            </div>

            <div class="form-group">
                <label for="image">Image :</label>
                <input type="file" name="image" id="image" accept="image/*">
            </div>

            <button type="submit" class="btn-submit">Ajouter</button>
        </form>
        
        <a href="home.php" class="back-link">← Retour à l'accueil</a>
    </div>
</body>
</html>
