<?php
session_start();
include "db.php";
require_once "vaccine_helpers.php";
require_once "pdf_helpers.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "patient") !== "patient") {
    header("Location: login.php");
    exit;
}

$vaccineId = (int)($_GET["vaccine_id"] ?? $_POST["vaccine_id"] ?? 0);
if ($vaccineId <= 0) {
    header("Location: vaccines.php");
    exit;
}

$stmt = sqlsrv_query(
    $conn,
    "SELECT vaccine_id, vaccine_name, price, min_age, max_age, gender_applicable, preparation_notes FROM dbo.vaccines WHERE vaccine_id = ?",
    [$vaccineId]
);

if ($stmt === false) {
    die("SQL Error (vaccines): " . print_r(sqlsrv_errors(), true));
}

$vaccine = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$vaccine) {
    header("Location: vaccines.php");
    exit;
}

$error = "";
$patientName = trim($_POST["patient_name"] ?? "");
$patientAge = trim($_POST["patient_age"] ?? "");
$patientAgeUnit = strtolower(trim($_POST["patient_age_unit"] ?? "years"));
$patientGender = vaccine_normalize_gender($_POST["patient_gender"] ?? "");
$patientPhone = trim($_POST["patient_phone"] ?? "");
$patientAddress = trim($_POST["patient_address"] ?? "");
$allowedAgeUnits = vaccine_allowed_age_units();
$pdfReady = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && (isset($_POST["generate_bill"]) || isset($_POST["download_pdf"]))) {
    if ($patientName === "" || $patientAge === "" || $patientGender === "" || $patientPhone === "" || $patientAddress === "") {
        $error = "Patient name, age, gender, phone number, and address are required.";
    } elseif (!in_array($patientAgeUnit, $allowedAgeUnits, true)) {
        $error = "Please select a valid age unit.";
    } elseif (!is_numeric($patientAge) || (int)$patientAge < 0) {
        $error = "Please enter a valid patient age.";
    } else {
        $patientAgeMonths = vaccine_age_to_months($patientAge, $patientAgeUnit);
        $eligibility = vaccine_check_eligibility($vaccine, $patientAgeMonths, $patientGender);

        if (!$eligibility["eligible"]) {
            $error = $eligibility["reason"];
        }
    }

    if ($error === "" && isset($_POST["download_pdf"])) {
        $insertHistory = "
            INSERT INTO dbo.vaccine_billing_history (
                vaccine_id,
                user_id,
                patient_name,
                patient_age_value,
                patient_age_unit,
                patient_gender,
                patient_phone,
                patient_address,
                billed_price
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $historyStmt = sqlsrv_query($conn, $insertHistory, [
            (int)$vaccine["vaccine_id"],
            (int)($_SESSION["user_id"] ?? 0) ?: null,
            $patientName,
            (int)$patientAge,
            $patientAgeUnit,
            $patientGender,
            $patientPhone,
            $patientAddress,
            (float)($vaccine["price"] ?? 0),
        ]);

        if ($historyStmt === false) {
            $error = "Failed to save billing history.";
        }
    }

    if ($error === "" && isset($_POST["download_pdf"])) {
        $filename = "vaccine-bill-" . (int)$vaccine["vaccine_id"] . ".pdf";
        $billDate = date("Y-m-d H:i");
        $lines = [
            "Bill Date: " . $billDate,
            "",
            "Vaccine Information",
            "Vaccine: " . ($vaccine["vaccine_name"] ?? ""),
            "Price: Tk " . number_format((float)($vaccine["price"] ?? 0), 2),
            "Minimum Age: " . vaccine_format_age_months((int)($vaccine["min_age"] ?? 0)),
            "Maximum Age: " . vaccine_format_max_age((int)($vaccine["max_age"] ?? 0)),
            "Gender Applicable: " . ($vaccine["gender_applicable"] ?? "Both"),
            "",
            "Patient Information",
            "Patient Name: " . $patientName,
            "Patient Age: " . $patientAge . " " . ucfirst($patientAgeUnit),
            "Patient Gender: " . $patientGender,
            "Phone Number: " . $patientPhone,
            "Address: " . $patientAddress,
            "",
            "Total Billing Amount: Tk " . number_format((float)($vaccine["price"] ?? 0), 2),
        ];

        pdf_output_invoice($filename, "HMDS Vaccine Billing Receipt", $lines);
    }

    if ($error === "" && isset($_POST["generate_bill"])) {
        $pdfReady = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vaccine Details - PISD</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

<nav class="nav">
    <div class="logo">🏥</div>
    <div class="actions">
        <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Patient"); ?></span>
        <a class="btn" href="vaccines.php">Back</a>
        <a class="btn" href="logout.php">Logout</a>
    </div>
</nav>

<div style="display:flex; gap:32px; margin-top:32px;">
    <div style="width:280px; background:white; padding:24px; border-radius:16px; box-shadow:0 8px 24px rgba(10,44,62,.08);">
        <h3 style="margin-bottom:16px;">Vaccine Details</h3>
        <p style="margin-bottom:10px;"><strong>Name:</strong> <?php echo htmlspecialchars($vaccine["vaccine_name"] ?? ""); ?></p>
        <p style="margin-bottom:10px;"><strong>Price:</strong> Tk <?php echo number_format((float)($vaccine["price"] ?? 0), 2); ?></p>
        <p style="margin-bottom:10px;"><strong>Minimum Age:</strong> <?php echo htmlspecialchars(vaccine_format_age_months((int)($vaccine["min_age"] ?? 0))); ?></p>
        <p style="margin-bottom:10px;"><strong>Maximum Age:</strong> <?php echo htmlspecialchars(vaccine_format_max_age((int)($vaccine["max_age"] ?? 0))); ?></p>
        <p style="margin-bottom:10px;"><strong>Gender:</strong> <?php echo htmlspecialchars($vaccine["gender_applicable"] ?? "Both"); ?></p>
        <p><strong>Preparation Notes:</strong> <?php echo htmlspecialchars($vaccine["preparation_notes"] ?? "No preparation notes added."); ?></p>
    </div>

    <div style="flex:1;">
        <h2 class="section-title">Patient Billing Form</h2>

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="feature-card" style="text-align:left;">
            <h3>Generate Vaccine Bill</h3>
            <form method="post">
                <input type="hidden" name="vaccine_id" value="<?php echo (int)$vaccine["vaccine_id"]; ?>">
                <input class="form-field" type="text" name="patient_name" placeholder="Patient Name" value="<?php echo htmlspecialchars($patientName); ?>" required>
                <div class="age-input-group">
                    <input class="form-field" type="number" min="0" name="patient_age" placeholder="Patient Age" value="<?php echo htmlspecialchars($patientAge); ?>" required>
                    <select class="form-field age-unit-field" name="patient_age_unit" required>
                        <?php foreach ($allowedAgeUnits as $ageUnit): ?>
                            <option value="<?php echo htmlspecialchars($ageUnit); ?>" <?php echo $patientAgeUnit === $ageUnit ? "selected" : ""; ?>>
                                <?php echo ucfirst($ageUnit); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <select class="form-field" name="patient_gender" required>
                    <option value="">Patient Gender</option>
                    <option value="Male" <?php echo $patientGender === "Male" ? "selected" : ""; ?>>Male</option>
                    <option value="Female" <?php echo $patientGender === "Female" ? "selected" : ""; ?>>Female</option>
                    <option value="Other" <?php echo $patientGender === "Other" ? "selected" : ""; ?>>Other</option>
                </select>
                <input class="form-field" type="text" name="patient_phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($patientPhone); ?>" required>
                <input class="form-field" type="text" name="patient_address" placeholder="Address" value="<?php echo htmlspecialchars($patientAddress); ?>" required>
                <button class="btn" type="submit" name="generate_bill">Prepare PDF Bill</button>
            </form>

            <?php if ($pdfReady): ?>
                <div style="margin-top:20px; padding:16px; border-radius:16px; background:#eef7fb; border:1px solid rgba(10,44,62,.12);">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                        <div style="width:42px; height:42px; border-radius:12px; background:#fff; display:grid; place-items:center; font-size:20px;">📄</div>
                        <div>
                            <strong>PDF Bill Ready</strong>
                            <p style="margin-top:4px; color:#4a6572;">Review complete. Click below to download the billing PDF.</p>
                        </div>
                    </div>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="vaccine_id" value="<?php echo (int)$vaccine["vaccine_id"]; ?>">
                        <input type="hidden" name="patient_name" value="<?php echo htmlspecialchars($patientName); ?>">
                        <input type="hidden" name="patient_age" value="<?php echo htmlspecialchars($patientAge); ?>">
                        <input type="hidden" name="patient_age_unit" value="<?php echo htmlspecialchars($patientAgeUnit); ?>">
                        <input type="hidden" name="patient_gender" value="<?php echo htmlspecialchars($patientGender); ?>">
                        <input type="hidden" name="patient_phone" value="<?php echo htmlspecialchars($patientPhone); ?>">
                        <input type="hidden" name="patient_address" value="<?php echo htmlspecialchars($patientAddress); ?>">
                        <button class="btn" type="submit" name="download_pdf">Download PDF Bill</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</div>
</body>
</html>
