<?php
session_start();

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "", "chrome-haven");

if ($mysqli->connect_error) {
    die("Échec de connexion : " . $mysqli->connect_error);
}

// ID de l'utilisateur connecté (toujours accessible)
$current_user_id = $_SESSION['user_id'] ?? null;

// ID de l'utilisateur dont on affiche le profil
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $profile_user_id = intval($_GET['id']); // ID passé dans l'URL
} elseif ($current_user_id) {
    $profile_user_id = $current_user_id; // Si aucun ID passé, affiche le compte connecté
} else {
    // Si aucune information, redirige vers la page de connexion
    header("Location: login.php");
    exit;
}

// Récupération des informations du profil à afficher
$query = "SELECT id, username, email, balance, role, created_at FROM user WHERE id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la requête SQL (user) : " . $mysqli->error);
}

$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_info = $user_result->fetch_assoc();

if (!$user_info) {
    die("Utilisateur introuvable.");
}

// Récupération des articles postés par cet utilisateur
$query = "SELECT id, name, price FROM article WHERE author_id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Erreur dans la requête SQL (articles) : " . $mysqli->error);
}

$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$articles_result = $stmt->get_result();

// Historique des factures uniquement pour l'utilisateur connecté
$invoices = [];
if ($profile_user_id == $current_user_id) {
    $invoice_query = "SELECT id, transaction_date, amount, billing_address, billing_city, billing_postal_code 
                      FROM invoice WHERE user_id = ?";
    $invoice_stmt = $mysqli->prepare($invoice_query);

    if (!$invoice_stmt) {
        die("Erreur dans la requête SQL (invoices) : " . $mysqli->error);
    }

    $invoice_stmt->bind_param("i", $current_user_id);
    $invoice_stmt->execute();
    $invoices_result = $invoice_stmt->get_result();
    while ($row = $invoices_result->fetch_assoc()) {
        $invoices[] = $row;
    }
    $invoice_stmt->close();
}

// Mise à jour des informations (uniquement si c'est le compte connecté)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile_user_id == $current_user_id) {
    $new_email = $_POST['email'] ?? null;
    $new_password = $_POST['password'] ?? null;

    if ($new_email && $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update_query = "UPDATE user SET email = ?, password = ? WHERE id = ?";
        $update_stmt = $mysqli->prepare($update_query);

        if (!$update_stmt) {
            die("Erreur dans la requête SQL (mise à jour) : " . $mysqli->error);
        }

        $update_stmt->bind_param("ssi", $new_email, $hashed_password, $current_user_id);
        if ($update_stmt->execute()) {
            // Redirection pour éviter la double soumission du formulaire
            header("Location: account.php");
            exit;
        } else {
            die("Échec de la mise à jour.");
        }
    } else {
        echo "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte</title>
    <link rel="stylesheet" href="static/account.css">
</head>
<body>
    <div class="profile-container">
        <h1>
            <?php if ($profile_user_id == $current_user_id): ?>
                Mon compte
            <?php else: ?>
                Compte de <?php echo htmlspecialchars($user_info['username']); ?>
            <?php endif; ?>
        </h1>

        <p>Email : <?php echo htmlspecialchars($user_info['email']); ?></p>
        <?php if ($profile_user_id == $current_user_id): ?>
            <p>Solde : <?php echo number_format($user_info['balance'], 2); ?> €</p>
            <p>Rôle : <?php echo htmlspecialchars($user_info['role']); ?></p>
        <?php endif; ?>
        <p>Date d'inscription : <?php echo htmlspecialchars($user_info['created_at']); ?></p>

        <!-- Si l'utilisateur connecté regarde son propre profil -->
        <?php if ($profile_user_id == $current_user_id): ?>
            <h2>Modifier mes informations</h2>
            <form method="POST">
                <label for="email">Nouvel email :</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                <label for="password">Nouveau mot de passe :</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Mettre à jour</button>
            </form>
            <div class="form-container">
                <h2>Ajouter de l'argent à votre solde</h2>
                <form method="POST" action="add_balance.php">
                    <label for="amount">Montant :</label>
                    <input type="number" id="amount" name="amount" step="1" required>
                    <button type="submit">Ajouter</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="articles-container">
            <h2>Articles postés</h2>
            <?php if ($articles_result->num_rows > 0): ?>
                <ul>
                    <?php while ($article = $articles_result->fetch_assoc()): ?>
                        <li>
                            <a href="detail.php?id=<?php echo htmlspecialchars($article['id']); ?>">
                                <?php echo htmlspecialchars($article['name']); ?>
                            </a> 
                            - <?php echo htmlspecialchars($article['price']); ?> €
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>Aucun article posté.</p>
            <?php endif; ?>
        </div>

        <!-- Historique des factures (uniquement pour l'utilisateur connecté) -->
        <?php if ($profile_user_id == $current_user_id): ?>
            <div class="invoices-container">
                <h2>Historique des Achats Validés</h2>
                <?php if (!empty($invoices)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID Facture</th>
                                <th>Date</th>
                                <th>Montant (€)</th>
                                <th>Adresse</th>
                                <th>Ville</th>
                                <th>Code Postal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['id']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['transaction_date']); ?></td>
                                    <td><?php echo number_format($invoice['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['billing_address']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['billing_city']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['billing_postal_code']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Vous n'avez pas encore validé d'achat.</p>
                <?php endif; ?>
            </div>
            <form action="logout.php" method="POST" class="logout-form">
                <button type="submit">Se déconnecter</button>
            </form>
        <?php endif; ?>
    </div>
    <a href="home.php" >← Retour à l'accueil</a>
</body>
</html>
