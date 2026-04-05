<?php
session_start();
include "db.php";
require_once "pdf_helpers.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$receiptId = (int)($_GET["id"] ?? 0);
$all = isset($_GET["all"]);

$userId = $_SESSION["user_id"];

$testLines = [];
$total = 0;
$paymentStatus = "Pending";
$receiptTitle = "HMDS Test Receipt";

if ($all) {
    $sql = "
SELECT t.test_name, t.price, pt.status, pt.applied_date
FROM patient_test pt
JOIN tests t ON pt.test_id = t.test_id
WHERE pt.patient_id = ?
";

    $stmt = sqlsrv_query($conn, $sql, [$userId]);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $allPaid = true;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $testLines[] = "- " . $row["test_name"] . " (BDT " . $row["price"] . ")";
        $total += $row["price"];
        if (strtolower($row["status"]) === "pending") {
            $allPaid = false;
        }
    }

    if (empty($testLines)) {
        header("Location: my_tests.php");
        exit;
    }

    $paymentStatus = $allPaid ? "Paid" : "Pending";
    $receiptTitle = "HMDS Combined Test Receipt";
} else {
    if ($receiptId <= 0) {
        header("Location: my_tests.php");
        exit;
    }

    // ✅ Get receipt info
    $sqlReceipt = "
SELECT * FROM test_receipts 
WHERE receipt_id = ? AND patient_id = ?
";

    $stmtReceipt = sqlsrv_query($conn, $sqlReceipt, [$receiptId, $userId]);
    $receipt = sqlsrv_fetch_array($stmtReceipt, SQLSRV_FETCH_ASSOC);

    if (!$receipt) {
        header("Location: my_tests.php");
        exit;
    }

    $paymentStatus = $receipt["payment_status"];

    // ✅ Get ALL tests under this receipt
    $sql = "
SELECT t.test_name, t.price, pt.applied_date
FROM patient_test pt
JOIN tests t ON pt.test_id = t.test_id
WHERE pt.receipt_id = ?
";

    $stmt = sqlsrv_query($conn, $sql, [$receiptId]);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $testLines[] = "- " . $row["test_name"] . " (BDT " . $row["price"] . ")";
        $total += $row["price"];
    }
}

// ✅ Get patient info
$sqlPatient = "SELECT PatientName, Phone FROM patients WHERE id = ?";
$stmtPatient = sqlsrv_query($conn, $sqlPatient, [$userId]);
$patient = sqlsrv_fetch_array($stmtPatient, SQLSRV_FETCH_ASSOC);

// ✅ Build PDF content
$lines = [
    "Date: " . date("Y-m-d H:i"),
    "Receipt ID: " . ($all ? "ALL" : $receiptId),
    "",
    "Patient Information",
    "Name: " . ($patient["PatientName"] ?? "N/A"),
    "Phone: " . ($patient["Phone"] ?? "N/A"),
    "",
    "Tests:"
];

// Add test list
foreach ($testLines as $line) {
    $lines[] = $line;
}

$lines[] = "";
$lines[] = "Total Amount: BDT " . number_format($total, 2);

// ✅ Show PAID seal only if paid
$showPaidSeal = (strtolower($paymentStatus) === "paid");

$filename = $all ? "test-receipt-all.pdf" : "test-receipt-" . $receiptId . ".pdf";

// Generate PDF
pdf_output_invoice($filename, $receiptTitle, $lines, $showPaidSeal);
?>