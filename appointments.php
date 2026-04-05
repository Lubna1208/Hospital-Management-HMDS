<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "patient") {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$success = '';
$error = '';

if (isset($_SESSION['appointment_success'])) {
    $success = $_SESSION['appointment_success'];
    unset($_SESSION['appointment_success']);
}
if (isset($_SESSION['appointment_error'])) {
    $error = $_SESSION['appointment_error'];
    unset($_SESSION['appointment_error']);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Appointments</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>

<div class="container">

<nav class="nav">
<div class="logo">🏥</div>
<div class="actions">
<a class="btn" href="patient_home.php">Back</a>
<a class="btn" href="logout.php">Logout</a>
</div>
</nav>

<h2>Book Appointment</h2>

<?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
<?php if($success) echo "<p style='color:green;'>$success</p>"; ?>

<form method="POST" action="book_appointment.php">

<label>Doctor:</label>
<select name="doctor_id" required>
<?php
$stmt = sqlsrv_query($conn, "SELECT id, full_name FROM doctors");
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
echo "<option value='{$row['id']}'>{$row['full_name']}</option>";
}
?>
</select>

<label>Date:</label>
<input type="date" name="appointment_date" required>

<button class="btn" name="submit">Book</button>

</form>

<h2>Your Appointments</h2>

<table border="1">
<tr>
<th>Doctor</th>
<th>Date</th>
<th>Time</th>
<th>Serial</th>
<th>Fee</th>
<th>Bill</th>
</tr>

<?php
$sql = "
WITH latest_schedule AS (
    SELECT
        id,
        doctor_id,
        day_of_week,
        start_time,
        end_time,
        ROW_NUMBER() OVER (
            PARTITION BY doctor_id, day_of_week
            ORDER BY id DESC
        ) AS rn
    FROM doctor_schedule
)
SELECT a.*, d.full_name, s.start_time, s.end_time
FROM appointments a
JOIN doctors d ON a.doctor_id=d.id
JOIN latest_schedule s
ON s.doctor_id=a.doctor_id
AND s.day_of_week = ((DATEDIFF(DAY, '19000101', a.appointment_date) % 7) + 1)
AND s.rn = 1
WHERE a.patient_id=?";

$stmt = sqlsrv_query($conn,$sql,[$user_id]);

while($row=sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){

$date = $row['appointment_date']->format('Y-m-d');
$start = $row['start_time']->format('H:i');
$end = $row['end_time']->format('H:i');

echo "<tr>
<td>{$row['full_name']}</td>
<td>$date</td>
<td>($start - $end)</td>
<td>{$row['serial_no']}</td>
<td>Tk " . number_format((float)($row['consultation_fee'] ?? 0), 2) . "</td>
<td><a class='btn small-btn' href='appointment_bill.php?appointment_id=" . (int)$row['id'] . "'>Download Bill</a></td>
</tr>";
}
?>

</table>

</div>
</body>
</html>
