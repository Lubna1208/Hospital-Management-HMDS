<?php
session_start();
include "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PISD — Hospital Management System</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-landing">
<div class="container">

  <nav class="nav">
    <div class="brand">
      <div class="logo">🏥</div>
      <div class="brand-text">
        <span class="brand-name">PISD</span>
        <span class="brand-sub">Medical Center</span>
      </div>
    </div>

    <div class="links">
      <a href="#features">Services</a>
      <a href="#about">About</a>
    </div>

    <div class="actions">
      <?php if(isset($_SESSION["user_email"])): ?>
        <span style="color: var(--primary-600); font-weight: 600; margin-right: 8px;"><?php echo htmlspecialchars($_SESSION["user_name"] ?? 'User'); ?></span>
        <a class="btn" href="logout.php">Logout</a>
      <?php else: ?>
        <a class="btn" href="login.php">Login</a>
        <a class="btn primary" href="register.php">Register</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="hero-section">
    <div class="hero-content">
      <span class="badge">Serving Health Since 2005</span>
      
      <h1>PISD Medical Center<br>Patient Management System</h1>
      
      <p class="hero-description">
        Secure, integrated platform for managing patient records, doctor schedules, 
        and appointments. Trusted by over 200 healthcare professionals.
      </p>
      
      <div class="cta-buttons">
        <?php if(isset($_SESSION["user_email"])): ?>
          <a class="btn primary" href="#features">Dashboard</a>
        <?php else: ?>
          <a class="btn primary" href="register.php">Register</a>
          <a class="btn" href="login.php">Login</a>
        <?php endif; ?>
      </div>
      
      <div class="stats">
        <div class="stat-item">
          <span class="stat-number">25,000+</span>
          <span class="stat-label">Patients Yearly</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">120+</span>
          <span class="stat-label">Specialist Doctors</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">24/7</span>
          <span class="stat-label">Emergency Care</span>
        </div>
      </div>
      
      <?php if(isset($_SESSION["user_email"])): ?>
        <div class="logged-in-badge">
          Logged in as <?php echo htmlspecialchars($_SESSION["user_email"]); ?>
        </div>
      <?php endif; ?>
    </div>
    
    <div class="image-collage">
      <div class="collage-grid">
        <div class="collage-item img1">
          <img src="https://images.pexels.com/photos/247786/pexels-photo-247786.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Modern hospital building">
        </div>
        <div class="collage-item img2">
          <img src="https://images.pexels.com/photos/8376252/pexels-photo-8376252.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Doctor with patient">
        </div>
        <div class="collage-item img3">
          <img src="https://images.pexels.com/photos/7659564/pexels-photo-7659564.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Medical equipment">
        </div>
        <div class="collage-item img4">
          <img src="https://images.pexels.com/photos/7089632/pexels-photo-7089632.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Surgery room">
        </div>
        <div class="collage-item img5">
          <img src="https://images.pexels.com/photos/4386467/pexels-photo-4386467.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Medical team">
        </div>
      </div>
    </div>
  </div>

  <div id="features" style="margin-top: 80px;">
    <h2 class="section-title">Our Medical Services</h2>
    <p class="section-subtitle">
      Comprehensive healthcare management solutions for our medical staff
    </p>
    
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">📋</div>
        <h3>Patient Records</h3>
        <p>Secure, centralized patient information with instant access to medical history, prescriptions, and treatment plans.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">👨‍⚕️</div>
        <h3>Doctor Scheduling</h3>
        <p>Efficient duty scheduling for specialists across departments with real-time availability.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📅</div>
        <h3>Appointment System</h3>
        <p>Streamlined OPD appointment booking with automated reminders and queue management.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💊</div>
        <h3>Pharmacy Integration</h3>
        <p>In-house pharmacy inventory management with prescription integration and expiry tracking.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔬</div>
        <h3>Diagnostic Reports</h3>
        <p>Digital lab report delivery directly to patient records with secure access.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💰</div>
        <h3>Billing System</h3>
        <p>Integrated billing for consultations, diagnostics, and procedures.</p>
      </div>
    </div>
  </div>

  <div id="about" style="margin-top: 80px; background: white; padding: 48px; border-radius: 32px; border: 1px solid var(--line);">
    <h2 class="section-title" style="margin-bottom: 16px;">About PISD Medical Center</h2>
    <p style="font-size: 18px; color: var(--muted); line-height: 1.7; margin-bottom: 24px;">
      PISD Medical Center has been a trusted healthcare provider since 2005, committed to delivering quality medical care with compassion and excellence. We have grown into a multi-specialty hospital serving thousands of patients annually from our community and surrounding regions.
    </p>
    <p style="font-size: 18px; color: var(--muted); line-height: 1.7; margin-bottom: 24px;">
      Our Patient Management System is designed to digitize and streamline hospital operations—from patient registration and doctor scheduling to pharmacy management and diagnostic reporting. This secure platform ensures that our medical staff can focus on what matters most: patient care.
    </p>
    <p style="font-size: 18px; color: var(--muted); line-height: 1.7;">
      With 24/7 emergency services, 120+ specialist doctors, and modern facilities, PISD Medical Center continues its legacy of compassionate, excellence-driven healthcare.
    </p>
  </div>

  <footer class="footer">
    <p>© <?php echo date("Y"); ?> PISD Medical Center — Patient Management System. All rights reserved.</p>
    <p style="margin-top: 8px; font-size: 13px;">123 Healthcare Avenue, Medical District, PISD 12345 | Tel: +1-555-789-0123</p>
  </footer>

</div>
<script src="assets/app.js"></script>
</body>
</html>