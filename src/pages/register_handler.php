<?php
session_start();
include("../php/connection.php");
include("../php/mailer.php");

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// ── STEP 1: Send verification code ──────────────────────
if ($action === 'send_code') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($password) || empty($confirm)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    if ($password !== $confirm) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    // Check if email already exists
    $stmt = mysqli_prepare($con, "SELECT applicant_id FROM applicants WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered. Please sign in instead.']);
        exit;
    }

    // Generate 6-digit code
    $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = time() + 600; // 10 minutes

    // Store in session
    $_SESSION['reg_pending'] = [
        'email'    => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'code'     => $code,
        'expires'  => $expires,
    ];

    // Send email
    $subject = 'PLM Applicant Portal – Email Verification';
    $body    = "
    <div style='font-family:DM Sans,sans-serif;max-width:480px;margin:0 auto;background:#0d0a07;color:#F2F3F2;padding:2rem;border-radius:8px;'>
        <div style='text-align:center;margin-bottom:1.5rem;'>
            <img src='https://upload.wikimedia.org/wikipedia/en/thumb/6/6b/Pamantasan_ng_Lungsod_ng_Maynila_logo.png/200px-Pamantasan_ng_Lungsod_ng_Maynila_logo.png' width='60' style='border-radius:50%;'>
            <h2 style='font-family:Georgia,serif;color:#D4AF37;margin:0.5rem 0 0;'>PLM Applicant Portal</h2>
        </div>
        <p style='margin-bottom:0.5rem;'>Hello,</p>
        <p style='margin-bottom:1.5rem;color:rgba(242,243,242,0.7);'>Use the verification code below to complete your account registration. This code expires in <strong style='color:#F2F3F2;'>10 minutes</strong>.</p>
        <div style='text-align:center;background:rgba(212,175,55,0.1);border:1px solid rgba(212,175,55,0.3);border-radius:6px;padding:1.5rem;margin-bottom:1.5rem;'>
            <span style='font-size:2.5rem;font-weight:700;letter-spacing:0.4em;color:#D4AF37;'>{$code}</span>
        </div>
        <p style='font-size:0.8rem;color:rgba(242,243,242,0.35);text-align:center;'>If you did not request this, you can safely ignore this email.</p>
    </div>";

    $sent = mailer_send($email, $subject, $body, ['is_html' => true]);

    if (!$sent) {
        echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Verification code sent to ' . htmlspecialchars($email)]);
    exit;
}

// ── STEP 2: Verify code and create account ───────────────
if ($action === 'verify_code') {
    $entered = trim($_POST['code'] ?? '');
    $pending = $_SESSION['reg_pending'] ?? null;

    if (!$pending) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
        exit;
    }

    if (time() > $pending['expires']) {
        unset($_SESSION['reg_pending']);
        echo json_encode(['success' => false, 'message' => 'Verification code expired. Please start over.']);
        exit;
    }

    if ($entered !== $pending['code']) {
        echo json_encode(['success' => false, 'message' => 'Incorrect verification code.']);
        exit;
    }

    // Create account
    $stmt = mysqli_prepare($con, "INSERT INTO applicants (email, password, application_status, created_at) VALUES (?, ?, 'incomplete', NOW())");
    mysqli_stmt_bind_param($stmt, "ss", $pending['email'], $pending['password']);

    if (!mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => false, 'message' => 'Account creation failed. Please try again.']);
        exit;
    }

    $applicant_id = mysqli_insert_id($con);
    unset($_SESSION['reg_pending']);
    $_SESSION['applicant_id'] = $applicant_id;

    echo json_encode(['success' => true, 'redirect' => 'applicants/applicant_home.php']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
