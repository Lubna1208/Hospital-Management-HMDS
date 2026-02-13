<?php
session_start();
include "db.php";

$error = "";
$success = "";

if(isset($_POST["register"])) {
  $name = trim($_POST["name"]);
  $email = trim($_POST["email"]);
  $password = $_POST["password"];
  $confirm_password = $_POST["confirm_password"];

  if($name === "" || $email === "" || $password === "" || $confirm_password === "") {
    $error = "All fields are required.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email address.";
  } elseif (strlen($password) < 8) {
    $error = "Password must be at least 8 characters.";
  } elseif (!preg_match("/[A-Z]/", $password)) {
    $error = "Password must contain at least one uppercase letter.";
  } elseif (!preg_match("/[a-z]/", $password)) {
    $error = "Password must contain at least one lowercase letter.";
  } elseif (!preg_match("/[0-9]/", $password)) {
    $error = "Password must contain at least one number.";
  } elseif (!preg_match("/[!@#$%^&*]/", $password)) {
    $error = "Password must contain at least one special character (!@#$%^&*).";
  } elseif ($password !== $confirm_password) {
    $error = "Passwords do not match.";
  } else {
    $check_sql = "SELECT TOP 1 id FROM dbo.users WHERE email = ?";
    $check_stmt = sqlsrv_query($conn, $check_sql, [$email]);
    
    if ($check_stmt && sqlsrv_has_rows($check_stmt)) {
      $error = "This email is already registered. Please login.";
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $sql = "INSERT INTO dbo.users (name, email, password_hash) VALUES (?, ?, ?)";
      $stmt = sqlsrv_query($conn, $sql, [$name, $email, $hash]);

      if($stmt) {
        $success = "Account created successfully! Redirecting to login...";
        header("refresh:2; url=login.php");
      } else {
        $error = "Registration failed. Please try again.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — PISD Medical Center</title>
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
      
      <?php if($success !== ""): ?>
        <div style="margin-bottom: 20px; padding: 12px 16px; border-radius: 12px; background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2); color: #166534; font-size: 14px; font-weight: 600;">
          <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input 
            type="text" 
            name="name" 
            class="form-field" 
            placeholder="Full Name"
            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
            required
          >
        </div>

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
          <label class="form-label">Password</label>
          <div class="password-wrapper">
            <input 
              id="register-password" 
              type="password" 
              name="password" 
              class="password-field" 
              placeholder="Password"
              required
            >
            <button 
              type="button" 
              class="toggle-btn" 
              onclick="togglePassword(this, 'register-password')"
            >Show</button>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="password-wrapper">
            <input 
              id="register-confirm-password" 
              type="password" 
              name="confirm_password" 
              class="password-field" 
              placeholder="Confirm Password"
              required
            >
            <button 
              type="button" 
              class="toggle-btn" 
              onclick="togglePassword(this, 'register-confirm-password')"
            >Show</button>
          </div>
        </div>

        <button type="submit" name="register" class="auth-button">
          Sign Up
        </button>

        <div class="auth-footer">
          Already have an account?
          <a href="login.php">Login Now</a>
        </div>
        
        <a href="index.php" class="back-home">← Back to Home</a>
      </form>
    </div>
  </div>
  <script src="assets/app.js"></script>
</body>
</html>