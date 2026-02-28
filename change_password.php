<?php
session_start();
include "db.php";

if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$error = "";
$success = "";

if(isset($_POST["change"])){
    $current = $_POST["current_password"];
    $new = $_POST["new_password"];
    $confirm = $_POST["confirm_password"];

    // Fetch current password hash
    $sql = "SELECT password_hash FROM dbo.users WHERE id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$user_id]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if(!password_verify($current, $row["password_hash"])){
        $error = "Current password is incorrect.";
    } elseif($new !== $confirm){
        $error = "New passwords do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $update_sql = "UPDATE dbo.users SET password_hash = ? WHERE id = ?";
        $update_stmt = sqlsrv_query($conn, $update_sql, [$hash, $user_id]);
        if($update_stmt){
            $success = "Password updated successfully!";
        } else {
            $error = "Password update failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password — Patient Portal</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="patient-container">
    <h2>Change Password</h2>

    <?php if($error !== ""): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if($success !== ""): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" name="change" class="update-btn">Change Password</button>
    </form>

    <div class="links">
        <a href="patient_info.php">Back to Profile</a> | 
        <a href="logout.php">Logout</a>
    </div>
</div>
</body>
</html>