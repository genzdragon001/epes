<?php
session_start();
include './db_connect.php';
include 'csrf_helper.php';

// Load system settings
$system = $conn->query("SELECT * FROM system_settings")->fetch_array();
foreach ($system as $k => $v) {
    $_SESSION['system'][$k] = $v;
}

// If already logged in, go straight to dashboard
if (isset($_SESSION['login_id'])) {
    header("location:index.php?page=home");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<body class="hold-transition landing-page">
<style>
  /* ── Landing Page Styles ── */
  .landing-page {
    background: #1a1a2e;
    color: #e0e0e0;
    font-family: 'Source Sans Pro', sans-serif;
    overflow-x: hidden;
  }

  /* Hero Section */
  .hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%);
    position: relative;
    overflow: hidden;
  }
  .hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 60%);
    animation: pulse-bg 8s ease-in-out infinite;
  }
  @keyframes pulse-bg {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
  }
  .hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    padding: 2rem;
  }
  .hero-content h1 {
    font-size: 2.8rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 1rem;
    letter-spacing: -0.5px;
  }
  .hero-content .tagline {
    font-size: 1.15rem;
    color: rgba(255,255,255,0.85);
    line-height: 1.6;
    margin-bottom: 2.5rem;
    max-width: 650px;
    margin-left: auto;
    margin-right: auto;
  }
  .hero-buttons .btn {
    padding: 0.7rem 2.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 50px;
    margin: 0 0.5rem;
  }
  .btn-landing-login {
    background: #fff;
    color: #495057;
    border: 2px solid #fff;
  }
  .btn-landing-login:hover {
    background: #f8f9fa;
    color: #1a1a2e;
    border-color: #f8f9fa;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  }
  .btn-landing-register {
    background: transparent;
    color: #fff;
    border: 2px solid rgba(255,255,255,0.6);
  }
  .btn-landing-register:hover {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border-color: #fff;
  }
  .hero-scroll {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(255,255,255,0.5);
    font-size: 1.5rem;
    z-index: 2;
    animation: bounce 2s infinite;
  }
  @keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
    40% { transform: translateX(-50%) translateY(-10px); }
    60% { transform: translateX(-50%) translateY(-5px); }
  }

  /* Section Headers */
  .section-header {
    text-align: center;
    margin-bottom: 3rem;
  }
  .section-header h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.5rem;
  }
  .section-header p {
    color: rgba(255,255,255,0.5);
    font-size: 1rem;
  }

  /* Features Section */
  .features-section {
    padding: 5rem 2rem;
    background: #1a1a2e;
  }
  .feature-card {
    background: #16213e;
    border-radius: 12px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    height: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid rgba(255,255,255,0.05);
  }
  .feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 30px rgba(23,162,184,0.15);
    border-color: rgba(23,162,184,0.3);
  }
  .feature-card .icon-wrap {
    width: 70px;
    height: 70px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #fff;
  }
  .icon-cyan    { background: linear-gradient(135deg, #17a2b8, #0e8a9e); }
  .icon-purple  { background: linear-gradient(135deg, #6610f2, #520dc2); }
  .icon-blue    { background: linear-gradient(135deg, #3b82f6, #2563eb); }
  .icon-teal    { background: linear-gradient(135deg, #20c997, #15a36b); }
  .feature-card h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.8rem;
  }
  .feature-card p {
    color: rgba(255,255,255,0.6);
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
  }

  /* Roles Section */
  .roles-section {
    padding: 5rem 2rem;
    background: #16213e;
  }
  .role-card {
    background: #1a1a2e;
    border-radius: 12px;
    padding: 2rem 1.5rem;
    text-align: center;
    height: 100%;
    border: 1px solid rgba(255,255,255,0.05);
    transition: transform 0.3s ease;
  }
  .role-card:hover {
    transform: translateY(-5px);
    border-color: rgba(102,16,242,0.3);
  }
  .role-card .role-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
  }
  .role-card h3 {
    font-size: 1.15rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.6rem;
  }
  .role-card p {
    color: rgba(255,255,255,0.5);
    font-size: 0.92rem;
    line-height: 1.6;
    margin: 0;
  }

  /* CTA Section */
  .cta-section {
    padding: 5rem 2rem;
    text-align: center;
    background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%);
  }
  .cta-section h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 1rem;
  }
  .cta-section p {
    color: rgba(255,255,255,0.8);
    font-size: 1.05rem;
    margin-bottom: 2rem;
  }

  /* Footer */
  .landing-footer {
    background: #0d0d1a;
    padding: 2.5rem 2rem 1.5rem;
    text-align: center;
  }
  .landing-footer .footer-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.3rem;
  }
  .landing-footer .footer-sub {
    color: rgba(255,255,255,0.4);
    font-size: 0.85rem;
    margin-bottom: 1rem;
  }
  .landing-footer .footer-links {
    margin-bottom: 1rem;
  }
  .landing-footer .footer-links a {
    color: rgba(255,255,255,0.6);
    font-size: 0.9rem;
    margin: 0 0.8rem;
    text-decoration: none;
    transition: color 0.2s ease;
  }
  .landing-footer .footer-links a:hover {
    color: #17a2b8;
  }
  .landing-footer .copyright {
    color: rgba(255,255,255,0.3);
    font-size: 0.82rem;
    margin: 0;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .hero-content h1 { font-size: 2rem; }
    .hero-content .tagline { font-size: 1rem; }
    .hero-buttons .btn { display: block; margin: 0.5rem auto; max-width: 280px; }
    .section-header h2 { font-size: 1.6rem; }
  }
</style>

<!-- ════════════ HERO ════════════ -->
<section class="hero">
  <div class="hero-content">
    <h1><?php echo htmlspecialchars($_SESSION['system']['name']); ?></h1>
    <p class="tagline">
      A Faculty Performance Documentation and Evaluation System for DEBESMSCAT,
      supporting evidence-based decision-making through automated IPCR/OPCR
      documentation, evaluation, and reporting.
    </p>
    <div class="hero-buttons">
      <a href="login" class="btn btn-landing-login">
        <i class="fas fa-sign-in-alt mr-2"></i> Login
      </a>
      <a href="register" class="btn btn-landing-register">
        <i class="fas fa-user-plus mr-2"></i> Register
      </a>
    </div>
  </div>
  <a href="#features" class="hero-scroll">
    <i class="fas fa-chevron-down"></i>
  </a>
</section>

<!-- ════════════ FEATURES ════════════ -->
<section class="features-section" id="features">
  <div class="container">
    <div class="section-header">
      <h2>System Features</h2>
      <p>Built to streamline faculty performance documentation and evaluation</p>
    </div>
    <div class="row">
      <div class="col-md-6 col-lg-3 mb-4">
        <div class="feature-card">
          <div class="icon-wrap icon-cyan">
            <i class="fas fa-tachometer-alt"></i>
          </div>
          <h3>Performance Evaluation</h3>
          <p>Automated computation of faculty performance ratings based on weighted institutional criteria aligned with SPMS and IPCR standards.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3 mb-4">
        <div class="feature-card">
          <div class="icon-wrap icon-purple">
            <i class="fas fa-file-alt"></i>
          </div>
          <h3>IPCR / OPCR / DPCR Reports</h3>
          <p>Digital encoding, submission, and consolidation of IPCR, OPCR, and DPCR forms with exportable performance summaries.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3 mb-4">
        <div class="feature-card">
          <div class="icon-wrap icon-blue">
            <i class="fas fa-folder-open"></i>
          </div>
          <h3>Document Archive</h3>
          <p>Stores generated reports and institutional summaries for compliance, audit, and historical tracking of faculty performance.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3 mb-4">
        <div class="feature-card">
          <div class="icon-wrap icon-teal">
            <i class="fas fa-chart-bar"></i>
          </div>
          <h3>Analytics &amp; Trends</h3>
          <p>Real-time dashboards and performance summaries to support evidence-based academic planning and informed decision-making.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════ ROLES ════════════ -->
<section class="roles-section">
  <div class="container">
    <div class="section-header">
      <h2>Who Uses the System</h2>
      <p>Role-based access for faculty, evaluators, and administrators</p>
    </div>
    <div class="row">
      <div class="col-md-4 mb-4">
        <div class="role-card">
          <div class="role-icon" style="color: #17a2b8;">
            <i class="fas fa-chalkboard-teacher"></i>
          </div>
          <h3>Faculty</h3>
          <p>Document achievements, submit IPCR forms, and track evaluation status in real-time across defined performance periods.</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="role-card">
          <div class="role-icon" style="color: #6610f2;">
            <i class="fas fa-user-tie"></i>
          </div>
          <h3>Dean / Evaluator</h3>
          <p>Evaluate faculty performance, consolidate reports, and access reliable data for promotions, workload distribution, and development programs.</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="role-card">
          <div class="role-icon" style="color: #20c997;">
            <i class="fas fa-user-shield"></i>
          </div>
          <h3>Administrator</h3>
          <p>Manage user accounts, configure evaluation periods, set criteria weights, and monitor submission activity with centralized oversight.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════ CTA ════════════ -->
<section class="cta-section">
  <h2>Ready to Get Started?</h2>
  <p>Access the system to document, evaluate, and generate performance reports.</p>
  <a href="login" class="btn btn-landing-login">
    <i class="fas fa-sign-in-alt mr-2"></i> Login to the System
  </a>
</section>

<!-- ════════════ FOOTER ════════════ -->
<footer class="landing-footer">
  <div class="footer-name"><?php echo htmlspecialchars($_SESSION['system']['name']); ?></div>
  <div class="footer-sub">DEBESMSCAT &middot; Version <?php echo EPES_VERSION; ?></div>
  <div class="footer-links">
    <a href="login">Login</a>
    <a href="register">Register</a>
    <a href="forgot_password">Forgot Password</a>
  </div>
  <p class="copyright">
    &copy; <?php echo date('Y'); ?> Dr. Emilio B. Espinosa Sr. Memorial State College of Agriculture and Technology. All rights reserved.
  </p>
</footer>

<?php include 'footer.php'; ?>
</body>
</html>