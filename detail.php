<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");
if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

$id = $_GET['id'];
$stmt = $mysqli->prepare("SELECT * FROM Article WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Détail de l'article</title>
</head>
<body>
<h1><?php echo $article['title']; ?></h1>
<p><?php echo $article['description']; ?></p>
<p>Prix : <?php echo $article['price']; ?> €</p>
<form method="POST" action="cart.php">
    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
    <button type="submit">Ajouter au panier</button>
</form>
</body>
</html>
