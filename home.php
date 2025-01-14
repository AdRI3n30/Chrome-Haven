<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

// Vérification du rôle de l'utilisateur connecté
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT role FROM User WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result_role = $stmt->get_result();
    if ($result_role->num_rows > 0) {
        $user = $result_role->fetch_assoc();
        $is_admin = ($user['role'] === 'admin');
    }
}

$query = "SELECT * FROM Article ORDER BY id DESC"; 
$result = $mysqli->query($query);

if (!$result) {
    die("Erreur dans la requête SQL : " . $mysqli->error);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
</head>
<body>
    <h1>Home</h1>

    <div class="navbar">
        <a href="login.php"><button>Connexion</button></a>
        <a href="register.php"><button>Inscription</button></a>
        <a href="sell.php"><button>Vendre un article</button></a>
        <a href="cart.php"><button>Panier</button></a>
        <a href="account.php"><button>Mon compte</button></a>
        <?php if ($is_admin): ?>
            <a href="admin.php"><button style="color: red;">Admin</button></a>
        <?php endif; ?>
    </div>

    <h2>Articles en vente</h2>
    <?php
    if ($result->num_rows > 0) {
        while ($article = $result->fetch_assoc()) {
            ?>
            <div class="article">
                <h2><?php echo htmlspecialchars($article['name']); ?></h2>
                <p><?php echo htmlspecialchars($article['description']); ?></p>
                <p>Prix : <?php echo htmlspecialchars($article['price']); ?> €</p>
                <a href="detail.php?id=<?php echo $article['id']; ?>">Voir les détails</a>
            </div>
            <?php
        }
    } else {
        echo "<p>Aucun article disponible pour le moment.</p>";
    }
    ?>
</body>
</html>
