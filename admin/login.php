<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $mdp = $_POST['mdp'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($mdp, $admin['mdp'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: index.php");
        exit();
    } else {
        $erreur = "Identifiants incorrects.";
    }
}
?>

<link rel="stylesheet" href="../css/login.css">

<div class="login-container">
    <h2>Connexion administrateur</h2>

    <?php if (isset($erreur)): ?>
        <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <form action="login.php" method="post" class="login-form">
        <label for="login">Identifiant :</label>
        <input type="text" name="login" id="login" required>

        <label for="mdp">Mot de passe :</label>
        <input type="password" name="mdp" id="mdp" required>

        <button type="submit">Connexion</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
