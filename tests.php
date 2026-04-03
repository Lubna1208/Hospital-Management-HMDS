<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Fetch tests (using vaccines table)
$stmt = sqlsrv_query($conn, "SELECT * FROM vaccines");

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Check if data exists
$hasData = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Tests</title>
    <link rel="stylesheet" href="assets/patient.css">
</head>
<body>

<div class="container">

    <!-- Navbar -->
    <nav class="nav">
        <div class="logo">🏥</div>
        <div class="actions">
            <a class="btn" href="patient_home.php">Back</a>
        </div>
    </nav>

    <h2 class="section-title" style="margin-top:20px;">Available Tests</h2>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:20px; margin-top:20px;">

        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
            <?php $hasData = true; ?>

            <div class="feature-card">
                <h3><?php echo htmlspecialchars($row['vaccine_name']); ?></h3>

                <p><strong>Price:</strong> <?php echo $row['price']; ?> BDT</p>

                <p><strong>Age Range:</strong>
                    <?php echo $row['min_age']; ?> - <?php echo $row['max_age']; ?>
                </p>

                <p><strong>Gender:</strong>
                    <?php echo htmlspecialchars($row['gender_applicable']); ?>
                </p>

                <form method="POST" action="apply_test.php">
                    <input type="hidden" name="test_id" value="<?php echo $row['vaccine_id']; ?>">
                    <button class="btn small-btn" type="submit">Select Test</button>
                </form>
            </div>

        <?php endwhile; ?>

    </div>

    <!-- If no data -->
    <?php if (!$hasData): ?>
        <p style="margin-top:20px; color:#555;">
            No tests available right now. Please add data in database.
        </p>
    <?php endif; ?>

</div>

</body>
</html>