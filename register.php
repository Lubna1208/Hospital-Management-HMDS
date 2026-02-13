<?php
session_start();
include "db.php";

$msg = "";

if(isset($_POST["register"])) {
  $name = trim($_POST["name"]);
  $email = trim($_POST["email"]);
  $password = $_POST["password"];

  if($name === "" || $email === "" || $password === "") {
    $msg = "All fields are required.";
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO dbo.users (name, email, password_hash) VALUES (?, ?, ?)";
    $params = [$name, $email, $hash];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt) {
      header("Location: login.php?registered=1");
      exit();
    } else {
      $msg = "Registration failed:\n" . print_r(sqlsrv_errors(), true);
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
  <div class="centerWrap">
    <div class="formCard">
      <h2>Create Account</h2>
      <small>Register to access HMDS.</small>

      <?php if($msg !== ""): ?>
        <div class="error"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>

      <form method="post">
        <label>Name</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input id="pw" type="password" name="password" required>

        <div class="row">
          <button class="btn primary" type="submit" name="register">Register</button>
          <button class="btn" type="button" onclick="togglePw()">Show</button>
        </div>

        <div class="footer">
          Already have an account? <a href="login.php">Login</a> · <a href="index.php">Home</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function togglePw(){
  const pw = document.getElementById('pw');
  pw.type = (pw.type === 'password') ? 'text' : 'password';
}
</script>
</body>
</html>
