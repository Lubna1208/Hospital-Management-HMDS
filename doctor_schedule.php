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

if(isset($_POST['add_schedule'])){
    $day = (int)$_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $max = (int)$_POST['max_patients'];

    $sql = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time, max_patients)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = sqlsrv_query($conn, $sql, [$doctor_id, $day, $start, $end, $max]);

    if($stmt){
        $msg = "Schedule added!";
    } else {
        $error = "Error adding schedule";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Doctor Schedule</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>

<div class="container">

<a class="btn" href="doctor_dashboard.php">⬅ Back</a>

<h2>Add Weekly Schedule</h2>

<form method="post">

<select name="day_of_week" required>
<option value="">Select Day</option>
<option value="1">Monday</option>
<option value="2">Tuesday</option>
<option value="3">Wednesday</option>
<option value="4">Thursday</option>
<option value="5">Friday</option>
<option value="6">Saturday</option>
<option value="7">Sunday</option>
</select>

<input type="time" name="start_time" required>
<input type="time" name="end_time" required>
<input type="number" name="max_patients" placeholder="Max Patients" required>

<button class="btn" name="add_schedule">Save</button>
</form>

<?php if($msg) echo "<p style='color:green;'>$msg</p>"; ?>
<?php if($error) echo "<p style='color:red;'>$error</p>"; ?>

</div>
</body>
</html>