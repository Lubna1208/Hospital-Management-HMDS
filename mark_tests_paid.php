<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

$sql = "
UPDATE patient_test
SET status = 'Paid'
WHERE patient_id = ? AND LOWER(status) = 'pending'
";

$stmt = sqlsrv_query($conn, $sql, [$userId]);

if ($stmt === false) {
    header("Location: my_tests.php?paid_error=1");
    exit;
}

header("Location: my_tests.php?paid=1");
exit;
