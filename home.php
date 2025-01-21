<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

$query = "SELECT * FROM Article ORDER BY id DESC"; 
$result = $mysqli->query($query);

if (!$result) {
    die("Erreur dans la requête SQL : " . $mysqli->error);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chrome Haven</title>
    <link rel="stylesheet" href="static/header.css">
    <link rel="stylesheet" href="static/style.css">
</head>
<body>
    <header>
        <div class="header-container">
            <img src="/source/donut_header.png" alt="Logo accueil" class="header-logo">
            <div class="search-and-navbar">
                <div class="search-bar">
                    <input type="text" placeholder="Rechercher...">
                    <button type="button">
                        <img src="/source/Vector.png" alt="Loupe">
                    </button>
                </div>
                <div class="navbar">
                    <a href="sell.php">
                        <img src="/source/Main.png" alt="Vendre un article" class="nav-icon">
                    </a>
                    <a href="cart.php">
                        <img src="/source/Frame.png" alt="Panier" class="nav-icon">
                    </a>
                    <a href="account.php">
                        <img src="/source/Profile.png" alt="Mon compte" class="nav-icon">
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex-container">
        <div class="section-title-container">
            <h2 class="section-title">Articles en vente</h2>
        </div>
    </div>

    <div class="articles-flex">
        <?php
        if ($result->num_rows > 0) {
            while ($article = $result->fetch_assoc()) {
                ?>
                <div class="article">
                    <h3><?php echo htmlspecialchars($article['name']); ?></h3>
                    <p><?php echo htmlspecialchars($article['description']); ?></p>
                    <p class="price">Prix : <?php echo htmlspecialchars($article['price']); ?> €</p>
                    <a href="detail.php?id=<?php echo $article['id']; ?>" class="details-link">Voir les détails</a>
                </div>
                
                <?php
            }
        } else {
            echo "<p>Aucun article disponible pour le moment.</p>";
        }
        ?>
    </div>
</body>
</html>
