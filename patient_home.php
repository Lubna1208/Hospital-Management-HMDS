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

// Get patient info
$stmtPatient = sqlsrv_query($conn, "SELECT * FROM patients WHERE id = ?", [$user_id]);
if ($stmtPatient === false) {
    die("SQL Error (patients): " . print_r(sqlsrv_errors(), true));
}
$patient = sqlsrv_fetch_array($stmtPatient, SQLSRV_FETCH_ASSOC);

// Get user info
$stmtUser = sqlsrv_query($conn, "SELECT name, email FROM users WHERE id = ?", [$user_id]);
if ($stmtUser === false) {
    die("SQL Error (users): " . print_r(sqlsrv_errors(), true));
}
$user = sqlsrv_fetch_array($stmtUser, SQLSRV_FETCH_ASSOC);

if (!$user) die("User not found.");

$user_email = $user['email'];
$user_name = $user['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Home — PISD</title>
    <link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

    <!-- Navigation Bar -->
    <nav class="nav">
        <div class="logo">🏥</div>
        <div class="actions">
            <span class="user-name"><?php echo htmlspecialchars($patient['PatientName'] ?? $user_name); ?></span>
            <a class="btn" href="logout.php">Logout</a>
        </div>
    </nav>

    <div style="display:flex; gap:32px; margin-top:32px;">

        <!-- Sidebar -->
        <div style="width:250px; background:white; padding:24px; border-radius:16px; box-shadow:0 8px 24px rgba(10,44,62,.08);">
            <h3 style="margin-bottom:16px;">Patient Dashboard</h3>
            <ul style="list-style:none; padding:0;">
                <li style="margin-bottom:12px;">
                    <a class="btn small-btn" href="patient_info.php">
                        <?php echo $patient ? "Update Patient Info" : "Add Patient Info"; ?>
                    </a>
                </li>
                <li style="margin-bottom:12px;">
                    <a class="btn small-btn" href="appointments.php">Appointments</a>
                </li>
                <li style="margin-bottom:12px;">
                    <a class="btn small-btn" href="search_doctors.php">Search Doctors</a>
                </li>
                <li style="margin-bottom:12px;">
                    <a class="btn small-btn" href="vaccines.php">Vaccines</a>
                </li>

                <!-- ✅ NEW TEST BUTTON -->
                <li style="margin-bottom:12px;">
                    <a class="btn small-btn" href="tests.php">Tests</a>
                </li>

                <!-- OPTIONAL -->
                <li style="margin-bottom:12px;">
                    <a class="btn small-btn" href="my_tests.php">My Tests</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div style="flex:1;">
            <h2 class="section-title">Welcome, <?php echo htmlspecialchars($patient['PatientName'] ?? $user_name); ?></h2>

            <?php if ($patient): ?>
                <p>Your patient information is available. You can update it anytime using the sidebar.</p>
                <div class="feature-card" style="margin-top:24px;">
                    <h3>Patient Info Summary</h3>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($patient['PatientName']); ?></p>
                    <p><strong>Date of Birth:</strong> 
                        <?php 
                        if (!empty($patient['DateOfBirth']) && $patient['DateOfBirth'] instanceof DateTime) {
                            echo $patient['DateOfBirth']->format('Y-m-d');
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['Gender'] ?? 'N/A'); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['Phone'] ?? 'N/A'); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['Address'] ?? 'N/A'); ?></p>
                    <p><strong>Marital Status:</strong> <?php echo htmlspecialchars($patient['MaritalStatus'] ?? 'N/A'); ?></p>
                </div>
            <?php else: ?>
                <p>You have not added your patient information yet. Click the sidebar button to add details.</p>
            <?php endif; ?>
        </div>

    </div>
</div>
</body>
</html>