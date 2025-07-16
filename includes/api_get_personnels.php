<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$stmt = $pdo->query("SELECT id, nom, prenom FROM personnels ORDER BY nom");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
