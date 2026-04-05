<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "doctor") {
    header("Location: login.php");
    exit;
}

$doctor_id = (int)$_SESSION["user_id"];
$msg = "";
$error = "";
$departments = get_departments($conn);

if (isset($_POST["save_profile"])) {
    $full_name = trim($_POST["full_name"] ?? "");
    $department_id = (int)($_POST["department_id"] ?? 0);
    $phone = trim($_POST["phone"] ?? "");
    $room_no = trim($_POST["room_no"] ?? "");
    $consultation_fee = trim($_POST["consultation_fee"] ?? "");

    if ($department_id > 0 && !department_exists($departments, $department_id)) {
        $error = "Please select a valid department.";
    } elseif ($consultation_fee !== "" && (!is_numeric($consultation_fee) || (float)$consultation_fee < 0)) {
        $error = "Please enter a valid consultation fee.";
    }

    if ($error === "") {
        $existsStmt = sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.doctors WHERE id = ?", [$doctor_id]);
        $exists = $existsStmt && sqlsrv_has_rows($existsStmt);

        if ($exists) {
            $sql = "UPDATE dbo.doctors
                    SET full_name=?, department_id=?, phone=?, room_no=?, consultation_fee=?, updated_at=GETDATE()
                    WHERE id=?";
            $departmentValue = $department_id > 0 ? $department_id : null;
            $feeValue = $consultation_fee !== "" ? (float)$consultation_fee : 0;
            $ok = sqlsrv_query($conn, $sql, [$full_name, $departmentValue, $phone, $room_no, $feeValue, $doctor_id]);
        } else {
            $sql = "INSERT INTO dbo.doctors (id, full_name, department_id, phone, room_no, consultation_fee)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $departmentValue = $department_id > 0 ? $department_id : null;
            $feeValue = $consultation_fee !== "" ? (float)$consultation_fee : 0;
            $ok = sqlsrv_query($conn, $sql, [$doctor_id, $full_name, $departmentValue, $phone, $room_no, $feeValue]);
        }

        if ($ok) {
            $msg = "Profile saved successfully.";
        } else {
            $error = "Failed to save profile.";
        }
    }
}

$loadStmt = sqlsrv_query(
    $conn,
    "SELECT TOP 1 d.*, dep.department_name
     FROM dbo.doctors d
     LEFT JOIN dbo.departments dep ON dep.department_id = d.department_id
     WHERE d.id = ?",
    [$doctor_id]
);
$doctor = $loadStmt ? sqlsrv_fetch_array($loadStmt, SQLSRV_FETCH_ASSOC) : null;

$userStmt = sqlsrv_query($conn, "SELECT name, email FROM dbo.users WHERE id = ?", [$doctor_id]);
$userRow = $userStmt ? sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Profile - PISD</title>
  <link rel="stylesheet" href="assets/patient.css">
  <style>
    .schedule-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
    }
    .schedule-table th, .schedule-table td {
      border: 1px solid #e2e8f0;
      padding: 8px 12px;
      text-align: left;
    }
    .schedule-table th {
      background-color: #f8fafc;
      font-weight: 600;
    }
  </style>
</head>
<body>
<div class="container">

  <nav class="nav">
      <div class="logo">Hospital</div>
      <div class="actions">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Doctor"); ?></span>
          <a class="btn" href="logout.php">Logout</a>
      </div>
  </nav>

  <div class="layout-grid">
      <div class="sidebar-card">
          <h3 style="margin-bottom:16px;">Doctor</h3>
          <ul class="sidebar-list">
              <li><a class="btn small-btn" href="doctor_dashboard.php">Dashboard</a></li>
              <li><a class="btn small-btn" href="doctor_profile.php">My Profile</a></li>
              <li><a class="btn small-btn" href="doctor_schedule.php">My Schedule</a></li>
          </ul>
      </div>

      <div style="flex:1; min-width:0;">
          <h2 class="section-title">Update My Profile</h2>

          <?php if ($msg): ?>
            <div style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.2); color:#166534; font-size:14px; font-weight:600;">
              <?php echo htmlspecialchars($msg); ?>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="error-message" style="margin-bottom:16px;">
              <?php echo htmlspecialchars($error); ?>
            </div>
          <?php endif; ?>

          <div class="feature-card form-card">
            <h3>Account Info</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userRow["email"] ?? ($_SESSION["user_email"] ?? "")); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($userRow["name"] ?? ($_SESSION["user_name"] ?? "")); ?></p>
          </div>

          <div class="feature-card form-card" style="margin-top:16px;">
            <h3>Doctor Profile</h3>
            <form method="post">
              <div class="form-grid">
                <input class="form-field" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($doctor["full_name"] ?? ($userRow["name"] ?? "")); ?>">
                <select class="form-field" name="department_id">
                  <option value="">Select Department</option>
                  <?php foreach ($departments as $department): ?>
                    <option value="<?php echo (int)$department["department_id"]; ?>" <?php echo ((int)($doctor["department_id"] ?? 0) === (int)$department["department_id"]) ? "selected" : ""; ?>>
                      <?php echo htmlspecialchars($department["department_name"]); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <input class="form-field" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($doctor["phone"] ?? ""); ?>">
                <input class="form-field" name="room_no" placeholder="Room No" value="<?php echo htmlspecialchars($doctor["room_no"] ?? ""); ?>">
                <input class="form-field" type="number" min="0" step="0.01" name="consultation_fee" placeholder="Consultation Fee" value="<?php echo htmlspecialchars((string)($doctor["consultation_fee"] ?? "")); ?>">
              </div>

              <div class="inline-actions" style="margin-top:12px;">
                <button class="btn" type="submit" name="save_profile">Save</button>
              </div>
            </form>
          </div>

          <div class="feature-card form-card" style="margin-top:16px;">
            <h3>My Schedule</h3>
            <?php
            $scheduleSql = "
                WITH latest_schedule AS (
                    SELECT *,
                           ROW_NUMBER() OVER (
                               PARTITION BY doctor_id, day_of_week
                               ORDER BY id DESC
                           ) AS rn
                    FROM doctor_schedule
                    WHERE doctor_id = ?
                )
                SELECT *
                FROM latest_schedule
                WHERE rn = 1
                ORDER BY day_of_week
            ";
            $scheduleStmt = sqlsrv_query($conn, $scheduleSql, [$doctor_id]);

            if ($scheduleStmt === false) {
                echo '<p style="color:#b91c1c;">Error loading schedule: ' . htmlspecialchars(print_r(sqlsrv_errors(), true)) . '</p>';
            } else {
                $days = [1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday", 7 => "Sunday"];
                $hasRows = false;
                echo '<div class="table-wrap"><table class="schedule-table">';
                echo '<tr><th>Day</th><th>Time</th><th>Max Patients</th></tr>';
                while ($row = sqlsrv_fetch_array($scheduleStmt, SQLSRV_FETCH_ASSOC)) {
                    $hasRows = true;
                    $start = isset($row["start_time"]) ? $row["start_time"]->format("H:i") : "--:--";
                    $end = isset($row["end_time"]) ? $row["end_time"]->format("H:i") : "--:--";
                    $dayName = $days[$row["day_of_week"]] ?? "Unknown";
                    $maxPatients = htmlspecialchars($row["max_patients"] ?? "");
                    echo "<tr><td>{$dayName}</td><td>{$start} - {$end}</td><td>{$maxPatients}</td></tr>";
                }
                echo '</table></div>';
                if (!$hasRows) {
                    echo '<p>No schedule has been set up yet.</p>';
                }
            }
            ?>
          </div>
      </div>
  </div>

</div>
</body>
</html>
