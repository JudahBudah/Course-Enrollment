<?php
session_start();
include("connection.php");
include("functions.php"); // ✅ IMPORTANT

// ✅ Get logged-in user (same as your other pages)
$user_data = check_login($con);

// If somehow not logged in
if (!$user_data) {
    die("Error: student not logged in");
}

// ✅ Get correct ID from DB (NOT from session guess)
$user_id = $user_data['student_id'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $profile_path = null;

    // ✅ FILE UPLOAD
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {

        $file_tmp = $_FILES['profile_photo']['tmp_name'];
        $file_name = $_FILES['profile_photo']['name'];

        $new_name = uniqid() . "_" . $file_name;

        $upload_dir = "../uploads/";

        // Create folder if not exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $destination = $upload_dir . $new_name;

        if (move_uploaded_file($file_tmp, $destination)) {
            $profile_path = "uploads/" . $new_name;
        } else {
            die("Upload failed");
        }
    }

    // ✅ FORM DATA (safe fallback)
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact-no'] ?? '';
    $birth = $_POST['birth'] ?? '';
    $sex = $_POST['sex'] ?? '';

    // ✅ QUERY (NO suffix_name — doesn't exist in your DB)
    if ($profile_path) {
        $query = "UPDATE students SET 
            first_name='$first_name',
            last_name='$last_name',
            middle_name='$middle_name',
            email='$email',
            contact_number='$contact',
            birthdate='$birth',
            gender='$sex',
            profile_photo='$profile_path'
            WHERE student_id='$user_id'";
    } else {
        $query = "UPDATE students SET 
            first_name='$first_name',
            last_name='$last_name',
            middle_name='$middle_name',
            email='$email',
            contact_number='$contact',
            birthdate='$birth',
            gender='$sex'
            WHERE student_id='$user_id'";
    }

    mysqli_query($con, $query);

    // ✅ Redirect back
    header("Location: ../pages/student/student_account.php?msg=Profile updated successfully");
    die;
}
?>