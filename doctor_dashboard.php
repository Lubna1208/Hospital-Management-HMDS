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
  <title>Doctor Dashboard — PISD</title>
  <link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

  <nav class="nav">
      <div class="logo">🏥</div>
      <div class="actions">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Doctor"); ?></span>
          <a class="btn" href="logout.php">Logout</a>
      </div>
  </nav>

  <div style="display:flex; gap:32px; margin-top:32px;">

      <div style="width:250px; background:white; padding:24px; border-radius:16px; box-shadow:0 8px 24px rgba(10,44,62,.08);">
          <h3 style="margin-bottom:16px;">Doctor Dashboard</h3>
          <ul style="list-style:none; padding:0;">
              <li style="margin-bottom:12px;">
                  <a class="btn small-btn" href="doctor_profile.php">Update My Profile</a>
              </li>
          </ul>
      </div>

      <div style="flex:1;">
          <h2 class="section-title">Welcome, Doctor</h2>
          <p>You can update your profile information from the sidebar.</p>
      </div>

  </div>

</div>
</body>
</html>