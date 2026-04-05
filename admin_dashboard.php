<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - PISD</title>
  <link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

  <nav class="nav">
      <div class="logo">Hospital</div>
      <div class="actions">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Admin"); ?></span>
          <a class="btn" href="logout.php">Logout</a>
      </div>
  </nav>

  <div class="layout-grid">
      <div class="sidebar-card">
          <h3 style="margin-bottom:16px;">Admin Dashboard</h3>
          <ul class="sidebar-list">
              <li>
                  <a class="btn small-btn" href="admin_doctors.php">Manage Doctors</a>
              </li>
              <li>
                  <a class="btn small-btn" href="admin_vaccines.php">Manage Vaccines</a>
              </li>
          </ul>
      </div>

      <div style="flex:1; min-width:0;">
          <h2 class="section-title">Welcome, Admin</h2>
          <div class="feature-card">
              <div class="feature-card-body">
                  <p>From here you can manage doctors and maintain the vaccine module for the rest of HMDS.</p>
              </div>
          </div>
      </div>
  </div>

</div>
</body>
</html>
