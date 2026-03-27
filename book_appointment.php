<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "patient") !== "patient") {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION["user_id"];

if(isset($_POST['submit'])){
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];

    // 1️⃣ Ensure patient exists
    $stmtPatient = sqlsrv_query($conn, "SELECT id FROM patients WHERE id = ?", [$patient_id]);
    $patient = sqlsrv_fetch_array($stmtPatient, SQLSRV_FETCH_ASSOC);

    if(!$patient){
        // Auto-create patient with minimal info (name from session)
        $patientName = $_SESSION['user_name'] ?? 'Unknown';
        $sqlInsertPatient = "INSERT INTO patients (id, PatientName) VALUES (?, ?)";
        $stmtInsertPatient = sqlsrv_query($conn, $sqlInsertPatient, [$patient_id, $patientName]);

        if(!$stmtInsertPatient){
            die("Error creating patient: " . print_r(sqlsrv_errors(), true));
        }
    }

    // 2️⃣ Calculate serial number for doctor per day
    $sqlCount = "SELECT COUNT(*) AS cnt FROM appointments WHERE doctor_id=? AND appointment_date=?";
    $stmtCount = sqlsrv_query($conn, $sqlCount, [$doctor_id, $appointment_date]);
    $rowCount = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
    $serial_no = ($rowCount['cnt'] ?? 0) + 1;

    // 3️⃣ Insert appointment
    $sqlInsert = "INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, serial_no, status)
                  VALUES (?, ?, ?, ?, ?, 'Pending')";
    $paramsInsert = [$doctor_id, $patient_id, $appointment_date, $appointment_time, $serial_no];
    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);

    if($stmtInsert){
        // Redirect back with success
        $_SESSION['appointment_success'] = "Appointment booked successfully.";
        header("Location: appointments.php");
        exit;
    } else {
        die("Error booking appointment: " . print_r(sqlsrv_errors(), true));
    }
}
?>
