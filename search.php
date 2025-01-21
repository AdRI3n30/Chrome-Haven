<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

if (!isset($_GET['query']) || empty(trim($_GET['query']))) {

    header("Location: home.php");
    exit();
}

$searchTerm = $mysqli->real_escape_string($_GET['query']); // Échapper la chaîne pour éviter les injections SQL
$query = "SELECT * FROM Article WHERE name LIKE '%$searchTerm%' ORDER BY id DESC";

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
    <title>Chrome Haven - Recherche</title>
    <link rel="stylesheet" href="static/style.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo-and-title">
                <img src="source/donut_header.png" alt="Logo accueil" class="header-logo">
                <h1 class="header-title">Chrome Haven</h1>
            </div>
            <div class="search-and-navbar">
                <form action="search.php" method="GET" class="search-bar">
                    <input type="text" name="query" placeholder="Rechercher..." aria-label="Rechercher un article">
                    <button type="submit">
                        <img src="source/Vector.png" alt="Loupe">
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="articles-flex">
        <?php
        if ($result->num_rows > 0) {
            while ($article = $result->fetch_assoc()) {
                ?>
                <div class="article">
                    <h3><?php echo htmlspecialchars($article['name']); ?></h3>
                    <?php if (!empty($article['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="Image de l'article">
                    <?php endif; ?>
                    <a href="detail.php?id=<?php echo $article['id']; ?>" class="details-link">Voir les détails</a>
                </div>
                <?php
            }
        } else {
            echo "<p style=\"color: white;\">Aucun article trouvé pour cette recherche.</p>";
        }
        ?>
    </div>
</body>
</html>
