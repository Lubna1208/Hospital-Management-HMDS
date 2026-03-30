<?php
session_start();
include "db.php";
require_once "pdf_helpers.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "patient") {
    header("Location: login.php");
    exit;
}

$appointmentId = (int)($_GET["appointment_id"] ?? 0);
if ($appointmentId <= 0) {
    header("Location: appointments.php");
    exit;
}

$patientId = (int)$_SESSION["user_id"];

$sql = "
    SELECT
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.serial_no,
        a.status,
        a.consultation_fee,
        d.full_name AS doctor_name,
        d.phone AS doctor_phone,
        d.room_no,
        dep.department_name,
        p.PatientName,
        p.DateOfBirth,
        p.Gender,
        p.Phone AS patient_phone,
        p.Address AS patient_address
    FROM dbo.appointments a
    INNER JOIN dbo.doctors d ON d.id = a.doctor_id
    LEFT JOIN dbo.departments dep ON dep.department_id = d.department_id
    INNER JOIN dbo.patients p ON p.id = a.patient_id
    WHERE a.id = ? AND a.patient_id = ?
";

$stmt = sqlsrv_query($conn, $sql, [$appointmentId, $patientId]);
$appointment = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

if (!$appointment) {
    header("Location: appointments.php");
    exit;
}

$historyStmt = sqlsrv_query(
    $conn,
    "INSERT INTO dbo.appointment_billing_history (appointment_id, user_id, billed_amount) VALUES (?, ?, ?)",
    [$appointmentId, $patientId, (float)($appointment["consultation_fee"] ?? 0)]
);

if ($historyStmt === false) {
    die("Failed to save billing history.");
}

$appointmentDate = $appointment["appointment_date"] instanceof DateTime
    ? $appointment["appointment_date"]->format("Y-m-d")
    : "";
$appointmentTime = $appointment["appointment_time"] instanceof DateTime
    ? $appointment["appointment_time"]->format("H:i")
    : "";
$dob = $appointment["DateOfBirth"] instanceof DateTime
    ? $appointment["DateOfBirth"]->format("Y-m-d")
    : "N/A";

$filename = "appointment-bill-" . $appointmentId . ".pdf";
$lines = [
    "Bill Date: " . date("Y-m-d H:i"),
    "Appointment ID: " . $appointmentId,
    "",
    "Patient Information",
    "Patient Name: " . ($appointment["PatientName"] ?? ""),
    "Date of Birth: " . $dob,
    "Gender: " . ($appointment["Gender"] ?? "N/A"),
    "Phone Number: " . ($appointment["patient_phone"] ?? "N/A"),
    "Address: " . ($appointment["patient_address"] ?? "N/A"),
    "",
    "Doctor Information",
    "Doctor Name: " . ($appointment["doctor_name"] ?? ""),
    "Department: " . ($appointment["department_name"] ?? "N/A"),
    "Phone Number: " . ($appointment["doctor_phone"] ?? "N/A"),
    "Room No: " . ($appointment["room_no"] ?? "N/A"),
    "",
    "Appointment Information",
    "Appointment Date: " . $appointmentDate,
    "Appointment Time: " . $appointmentTime,
    "Serial No: " . (int)($appointment["serial_no"] ?? 0),
    "Status: " . ($appointment["status"] ?? "Pending"),
    "",
    "Billing Information",
    "Consultation Fee: Tk " . number_format((float)($appointment["consultation_fee"] ?? 0), 2),
    "Total Amount: Tk " . number_format((float)($appointment["consultation_fee"] ?? 0), 2),
];

pdf_output_invoice($filename, "HMDS Appointment Bill", $lines);
?>
