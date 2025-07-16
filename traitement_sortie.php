<?php
require_once 'includes/db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Traitement POST formulaire sortie ici
// Exemple : récupérer $_POST['qr_code_id'], $_POST['email'], etc.
// Valider les données, modifier en base, renvoyer un message

// Récupération des données
$qr_code_id = isset($_POST['qr_code_id']) ? trim($_POST['qr_code_id']) : '';
$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';

// Vérification que l'un des deux champs est rempli
if (empty($qr_code_id) && empty($email)) {
    afficherErreur("Veuillez saisir votre identifiant unique ou votre email.");
}

// Recherche du visiteur
if (!empty($qr_code_id)) {
    $stmt = $pdo->prepare("SELECT * FROM visiteurs WHERE qr_code_id = ?");
    $stmt->execute([$qr_code_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM visiteurs WHERE email = ?");
    $stmt->execute([$email]);
}

$visiteur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$visiteur) {
    afficherErreur("Visiteur non reconnu.");
}

$visiteur_id = $visiteur['id'];

// Récupérer le dernier pointage
$stmt = $pdo->prepare("SELECT * FROM pointages WHERE visiteur_id = ? ORDER BY horodatage DESC LIMIT 1");
$stmt->execute([$visiteur_id]);
$dernier_pointage = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérification : le dernier pointage doit être une entrée
if (!$dernier_pointage || $dernier_pointage['type_action'] !== 'entrée') {
    afficherErreur("Vous n'êtes pas enregistré comme présent dans le bâtiment. Aucune sortie possible.");
}

// Enregistrement de la sortie
$horodatage = date('Y-m-d H:i:s');
try {
    $stmt = $pdo->prepare("
        INSERT INTO pointages (visiteur_id, type_action, horodatage, formation_id, personnel_id)
        VALUES (?, 'sortie', ?, NULL, NULL)
    ");
    $stmt->execute([$visiteur_id, $horodatage]);
} catch (PDOException $e) {
    afficherErreur("Erreur lors de l'enregistrement du pointage : " . htmlspecialchars($e->getMessage()));
}

// Affichage confirmation
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Sortie enregistrée</title>
  <meta http-equiv="refresh" content="3;url=https://qui-est-la.netlify.app">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5fff7;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      text-align: center;
    }
    .message-ok {
      max-width: 600px;
      padding: 2rem;
      background: #e6ffed;
      border: 1px solid #a3d9a5;
      border-radius: 8px;
      color: #2c662d;
      font-size: 1.5rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <div class="message-ok">
    <p>Sortie enregistrée avec succès !</p>
    <p><strong><?= htmlspecialchars($visiteur['prenom'] . ' ' . $visiteur['nom']) ?></strong></p>
    <p>à <?= htmlspecialchars($horodatage) ?></p>
    <p>Redirection automatique...</p>
  </div>
</body>
</html>

<?php
exit();

// Fonction d'affichage stylisé des erreurs
function afficherErreur($message) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
      <meta charset="UTF-8">
      <title>Erreur</title>
      <meta http-equiv="refresh" content="3;url=index.php">
      <style>
        body {
          font-family: Arial, sans-serif;
          background: #fff5f5;
          display: flex;
          align-items: center;
          justify-content: center;
          height: 100vh;
          text-align: center;
        }
        .message-erreur {
          max-width: 600px;
          padding: 2rem;
          background: #ffecec;
          border: 1px solid #ff9e9e;
          border-radius: 8px;
          color: #c0392b;
          font-size: 1.5rem;
          box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
      </style>
    </head>
    <body>
      <div class="message-erreur">
        <p><?= htmlspecialchars($message) ?></p>
        <p>Redirection automatique...</p>
      </div>
    </body>
    </html>
    <?php
    exit();
}
