<?php
session_start();
include "db.php";
require_once "vaccine_helpers.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "patient") !== "patient") {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];
$searchName = trim($_GET["search_name"] ?? "");
$genderFilter = vaccine_normalize_gender($_GET["gender_applicable"] ?? "");
$ageFrom = trim($_GET["age_from"] ?? "");
$ageTo = trim($_GET["age_to"] ?? "");
$ageFromUnit = strtolower(trim($_GET["age_from_unit"] ?? "months"));
$ageToUnit = strtolower(trim($_GET["age_to_unit"] ?? "months"));

$allowedGenders = vaccine_allowed_genders();
$allowedAgeUnits = vaccine_allowed_age_units();

$patientStmt = sqlsrv_query($conn, "SELECT PatientName, DateOfBirth, Gender FROM dbo.patients WHERE id = ?", [$userId]);
if ($patientStmt === false) {
    die("SQL Error (patients): " . print_r(sqlsrv_errors(), true));
}
$patient = sqlsrv_fetch_array($patientStmt, SQLSRV_FETCH_ASSOC);
$patientAgeMonths = vaccine_calculate_age_months($patient["DateOfBirth"] ?? null);
$patientGender = vaccine_normalize_gender($patient["Gender"] ?? "");

$sql = "
    SELECT vaccine_id, vaccine_name, price, min_age, max_age, gender_applicable, preparation_notes
    FROM dbo.vaccines
    WHERE 1 = 1
";
$params = [];

if ($searchName !== "") {
    $sql .= " AND vaccine_name LIKE ? ";
    $params[] = "%" . $searchName . "%";
}

$ageFromMonths = in_array($ageFromUnit, $allowedAgeUnits, true) ? vaccine_age_to_months($ageFrom, $ageFromUnit) : null;
$ageToMonths = in_array($ageToUnit, $allowedAgeUnits, true) ? vaccine_age_to_months($ageTo, $ageToUnit) : null;

if ($ageFrom !== "" && $ageFromMonths !== null && ($ageTo === "" || $ageToMonths === null)) {
    $sql .= " AND min_age <= ? AND max_age >= ? ";
    $params[] = $ageFromMonths;
    $params[] = $ageFromMonths;
}

if ($ageTo !== "" && $ageToMonths !== null && ($ageFrom === "" || $ageFromMonths === null)) {
    $sql .= " AND min_age <= ? AND max_age >= ? ";
    $params[] = $ageToMonths;
    $params[] = $ageToMonths;
}

if ($ageFrom !== "" && $ageFromMonths !== null && $ageTo !== "" && $ageToMonths !== null) {
    $sql .= " AND max_age >= ? AND min_age <= ? ";
    $params[] = $ageFromMonths;
    $params[] = $ageToMonths;
}

if ($genderFilter !== "") {
    $sql .= " AND gender_applicable = ? ";
    $params[] = $genderFilter;
}

