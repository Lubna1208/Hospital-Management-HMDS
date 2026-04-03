<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$sql = "
SELECT pt.patient_test_id, v.vaccine_name, v.price, pt.status, pt.applied_date
FROM patient_test pt
JOIN vaccines v ON pt.test_id = v.vaccine_id
WHERE pt.patient_id = ?
ORDER BY pt.applied_date DESC
";

$stmt = sqlsrv_query($conn, $sql, [$user_id]);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Tests</title>
    <link rel="stylesheet" href="assets/patient.css">
</head>
<body>

<div class="container">

    <nav class="nav">
        <div class="logo">🏥</div>
        <div class="actions">
            <a class="btn" href="patient_home.php">Back</a>
        </div>
    </nav>

    <h2 style="margin-top:20px;">My Tests</h2>

    <!-- Messages -->
    <?php if (isset($_GET['success'])): ?>
        <p style="color:green;">✔ Test applied successfully!</p>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <p style="color:red;">⚠ You already selected this test.</p>
    <?php endif; ?>

    <div style="margin-top:20px;">
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>

            <?php
            $status = $row['status'];
            $color = ($status == 'Pending') ? 'orange' : 'green';
            ?>

            <div class="feature-card" style="margin-bottom:16px;">
                
                <h3 style="margin-bottom:10px;">
                    <?php echo htmlspecialchars($row['vaccine_name']); ?>
                </h3>

                <p><strong>Price:</strong> <?php echo $row['price']; ?> BDT</p>

                <p><strong>Status:</strong> 
                    <span style="color:<?php echo $color; ?>;">
                        <?php echo $status; ?>
                    </span>
                </p>

                <p><strong>Date:</strong>
                    <?php echo $row['applied_date']->format('Y-m-d'); ?>
                </p>

                <!-- Optional button -->
                <a class="btn small-btn" target="_blank"
                   href="test_report.php?id=<?php echo $row['patient_test_id']; ?>">
                   Generate Receipt (PDF)
                </a>

            </div>

        <?php endwhile; ?>
    </div>

</div>

</body>
</html>