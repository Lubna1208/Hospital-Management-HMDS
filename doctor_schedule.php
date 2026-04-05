<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "doctor") {
    header("Location: login.php");
    exit;
}

$doctor_id = $_SESSION["user_id"];
$msg = "";
$error = "";

if (isset($_POST["add_schedule"])) {
    $day = (int)$_POST["day_of_week"];
    $start = $_POST["start_time"];
    $end = $_POST["end_time"];
    $max = (int)$_POST["max_patients"];

    $existingStmt = sqlsrv_query(
        $conn,
        "SELECT TOP 1 id FROM doctor_schedule WHERE doctor_id = ? AND day_of_week = ? ORDER BY id DESC",
        [$doctor_id, $day]
    );
    $existingRow = $existingStmt ? sqlsrv_fetch_array($existingStmt, SQLSRV_FETCH_ASSOC) : null;

    if ($existingRow) {
        $sql = "UPDATE doctor_schedule
                SET start_time = ?, end_time = ?, max_patients = ?
                WHERE id = ?";
        $stmt = sqlsrv_query($conn, $sql, [$start, $end, $max, (int)$existingRow["id"]]);
    } else {
        $sql = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time, max_patients)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = sqlsrv_query($conn, $sql, [$doctor_id, $day, $start, $end, $max]);
    }

    if ($stmt) {
        $msg = $existingRow ? "Schedule updated for the selected day." : "Schedule added!";
    } else {
        $error = "Error saving schedule";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Schedule</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>

<div class="container">

<nav class="nav">
    <div class="logo">Hospital</div>
    <div class="actions">
        <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Doctor"); ?></span>
        <a class="btn" href="doctor_dashboard.php">Back</a>
        <a class="btn" href="logout.php">Logout</a>
    </div>
</nav>

<div class="layout-grid">
    <div class="sidebar-card">
        <h3 style="margin-bottom:16px;">Doctor</h3>
        <ul class="sidebar-list">
            <li><a class="btn small-btn" href="doctor_dashboard.php">Dashboard</a></li>
            <li><a class="btn small-btn" href="doctor_profile.php">My Profile</a></li>
            <li><a class="btn small-btn" href="doctor_schedule.php">Add Schedule</a></li>
        </ul>
    </div>

    <div style="flex:1; min-width:0;">
        <h2 class="section-title">Add Weekly Schedule</h2>

        <?php if ($msg): ?><p class="success"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

        <div class="feature-card form-card">
            <form method="post" style="margin:0;">
                <label class="field-label">Day of Week</label>
                <select class="form-field" name="day_of_week" required>
                    <option value="">Select Day</option>
                    <option value="1">Monday</option>
                    <option value="2">Tuesday</option>
                    <option value="3">Wednesday</option>
                    <option value="4">Thursday</option>
                    <option value="5">Friday</option>
                    <option value="6">Saturday</option>
                    <option value="7">Sunday</option>
                </select>

                <div class="form-grid">
                    <div>
                        <label class="field-label">Start Time</label>
                        <input class="form-field" type="time" name="start_time" required>
                    </div>
                    <div>
                        <label class="field-label">End Time</label>
                        <input class="form-field" type="time" name="end_time" required>
                    </div>
                </div>

                <label class="field-label">Max Patients</label>
                <input class="form-field" type="number" name="max_patients" placeholder="Max Patients" required>

                <div class="inline-actions">
                    <button class="btn" type="submit" name="add_schedule">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
</body>
</html>
