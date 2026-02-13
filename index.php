<?php
session_start();
include "db.php";

// Query only needed if you show the optional table
$sql = "SELECT TOP 20 id, name, email, created_at FROM dbo.users ORDER BY id DESC";
$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>HMDS - Home</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

  <div class="nav">
    <div>
      <h2>HMDS — Hospital Management Database System</h2>
      <small>Raw PHP + Raw SQL (SQL Server Authentication)</small>
    </div>
    <div>
      <?php if(isset($_SESSION["user_email"])): ?>
        <a class="btn" href="logout.php">Logout</a>
      <?php else: ?>
        <a class="btn" href="login.php">Login</a>
        <a class="btn primary" href="register.php">Register</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <h3>Landing / Home Page</h3>
      <p>
        HMDS is a hospital management system to organize patient records, doctor schedules,
        appointments, and department operations in a secure and structured way.
      </p>

      <div class="pills">
        <div class="pill">Patients</div>
        <div class="pill">Doctors</div>
        <div class="pill">Departments</div>
        <div class="pill">Appointments</div>
        <div class="pill">Authentication</div>
      </div>

      <div class="hr"></div>

      <?php if(isset($_SESSION["user_email"])): ?>
        <div class="pill">✅ Logged in as <?php echo htmlspecialchars($_SESSION["user_email"]); ?></div>
      <?php else: ?>
        <p style="margin-top:10px;">Please Register or Login to continue.</p>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Checkpoint 1 Requirements</h3>
      <div class="pills">
        <div class="pill">✅ Database connection</div>
        <div class="pill">✅ Authentication (Register/Login)</div>
        <div class="pill">✅ Complete landing page</div>
      </div>

      <div class="hr"></div>

      <h3>About Us</h3>
      <p>
        We are building HMDS as a course project. Our goal is to create an easy system
        that supports hospital work and reduces paperwork errors.
      </p>
      <p style="margin-top:10px;">
        <b>Team:</b> Add member names here.
      </p>
    </div>
  </div>

  <div class="card">
    <h3>Key Features</h3>
    <div class="featureGrid">
      <div class="feature">
        <b>Patient Management</b>
        <span>Store patient details, history, and basic records.</span>
      </div>
      <div class="feature">
        <b>Doctor & Department</b>
        <span>Manage doctors and link them with departments.</span>
      </div>
      <div class="feature">
        <b>Appointments</b>
        <span>Schedule and track appointments with timestamps.</span>
      </div>
    </div>
  </div>

  <!--  OPTIONAL TABLE START -->

  <div class="card">
    <h3>Database Proof: Users Table (Raw SQL Query)</h3>

    <?php
    if($stmt === false) {
        echo "<div class='error'>" . print_r(sqlsrv_errors(), true) . "</div>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Created At</th></tr>";

        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $created = $row["created_at"];
            $createdText = $created ? $created->format("Y-m-d H:i:s") : "";

            echo "<tr>";
            echo "<td>" . (int)$row["id"] . "</td>";
            echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
            echo "<td>" . htmlspecialchars($createdText) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    }
    ?>
  </div>

  <!-- OPTIONAL TABLE END -->

  <div class="card">
    <h3>Contact</h3>
    <p>
      Email: <b>add your email</b><br>
      Address: <b>add hospital / campus details</b><br>
      Phone: <b>add phone</b>
    </p>
  </div>

  <div class="footer">
    © <?php echo date("Y"); ?> HMDS — Hospital Management Database System
  </div>

</div>
</body>
</html>
