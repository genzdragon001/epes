<?php
/**
 * First-Login Setup: Password Change + Data Privacy Consent
 * Redirected here from index.php when password_changed=0 or privacy_accepted=0
 */
session_start();
include 'db_connect.php';

// Must be logged in
if (!isset($_SESSION['login_id'])) {
    header('location:login.php');
    exit;
}

$login_id   = $_SESSION['login_id'];
$login_type = $_SESSION['login_type'] ?? -1;

// Determine which table
$tables = ['employee_list', 'evaluator_list', 'users'];
$table  = $tables[$login_type] ?? 'employee_list';

// Fetch current user flags
$stmt = $conn->prepare("SELECT id, password_changed, privacy_accepted, firstname, lastname FROM {$table} WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $login_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('location:login.php');
    exit;
}

$needs_password = ($user['password_changed'] == 0);
$needs_privacy  = ($user['privacy_accepted'] == 0);

// If both already done, redirect to home
if (!$needs_password && !$needs_privacy) {
    header('location:index.php?page=home');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'setup') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $accept_privacy = isset($_POST['accept_privacy']) && $_POST['accept_privacy'] == '1';

        // Validate password
        if (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif ($needs_privacy && !$accept_privacy) {
            $error = 'You must accept the Data Privacy Agreement to continue.';
        } else {
            // Hash password with bcrypt
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            // Update user
            $updates = [];
            $params = [];
            $types = '';

            if ($needs_password) {
                $updates[] = "password = ?";
                $updates[] = "password_changed = 1";
                $params[] = $hashed;
                $types .= 's';
            }
            if ($needs_privacy && $accept_privacy) {
                $updates[] = "privacy_accepted = 1";
                $updates[] = "privacy_accepted_at = NOW()";
            }

            $params[] = $login_id;
            $types .= 'i';

            $sql = "UPDATE {$table} SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();

            // Update session flags
            $_SESSION['password_changed'] = 1;
            $_SESSION['privacy_accepted'] = 1;

            $success = 'Setup complete! Redirecting to dashboard...';
            header("Refresh: 2; URL=index.php?page=home");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setup — EPES</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .setup-card { max-width: 550px; width: 100%; border-radius: 12px; }
        .card-header { border-radius: 12px 12px 0 0 !important; }
        .step-indicator { display: flex; gap: 10px; margin-bottom: 20px; }
        .step { flex: 1; text-align: center; padding: 8px; border-radius: 6px; font-size: 0.85rem; }
        .step.active { background: #007bff; color: #fff; }
        .step.done { background: #28a745; color: #fff; }
        .step.pending { background: #e9ecef; color: #6c757d; }
    </style>
</head>
<body>
<div class="card setup-card shadow-lg">
    <div class="card-header bg-primary text-white text-center py-3">
        <h4 class="mb-0"><i class="fa fa-user-cog"></i> Account Setup</h4>
        <small>Welcome, <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></small>
    </div>
    <div class="card-body p-4">
        
        <?php if ($success): ?>
            <div class="alert alert-success text-center">
                <i class="fa fa-check-circle fa-3x mb-2"></i>
                <h5><?= $success ?></h5>
            </div>
        <?php else: ?>
        
        <!-- Step indicators -->
        <div class="step-indicator">
            <div class="step <?= $needs_password ? 'active' : 'done' ?>">
                <i class="fa fa-lock"></i> Change Password
            </div>
            <div class="step <?= !$needs_password && $needs_privacy ? 'active' : (!$needs_privacy ? 'done' : 'pending') ?>">
                <i class="fa fa-shield-alt"></i> Privacy Consent
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="setup">

            <?php if ($needs_password): ?>
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fa fa-key"></i> New Password
                </label>
                <input type="password" name="new_password" class="form-control" 
                       placeholder="Minimum 8 characters" required minlength="8"
                       autocomplete="new-password">
                <small class="text-muted">For your security, please choose a strong password.</small>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fa fa-check-double"></i> Confirm Password
                </label>
                <input type="password" name="confirm_password" class="form-control" 
                       placeholder="Re-enter your password" required minlength="8"
                       autocomplete="new-password">
            </div>
            <?php endif; ?>

            <?php if ($needs_privacy): ?>
            <div class="card bg-light mb-3">
                <div class="card-body" style="max-height: 200px; overflow-y: auto; font-size: 0.85rem;">
                    <h6 class="fw-bold"><i class="fa fa-shield-alt"></i> Data Privacy Agreement</h6>
                    <p>In compliance with the <strong>Data Privacy Act of 2012 (RA 10173)</strong>, 
                    the EPES System collects and processes your personal and performance data 
                    for the purpose of faculty evaluation and institutional reporting.</p>
                    <p><strong>What we collect:</strong> Your name, email, position, department, 
                    performance ratings, submitted documents, and evaluation records.</p>
                    <p><strong>How we use it:</strong> Data is used exclusively for performance 
                    evaluation, IPCR/OPCR generation, and institutional analytics. Your data 
                    will not be shared with unauthorized third parties.</p>
                    <p><strong>Your rights:</strong> You may request access to your data, 
                    correction of inaccuracies, or deletion of records by contacting the 
                    system administrator.</p>
                    <p>By clicking "I Accept" below, you consent to the collection and 
                    processing of your data as described.</p>
                </div>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="accept_privacy" id="accept_privacy" 
                       class="form-check-input" value="1" required>
                <label class="form-check-label" for="accept_privacy">
                    <strong>I have read and accept the Data Privacy Agreement</strong>
                </label>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary w-100 btn-lg">
                <i class="fa fa-check-circle"></i> Complete Setup
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