$sql .= " ORDER BY vaccine_name";
$stmt = sqlsrv_query($conn, $sql, $params);
$vaccines = [];

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $row["_eligibility"] = vaccine_check_eligibility($row, $patientAgeMonths, $patientGender);
        $vaccines[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vaccines - PISD</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

<nav class="nav">
    <div class="logo">Hospital</div>
    <div class="actions">
        <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Patient"); ?></span>
        <a class="btn" href="patient_home.php">Home</a>
        <a class="btn" href="logout.php">Logout</a>
    </div>
</nav>

<div class="layout-grid">
    <div class="sidebar-card">
        <h3 style="margin-bottom:16px;">Filter Vaccines</h3>

        <?php if ($patient && $patientAgeMonths !== null && $patientGender !== ""): ?>
            <p style="margin-bottom:12px;"><strong>Your Age:</strong> <?php echo htmlspecialchars(vaccine_format_age_months($patientAgeMonths)); ?></p>
            <p style="margin-bottom:16px;"><strong>Your Gender:</strong> <?php echo htmlspecialchars($patientGender); ?></p>
        <?php endif; ?>

        <form method="get">
            <label class="field-label">Gender</label>
            <select class="form-field" name="gender_applicable" onchange="this.form.submit()">
                <option value="">All Genders</option>
                <?php foreach ($allowedGenders as $genderOption): ?>
                    <option value="<?php echo htmlspecialchars($genderOption); ?>" <?php echo $genderFilter === $genderOption ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($genderOption); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="field-label" style="margin-top:16px;">Age From</label>
            <div class="age-input-group">
                <input class="form-field" type="number" min="0" name="age_from" value="<?php echo htmlspecialchars($ageFrom); ?>" placeholder="Age from">
                <select class="form-field age-unit-field" name="age_from_unit">
                    <?php foreach ($allowedAgeUnits as $ageUnit): ?>
                        <option value="<?php echo htmlspecialchars($ageUnit); ?>" <?php echo $ageFromUnit === $ageUnit ? "selected" : ""; ?>><?php echo ucfirst($ageUnit); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label class="field-label" style="margin-top:16px;">Age To</label>
            <div class="age-input-group">
                <input class="form-field" type="number" min="0" name="age_to" value="<?php echo htmlspecialchars($ageTo); ?>" placeholder="Age to">
                <select class="form-field age-unit-field" name="age_to_unit">
                    <?php foreach ($allowedAgeUnits as $ageUnit): ?>
                        <option value="<?php echo htmlspecialchars($ageUnit); ?>" <?php echo $ageToUnit === $ageUnit ? "selected" : ""; ?>><?php echo ucfirst($ageUnit); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($searchName): ?>
                <input type="hidden" name="search_name" value="<?php echo htmlspecialchars($searchName); ?>">
            <?php endif; ?>

            <button class="btn" type="submit" style="margin-top:16px;">Apply Filters</button>
        </form>
    </div>

    <div style="flex:1; min-width:0;">
        <h2 class="section-title">Search Vaccines</h2>

        <form method="get" class="inline-actions" style="margin-bottom:24px;">
            <input class="form-field" type="text" name="search_name" placeholder="Search by vaccine name" value="<?php echo htmlspecialchars($searchName); ?>" style="max-width:360px;">

            <?php if ($genderFilter): ?>
                <input type="hidden" name="gender_applicable" value="<?php echo htmlspecialchars($genderFilter); ?>">
            <?php endif; ?>
            <?php if ($ageFrom): ?>
                <input type="hidden" name="age_from" value="<?php echo htmlspecialchars($ageFrom); ?>">
                <input type="hidden" name="age_from_unit" value="<?php echo htmlspecialchars($ageFromUnit); ?>">
            <?php endif; ?>
            <?php if ($ageTo): ?>
                <input type="hidden" name="age_to" value="<?php echo htmlspecialchars($ageTo); ?>">
                <input type="hidden" name="age_to_unit" value="<?php echo htmlspecialchars($ageToUnit); ?>">
            <?php endif; ?>

            <button class="btn" type="submit">Search</button>
            <a class="btn small-btn" href="vaccines.php">Reset</a>
        </form>

        <div class="vaccine-grid">
            <?php if (count($vaccines) === 0): ?>
                <div class="feature-card">
                    <div class="feature-card-body">
                        <p>No vaccines found.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($vaccines as $vaccine): ?>
                <?php $eligibility = $vaccine["_eligibility"]; ?>
                <div class="vaccine-card">
                    <div class="inline-spread">
                        <div>
                            <h3><?php echo htmlspecialchars($vaccine["vaccine_name"] ?? ""); ?></h3>
                            <p class="eligibility-text"><?php echo htmlspecialchars($eligibility["reason"] ?? ""); ?></p>
                        </div>
                        <span class="status-badge <?php echo !empty($eligibility["eligible"]) ? "status-eligible" : "status-not-eligible"; ?>">
                            <?php echo htmlspecialchars($eligibility["status"] ?? "Unknown"); ?>
                        </span>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Price</span>
                            <span class="detail-value">Tk <?php echo number_format((float)($vaccine["price"] ?? 0), 2); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Gender</span>
                            <span class="detail-value"><?php echo htmlspecialchars($vaccine["gender_applicable"] ?? "Both"); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Minimum Age</span>
                            <span class="detail-value"><?php echo htmlspecialchars(vaccine_format_age_months((int)($vaccine["min_age"] ?? 0))); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Maximum Age</span>
                            <span class="detail-value"><?php echo htmlspecialchars(vaccine_format_max_age((int)($vaccine["max_age"] ?? 0))); ?></span>
                        </div>
                    </div>

                    <div class="notes-copy">
                        <span class="detail-label">Preparation Notes</span>
                        <p><?php echo htmlspecialchars($vaccine["preparation_notes"] ?? "No preparation notes added."); ?></p>
                    </div>

                    <div class="inline-actions" style="margin-top:16px;">
                        <a class="btn small-btn" href="vaccine_billing.php?vaccine_id=<?php echo (int)$vaccine["vaccine_id"]; ?>">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</body>
</html>
