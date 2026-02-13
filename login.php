<?php
session_start();
include "db.php";

$error = "";

if(isset($_POST["login"])) {
  $email = trim($_POST["email"]);
  $password = $_POST["password"];

  if($email === "" || $password === "") {
    $error = "Email and password are required.";
  } else {
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
        $error = "Incorrect password.";
      }
    } else {
      $error = "Email not found.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — PISD Medical Center</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-auth">
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-logo">🏥</div>
        <h2>PISD</h2>
        <p class="auth-sub">Medical Center</p>
      </div>

      <?php if($error !== ""): ?>
        <div class="error-message">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input 
            type="email" 
            name="email" 
            class="form-field" 
            placeholder="Email"
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
            required
          >
        </div>

        <div class="form-group">
          <div class="password-header">
            <label class="form-label">Password</label>
            <a href="#" class="forgot-link">Forgot password?</a>
          </div>
          <div class="password-wrapper">
            <input 
              id="login-password" 
              type="password" 
              name="password" 
              class="password-field" 
              placeholder="Password"
              required
            >
            <button 
              type="button" 
              class="toggle-btn" 
              onclick="togglePassword(this, 'login-password')"
            >Show</button>
          </div>
        </div>

        <button type="submit" name="login" class="auth-button">
          Sign In
        </button>

        <div class="auth-footer">
          Don't have an account?
          <a href="register.php">Sign up Now</a>
        </div>
        
        <a href="index.php" class="back-home">← Back to Home</a>
      </form>
    </div>
  </div>
  <script src="assets/app.js"></script>
</body>
</html>