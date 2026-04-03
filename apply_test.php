<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    die("User not logged in");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request");
}

if (!isset($_POST["test_id"])) {
    die("Test ID missing");
}

$patient_id = $_SESSION["user_id"];
$test_id = $_POST["test_id"];

// ✅ Prevent duplicate test
$sqlCheck = "SELECT * FROM patient_test WHERE patient_id = ? AND test_id = ?";
$checkStmt = sqlsrv_query($conn, $sqlCheck, [$patient_id, $test_id]);

if (sqlsrv_fetch_array($checkStmt)) {
    header("Location: my_tests.php?error=duplicate");
    exit;
}

// ✅ Insert test
$sql = "INSERT INTO patient_test (patient_id, test_id) VALUES (?, ?)";
$stmt = sqlsrv_query($conn, $sql, [$patient_id, $test_id]);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Success
header("Location: my_tests.php?success=1");
exit;
?>