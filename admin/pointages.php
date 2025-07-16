<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';
require_once '../includes/header.php';

$type_personne = $_GET['type_personne'] ?? 'tous';
$type_action = $_GET['type_action'] ?? 'entrée'; // Par défaut : personnes présentes

$conditions = [];
$params = [];

$is_history_view = ($type_action === 'toutes');

// Filtre motif (type_personne)
if ($type_personne === 'formateur') {
    $conditions[] = "sous.formation_id IS NOT NULL";
} elseif ($type_personne === 'visiteur') {
    $conditions[] = "sous.personnel_id IS NOT NULL AND sous.formation_id IS NULL";
}

// Filtre action (type_action)
if ($is_history_view) {
    // historique complet : on ne filtre pas sur type_action
} else {
    $conditions[] = "sous.type_action = ?";
    $params[] = $type_action;
}

$where_clause = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
?>

<link rel="stylesheet" href="/qui_est_la/public/css/style.css" />

<h2>Historique des pointages</h2>

<!-- Boutons de sélection rapide -->
<div style="margin-bottom:20px;">
     <a href="pointages.php?type_action=toutes&type_personne=<?= htmlspecialchars($type_personne) ?>" 
    class="btn-home" 
     style="<?= $type_action === 'toutes' ? 'background-color:#2c3e50;color:#fff;' : '' ?>">
    Voir tout l'historique
  </a>
  <a href="pointages.php?type_action=entrée&type_personne=<?= htmlspecialchars($type_personne) ?>" 
    class="btn-home" 
    style="margin-right:10px; <?= $type_action === 'entrée' ? 'background-color:#2c3e50;color:#fff;' : '' ?>">
    Personnes dans le bâtiment
</a>
</div>

<!-- Filtres -->
<div class="filters-wrapper">
  <form method="get" action="pointages.php" class="filters-form" style="flex-wrap: wrap;">
    <label for="type_personne">Motif :</label>
    <select name="type_personne" id="type_personne">
      <option value="tous" <?= $type_personne === 'tous' ? 'selected' : '' ?>>Tous</option>
      <option value="formateur" <?= $type_personne === 'formateur' ? 'selected' : '' ?>>En formation</option>
      <option value="visiteur" <?= $type_personne === 'visiteur' ? 'selected' : '' ?>>En visite</option>
    </select>

    <label for="type_action">Type d'action :</label>
    <select name="type_action" id="type_action">
      <option value="toutes" <?= $type_action === 'toutes' ? 'selected' : '' ?>>Toutes</option>
      <option value="entrée" <?= $type_action === 'entrée' ? 'selected' : '' ?>>Entrées</option>
      <option value="sortie" <?= $type_action === 'sortie' ? 'selected' : '' ?>>Sorties</option>
    </select>

    <button type="submit">Filtrer</button>
  </form>

  <!-- Export CSV -->
  <form method="get" action="export_csv.php" class="export-form" style="margin-left:auto;">
    <input type="hidden" name="type_personne" value="<?= htmlspecialchars($type_personne) ?>">
    <input type="hidden" name="type_action" value="<?= htmlspecialchars($type_action) ?>">
    <button type="submit">Exporter CSV</button>
  </form>
</div>

<!-- Tableau -->
<table>
  <tr>
    <th>Date</th>
    <th>Nom du visiteur</th>
    <th>Action</th>
    <th>Motif</th>
    <th>Détail</th>
  </tr>

<?php
try {
    $sql = "
        SELECT sous.*
        FROM (
            SELECT 
                p.type_action,
                p.horodatage,
                v.nom AS visiteur_nom,
                v.prenom AS visiteur_prenom,
                p.formation_id,
                p.personnel_id,
                f.intitule AS formation_intitule,
                pe.nom AS personnel_nom,
                pe.prenom AS personnel_prenom,
                p.visiteur_id
            FROM pointages p
            JOIN visiteurs v ON p.visiteur_id = v.id
            LEFT JOIN formations f ON p.formation_id = f.id
            LEFT JOIN personnels pe ON p.personnel_id = pe.id
            WHERE p.horodatage = (
                SELECT MAX(p2.horodatage)
                FROM pointages p2
                WHERE p2.visiteur_id = p.visiteur_id
            )
        ) AS sous
        $where_clause
        ORDER BY sous.horodatage DESC
    ";

    $requete = $pdo->prepare($sql);
    $requete->execute($params);

    while ($row = $requete->fetch(PDO::FETCH_ASSOC)) {
        $nom = htmlspecialchars($row['visiteur_prenom'] . ' ' . $row['visiteur_nom']);
        $date = htmlspecialchars($row['horodatage']);
        $action = htmlspecialchars($row['type_action']);

        if ($action === 'sortie') {
            $motif = 'Sortie';
            $detail = '-';
        } elseif (!empty($row['formation_intitule'])) {
            $motif = 'Formation';
            $detail = htmlspecialchars($row['formation_intitule']);
        } elseif (!empty($row['personnel_nom'])) {
            $motif = 'Visite';
            $detail = htmlspecialchars($row['personnel_prenom'] . ' ' . $row['personnel_nom']);
        } else {
            $motif = 'Inconnu';
            $detail = '-';
        }

        $classe = ($action === 'entrée') ? 'bg-entree' : 'bg-sortie';

        echo "<tr class=\"$classe\">
                <td>$date</td>
                <td>$nom</td>
                <td>" . ucfirst($action) . "</td>
                <td>$motif</td>
                <td>$detail</td>
              </tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='5'>Erreur : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>
</table>

<?php require_once '../includes/footer.php'; ?>
