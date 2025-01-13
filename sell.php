<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");
if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $author_id = $_SESSION['user_id'];

    $stmt = $mysqli->prepare("INSERT INTO Article (title, description, price, author_id, stock, published_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssdii", $title, $description, $price, $author_id, $stock);

    if ($stmt->execute()) {
        header("Location: home.php");
        exit;
    } else {
        $error = "Erreur lors de l'ajout de l'article.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vente</title>
</head>
<body>
<h1>Mettre un article en vente</h1>
<?php if (isset($error)) echo "<p>$error</p>"; ?>
<form method="POST">
    <label>Nom</label>
    <input type="text" name="title" required>
    <label>Description</label>
    <textarea name="description" required></textarea>
    <label>Prix</label>
    <input type="number" step="0.01" name="price" required>
    <label>Stock</label>
    <input type="number" name="stock" required>
    <button type="submit">Ajouter</button>
</form>
</body>
</html>
