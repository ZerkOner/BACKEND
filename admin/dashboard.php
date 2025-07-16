<link rel="stylesheet" href="/qui_est_la/public/css/style.css" />
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<div class="dashboard-wrapper">
  <div class="dashboard">
    <h1><u>Tableau de bord</u></h1>

    <div class="dashboard-grid">
      <a href="pointages.php" class="dashboard-card"> Voir les pointages <br>🕒</a>
      <a href="personnels.php" class="dashboard-card"> Gérer le personnel <br>👥</a>
      <a href="formations.php" class="dashboard-card"> Gérer les formations <br>📅</a>
    </div>

    <div class="dashboard-bottom">
      <a href="logout.php" class="dashboard-card logout">Se déconnecter<br>👋</a>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
