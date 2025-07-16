<?php
require_once 'includes/db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Traitement POST formulaire entrée ici
// Exemple : récupérer $_POST['nom'], $_POST['prenom'], etc.
// Valider les données, insérer en base, renvoyer un message

// 2. Champs obligatoires
if (
    empty($_POST['nom']) || 
    empty($_POST['prenom']) || 
    empty($_POST['email']) || 
    empty($_POST['objet'])
) {
    die("Tous les champs obligatoires doivent être remplis.");
}

// 3. Nettoyage des données
$nom = trim($_POST['nom']);
$prenom = trim($_POST['prenom']);
$email = strtolower(trim($_POST['email']));
$objet = $_POST['objet'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Adresse email invalide.");
}

// 4. Initialisation des IDs
$formation_id = null;
$personnel_id = null;

if ($objet === 'formation') {
    if (empty($_POST['formation_id'])) {
        die("Vous devez choisir une formation."); // Refus immédiat si vide
    }
    $formation_id = (int)$_POST['formation_id'];
} elseif ($objet === 'personnel') {
    if (empty($_POST['personnel_id'])) {
        die("Vous devez choisir une personne à rencontrer."); // Refus immédiat si vide
    }
    $personnel_id = (int)$_POST['personnel_id'];
} else {
    die("Vous devez spécifier un motif valide."); // Refus si objet inconnu
}

// 5. Recherche ou création du visiteur
try {
    $requete = $pdo->prepare("SELECT * FROM visiteurs WHERE email = ?");
    $requete->execute([$email]);
    $visiteur = $requete->fetch(PDO::FETCH_ASSOC);

    if ($visiteur) {
        $visiteur_id = $visiteur['id'];
        $qr_code_id = $visiteur['qr_code_id'];
    } else {
        $qr_code_id = uniqid("qr_", true);
        $requete = $pdo->prepare("INSERT INTO visiteurs (nom, prenom, email, qr_code_id) VALUES (?, ?, ?, ?)");
        $requete->execute([$nom, $prenom, $email, $qr_code_id]);
        $visiteur_id = $pdo->lastInsertId();

        if (!$visiteur_id) {
            die("Erreur lors de la création du visiteur.");
        }
    }
} catch (PDOException $e) {
    die("Erreur base de données (visiteur) : " . htmlspecialchars($e->getMessage()));
}

// 6. Insertion du pointage
try {
    $type_action = 'entrée';
    $horodatage = date('Y-m-d H:i:s');

    $requete = $pdo->prepare("INSERT INTO pointages (visiteur_id, type_action, horodatage, formation_id, personnel_id) VALUES (?, ?, ?, ?, ?)");
    $requete->execute([
        $visiteur_id,
        $type_action,
        $horodatage,
        $formation_id,
        $personnel_id
    ]);
} catch (PDOException $e) {
    die("Erreur base de données (pointage) : " . htmlspecialchars($e->getMessage()));
}

// 7. Affichage de confirmation
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Entrée enregistrée</title>
    <?php
// Après avoir affiché le message de confirmation de sortie
?>

<script>
    setTimeout(() => {
        window.location.href = 'https://qui-est-la.netlify.app';
    }, 5000);
</script>

</head>
<body>
    echo '<p>Sortie enregistrée. Vous allez être redirigé dans 5 secondes :D </p>';
    <h2>Bienvenue, <?= htmlspecialchars($prenom) . ' ' . htmlspecialchars($nom) ?> !</h2>
    <p>Votre entrée a été enregistrée à <?= htmlspecialchars($horodatage) ?>.</p>

    <?php if ($objet === 'formation'): ?>
        <p>Vous assistez à la formation ID <?= htmlspecialchars($formation_id) ?>.</p>
    <?php elseif ($objet === 'personnel'): ?>
        <p>Vous allez rencontrer un membre du personnel ID <?= htmlspecialchars($personnel_id) ?>.</p>
    <?php endif; ?>

    <p>Voici votre identifiant unique : <strong><?= htmlspecialchars($qr_code_id) ?></strong></p>
    <button onclick="window.print()">Imprimer</button>
</body>
</html>