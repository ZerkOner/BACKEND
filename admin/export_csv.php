<link rel="stylesheet" href="/css/style.css" />
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=pointages.csv");

$output = fopen("php://output", "w");
fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

fputcsv($output, ['Date', 'Nom', 'Action', 'Motif', 'Détail']);

// Filtres GET
$type_personne = $_GET['type_personne'] ?? 'tous';
$type_action = $_GET['type_action'] ?? 'toutes';

$conditions = [];
$params = [];

$is_default_view = ($type_action === 'toutes');

// Filtres de personnes
if ($type_personne === 'formateur') {
    $conditions[] = "sous.formation_id IS NOT NULL";
} elseif ($type_personne === 'visiteur') {
    $conditions[] = "sous.personnel_id IS NOT NULL AND sous.formation_id IS NULL";
}

// Action : par défaut = seulement entrées (présents)
if ($is_default_view) {
    $conditions[] = "sous.type_action = 'entrée'";
} else {
    $conditions[] = "sous.type_action = ?";
    $params[] = $type_action;
}

$where_clause = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $nom = $row['visiteur_prenom'] . ' ' . $row['visiteur_nom'];
    $date = $row['horodatage'];
    $action = ucfirst($row['type_action']);

    if ($row['type_action'] === 'sortie') {
        $motif = 'Sortie';
        $detail = '-';
    } elseif (!empty($row['formation_intitule'])) {
        $motif = 'Formation';
        $detail = $row['formation_intitule'];
    } elseif (!empty($row['personnel_nom'])) {
        $motif = 'Visite';
        $detail = $row['personnel_prenom'] . ' ' . $row['personnel_nom'];
    } else {
        $motif = 'Inconnu';
        $detail = '-';
    }

    fputcsv($output, [$date, $nom, $action, $motif, $detail]);
}

fclose($output);
exit();
