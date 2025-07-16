<link rel="stylesheet" href="/qui_est_la/public/css/style.css" />
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
    $intitule = trim($_POST['intitule']);
    $date_formation = $_POST['date_formation'];
    $local = trim($_POST['local']);
    $formateur_id = $_POST['formateur_id'] !== '' ? (int)$_POST['formateur_id'] : null;

    $stmt = $pdo->prepare("INSERT INTO formations (intitule, date_formation, local, formateur_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$intitule, $date_formation, $local, $formateur_id]);

    header("Location: formations.php");
    exit();
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $id = (int) $_POST['id'];
    $intitule = trim($_POST['intitule']);
    $date_formation = $_POST['date_formation'];
    $local = trim($_POST['local']);
    $formateur_id = $_POST['formateur_id'] !== '' ? (int)$_POST['formateur_id'] : null;

    $stmt = $pdo->prepare("UPDATE formations SET intitule = ?, date_formation = ?, local = ?, formateur_id = ? WHERE id = ?");
    $stmt->execute([$intitule, $date_formation, $local, $formateur_id, $id]);

    header("Location: formations.php");
    exit();
}

// Récupération des formateurs
$personnels = $pdo->query("SELECT id, nom, prenom FROM personnels ORDER BY nom")->fetchAll();

// Récupération des formations avec ID pour édition
$stmt = $pdo->query("
    SELECT f.*, p.nom AS formateur_nom, p.prenom AS formateur_prenom
    FROM formations f
    LEFT JOIN personnels p ON f.formateur_id = p.id
    ORDER BY f.date_formation DESC
");
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si une édition est demandée
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$formation_to_edit = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM formations WHERE id = ?");
    $stmt->execute([$edit_id]);
    $formation_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<h2>Liste des formations</h2>

<table border="1" cellpadding="5">
  <tr>
    <th>Intitulé</th>
    <th>Date</th>
    <th>Local</th>
    <th>Formateur</th>
    <th>Actions</th>
  </tr>
  <?php foreach ($formations as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['intitule']) ?></td>
      <td><?= htmlspecialchars($row['date_formation']) ?></td>
      <td><?= htmlspecialchars($row['local']) ?></td>
      <td>
        <?= $row['formateur_nom'] ? htmlspecialchars($row['formateur_prenom'] . ' ' . $row['formateur_nom']) : 'Non défini' ?>
      </td>
      <td><a href="formations.php?edit=<?= $row['id'] ?>">Modifier</a></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if ($formation_to_edit): ?>
  <h3>Modifier une formation</h3>
  <form action="formations.php" method="post">
    <input type="hidden" name="id" value="<?= $formation_to_edit['id'] ?>">

    <label>Intitulé :</label><br>
    <input type="text" name="intitule" value="<?= htmlspecialchars($formation_to_edit['intitule']) ?>" required><br>

    <label>Date :</label><br>
    <input type="date" name="date_formation" value="<?= htmlspecialchars($formation_to_edit['date_formation']) ?>" required><br>

    <label>Local :</label><br>
    <input type="text" name="local" value="<?= htmlspecialchars($formation_to_edit['local']) ?>" required><br>

    <label>Formateur :</label><br>
    <select name="formateur_id">
      <option value="">-- Aucun --</option>
      <?php foreach ($personnels as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $formation_to_edit['formateur_id'] == $p['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select><br><br>

    <button type="submit" name="modifier">Mettre à jour</button>
    <a href="formations.php">Annuler</a>
  </form>

<?php else: ?>

  <h3>Ajouter une formation</h3>
  <form action="formations.php" method="post">
    <label>Intitulé :</label><br>
    <input type="text" name="intitule" required><br>

    <label>Date :</label><br>
    <input type="date" name="date_formation" required><br>

    <label>Local :</label><br>
    <input type="text" name="local" required><br>

    <label>Formateur :</label><br>
    <select name="formateur_id">
      <option value="">-- Aucun --</option>
      <?php foreach ($personnels as $p): ?>
        <option value="<?= $p['id'] ?>">
          <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select><br><br>

    <button type="submit" name="ajouter">Ajouter</button>
  </form>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
