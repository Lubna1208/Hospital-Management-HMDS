<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "doctor") {
    header("Location: login.php");
    exit;
}

$doctor_id = (int)$_SESSION["user_id"];
$msg = "";
$error = "";

/* Save Profile */
if (isset($_POST["save_profile"])) {
    $full_name = trim($_POST["full_name"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $room_no = trim($_POST["room_no"] ?? "");

    // Check if doctor row exists
    $existsStmt = sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.doctors WHERE id = ?", [$doctor_id]);
    $exists = $existsStmt && sqlsrv_has_rows($existsStmt);

    if ($exists) {
        $sql = "UPDATE dbo.doctors
                SET full_name=?, department=?, phone=?, room_no=?, updated_at=GETDATE()
                WHERE id=?";
        $ok = sqlsrv_query($conn, $sql, [$full_name, $department, $phone, $room_no, $doctor_id]);
    } else {
        $sql = "INSERT INTO dbo.doctors (id, full_name, department, phone, room_no)
                VALUES (?, ?, ?, ?, ?)";
        $ok = sqlsrv_query($conn, $sql, [$doctor_id, $full_name, $department, $phone, $room_no]);
    }

    if ($ok) $msg = "Profile saved successfully.";
    else $error = "Failed to save profile.";
}

/* Load Profile */
$loadStmt = sqlsrv_query($conn, "SELECT TOP 1 * FROM dbo.doctors WHERE id = ?", [$doctor_id]);
$doctor = $loadStmt ? sqlsrv_fetch_array($loadStmt, SQLSRV_FETCH_ASSOC) : null;

// Also load email/name from users
$userStmt = sqlsrv_query($conn, "SELECT name, email FROM dbo.users WHERE id = ?", [$doctor_id]);
$userRow = $userStmt ? sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Profile — PISD</title>
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
          <h3 style="margin-bottom:16px;">Doctor</h3>
          <ul style="list-style:none; padding:0;">
              <li style="margin-bottom:12px;">
                  <a class="btn small-btn" href="doctor_dashboard.php">Dashboard</a>
              </li>
              <li style="margin-bottom:12px;">
                  <a class="btn small-btn" href="doctor_profile.php">My Profile</a>
              </li>
          </ul>
      </div>

      <div style="flex:1;">
          <h2 class="section-title">Update My Profile</h2>

          <?php if ($msg): ?>
            <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2); color: #166534; font-size: 14px; font-weight: 600;">
              <?php echo htmlspecialchars($msg); ?>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="error-message" style="margin-bottom: 16px;">
              <?php echo htmlspecialchars($error); ?>
            </div>
          <?php endif; ?>

          <div class="feature-card">
            <h3>Account Info</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userRow["email"] ?? ($_SESSION["user_email"] ?? "")); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($userRow["name"] ?? ($_SESSION["user_name"] ?? "")); ?></p>
          </div>

          <div class="feature-card" style="margin-top:16px;">
            <h3>Doctor Profile</h3>
            <form method="post">
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <input class="form-field" name="full_name" placeholder="Full Name"
                  value="<?php echo htmlspecialchars($doctor["full_name"] ?? ($userRow["name"] ?? "")); ?>">
                <input class="form-field" name="department" placeholder="Department"
                  value="<?php echo htmlspecialchars($doctor["department"] ?? ""); ?>">
                <input class="form-field" name="phone" placeholder="Phone"
                  value="<?php echo htmlspecialchars($doctor["phone"] ?? ""); ?>">
                <input class="form-field" name="room_no" placeholder="Room No"
                  value="<?php echo htmlspecialchars($doctor["room_no"] ?? ""); ?>">
              </div>

              <div style="margin-top:12px;">
                <button class="btn" type="submit" name="save_profile">Save</button>
              </div>
            </form>
          </div>

      </div>
  </div>

</div>
</body>
</html>