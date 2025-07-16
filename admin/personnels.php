<link rel="stylesheet" href="/css/style.css" />
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';
require_once '../includes/header.php';

// Traitement de l'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $fonction = trim($_POST['fonction']);
    $email = trim($_POST['email']);
    $local = trim($_POST['local']);

    $stmt = $pdo->prepare("INSERT INTO personnels (nom, prenom, fonction, email, local) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $fonction, $email, $local]);

    header("Location: personnels.php");
    exit();
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $id = (int) $_POST['id'];
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $fonction = trim($_POST['fonction']);
    $email = trim($_POST['email']);
    $local = trim($_POST['local']);

    $stmt = $pdo->prepare("UPDATE personnels SET nom = ?, prenom = ?, fonction = ?, email = ?, local = ? WHERE id = ?");
    $stmt->execute([$nom, $prenom, $fonction, $email, $local, $id]);

    header("Location: personnels.php");
    exit();
}

// Récupération de la liste
$stmt = $pdo->query("SELECT * FROM personnels ORDER BY nom");
$personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si édition demandée
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$personnel_to_edit = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM personnels WHERE id = ?");
    $stmt->execute([$edit_id]);
    $personnel_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<h2>Membres du personnel</h2>

<table border="1" cellpadding="5">
  <tr>
    <th>Nom</th>
    <th>Prénom</th>
    <th>Fonction</th>
    <th>Email</th>
    <th>Local</th>
    <th>Actions</th>
  </tr>
  <?php foreach ($personnels as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['nom']) ?></td>
      <td><?= htmlspecialchars($row['prenom']) ?></td>
      <td><?= htmlspecialchars($row['fonction']) ?></td>
      <td><?= htmlspecialchars($row['email']) ?></td>
      <td><?= htmlspecialchars($row['local']) ?></td>
      <td><a href="personnels.php?edit=<?= $row['id'] ?>">Modifier</a></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if ($personnel_to_edit): ?>
  <h3>Modifier un membre</h3>
  <form action="personnels.php" method="post">
    <input type="hidden" name="id" value="<?= $personnel_to_edit['id'] ?>">

    <label>Nom :</label><br>
    <input type="text" name="nom" value="<?= htmlspecialchars($personnel_to_edit['nom']) ?>" required><br>

    <label>Prénom :</label><br>
    <input type="text" name="prenom" value="<?= htmlspecialchars($personnel_to_edit['prenom']) ?>" required><br>

    <label>Fonction :</label><br>
    <input type="text" name="fonction" value="<?= htmlspecialchars($personnel_to_edit['fonction']) ?>" required><br>

    <label>Email :</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($personnel_to_edit['email']) ?>" required><br>

    <label>Local :</label><br>
    <input type="text" name="local" value="<?= htmlspecialchars($personnel_to_edit['local']) ?>" required><br><br>

    <button type="submit" name="modifier">Mettre à jour</button>
    <a href="personnels.php">Annuler</a>
  </form>

<?php else: ?>

  <h3>Ajouter un nouveau membre</h3>
  <form action="personnels.php" method="post">
    <label>Nom :</label><br>
    <input type="text" name="nom" required><br>

    <label>Prénom :</label><br>
    <input type="text" name="prenom" required><br>

    <label>Fonction :</label><br>
    <input type="text" name="fonction" required><br>

    <label>Email :</label><br>
    <input type="email" name="email" required><br>

    <label>Local :</label><br>
    <input type="text" name="local" required><br><br>

    <button type="submit" name="ajouter">Ajouter</button>
  </form>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
