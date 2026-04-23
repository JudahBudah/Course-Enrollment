<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);
$user_id   = $user_data['student_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/student/student_account.php");
    die;
}

// ── Change password ──────────────────────────────────────────────────────────
if (($_POST['action'] ?? '') === 'change_password') {
    header('Content-Type: application/json');
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 6) {
        echo json_encode(['ok'=>false,'msg'=>'Password must be at least 6 characters.']); die;
    }
    if ($new !== $confirm) {
        echo json_encode(['ok'=>false,'msg'=>'Passwords do not match.']); die;
    }

    $forced = !empty($_SESSION['must_change_password']);
    if (!$forced) {
        $current = $_POST['current_password'] ?? '';
        $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT password FROM students WHERE student_id=$user_id"));
        if (!password_verify($current, $row['password'])) {
            echo json_encode(['ok'=>false,'msg'=>'Current password is incorrect.']); die;
        }
    }

    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($con, "UPDATE students SET password=?, must_change_password=0 WHERE student_id=?");
    mysqli_stmt_bind_param($stmt, 'si', $hashed, $user_id);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) unset($_SESSION['must_change_password']);
    echo json_encode(['ok'=>$ok]);
    die;
}

$profile_path = null;
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
    $upload_dir = "../uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $new_name = uniqid() . "_" . basename($_FILES['profile_photo']['name']);
    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_dir . $new_name)) {
        $profile_path = "uploads/" . $new_name;
    }
}

// Photo-only update (profile picture change)
if (!empty($_POST['photo_only']) && $profile_path) {
    $stmt = mysqli_prepare($con, "UPDATE students SET profile_photo=? WHERE student_id=?");
    mysqli_stmt_bind_param($stmt, "si", $profile_path, $user_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/student/student_account.php?msg=Profile photo updated");
    die;
}

$first_name       = $_POST['first_name']       ?? '';
$last_name        = $_POST['last_name']        ?? '';
$middle_name      = $_POST['middle_name']      ?? '';
$suffix_name      = $_POST['suffix_name']      ?? '';
$lrn              = $_POST['lrn']              ?? '';
$email            = $_POST['email']            ?? '';
$contact          = $_POST['contact-no']       ?? '';
$birth            = $_POST['birth']            ?? '';
$birth_place      = $_POST['birth-place']      ?? '';
$sex              = $_POST['sex']              ?? '';
$civil_status     = $_POST['civil-status']     ?? '';
$religion         = $_POST['religion']         ?? '';
$nationality      = $_POST['nationality']      ?? '';
$disability       = $_POST['disability']       ?? '';
$perm_region      = $_POST['perm_region']      ?? '';
$perm_province    = $_POST['perm_province']    ?? '';
$perm_municipality= $_POST['perm_municipality']?? '';
$perm_barangay    = $_POST['perm_barangay']    ?? '';
$perm_address     = $_POST['perm_address']     ?? '';
$perm_zipcode     = $_POST['perm_zipcode']     ?? '';
$mail_region      = $_POST['mail_region']      ?? '';
$mail_province    = $_POST['mail_province']    ?? '';
$mail_municipality= $_POST['mail_municipality']?? '';
$mail_barangay    = $_POST['mail_barangay']    ?? '';
$mail_address     = $_POST['mail_address']     ?? '';
$mail_zipcode     = $_POST['mail_zipcode']     ?? '';

$sql = "UPDATE students SET
    first_name=?, last_name=?, middle_name=?, suffix_name=?, lrn=?,
    email=?, contact_number=?, birthdate=?, place_of_birth=?, gender=?,
    civil_status=?, religion=?, nationality=?, disability=?,
    perm_region=?, perm_province=?, perm_municipality=?, perm_barangay=?, perm_address=?, perm_zipcode=?,
    mail_region=?, mail_province=?, mail_municipality=?, mail_barangay=?, mail_address=?, mail_zipcode=?"
    . ($profile_path ? ", profile_photo=?" : "") .
    " WHERE student_id=?";

$stmt = mysqli_prepare($con, $sql);

if ($profile_path) {
    mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssssssssssi",
        $first_name, $last_name, $middle_name, $suffix_name, $lrn,
        $email, $contact, $birth, $birth_place, $sex,
        $civil_status, $religion, $nationality, $disability,
        $perm_region, $perm_province, $perm_municipality, $perm_barangay, $perm_address, $perm_zipcode,
        $mail_region, $mail_province, $mail_municipality, $mail_barangay, $mail_address, $mail_zipcode,
        $profile_path, $user_id
    );
} else {
    mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssssssssi",
        $first_name, $last_name, $middle_name, $suffix_name, $lrn,
        $email, $contact, $birth, $birth_place, $sex,
        $civil_status, $religion, $nationality, $disability,
        $perm_region, $perm_province, $perm_municipality, $perm_barangay, $perm_address, $perm_zipcode,
        $mail_region, $mail_province, $mail_municipality, $mail_barangay, $mail_address, $mail_zipcode,
        $user_id
    );
}

mysqli_stmt_execute($stmt);

header("Location: ../pages/student/student_account.php?msg=Profile updated successfully");
die;
