<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit;
}

$msg = "";
$error = "";
$departments = get_departments($conn);

if (isset($_POST["add_doctor"])) {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $department_id = (int)($_POST["department_id"] ?? 0);
    $phone = trim($_POST["phone"] ?? "");
    $room_no = trim($_POST["room_no"] ?? "");
    $consultation_fee = trim($_POST["consultation_fee"] ?? "");

    if ($name === "" || $email === "" || $password === "") {
        $error = "Doctor name, email and password are required.";
    } elseif ($department_id > 0 && !department_exists($departments, $department_id)) {
        $error = "Please select a valid department.";
    } elseif ($consultation_fee !== "" && (!is_numeric($consultation_fee) || (float)$consultation_fee < 0)) {
        $error = "Please enter a valid consultation fee.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } else {
        $check = sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.users WHERE email = ?", [$email]);
        if ($check && sqlsrv_has_rows($check)) {
            $error = "This email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            sqlsrv_begin_transaction($conn);

            $insertUser = "
                INSERT INTO dbo.users (name, email, password_hash, role)
                OUTPUT INSERTED.id AS new_id
                VALUES (?, ?, ?, 'doctor')
            ";
            $userStmt = sqlsrv_query($conn, $insertUser, [$name, $email, $hash]);

            if ($userStmt === false) {
                sqlsrv_rollback($conn);
                $error = "Failed to create doctor user.";
            } else {
                $idRow = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);
                $newId = $idRow ? (int)$idRow["new_id"] : 0;

                if ($newId <= 0) {
                    sqlsrv_rollback($conn);
                    $error = "Failed to get new doctor id.";
                } else {
                    $insertDoc = "
                        INSERT INTO dbo.doctors (id, full_name, department_id, phone, room_no, consultation_fee)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ";
                    $departmentValue = $department_id > 0 ? $department_id : null;
                    $feeValue = $consultation_fee !== "" ? (float)$consultation_fee : 0;
                    $ok2 = sqlsrv_query($conn, $insertDoc, [$newId, $name, $departmentValue, $phone, $room_no, $feeValue]);

                    if ($ok2 === false) {
                        sqlsrv_rollback($conn);
                        $error = "Failed to create doctor profile.";
                    } else {
                        sqlsrv_commit($conn);
                        $msg = "Doctor created successfully.";
                    }
                }
            }
        }
    }
}

if (isset($_POST["delete_doctor"])) {
    $doctor_id = (int)($_POST["doctor_id"] ?? 0);

    if ($doctor_id <= 0) {
        $error = "Invalid doctor id.";
    } else {
        $roleStmt = sqlsrv_query($conn, "SELECT role FROM dbo.users WHERE id = ?", [$doctor_id]);
        $roleRow = $roleStmt ? sqlsrv_fetch_array($roleStmt, SQLSRV_FETCH_ASSOC) : null;

        if (!$roleRow || $roleRow["role"] !== "doctor") {
            $error = "This user is not a doctor.";
        } else {
            $del = sqlsrv_query($conn, "DELETE FROM dbo.users WHERE id = ?", [$doctor_id]);
            if ($del) {
                $msg = "Doctor deleted successfully.";
            } else {
                $error = "Delete failed.";
            }
        }
    }
}

$listSql = "
    SELECT u.id, u.name, u.email, dep.department_name AS department, d.phone, d.room_no, d.consultation_fee
    FROM dbo.users u
    LEFT JOIN dbo.doctors d ON d.id = u.id
    LEFT JOIN dbo.departments dep ON dep.department_id = d.department_id
    WHERE u.role = 'doctor'
    ORDER BY u.id DESC
";
$listStmt = sqlsrv_query($conn, $listSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Doctors - PISD</title>
  <link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

  <nav class="nav">
      <div class="logo">Hospital</div>
      <div class="actions">
          <span class="user-name">Admin</span>
          <a class="btn" href="logout.php">Logout</a>
      </div>
  </nav>

  <div class="layout-grid">
      <div class="sidebar-card">
          <h3 style="margin-bottom:16px;">Admin</h3>
          <ul class="sidebar-list">
              <li><a class="btn small-btn" href="admin_dashboard.php">Dashboard</a></li>
              <li><a class="btn small-btn" href="admin_doctors.php">Manage Doctors</a></li>
              <li><a class="btn small-btn" href="admin_vaccines.php">Manage Vaccines</a></li>
          </ul>
      </div>

      <div style="flex:1; min-width:0;">
          <h2 class="section-title">Manage Doctors</h2>

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

          <div class="feature-card form-card" style="margin-top:16px;">
            <h3>Add Doctor</h3>
            <form method="post">
              <div class="form-grid">
                <input class="form-field" name="name" placeholder="Doctor Name" required>
                <input class="form-field" name="email" placeholder="Doctor Email" required>
                <input class="form-field" name="password" placeholder="Temporary Password" required>
                <select class="form-field" name="department_id">
                  <option value="">Select Department</option>
                  <?php foreach ($departments as $department): ?>
                    <option value="<?php echo (int)$department["department_id"]; ?>">
                      <?php echo htmlspecialchars($department["department_name"]); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <input class="form-field" name="phone" placeholder="Phone (optional)">
                <input class="form-field" name="room_no" placeholder="Room No (optional)">
                <input class="form-field" type="number" min="0" step="0.01" name="consultation_fee" placeholder="Consultation Fee (Tk)">
              </div>
              <div class="inline-actions" style="margin-top:12px;">
                <button class="btn" type="submit" name="add_doctor">Create Doctor</button>
              </div>
            </form>
          </div>

          <div class="feature-card form-card" style="margin-top:24px;">
            <h3>Doctors List</h3>
            <div class="table-wrap">
              <table border="1" cellpadding="10" style="width:100%; background:white; border-radius:12px; overflow:hidden;">
                <tr>
                  <th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Phone</th><th>Room</th><th>Fee</th><th>Action</th>
                </tr>

                <?php if ($listStmt): ?>
                  <?php while ($r = sqlsrv_fetch_array($listStmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                      <td><?php echo (int)$r["id"]; ?></td>
                      <td><?php echo htmlspecialchars($r["name"] ?? ""); ?></td>
                      <td><?php echo htmlspecialchars($r["email"] ?? ""); ?></td>
                      <td><?php echo htmlspecialchars($r["department"] ?? ""); ?></td>
                      <td><?php echo htmlspecialchars($r["phone"] ?? ""); ?></td>
                      <td><?php echo htmlspecialchars($r["room_no"] ?? ""); ?></td>
                      <td><?php echo "Tk " . number_format((float)($r["consultation_fee"] ?? 0), 2); ?></td>
                      <td>
                        <form method="post" style="display:inline;">
                          <input type="hidden" name="doctor_id" value="<?php echo (int)$r["id"]; ?>">
                          <button class="btn small-btn" type="submit" name="delete_doctor" onclick="return confirm('Delete this doctor?');">
                            Delete
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php endif; ?>
              </table>
            </div>
          </div>
      </div>
  </div>

</div>
</body>
</html>
