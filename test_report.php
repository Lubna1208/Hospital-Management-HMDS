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
$finalReceiptId = $receiptId;

if ($all) {
    $sql = "
SELECT pt.patient_test_id, t.test_name, t.price, pt.status, pt.applied_date
FROM patient_test pt
JOIN tests t ON pt.test_id = t.test_id
WHERE pt.patient_id = ?
ORDER BY pt.applied_date DESC
";

    $stmt = sqlsrv_query($conn, $sql, [$userId]);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $allPaid = true;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $testLines[] = "- " . $row["test_name"] . " (BDT " . $row["price"] . ")";
        $total += $row["price"];

        if (strtolower($row["status"]) !== "paid") {
            $allPaid = false;
        }
    }

    if (empty($testLines)) {
        header("Location: my_tests.php");
        exit;
    }

    if (!$allPaid) {
        header("Location: my_tests.php?receipt_error=unpaid");
        exit;
    }

    $paymentStatus = "Paid";
    $receiptTitle = "HMDS Combined Test Receipt";

    if (!sqlsrv_begin_transaction($conn)) {
        die(print_r(sqlsrv_errors(), true));
    }

    $insertReceiptSql = "
INSERT INTO test_receipts (patient_id, total_amount, payment_status)
OUTPUT INSERTED.receipt_id
VALUES (?, ?, ?)
";
    $insertReceiptStmt = sqlsrv_query($conn, $insertReceiptSql, [$userId, $total, $paymentStatus]);

    if ($insertReceiptStmt === false) {
        sqlsrv_rollback($conn);
        die(print_r(sqlsrv_errors(), true));
    }

    $receiptRow = sqlsrv_fetch_array($insertReceiptStmt, SQLSRV_FETCH_ASSOC);
    $finalReceiptId = (int)($receiptRow["receipt_id"] ?? 0);

    if ($finalReceiptId <= 0) {
        sqlsrv_rollback($conn);
        die("Unable to create test receipt.");
    }

    $deleteStmt = sqlsrv_query(
        $conn,
        "DELETE FROM patient_test WHERE patient_id = ? AND LOWER(status) = 'paid'",
        [$userId]
    );

    if ($deleteStmt === false) {
        sqlsrv_rollback($conn);
        die(print_r(sqlsrv_errors(), true));
    }

    if (!sqlsrv_commit($conn)) {
        sqlsrv_rollback($conn);
        die(print_r(sqlsrv_errors(), true));
    }
} else {
    if ($receiptId <= 0) {
        header("Location: my_tests.php");
        exit;
    }

    $sqlReceipt = "
SELECT *
FROM test_receipts
WHERE receipt_id = ? AND patient_id = ?
";

    $stmtReceipt = sqlsrv_query($conn, $sqlReceipt, [$receiptId, $userId]);
    $receipt = sqlsrv_fetch_array($stmtReceipt, SQLSRV_FETCH_ASSOC);

    if (!$receipt) {
        header("Location: my_tests.php");
        exit;
    }

    $paymentStatus = $receipt["payment_status"];
    $total = (float)$receipt["total_amount"];
}

$sqlPatient = "SELECT PatientName, Phone FROM patients WHERE id = ?";
$stmtPatient = sqlsrv_query($conn, $sqlPatient, [$userId]);
$patient = sqlsrv_fetch_array($stmtPatient, SQLSRV_FETCH_ASSOC);

$lines = [
    "Date: " . date("Y-m-d H:i"),
    "Receipt ID: " . ($all ? $finalReceiptId : $receiptId),
    "",
    "Patient Information",
    "Name: " . ($patient["PatientName"] ?? "N/A"),
    "Phone: " . ($patient["Phone"] ?? "N/A"),
    "",
    "Tests:"
];

foreach ($testLines as $line) {
    $lines[] = $line;
}

$lines[] = "";
$lines[] = "Total Amount: BDT " . number_format($total, 2);

$showPaidSeal = (strtolower($paymentStatus) === "paid");
$filename = $all ? "test-receipt-" . $finalReceiptId . ".pdf" : "test-receipt-" . $receiptId . ".pdf";

pdf_output_invoice($filename, $receiptTitle, $lines, $showPaidSeal);
?>
