<link rel="stylesheet" href="/qui_est_la/public/css/style.css" />
<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
