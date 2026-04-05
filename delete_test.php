<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["id"])) {
    die("Invalid request");
}

$patient_test_id = $_GET["id"];
$user_id = $_SESSION["user_id"];

// सुरक्षा: ensure user deletes only their own test
$sql = "DELETE FROM patient_test WHERE patient_test_id = ? AND patient_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$patient_test_id, $user_id]);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// redirect back
header("Location: my_tests.php?deleted=1");
exit;
?>