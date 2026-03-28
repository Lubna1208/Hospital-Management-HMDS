<?php
session_start();
include "db.php";
require_once "vaccine_helpers.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit;
}

$msg = "";
$error = "";
$allowedGenders = vaccine_allowed_genders();
$allowedAgeUnits = vaccine_allowed_age_units();
$allowedMaxAgeUnits = vaccine_allowed_max_age_units();

$formMinAgeValue = $_POST["min_age"] ?? "";
$formMinAgeUnit = strtolower(trim($_POST["min_age_unit"] ?? "months"));
$formMaxAgeValue = $_POST["max_age"] ?? "";
$formMaxAgeUnit = strtolower(trim($_POST["max_age_unit"] ?? "months"));

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_vaccine"])) {
    $vaccineName = trim($_POST["vaccine_name"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $minAge = trim($_POST["min_age"] ?? "");
    $minAgeUnit = strtolower(trim($_POST["min_age_unit"] ?? "months"));
    $maxAge = trim($_POST["max_age"] ?? "");
    $maxAgeUnit = strtolower(trim($_POST["max_age_unit"] ?? "months"));
    $genderApplicable = vaccine_normalize_gender($_POST["gender_applicable"] ?? "Both");
    $preparationNotes = trim($_POST["preparation_notes"] ?? "");
    $minAgeMonths = vaccine_age_to_months($minAge, $minAgeUnit);
    $maxAgeMonths = vaccine_age_to_months($maxAge, $maxAgeUnit);

    if ($vaccineName === "" || $price === "" || $minAge === "" || $maxAgeUnit === "") {
        $error = "Vaccine name, price, minimum age and maximum age are required.";
    } elseif (!is_numeric($price) || (float)$price < 0) {
        $error = "Please enter a valid non-negative price.";
    } elseif (!in_array($minAgeUnit, $allowedAgeUnits, true) || !in_array($maxAgeUnit, $allowedMaxAgeUnits, true)) {
        $error = "Please select valid age units.";
    } elseif ($minAgeMonths === null) {
        $error = "Minimum age must be a whole number greater than or equal to 0.";
    } elseif ($maxAgeUnit !== "no_limit" && $maxAgeMonths === null) {
        $error = "Maximum age must be a whole number greater than or equal to 0.";
    } elseif ($error === "" && $minAgeMonths > $maxAgeMonths) {
        $error = "Maximum age must be greater than or equal to minimum age.";
    } elseif ($error === "" && !in_array($genderApplicable, $allowedGenders, true)) {
        $error = "Please select a valid gender applicability option.";
    } elseif ($error === "") {
        $insertSql = "
            INSERT INTO dbo.vaccines (vaccine_name, price, min_age, max_age, gender_applicable, preparation_notes, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, GETDATE())
        ";
        $insertStmt = sqlsrv_query($conn, $insertSql, [
            $vaccineName,
            number_format((float)$price, 2, ".", ""),
            $minAgeMonths,
            $maxAgeMonths,
            $genderApplicable,
            $preparationNotes !== "" ? $preparationNotes : null,
        ]);

        if ($insertStmt === false) {
            $error = "Failed to save vaccine details.";
        } else {
            $msg = "Vaccine added successfully.";
            $formMinAgeValue = "";
            $formMinAgeUnit = "months";
            $formMaxAgeValue = "";
            $formMaxAgeUnit = "months";
            $_POST = [];
        }
    }
}

if (isset($_POST["delete_vaccine"])) {
    $vaccineId = (int)($_POST["vaccine_id"] ?? 0);

    if ($vaccineId <= 0) {
        $error = "Invalid vaccine id.";
    } else {
        $deleteStmt = sqlsrv_query($conn, "DELETE FROM dbo.vaccines WHERE vaccine_id = ?", [$vaccineId]);
        if ($deleteStmt === false) {
            $error = "Delete failed.";
        } else {
            $msg = "Vaccine deleted successfully.";
        }
    }
}

$searchName = trim($_GET["search_name"] ?? "");
$ageFrom = trim($_GET["age_from"] ?? "");
$ageTo = trim($_GET["age_to"] ?? "");
$ageFromUnit = strtolower(trim($_GET["age_from_unit"] ?? "months"));
$ageToUnit = strtolower(trim($_GET["age_to_unit"] ?? "months"));
$genderFilter = vaccine_normalize_gender($_GET["gender_applicable"] ?? "");
$ageFromMonths = in_array($ageFromUnit, $allowedAgeUnits, true) ? vaccine_age_to_months($ageFrom, $ageFromUnit) : null;
$ageToMonths = in_array($ageToUnit, $allowedAgeUnits, true) ? vaccine_age_to_months($ageTo, $ageToUnit) : null;

$listSql = "
    SELECT vaccine_id, vaccine_name, price, min_age, max_age, gender_applicable, preparation_notes
    FROM dbo.vaccines
    WHERE 1 = 1
";
$params = [];

if ($searchName !== "") {
    $listSql .= " AND vaccine_name LIKE ? ";
    $params[] = "%" . $searchName . "%";
}

if ($ageFrom !== "" && $ageFromMonths !== null) {
    $listSql .= " AND max_age >= ? ";
    $params[] = $ageFromMonths;
}

if ($ageTo !== "" && $ageToMonths !== null) {
    $listSql .= " AND min_age <= ? ";
    $params[] = $ageToMonths;
}

if ($genderFilter !== "") {
    $listSql .= " AND gender_applicable = ? ";
    $params[] = $genderFilter;
}

$listSql .= " ORDER BY vaccine_name ASC, vaccine_id DESC";
$listStmt = sqlsrv_query($conn, $listSql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Vaccines - PISD</title>
  <link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

  <nav class="nav">
      <div class="logo">🏥</div>
      <div class="actions">
          <span class="user-name">Admin</span>
          <a class="btn" href="logout.php">Logout</a>
      </div>
  </nav>

  <div style="display:flex; gap:32px; margin-top:32px;">
      <div style="width:250px; background:white; padding:24px; border-radius:16px; box-shadow:0 8px 24px rgba(10,44,62,.08);">
          <h3 style="margin-bottom:16px;">Admin</h3>
          <ul style="list-style:none; padding:0;">
              <li style="margin-bottom:12px;">
                  <a class="btn small-btn" href="admin_dashboard.php">Dashboard</a>
              </li>
              <li style="margin-bottom:12px;">
                  <a class="btn small-btn" href="admin_doctors.php">Manage Doctors</a>
              </li>
              <li style="margin-bottom:12px;">
                  <a class="btn small-btn" href="admin_vaccines.php">Manage Vaccines</a>
              </li>
          </ul>
      </div>

      <div style="flex:1;">
          <h2 class="section-title">Manage Vaccines</h2>

          <?php if ($msg): ?>
            <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2); color: #166534; font-size: 14px; font-weight: 600;">
              <?php echo htmlspecialchars($msg); ?>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="error-message" style="margin-bottom: 16px;">
              <?php echo htmlspecialchars($error); ?>
            </div>
          <?php endif; ?>

          <div class="feature-card" style="margin-top:16px;">
            <h3>Add Vaccine</h3>
            <form method="post">
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <input class="form-field" name="vaccine_name" placeholder="Vaccine Name" value="<?php echo htmlspecialchars($_POST["vaccine_name"] ?? ""); ?>" required>
                <input class="form-field" type="number" min="0" step="0.01" name="price" placeholder="Price" value="<?php echo htmlspecialchars($_POST["price"] ?? ""); ?>" required>
                <div class="age-input-group">
                  <input class="form-field" type="number" min="0" name="min_age" placeholder="Minimum Age" value="<?php echo htmlspecialchars($formMinAgeValue); ?>" required>
                  <select class="form-field age-unit-field" name="min_age_unit">
                    <?php foreach ($allowedAgeUnits as $ageUnit): ?>
                      <option value="<?php echo htmlspecialchars($ageUnit); ?>" <?php echo $formMinAgeUnit === $ageUnit ? "selected" : ""; ?>><?php echo ucfirst($ageUnit); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="age-input-group">
                  <input class="form-field" type="number" min="0" name="max_age" placeholder="Maximum Age" value="<?php echo htmlspecialchars($formMaxAgeValue); ?>" <?php echo $formMaxAgeUnit === "no_limit" ? "disabled" : ""; ?>>
                  <select class="form-field age-unit-field" name="max_age_unit" required onchange="this.form.max_age.disabled = (this.value === 'no_limit'); if (this.value === 'no_limit') this.form.max_age.value='';">
                    <?php foreach ($allowedMaxAgeUnits as $ageUnit): ?>
                      <option value="<?php echo htmlspecialchars($ageUnit); ?>" <?php echo $formMaxAgeUnit === $ageUnit ? "selected" : ""; ?>>
                        <?php echo $ageUnit === "no_limit" ? "No limit" : ucfirst($ageUnit); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <select class="form-field" name="gender_applicable" required>
                  <option value="">Gender Applicable</option>
                  <?php foreach ($allowedGenders as $genderOption): ?>
                    <option value="<?php echo htmlspecialchars($genderOption); ?>" <?php echo (($_POST["gender_applicable"] ?? "Both") === $genderOption) ? "selected" : ""; ?>>
                      <?php echo htmlspecialchars($genderOption); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div style="margin-top:12px;">
                <textarea class="form-field textarea-field" name="preparation_notes" placeholder="Preparation Notes"><?php echo htmlspecialchars($_POST["preparation_notes"] ?? ""); ?></textarea>
              </div>
              <p class="muted-text" style="margin-top:8px;">Set maximum age in months or years, or choose `No limit`.</p>
              <div style="margin-top:12px;">
                <button class="btn" type="submit" name="add_vaccine">Add Vaccine</button>
              </div>
            </form>
          </div>

          <div class="feature-card" style="margin-top:24px;">
            <h3>Vaccines List</h3>

            <form method="get">
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <input class="form-field" name="search_name" placeholder="Search by vaccine name" value="<?php echo htmlspecialchars($searchName); ?>">
                <select class="form-field" name="gender_applicable">
                  <option value="">All Genders</option>
                  <?php foreach ($allowedGenders as $genderOption): ?>
                    <option value="<?php echo htmlspecialchars($genderOption); ?>" <?php echo $genderFilter === $genderOption ? "selected" : ""; ?>>
                      <?php echo htmlspecialchars($genderOption); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="age-input-group">
                  <input class="form-field" type="number" min="0" name="age_from" placeholder="Age from" value="<?php echo htmlspecialchars($ageFrom); ?>">
                  <select class="form-field age-unit-field" name="age_from_unit">
                    <?php foreach ($allowedAgeUnits as $ageUnit): ?>
                      <option value="<?php echo htmlspecialchars($ageUnit); ?>" <?php echo $ageFromUnit === $ageUnit ? "selected" : ""; ?>><?php echo ucfirst($ageUnit); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="age-input-group">
                  <input class="form-field" type="number" min="0" name="age_to" placeholder="Age to" value="<?php echo htmlspecialchars($ageTo); ?>">
                  <select class="form-field age-unit-field" name="age_to_unit">
                    <?php foreach ($allowedAgeUnits as $ageUnit): ?>
                      <option value="<?php echo htmlspecialchars($ageUnit); ?>" <?php echo $ageToUnit === $ageUnit ? "selected" : ""; ?>><?php echo ucfirst($ageUnit); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div style="margin-top:12px;">
                <button class="btn" type="submit">Search</button>
                <a class="btn small-btn" href="admin_vaccines.php" style="margin-left:8px;">Reset</a>
              </div>
            </form>

            <table border="1" cellpadding="10" style="width:100%; background:white; border-radius:12px; overflow:hidden;">
              <tr>
                <th>ID</th><th>Vaccine</th><th>Price</th><th>Age Range</th><th>Gender</th><th>Preparation Notes</th><th>Action</th>
              </tr>
              <?php if ($listStmt && sqlsrv_has_rows($listStmt)): ?>
                <?php while ($row = sqlsrv_fetch_array($listStmt, SQLSRV_FETCH_ASSOC)): ?>
                  <tr>
                    <td><?php echo (int)$row["vaccine_id"]; ?></td>
                    <td><?php echo htmlspecialchars($row["vaccine_name"] ?? ""); ?></td>
                    <td>Tk <?php echo number_format((float)($row["price"] ?? 0), 2); ?></td>
                    <td><?php echo htmlspecialchars(vaccine_age_label($row)); ?></td>
                    <td><?php echo htmlspecialchars($row["gender_applicable"] ?? "Both"); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($row["preparation_notes"] ?? "No preparation notes added.")); ?></td>
                    <td>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="vaccine_id" value="<?php echo (int)$row["vaccine_id"]; ?>">
                        <button class="btn small-btn" type="submit" name="delete_vaccine" onclick="return confirm('Delete this vaccine?');">
                          Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7">No vaccines found for the current filters.</td>
                </tr>
              <?php endif; ?>
            </table>
          </div>
      </div>
  </div>

</div>
</body>
</html>
