<?php
// includes/db.php

$host = 'localhost';
$dbname = 'qui_est_la';
$user = 'root';
$pass = ''; // si tu es en local avec XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // Activer les erreurs PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
