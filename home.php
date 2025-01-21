<?php
session_start();

$mysqli = new mysqli("localhost", "root", "", "chrome-haven");
if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

$user = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT role FROM User WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
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
            <div class="logo-and-title">
                <img src="source/donut_header.png" alt="Logo accueil" class="header-logo">
                <h1 class="header-title">Chrome Haven</h1>
            </div>

            <div class="search-and-navbar">

                <div class="search-bar">
                    <form action="search.php" method="GET" class="search-bar">
                        <input type="text" name="query" placeholder="Rechercher..." aria-label="Rechercher un article">
                        <button type="submit">
                            <img src="source/Vector.png" alt="Loupe">
                        </button>
                    </form>
                </div>

                <div class="navbar">
                    <a href="sell.php">
                        <img src="source/Main.png" alt="Vendre un article" class="nav-icon">
                    </a>
                    <a href="cart.php">
                        <img src="source/Frame.png" alt="Panier" class="nav-icon">
                    </a>
                    <a href="account.php">
                        <img src="source/Profile.png" alt="Mon compte" class="nav-icon">
                    </a>
                    <?php 
                    if ($user && $user['role'] === 'admin'): ?>
                        <a href="Admin/admin.php">
                            <img src="source/admin.png" alt="Admin Panel" class="nav-icon">
                        </a>
                    <?php endif; ?>
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
                    <?php if (!empty($article['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="Image de l'article" class="home-image">  
                    <?php endif; ?>
                    <a href="detail.php?id=<?php echo $article['id']; ?>" class="details-link">Voir les détails</a>
                </div>
                <?php
            }
        } else {
            echo "<p style=\"color: white;\">Aucun article disponible pour le moment.</p>";
        }
        ?>
    </div>
</body>
</html>
