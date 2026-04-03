<?php
session_start();
include "db.php";
require_once "pdf_helpers.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$patientTestId = (int)($_GET["id"] ?? 0);
if ($patientTestId <= 0) {
    header("Location: my_tests.php");
    exit;
}

$userId = $_SESSION["user_id"];

$sql = "
SELECT 
    pt.patient_test_id, 
    pt.applied_date, 
    pt.status, 
    v.vaccine_name AS test_name, 
    v.price,
    p.PatientName,
    p.Phone
FROM patient_test pt
JOIN vaccines v ON pt.test_id = v.vaccine_id
JOIN patients p ON pt.patient_id = p.id
WHERE pt.patient_test_id = ? AND pt.patient_id = ?
";

$stmt = sqlsrv_query($conn, $sql, [$patientTestId, $userId]);
$row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

if (!$row) {
    header("Location: my_tests.php");
    exit;
}

$appliedDate = $row["applied_date"] instanceof DateTime 
    ? $row["applied_date"]->format("Y-m-d") 
    : "N/A";

$filename = "test-receipt-" . $patientTestId . ".pdf";

$lines = [
    "Date: " . date("Y-m-d H:i"),
    "Receipt ID: " . $patientTestId,
    "",
    "Patient Information",
    "Name: " . ($row["PatientName"] ?? "N/A"),
    "Phone: " . ($row["Phone"] ?? "N/A"),
    "",
    "Test Information",
    "Test Name: " . ($row["test_name"] ?? "N/A"),
    "Applied Date: " . $appliedDate,
    "Status: " . ($row["status"] ?? "N/A"),
    "",
    "Total Billed Amount: BDT " . number_format((float)($row["price"] ?? 0), 2)
];

// Determine if we should show the "PAID" seal based on user request (always true or based on status)
$showPaidSeal = true;

// Open in new tab (inline), and show PAID seal
pdf_output_invoice($filename, "HMDS Test Receipt", $lines, true, $showPaidSeal);
