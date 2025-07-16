<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';
require_once '../includes/header.php';

$type_personne = $_GET['type_personne'] ?? 'tous';
$type_action = $_GET['type_action'] ?? 'entrée';

$params = [];
$filters = [];

if ($type_personne === 'formateur') {
    $filters[] = "entree.formation_id IS NOT NULL";
} elseif ($type_personne === 'visiteur') {
    $filters[] = "entree.personnel_id IS NOT NULL AND entree.formation_id IS NULL";
}

$where_clause = count($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
?>

<link rel="stylesheet" href="../css/style.css" />

<h2>Historique des pointages (1 ligne par personne)</h2>

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

    <button type="submit">Filtrer</button>
  </form>

  <form method="get" action="export_csv.php" class="export-form" style="margin-left:auto;">
    <input type="hidden" name="type_personne" value="<?= htmlspecialchars($type_personne) ?>">
    <input type="hidden" name="type_action" value="toutes">
    <button type="submit">Exporter CSV</button>
  </form>
</div>

<table>
  <tr>
    <th>Nom du visiteur</th>
    <th>Entrée</th>
    <th>Sortie</th>
    <th>Motif</th>
    <th>Détail</th>
    <th>Local</th>
  </tr>

<?php
try {
    $sql = "
        SELECT 
            v.nom AS visiteur_nom,
            v.prenom AS visiteur_prenom,
            entree.horodatage AS entree_time,
            sortie.horodatage AS sortie_time,
            entree.formation_id,
            entree.personnel_id,
            f.intitule AS formation_intitule,
            f.local AS formation_local,
            pe.nom AS personnel_nom,
            pe.prenom AS personnel_prenom,
            pe.local AS personnel_local
        FROM pointages AS entree
        JOIN visiteurs v ON entree.visiteur_id = v.id
        LEFT JOIN pointages AS sortie 
            ON sortie.visiteur_id = entree.visiteur_id 
            AND sortie.type_action = 'sortie' 
            AND sortie.horodatage > entree.horodatage
            AND NOT EXISTS (
                SELECT 1 FROM pointages p2
                WHERE p2.visiteur_id = entree.visiteur_id
                  AND p2.type_action = 'sortie'
                  AND p2.horodatage > entree.horodatage
                  AND p2.horodatage < sortie.horodatage
            )
        LEFT JOIN formations f ON entree.formation_id = f.id
        LEFT JOIN personnels pe ON entree.personnel_id = pe.id
        WHERE entree.type_action = 'entrée'
        $where_clause
        ORDER BY entree.horodatage DESC
    ";

    $requete = $pdo->prepare($sql);
    $requete->execute($params);

    while ($row = $requete->fetch(PDO::FETCH_ASSOC)) {
        $nom = htmlspecialchars($row['visiteur_prenom'] . ' ' . $row['visiteur_nom']);
        $entree = htmlspecialchars($row['entree_time']);
        $sortie = $row['sortie_time'] ? htmlspecialchars($row['sortie_time']) : '-';

        if (!empty($row['formation_intitule'])) {
            $motif = 'Formation';
            $detail = htmlspecialchars($row['formation_intitule']);
            $local = htmlspecialchars($row['formation_local']);
        } elseif (!empty($row['personnel_nom'])) {
            $motif = 'Visite';
            $detail = htmlspecialchars($row['personnel_prenom'] . ' ' . $row['personnel_nom']);
            $local = htmlspecialchars($row['personnel_local']);
        } else {
            $motif = 'Inconnu';
            $detail = '-';
            $local = '-';
        }

        echo "<tr>
                <td>$nom</td>
                <td>$entree</td>
                <td>$sortie</td>
                <td>$motif</td>
                <td>$detail</td>
                <td>$local</td>
              </tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='6'>Erreur : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>
</table>

<?php require_once '../includes/footer.php'; ?>
