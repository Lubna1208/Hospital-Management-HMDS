<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit;
}

$msg = "";
$error = "";

/* Create Doctor */
if (isset($_POST["add_doctor"])) {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $department = trim($_POST["department"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $room_no = trim($_POST["room_no"] ?? "");

    if ($name === "" || $email === "" || $password === "") {
        $error = "Doctor name, email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } else {
        // Check email unique
        $check = sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.users WHERE email = ?", [$email]);
        if ($check && sqlsrv_has_rows($check)) {
            $error = "This email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Transaction
            sqlsrv_begin_transaction($conn);

            // ✅ Insert + get ID reliably using OUTPUT INSERTED.id
            $insertUser = "
                INSERT INTO dbo.users (name, email, password_hash, role)
                OUTPUT INSERTED.id AS new_id
                VALUES (?, ?, ?, 'doctor')
            ";
            $userStmt = sqlsrv_query($conn, $insertUser, [$name, $email, $hash]);

            if ($userStmt === false) {
                sqlsrv_rollback($conn);
                $error = "Failed to create doctor user.";
                // Uncomment for debugging:
                // die(print_r(sqlsrv_errors(), true));
            } else {
                $idRow = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);
                $newId = $idRow ? (int)$idRow["new_id"] : 0;

                if ($newId <= 0) {
                    sqlsrv_rollback($conn);
                    $error = "Failed to get new doctor id.";
                } else {
                    $insertDoc = "
                        INSERT INTO dbo.doctors (id, full_name, department, phone, room_no)
                        VALUES (?, ?, ?, ?, ?)
                    ";
                    $ok2 = sqlsrv_query($conn, $insertDoc, [$newId, $name, $department, $phone, $room_no]);

                    if ($ok2 === false) {
                        sqlsrv_rollback($conn);
                        $error = "Failed to create doctor profile.";
                        // Uncomment for debugging:
                        // die(print_r(sqlsrv_errors(), true));
                    } else {
                        sqlsrv_commit($conn);
                        $msg = "Doctor created successfully.";
                    }
                }
            }
        }
    }
}

/* Delete Doctor */
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
            if ($del) $msg = "Doctor deleted successfully.";
            else $error = "Delete failed.";
        }
    }
}

/* List Doctors */
$listSql = "
    SELECT u.id, u.name, u.email, d.department, d.phone, d.room_no
    FROM dbo.users u
    LEFT JOIN dbo.doctors d ON d.id = u.id
    WHERE u.role = 'doctor'
    ORDER BY u.id DESC
";
$listStmt = sqlsrv_query($conn, $listSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Doctors — PISD</title>
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
          </ul>
      </div>

      <div style="flex:1;">
          <h2 class="section-title">Manage Doctors</h2>

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
            <h3>Add Doctor</h3>
            <form method="post">
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <input class="form-field" name="name" placeholder="Doctor Name" required>
                <input class="form-field" name="email" placeholder="Doctor Email" required>
                <input class="form-field" name="password" placeholder="Temporary Password" required>
                <input class="form-field" name="department" placeholder="Department (optional)">
                <input class="form-field" name="phone" placeholder="Phone (optional)">
                <input class="form-field" name="room_no" placeholder="Room No (optional)">
              </div>
              <div style="margin-top:12px;">
                <button class="btn" type="submit" name="add_doctor">Create Doctor</button>
              </div>
            </form>
          </div>

          <div class="feature-card" style="margin-top:24px;">
            <h3>Doctors List</h3>

            <table border="1" cellpadding="10" style="width:100%; background:white; border-radius:12px; overflow:hidden;">
              <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Phone</th><th>Room</th><th>Action</th>
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
                    <td>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="doctor_id" value="<?php echo (int)$r["id"]; ?>">
                        <button class="btn small-btn" type="submit" name="delete_doctor"
                          onclick="return confirm('Delete this doctor?');">
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
</body>
</html>