<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "patient") {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION["user_id"];

if (isset($_POST["submit"])) {
    if (!ensure_patient_record($conn, $patient_id)) {
        $_SESSION["appointment_error"] = "Patient profile is unavailable. Please try again.";
        header("Location: appointments.php");
        exit;
    }

    $doctor_id = (int)$_POST["doctor_id"];
    $date = $_POST["appointment_date"];
    $day = date("N", strtotime($date));

    $sql_check = "
        SELECT COUNT(*) AS cnt
        FROM appointments
        WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ?
    ";
    $stmt_check = sqlsrv_query($conn, $sql_check, [$patient_id, $doctor_id, $date]);
    $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

    if (($row_check["cnt"] ?? 0) > 0) {
        $_SESSION["appointment_error"] = "You already have an appointment with this doctor on this day.";
        header("Location: appointments.php");
        exit;
    }

    $sql = "
        SELECT TOP 1 *
        FROM doctor_schedule
        WHERE doctor_id = ? AND day_of_week = ?
        ORDER BY id DESC
    ";
    $stmt = sqlsrv_query($conn, $sql, [$doctor_id, $day]);
    $schedule = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$schedule) {
        $_SESSION["appointment_error"] = "Doctor is unavailable on selected day";
        header("Location: appointments.php");
        exit;
    }

    $doctorStmt = sqlsrv_query($conn, "SELECT consultation_fee FROM dbo.doctors WHERE id = ?", [$doctor_id]);
    $doctorRow = $doctorStmt ? sqlsrv_fetch_array($doctorStmt, SQLSRV_FETCH_ASSOC) : null;
    $consultationFee = (float)($doctorRow["consultation_fee"] ?? 0);

    $sql2 = "SELECT COUNT(*) AS cnt FROM appointments WHERE doctor_id = ? AND appointment_date = ?";
    $stmt2 = sqlsrv_query($conn, $sql2, [$doctor_id, $date]);
    $row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);

    $serial = ($row["cnt"] ?? 0) + 1;

    if ($serial > $schedule["max_patients"]) {
        $_SESSION["appointment_error"] = "Maximum patients reached for this day";
        header("Location: appointments.php");
        exit;
    }

    $start = strtotime($schedule["start_time"]->format("H:i:s"));
    $end = strtotime($schedule["end_time"]->format("H:i:s"));
    $interval = ($end - $start) / $schedule["max_patients"];
    $time = date("H:i:s", $start + ($serial - 1) * $interval);

    $sql3 = "INSERT INTO appointments
    (doctor_id, patient_id, appointment_date, appointment_time, serial_no, status, consultation_fee)
    VALUES (?, ?, ?, ?, ?, 'Pending', ?)";

    $params = [$doctor_id, $patient_id, $date, $time, $serial, $consultationFee];
    $ok = sqlsrv_query($conn, $sql3, $params);

    if ($ok) {
        $_SESSION["appointment_success"] = "Booked successfully. Serial No: $serial";
        header("Location: appointments.php");
        exit;
    }

    die(print_r(sqlsrv_errors(), true));
}
?>
