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

// Optional: show messages from book_appointment.php
if (isset($_SESSION['appointment_success'])) {
    $success = $_SESSION['appointment_success'];
    unset($_SESSION['appointment_success']);
}
if (isset($_SESSION['appointment_error'])) {
    $error = $_SESSION['appointment_error'];
    unset($_SESSION['appointment_error']);
}

// Fetch patient info for header display
$stmtPatient = sqlsrv_query($conn, "SELECT * FROM patients WHERE id = ?", [$user_id]);
if ($stmtPatient === false) die("SQL Error (patients): " . print_r(sqlsrv_errors(), true));
$patient = sqlsrv_fetch_array($stmtPatient, SQLSRV_FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointments — PISD</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

    <!-- Header -->
    <nav class="nav">
        <div class="logo">🏥</div>
        <div class="actions">
            <span class="user-name">Appointment</span>
            <a class="btn" href="patient_home.php">Back</a>
            <a class="btn" href="logout.php">Logout</a>
        </div>
    </nav>

    <h2 class="section-title">Book a New Appointment</h2>

    <?php if($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" action="book_appointment.php" style="max-width:600px;">
        <label for="doctor_id">Select Doctor:</label>
        <select class="form-field" name="doctor_id" required>
            <option value="">Select Doctor</option>
            <?php
            $stmt = sqlsrv_query($conn, "SELECT id, full_name, department FROM doctors ORDER BY full_name");
            if ($stmt === false) die("SQL Error (doctors): " . print_r(sqlsrv_errors(), true));

            $hasDoctors = false;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $hasDoctors = true;

                // Preselect if patient has an appointment with this doctor
                $preselected = '';
                $checkAppt = sqlsrv_query($conn, "SELECT TOP 1 * FROM appointments WHERE patient_id=? AND doctor_id=?", [$user_id, $row['id']]);
                if ($checkAppt && sqlsrv_fetch_array($checkAppt, SQLSRV_FETCH_ASSOC)) {
                    $preselected = 'selected';
                }

                echo "<option value='{$row['id']}' $preselected>".htmlspecialchars($row['full_name'])." (".htmlspecialchars($row['department']).")</option>";
            }
            if (!$hasDoctors) {
                echo "<option value=''>No doctors available</option>";
            }
            ?>
        </select>

        <label for="appointment_date">Date:</label>
        <input class="form-field" type="date" name="appointment_date" required>

        <label for="appointment_time">Time:</label>
        <input class="form-field" type="time" name="appointment_time" required>

        <button class="btn" type="submit" name="submit">Book Appointment</button>
    </form>

    <h2 class="section-title">Your Appointments</h2>
    <table border="1" cellpadding="8">
        <tr>
            <th>Doctor</th>
            <th>Date</th>
            <th>Time</th>
            <th>Serial No</th>
            <th>Status</th>
        </tr>
        <?php
        $sql = "SELECT a.*, d.full_name AS doctor_name 
                FROM appointments a 
                JOIN doctors d ON a.doctor_id = d.id 
                WHERE a.patient_id = ? 
                ORDER BY a.appointment_date, a.appointment_time";
        $stmt = sqlsrv_query($conn, $sql, [$user_id]);
        if ($stmt) {
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $date = ($row['appointment_date'] instanceof DateTime) ? $row['appointment_date']->format('Y-m-d') : '';
                $time = ($row['appointment_time'] instanceof DateTime) ? $row['appointment_time']->format('H:i') : '';
                echo "<tr>
                        <td>".htmlspecialchars($row['doctor_name'])."</td>
                        <td>$date</td>
                        <td>$time</td>
                        <td>".htmlspecialchars($row['serial_no'])."</td>
                        <td>".htmlspecialchars($row['status'])."</td>
                      </tr>";
            }
        }
        ?>
    </table>

</div>
</body>
</html>