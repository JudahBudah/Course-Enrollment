<?php
session_start();
include("connection.php");

if (!isset($_SESSION['faculty_id'])) {
    http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Unauthorized']); die;
}
$faculty_id = (int)$_SESSION['faculty_id'];
$action = $_POST['action'] ?? '';

// ── Upload profile photo ─────────────────────────────────────────────────────
if ($action === 'upload_photo') {
    header('Content-Type: application/json');
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok'=>false,'msg'=>'No file received.']); die;
    }
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo json_encode(['ok'=>false,'msg'=>'Invalid file type.']); die;
    }
    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['ok'=>false,'msg'=>'File too large (max 5MB).']); die;
    }

    $upload_dir = __DIR__ . '/../uploads/faculty/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    // Delete old photo
    $old = mysqli_fetch_assoc(mysqli_query($con, "SELECT profile_photo FROM faculty WHERE faculty_id=$faculty_id"));
    if (!empty($old['profile_photo'])) {
        $old_path = __DIR__ . '/../' . $old['profile_photo'];
        if (file_exists($old_path)) @unlink($old_path);
    }

    $filename = 'faculty_' . $faculty_id . '_' . time() . '.' . $ext;
    $dest = $upload_dir . $filename;
    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
        echo json_encode(['ok'=>false,'msg'=>'Upload failed.']); die;
    }

    $rel = 'uploads/faculty/' . $filename;
    $stmt = mysqli_prepare($con, "UPDATE faculty SET profile_photo=? WHERE faculty_id=?");
    mysqli_stmt_bind_param($stmt, 'si', $rel, $faculty_id);
    mysqli_stmt_execute($stmt);

    echo json_encode(['ok'=>true,'path'=>'../../'.$rel]);
    die;
}

// ── Save profile info ────────────────────────────────────────────────────────
if ($action === 'save_profile') {
    header('Content-Type: application/json');

    // Ensure new columns exist before saving
    $ensure_cols = [
        'emergency_name'         => "VARCHAR(150) DEFAULT NULL",
        'emergency_relationship' => "VARCHAR(100) DEFAULT NULL",
        'emergency_phone'        => "VARCHAR(20)  DEFAULT NULL",
        'emergency_address'      => "VARCHAR(255) DEFAULT NULL",
        'highest_education'      => "VARCHAR(150) DEFAULT NULL",
        'degree'                 => "VARCHAR(150) DEFAULT NULL",
        'school'                 => "VARCHAR(200) DEFAULT NULL",
        'year_graduated'         => "VARCHAR(10)  DEFAULT NULL",
    ];
    $col_res = mysqli_query($con, "SHOW COLUMNS FROM faculty");
    $existing_cols = [];
    while ($c = mysqli_fetch_assoc($col_res)) $existing_cols[] = $c['Field'];
    foreach ($ensure_cols as $col => $def) {
        if (!in_array($col, $existing_cols)) {
            mysqli_query($con, "ALTER TABLE faculty ADD COLUMN `$col` $def");
        }
    }

    // Faculty can only update their own personal/address/emergency fields
    // Admin-assigned fields (college, department, position, employment_status, email) are excluded
    $fields = [
        'first_name','middle_name','last_name','suffix_name',
        'date_of_birth','place_of_birth','sex','civil_status',
        'religion','nationality','disability',
        'phone','personal_email',
        'permanent_region','permanent_province','permanent_municipality',
        'permanent_barangay','permanent_address','permanent_zip_code',
        'mailing_same_as_permanent',
        'mailing_region','mailing_province','mailing_municipality',
        'mailing_barangay','mailing_address','mailing_zip_code',
        'emergency_name','emergency_relationship','emergency_phone','emergency_address',
    ];

    // Only include fields that actually exist in the table
    $col_res2 = mysqli_query($con, "SHOW COLUMNS FROM faculty");
    $existing_cols2 = [];
    while ($c = mysqli_fetch_assoc($col_res2)) $existing_cols2[] = $c['Field'];
    $fields = array_filter($fields, fn($f) => in_array($f, $existing_cols2) || $f === 'mailing_same_as_permanent');

    $set_parts = [];
    $types = '';
    $values = [];
    foreach ($fields as $f) {
        $val = isset($_POST[$f]) ? trim($_POST[$f]) : null;
        if ($f === 'mailing_same_as_permanent') $val = $val ? 1 : 0;
        if ($val === '') $val = null;
        $set_parts[] = "`$f` = ?";
        $types .= ($f === 'mailing_same_as_permanent') ? 'i' : 's';
        $values[] = $val;
    }
    $types .= 'i';
    $values[] = $faculty_id;

    $stmt = mysqli_prepare($con, "UPDATE faculty SET " . implode(', ', $set_parts) . " WHERE faculty_id = ?");
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    $ok = mysqli_stmt_execute($stmt);

    echo json_encode(['ok'=>$ok, 'msg'=> $ok ? 'Saved.' : mysqli_error($con)]);
    die;
}

// ── Change password ─────────────────────────────────────────────────────────
if ($action === 'change_password') {
    header('Content-Type: application/json');
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 6) {
        echo json_encode(['ok'=>false,'msg'=>'Password must be at least 6 characters.']); die;
    }
    if ($new !== $confirm) {
        echo json_encode(['ok'=>false,'msg'=>'Passwords do not match.']); die;
    }

    $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT password FROM faculty WHERE faculty_id=$faculty_id"));

    // Only verify current password when NOT a forced first-login change
    if (empty($_SESSION['must_change_password'])) {
        if (!password_verify($current, $row['password'])) {
            echo json_encode(['ok'=>false,'msg'=>'Current password is incorrect.']); die;
        }
    }

    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($con, "UPDATE faculty SET password=? WHERE faculty_id=?");
    mysqli_stmt_bind_param($stmt, 'si', $hashed, $faculty_id);
    $ok = mysqli_stmt_execute($stmt);

    if ($ok) unset($_SESSION['must_change_password']);

    echo json_encode(['ok'=>$ok, 'msg'=> $ok ? 'Password changed successfully.' : mysqli_error($con)]);
    die;
}

echo json_encode(['ok'=>false,'msg'=>'Unknown action']);
?>
