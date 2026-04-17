<?php
session_start();
include("../php/connection.php");
include("../php/mailer.php");

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// ── STEP 1: Send reset code ──────────────────────────────
if ($action === 'send_reset') {
    $portal     = $_POST['portal'] ?? '';
    $identifier = trim($_POST['identifier'] ?? '');

    $portals = [
        'student'   => ['table' => 'students',   'id_col' => 'student_number', 'email_col' => 'email',    'pk' => 'student_id'],
        'applicant' => ['table' => 'applicants', 'id_col' => 'email',          'email_col' => 'email',    'pk' => 'applicant_id'],
        'faculty'   => ['table' => 'faculty',    'id_col' => 'email',          'email_col' => 'email',    'pk' => 'faculty_id'],
        'admin'     => ['table' => 'admins',     'id_col' => 'username',       'email_col' => 'email',    'pk' => 'admin_id'],
    ];

    $cfg = $portals[$portal] ?? null;
    if (!$cfg || empty($identifier)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    $t      = $cfg['table'];
    $id_col = $cfg['id_col'];
    $stmt   = mysqli_prepare($con, "SELECT * FROM `$t` WHERE `$id_col` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $identifier);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Always respond success to prevent user enumeration
    if (!$user || empty($user[$cfg['email_col']])) {
        echo json_encode(['success' => true, 'message' => 'If that account exists, a code has been sent.']);
        exit;
    }

    $email   = $user[$cfg['email_col']];
    $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = time() + 600;

    $_SESSION['reset_pending'] = [
        'portal'   => $portal,
        'pk'       => $cfg['pk'],
        'user_id'  => $user[$cfg['pk']],
        'email'    => $email,
        'code'     => $code,
        'expires'  => $expires,
    ];

    $label = ucfirst($portal);
    $subject = "PLM {$label} Portal – Password Reset Code";
    $body = "
    <div style='font-family:DM Sans,sans-serif;max-width:480px;margin:0 auto;background:#0d0a07;color:#F2F3F2;padding:2rem;border-radius:8px;'>
        <div style='text-align:center;margin-bottom:1.5rem;'>
            <img src='https://upload.wikimedia.org/wikipedia/en/thumb/6/6b/Pamantasan_ng_Lungsod_ng_Maynila_logo.png/200px-Pamantasan_ng_Lungsod_ng_Maynila_logo.png' width='60' style='border-radius:50%;'>
            <h2 style='font-family:Georgia,serif;color:#D4AF37;margin:0.5rem 0 0;'>PLM {$label} Portal</h2>
        </div>
        <p style='margin-bottom:0.5rem;'>Hello,</p>
        <p style='margin-bottom:1.5rem;color:rgba(242,243,242,0.7);'>Use the code below to reset your password. It expires in <strong style='color:#F2F3F2;'>10 minutes</strong>.</p>
        <div style='text-align:center;background:rgba(212,175,55,0.1);border:1px solid rgba(212,175,55,0.3);border-radius:6px;padding:1.5rem;margin-bottom:1.5rem;'>
            <span style='font-size:2.5rem;font-weight:700;letter-spacing:0.4em;color:#D4AF37;'>{$code}</span>
        </div>
        <p style='font-size:0.8rem;color:rgba(242,243,242,0.35);text-align:center;'>If you did not request this, you can safely ignore this email.</p>
    </div>";

    mailer_send($email, $subject, $body, ['is_html' => true]);

    echo json_encode(['success' => true, 'message' => 'If that account exists, a code has been sent.']);
    exit;
}

// ── STEP 2: Verify code ──────────────────────────────────
if ($action === 'verify_reset') {
    $entered = trim($_POST['code'] ?? '');
    $pending = $_SESSION['reset_pending'] ?? null;

    if (!$pending) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
        exit;
    }
    if (time() > $pending['expires']) {
        unset($_SESSION['reset_pending']);
        echo json_encode(['success' => false, 'message' => 'Code expired. Please start over.']);
        exit;
    }
    if ($entered !== $pending['code']) {
        echo json_encode(['success' => false, 'message' => 'Incorrect code.']);
        exit;
    }

    // Mark code as verified, allow password reset
    $_SESSION['reset_pending']['verified'] = true;
    echo json_encode(['success' => true]);
    exit;
}

// ── STEP 3: Set new password ─────────────────────────────
if ($action === 'do_reset') {
    $pending  = $_SESSION['reset_pending'] ?? null;
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (!$pending || empty($pending['verified'])) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
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

    $portals = [
        'student'   => ['table' => 'students',   'pk' => 'student_id'],
        'applicant' => ['table' => 'applicants', 'pk' => 'applicant_id'],
        'faculty'   => ['table' => 'faculty',    'pk' => 'faculty_id'],
        'admin'     => ['table' => 'admins',     'pk' => 'admin_id'],
    ];

    $cfg  = $portals[$pending['portal']] ?? null;
    if (!$cfg) {
        echo json_encode(['success' => false, 'message' => 'Invalid session.']);
        exit;
    }

    $t   = $cfg['table'];
    $pk  = $cfg['pk'];
    $uid = $pending['user_id'];

    // Check if new password is the same as the current one
    $chk = mysqli_prepare($con, "SELECT password FROM `$t` WHERE `$pk` = ? LIMIT 1");
    mysqli_stmt_bind_param($chk, "i", $uid);
    mysqli_stmt_execute($chk);
    $cur = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
    if ($cur && password_verify($password, $cur['password'])) {
        echo json_encode(['success' => false, 'message' => 'You cannot use your old password. Please choose a different one.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($con, "UPDATE `$t` SET password = ? WHERE `$pk` = ?");
    mysqli_stmt_bind_param($stmt, "si", $hash, $uid);
    mysqli_stmt_execute($stmt);

    unset($_SESSION['reset_pending']);

    echo json_encode(['success' => true, 'portal' => $pending['portal']]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
