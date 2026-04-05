<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$sql = "
SELECT pt.patient_test_id, t.test_name, t.price, pt.status, pt.applied_date
FROM patient_test pt
JOIN tests t ON pt.test_id = t.test_id
WHERE pt.patient_id = ?
ORDER BY pt.applied_date DESC
";

$stmt = sqlsrv_query($conn, $sql, [$user_id]);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$tests = [];
$hasPendingTests = false;
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $tests[] = $row;
    if (strtolower($row['status']) === 'pending') {
        $hasPendingTests = true;
    }
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

    <?php if (isset($_GET['deleted'])): ?>
        <p style="color:red;">❌ Test cancelled successfully.</p>
    <?php endif; ?>

    <div style="margin-top:20px;">
        <?php if (isset($_GET['paid'])): ?>
            <p style="color:green;">✔ Payment marked as received for all selected tests.</p>
        <?php endif; ?>

        <?php if (isset($_GET['paid_error'])): ?>
            <p style="color:red;">⚠ Unable to update payment status. Please try again.</p>
        <?php endif; ?>

        <?php if ($hasPendingTests): ?>
            <form method="post" action="mark_tests_paid.php" style="display:inline-block; margin-bottom:16px;">
                <button class="btn" type="submit">Mark Selected Tests as Paid</button>
            </form>
        <?php endif; ?>

        <a class="btn" target="_blank" href="test_report.php?all=1" style="margin-bottom:16px; display:inline-block;">
            Generate One Receipt for All Tests
        </a>

        <?php foreach ($tests as $row): ?>

            <?php
            $status = $row['status'];
            $color = (strtolower($status) === 'pending') ? 'orange' : 'green';
            ?>

            <div class="feature-card" style="margin-bottom:16px;">
                
                <h3 style="margin-bottom:10px;">
                    <?php echo htmlspecialchars($row['test_name']); ?>
                </h3>

                <p><strong>Price:</strong> <?php echo $row['price']; ?> BDT</p>

                <p><strong>Status:</strong> 
                    <span style="color:<?php echo $color; ?>;">
                        <?php echo (strtolower($status) === 'paid' ? '✔ ' : ''); ?><?php echo $status; ?>
                    </span>
                </p>

                <p><strong>Date:</strong>
                    <?php echo $row['applied_date']->format('Y-m-d'); ?>
                </p>

                <!-- ✅ Cancel only if Pending -->
                <?php if (strtolower($status) === 'pending'): ?>
                    <a class="btn small-btn" style="background:red; color:white;"
                       href="delete_test.php?id=<?php echo $row['patient_test_id']; ?>"
                       onclick="return confirm('Are you sure you want to cancel this test?');">
                       Cancel Test
                    </a>
                <?php else: ?>
                    <p style="color:gray; font-size:14px;">Cannot cancel completed test</p>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>
    </div>

</div>

</body>
</html>