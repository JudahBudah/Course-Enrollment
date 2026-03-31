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

    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Softdev/src/uploads/faculty/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    // Delete old photo
    $old = mysqli_fetch_assoc(mysqli_query($con, "SELECT profile_photo FROM faculty WHERE faculty_id=$faculty_id"));
    if (!empty($old['profile_photo'])) {
        $old_path = $_SERVER['DOCUMENT_ROOT'] . '/Softdev/src/' . $old['profile_photo'];
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

    $fields = [
        'first_name','middle_name','last_name','suffix_name',
        'date_of_birth','place_of_birth','sex','civil_status',
        'religion','nationality','disability',
        'phone','personal_email','college','department','position','employment_status',
        'permanent_region','permanent_province','permanent_municipality',
        'permanent_barangay','permanent_address','permanent_zip_code',
        'mailing_same_as_permanent',
        'mailing_region','mailing_province','mailing_municipality',
        'mailing_barangay','mailing_address','mailing_zip_code',
    ];

    $set_parts = [];
    $types = '';
    $values = [];
    foreach ($fields as $f) {
        $val = isset($_POST[$f]) ? trim($_POST[$f]) : null;
        if ($f === 'mailing_same_as_permanent') $val = $val ? 1 : 0;
        if ($val === '') $val = null;
        $set_parts[] = "$f = ?";
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

echo json_encode(['ok'=>false,'msg'=>'Unknown action']);
?>
