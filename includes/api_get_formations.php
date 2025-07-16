<?php
require_once '../db.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$stmt = $pdo->query("SELECT id, intitule FROM formations ORDER BY date_formation DESC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
