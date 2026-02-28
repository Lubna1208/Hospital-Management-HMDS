<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "patient") !== "patient") {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$success = '';
$error = '';

// Get patient info
$stmt = sqlsrv_query($conn, "SELECT * FROM patients WHERE id = ?", [$user_id]);
if ($stmt === false) die("SQL Error (patients): " . print_r(sqlsrv_errors(), true));
$patient = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = $_POST['full_name'];
    $dob = $_POST['dob'] ?: null;
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $marital_status = $_POST['marital_status'];

    if ($patient) {
        $sqlUpdate = "UPDATE patients SET PatientName=?, DateOfBirth=?, Gender=?, Phone=?, Address=?, MaritalStatus=?, updated_at=GETDATE() WHERE id=?";
        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, [$full_name, $dob, $gender, $phone, $address, $marital_status, $user_id]);
        if ($stmtUpdate === false) $error = "Update failed: " . print_r(sqlsrv_errors(), true);
        else $success = "Patient info updated successfully.";
    } else {
        $sqlInsert = "INSERT INTO patients (id, PatientName, DateOfBirth, Gender, Phone, Address, MaritalStatus) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = sqlsrv_query($conn, $sqlInsert, [$user_id, $full_name, $dob, $gender, $phone, $address, $marital_status]);
        if ($stmtInsert === false) $error = "Insert failed: " . print_r(sqlsrv_errors(), true);
        else $success = "Patient info added successfully.";
    }

    // Refresh patient info
    $stmt = sqlsrv_query($conn, "SELECT * FROM patients WHERE id = ?", [$user_id]);
    $patient = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Info — PISD</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">
    <nav class="nav">
        <div class="logo">🏥</div>
        <div class="actions">
            <span class="user-name"><?php echo htmlspecialchars($patient['PatientName'] ?? 'Patient'); ?></span>
            <a class="btn" href="patient_home.php">Back</a>
            <a class="btn" href="logout.php">Logout</a>
        </div>
    </nav>

    <h2 class="section-title"><?php echo $patient ? "Update" : "Add"; ?> Patient Info</h2>

    <?php if($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if($success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="post" style="max-width:600px;">
        <label>Full Name</label>
        <input class="form-field" type="text" name="full_name" value="<?php echo htmlspecialchars($patient['PatientName'] ?? ''); ?>" required>

        <label>Date of Birth</label>
        <input class="form-field" type="date" name="dob" value="<?php 
            if (!empty($patient['DateOfBirth']) && $patient['DateOfBirth'] instanceof DateTime) {
                echo $patient['DateOfBirth']->format('Y-m-d');
            } 
        ?>">

        <label>Gender</label>
        <select class="form-field" name="gender">
            <option value="">Select</option>
            <option value="Male" <?php if(isset($patient['Gender']) && $patient['Gender']=="Male") echo "selected"; ?>>Male</option>
            <option value="Female" <?php if(isset($patient['Gender']) && $patient['Gender']=="Female") echo "selected"; ?>>Female</option>
            <option value="Other" <?php if(isset($patient['Gender']) && $patient['Gender']=="Other") echo "selected"; ?>>Other</option>
        </select>

        <label>Phone</label>
        <input class="form-field" type="text" name="phone" value="<?php echo htmlspecialchars($patient['Phone'] ?? ''); ?>">

        <label>Address</label>
        <input class="form-field" type="text" name="address" value="<?php echo htmlspecialchars($patient['Address'] ?? ''); ?>">

        <label>Marital Status</label>
        <select class="form-field" name="marital_status">
            <option value="">Select</option>
            <option value="Single" <?php if(isset($patient['MaritalStatus']) && $patient['MaritalStatus']=="Single") echo "selected"; ?>>Single</option>
            <option value="Married" <?php if(isset($patient['MaritalStatus']) && $patient['MaritalStatus']=="Married") echo "selected"; ?>>Married</option>
            <option value="Other" <?php if(isset($patient['MaritalStatus']) && $patient['MaritalStatus']=="Other") echo "selected"; ?>>Other</option>
        </select>

        <label>Change Password (coming soon)</label>
        <input class="form-field" type="password" placeholder="Not functional yet" disabled>

        <button class="btn" type="submit"><?php echo $patient ? "Update" : "Add"; ?> Info</button>
    </form>
</div>
</body>
</html>