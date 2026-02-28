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
  <title>Admin Dashboard — PISD</title>
  <link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

  <nav class="nav">
      <div class="logo">🏥</div>
      <div class="actions">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Admin"); ?></span>
          <a class="btn" href="logout.php">Logout</a>
      </div>
  </nav>

  <div style="display:flex; gap:32px; margin-top:32px;">
      <div style="width:250px; background:white; padding:24px; border-radius:16px; box-shadow:0 8px 24px rgba(10,44,62,.08);">
          <h3 style="margin-bottom:16px;">Admin Dashboard</h3>
          <ul style="list-style:none; padding:0;">
              <li style="margin-bottom:12px;">
                  <a class="btn small-btn" href="admin_doctors.php">Manage Doctors</a>
              </li>
          </ul>
      </div>

      <div style="flex:1;">
          <h2 class="section-title">Welcome, Admin</h2>
          <p>From here you can add doctors and delete doctors.</p>
      </div>
  </div>

</div>
</body>
</html>