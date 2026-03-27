<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "patient") !== "patient") {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$success = '';
$error = '';

// Handle password change form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$current_password || !$new_password || !$confirm_password) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } else {
        $stmtPwd = sqlsrv_query($conn, "SELECT password_hash FROM dbo.users WHERE id = ?", [$user_id]);
        $row = sqlsrv_fetch_array($stmtPwd, SQLSRV_FETCH_ASSOC);
        $current_hash = $row['password_hash'] ?? '';

        if (!password_verify($current_password, $current_hash)) {
            $error = "Current password is incorrect.";
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updatePwd = sqlsrv_query($conn, "UPDATE dbo.users SET password_hash = ? WHERE id = ?", [$new_hash, $user_id]);
            if ($updatePwd === false) $error = "Failed to update password: " . print_r(sqlsrv_errors(), true);
            else $success = "Password changed successfully.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password — PISD</title>
<link rel="stylesheet" href="assets/patient.css">
<script src="assets/patient.js"></script>
</head>
<body>
<div class="container">
    <nav class="nav">
        <div class="logo">🏥</div>
        <div class="actions">
            <span class="user-name">Patient</span>
            <a class="btn" href="patient_info.php">Back</a>
            <a class="btn" href="logout.php">Logout</a>
        </div>
    </nav>

    <h2 class="section-title">Change Password</h2>

    <?php if($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
    <?php if($success): ?><p class="success"><?php echo $success; ?></p><?php endif; ?>

    <form method="post" style="max-width:600px;">
        <label>Current Password</label>
        <div style="position:relative;">
            <input class="form-field" type="password" name="current_password" id="current_password" required>
            <button type="button" onclick="togglePassword('btn_current','current_password')" 
                    id="btn_current" style="position:absolute; top:10px; right:10px; padding:4px 8px; font-size:12px;">Show</button>
        </div>

        <label>New Password</label>
        <div style="position:relative;">
            <input class="form-field" type="password" name="new_password" id="new_password" required>
            <button type="button" onclick="togglePassword('btn_new','new_password')" 
                    id="btn_new" style="position:absolute; top:10px; right:10px; padding:4px 8px; font-size:12px;">Show</button>
        </div>

        <label>Confirm New Password</label>
        <div style="position:relative;">
            <input class="form-field" type="password" name="confirm_password" id="confirm_password" required>
            <button type="button" onclick="togglePassword('btn_confirm','confirm_password')" 
                    id="btn_confirm" style="position:absolute; top:10px; right:10px; padding:4px 8px; font-size:12px;">Show</button>
        </div>

        <button class="btn" type="submit">Change Password</button>
    </form>
</div>
</body>
</html>