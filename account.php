<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

// Vérifier la connexion
if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

// Récupération des informations de l'utilisateur
$user_id = $_GET['user_id'] ?? $_SESSION['user_id']; // Si aucun paramètre n'est fourni, on prend l'utilisateur connecté
$query = "SELECT id, username, email, balance, profile_picture, role, created_at FROM user WHERE id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la préparation de la requête pour les informations utilisateur : " . $mysqli->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_info = $user_result->fetch_assoc();

if (!$user_info) {
    die("Utilisateur introuvable.");
}

// Récupération des articles postés par l'utilisateur
$query = "SELECT id, name, price, published_at FROM article WHERE author_id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la préparation de la requête pour les articles : " . $mysqli->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$articles_result = $stmt->get_result();

// Récupération des articles achetés par l'utilisateur (uniquement si c'est le compte connecté)
$purchased_articles = [];
if ($user_id == $_SESSION['user_id']) {
    $query = "SELECT a.name, a.price, c.quantity FROM cart c
              INNER JOIN article a ON c.article_id = a.id
              WHERE c.user_id = ?";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        die("Erreur dans la préparation de la requête pour les achats : " . $mysqli->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $purchased_result = $stmt->get_result();
    while ($row = $purchased_result->fetch_assoc()) {
        $purchased_articles[] = $row;
    }
}

// Formulaire pour modifier les informations (uniquement pour le compte connecté)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id == $_SESSION['user_id']) {
    $new_email = $_POST['email'];
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $query = "UPDATE user SET email = ?, password = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        die("Erreur dans la préparation de la requête pour la mise à jour : " . $mysqli->error);
    }

    $stmt->bind_param("ssi", $new_email, $new_password, $user_id);

    if ($stmt->execute()) {
        echo "Informations mises à jour avec succès.";
    } else {
        echo "Erreur lors de la mise à jour des informations : " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte</title>
</head>
<body>
    <h1>Profil de <?php echo htmlspecialchars($user_info['username']); ?></h1>
    <p>Email : <?php echo htmlspecialchars($user_info['email']); ?></p>
    <p>Solde : <?php echo number_format($user_info['balance'], 2); ?> €</p>
    <p>Rôle : <?php echo htmlspecialchars($user_info['role']); ?></p>
    <p>Date d'inscription : <?php echo htmlspecialchars($user_info['created_at']); ?></p>

    <?php if ($user_id == $_SESSION['user_id']): ?>
        <h2>Modifier vos informations</h2>
        <form method="POST">
            <label for="email">Nouvel email :</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required><br>

            <label for="password">Nouveau mot de passe :</label>
            <input type="password" id="password" name="password" required><br>

            <button type="submit">Mettre à jour</button>
        </form>

        <h2>Ajouter de l'argent à votre solde</h2>
        <form method="POST" action="add_balance.php">
            <label for="amount">Montant :</label>
            <input type="number" id="amount" name="amount" step="0.01" required>
            <button type="submit">Ajouter</button>
        </form>
    <?php endif; ?>

    <h2>Articles postés</h2>
    <?php if ($articles_result->num_rows > 0): ?>
        <ul>
            <?php while ($article = $articles_result->fetch_assoc()): ?>
                <li><?php echo htmlspecialchars($article['name']); ?> - <?php echo htmlspecialchars($article['price']); ?> €</li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>Aucun article posté.</p>
    <?php endif; ?>

    <?php if ($user_id == $_SESSION['user_id']): ?>
        <h2>Articles achetés</h2>
        <?php if (!empty($purchased_articles)): ?>
            <ul>
                <?php foreach ($purchased_articles as $article): ?>
                    <li><?php echo htmlspecialchars($article['name']); ?> - <?php echo htmlspecialchars($article['price']); ?> € - Quantité : <?php echo htmlspecialchars($article['quantity']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Vous n'avez rien acheté pour le moment.</p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="home.php">Retour à l'accueil</a>
</body>
</html>
