<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "doctor") {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Dashboard</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>

<div class="container">

<nav class="nav">
    <div class="logo">Hospital</div>
    <div class="actions">
        <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Doctor"); ?></span>
        <a class="btn" href="logout.php">Logout</a>
    </div>
</nav>

<div class="layout-grid">
    <div class="sidebar-card">
        <h3 style="margin-bottom:16px;">Doctor</h3>
        <ul class="sidebar-list">
            <li><a class="btn small-btn" href="doctor_profile.php">Profile</a></li>
            <li><a class="btn small-btn" href="doctor_schedule.php">Add Schedule</a></li>
        </ul>
    </div>

    <div style="flex:1; min-width:0;">
        <h2 class="section-title">Welcome Doctor</h2>
        <div class="feature-card">
            <div class="feature-card-body">
                <p>Use the sidebar to update your profile and maintain your weekly schedule.</p>
            </div>
        </div>
    </div>
</div>

</div>
</body>
</html>
