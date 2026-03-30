<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "doctor") {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Doctor Dashboard</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>

<div class="container">

<nav class="nav">
<div class="logo">🏥</div>
<div class="actions">
<span><?php echo $_SESSION["user_name"]; ?></span>
<a class="btn" href="logout.php">Logout</a>
</div>
</nav>

<div style="display:flex; gap:32px; margin-top:32px;">

<div style="width:250px; background:white; padding:24px;">
<ul style="list-style:none;">

<li><a class="btn small-btn" href="doctor_profile.php">Profile</a></li>

<li style="margin-top:10px;">
<a class="btn small-btn" href="doctor_schedule.php">Add Schedule</a>
</li>

</ul>
</div>

<div>
<h2>Welcome Doctor</h2>
</div>

</div>

</div>
</body>
</html>