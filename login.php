<?php
session_start();
include "db.php";

$msg = "";

if(isset($_GET["registered"])) {
  $msg = "Registration successful! Now login.";
}

if(isset($_POST["login"])) {
  $email = trim($_POST["email"]);
  $password = $_POST["password"];

  $sql = "SELECT TOP 1 id, name, email, password_hash FROM dbo.users WHERE email = ?";
  $params = [$email];
  $stmt = sqlsrv_query($conn, $sql, $params);

  if($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    if(password_verify($password, $row["password_hash"])) {
      $_SESSION["user_id"] = $row["id"];
      $_SESSION["user_email"] = $row["email"];
      $_SESSION["user_name"] = $row["name"];
      header("Location: index.php");
      exit();
    } else {
      $msg = "Wrong password.";
    }
  } else {
    $msg = "User not found.";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
  <div class="centerWrap">
    <div class="formCard">
      <h2>Login</h2>
      <small>Use your email and password to enter.</small>

      <?php if($msg !== ""): ?>
        <div class="error"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>

      <form method="post">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input id="pw" type="password" name="password" required>

        <div class="row">
          <button class="btn primary" type="submit" name="login">Login</button>
          <button class="btn" type="button" onclick="togglePw()">Show</button>
        </div>

        <div class="footer">
          New user? <a href="register.php">Register</a> · <a href="index.php">Home</a>
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
