<?php
require_once 'includes/db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// 1. Validation des champs obligatoires
if (
    empty($_POST['nom']) || 
    empty($_POST['prenom']) || 
    empty($_POST['email']) || 
    empty($_POST['objet'])
) {
    die("Tous les champs obligatoires doivent être remplis.");
}

// 2. Nettoyage des données
$nom = trim($_POST['nom']);
$prenom = trim($_POST['prenom']);
$email = strtolower(trim($_POST['email']));
$objet = $_POST['objet'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Adresse email invalide.");
}

// 3. Initialisation des IDs
$formation_id = null;
$personnel_id = null;

if ($objet === 'formation') {
    if (empty($_POST['formation_id'])) {
        die("Vous devez choisir une formation.");
    }
    $formation_id = (int)$_POST['formation_id'];
} elseif ($objet === 'personnel') {
    if (empty($_POST['personnel_id'])) {
        die("Vous devez choisir une personne à rencontrer.");
    }
    $personnel_id = (int)$_POST['personnel_id'];
} else {
    die("Vous devez spécifier un motif valide.");
}

// 4. Recherche ou création du visiteur
try {
    $requete = $pdo->prepare("SELECT * FROM visiteurs WHERE email = ?");
    $requete->execute([$email]);
    $visiteur = $requete->fetch(PDO::FETCH_ASSOC);

    if ($visiteur) {
        $visiteur_id = $visiteur['id'];
        $qr_code_id = $visiteur['qr_code_id'];
        // Prendre aussi nom/prenom depuis base si tu préfères garder l'existant
        $nom = $visiteur['nom'];
        $prenom = $visiteur['prenom'];
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

// 5. Insertion du pointage
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

// 6. Récupération du détail pour affichage
$intitule = '';
$personnel_nom = '';

if ($objet === 'formation' && $formation_id) {
    $stmt = $pdo->prepare("SELECT intitule FROM formations WHERE id = ?");
    $stmt->execute([$formation_id]);
    $intitule = $stmt->fetchColumn() ?: '';
}

if ($objet === 'personnel' && $personnel_id) {
    $stmt = $pdo->prepare("SELECT prenom, nom FROM personnels WHERE id = ?");
    $stmt->execute([$personnel_id]);
    $personnel = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($personnel) {
        $personnel_nom = $personnel['prenom'] . ' ' . $personnel['nom'];
    }
}

// 7. Réponse JSON pour le JS frontend
header('Content-Type: application/json');
echo json_encode([
    'succes' => true,
    'nom' => $nom,
    'prenom' => $prenom,
    'horodatage' => $horodatage,
    'qr_code_id' => $qr_code_id,
    'objet' => $objet,
    'intitule' => $intitule,
    'personnel' => $personnel_nom
]);
exit;
