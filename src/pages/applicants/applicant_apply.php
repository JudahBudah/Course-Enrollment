<?php
session_start();
include("../../php/connection.php");
include("../../php/applicant_functions.php");

$applicant_data = check_applicant_login($con);
$applicant_id = $applicant_data['applicant_id'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Get all form data
    $lrn = $_POST['lrn'];
    $first_choice = $_POST['first_choice'];
    $second_choice = $_POST['second_choice'];
    $third_choice = $_POST['third_choice'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $suffix = $_POST['suffix'];
    $married_name = $_POST['married_name'];
    $birthdate = $_POST['birthdate'];
    $nationality = $_POST['nationality'];
    $place_of_birth = $_POST['place_of_birth'];
    $civil_status = $_POST['civil_status'];
    $contact_number = $_POST['contact_number'];
    $religion = $_POST['religion'];
    $gender = $_POST['gender'];
    $disability = $_POST['disability'];
    
    // Permanent Address
    $perm_region = $_POST['perm_region'];
    $perm_province = $_POST['perm_province'];
    $perm_municipality = $_POST['perm_municipality'];
    $perm_barangay = $_POST['perm_barangay'];
    $perm_address = $_POST['perm_address'];
    $perm_zipcode = $_POST['perm_zipcode'];
    
    // Mailing Address
    $mail_region = $_POST['mail_region'];
    $mail_province = $_POST['mail_province'];
    $mail_municipality = $_POST['mail_municipality'];
    $mail_barangay = $_POST['mail_barangay'];
    $mail_address = $_POST['mail_address'];
    $mail_zipcode = $_POST['mail_zipcode'];

    // Update existing record
    $stmt = mysqli_prepare($con, "UPDATE applicants SET lrn = ?, first_choice = ?, second_choice = ?, third_choice = ?, last_name = ?, first_name = ?, middle_name = ?, suffix = ?, married_name = ?, birthdate = ?, nationality = ?, place_of_birth = ?, civil_status = ?, contact_number = ?, religion = ?, gender = ?, disability = ?, perm_region = ?, perm_province = ?, perm_municipality = ?, perm_barangay = ?, perm_address = ?, perm_zipcode = ?, mail_region = ?, mail_province = ?, mail_municipality = ?, mail_barangay = ?, mail_address = ?, mail_zipcode = ?, application_status = 'pending' WHERE applicant_id = ?");
    
    mysqli_stmt_bind_param($stmt, "sssssssssssssssssssssssssssssi", $lrn, $first_choice, $second_choice, $third_choice, $last_name, $first_name, $middle_name, $suffix, $married_name, $birthdate, $nationality, $place_of_birth, $civil_status, $contact_number, $religion, $gender, $disability, $perm_region, $perm_province, $perm_municipality, $perm_barangay, $perm_address, $perm_zipcode, $mail_region, $mail_province, $mail_municipality, $mail_barangay, $mail_address, $mail_zipcode, $applicant_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Application form saved successfully!";
        // Refresh applicant data
        $applicant_data = check_applicant_login($con);
    } else {
        $error = "Failed to save application. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Form - PLM Applicant Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/applicant.css">
</head>
<body class="dashboard">
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <img src="../../assets/plm-logo.png" alt="PLM">
            <span>PLM Applicant Portal</span>
        </div>
          <div class="nav-user">
            <span><?php echo htmlspecialchars(($applicant_data['first_name'] ?? '') . ' ' . ($applicant_data['last_name'] ?? '')); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr($applicant_data['first_name'] ?? 'A', 0, 1) . substr($applicant_data['last_name'] ?? 'P', 0, 1)); ?></div>
        </div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <a href="applicant_home.php" class="sidebar-link">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
            <a href="applicant_apply.php" class="sidebar-link active">
                <i class="fa-solid fa-file-pen"></i>
                <span>Application Form</span>
            </a>
            <a href="applicant_submit.php" class="sidebar-link">
                <i class="fa-solid fa-file-arrow-up"></i>
                <span>Submit Documents</span>
            </a>
            <a href="applicant_exam.php" class="sidebar-link">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Exam Schedule</span>
            </a>
            <a href="../../php/applicant_logout.php" class="sidebar-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Application Form</h1>
                <p>Fill out your admission application form</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="success-message">
                    <i class="fa-solid fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="info-message" style="margin-bottom: 1.5rem;">
                <i class="fa-solid fa-info-circle"></i>
                Your progress is automatically saved. You can exit and continue later.
            </div>

            <form method="POST" class="application-form">
                <div class="form-section">
                    <h2><i class="fa-solid fa-id-card"></i> Basic Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>LRN (Learner Reference Number) <span class="required">*</span></label>
                            <input type="text" name="lrn" placeholder="12-digit LRN" maxlength="12" value="<?php echo htmlspecialchars($applicant_data['lrn'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>First Choice Program <span class="required">*</span></label>
                            <select name="first_choice" required>
                                <option value="">Select Program</option>
                                <option value="BS Computer Science" <?php echo ($applicant_data['first_choice'] ?? '') == 'BS Computer Science' ? 'selected' : ''; ?>>BS Computer Science</option>
                                <option value="BS Information Technology" <?php echo ($applicant_data['first_choice'] ?? '') == 'BS Information Technology' ? 'selected' : ''; ?>>BS Information Technology</option>
                                <option value="BS Business Administration" <?php echo ($applicant_data['first_choice'] ?? '') == 'BS Business Administration' ? 'selected' : ''; ?>>BS Business Administration</option>
                                <option value="BS Accountancy" <?php echo ($applicant_data['first_choice'] ?? '') == 'BS Accountancy' ? 'selected' : ''; ?>>BS Accountancy</option>
                                <option value="BS Nursing" <?php echo ($applicant_data['first_choice'] ?? '') == 'BS Nursing' ? 'selected' : ''; ?>>BS Nursing</option>
                                <option value="BS Psychology" <?php echo ($applicant_data['first_choice'] ?? '') == 'BS Psychology' ? 'selected' : ''; ?>>BS Psychology</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Second Choice Program</label>
                            <select name="second_choice">
                                <option value="">Select Program</option>
                                <option value="BS Computer Science" <?php echo ($applicant_data['second_choice'] ?? '') == 'BS Computer Science' ? 'selected' : ''; ?>>BS Computer Science</option>
                                <option value="BS Information Technology" <?php echo ($applicant_data['second_choice'] ?? '') == 'BS Information Technology' ? 'selected' : ''; ?>>BS Information Technology</option>
                                <option value="BS Business Administration" <?php echo ($applicant_data['second_choice'] ?? '') == 'BS Business Administration' ? 'selected' : ''; ?>>BS Business Administration</option>
                                <option value="BS Accountancy" <?php echo ($applicant_data['second_choice'] ?? '') == 'BS Accountancy' ? 'selected' : ''; ?>>BS Accountancy</option>
                                <option value="BS Nursing" <?php echo ($applicant_data['second_choice'] ?? '') == 'BS Nursing' ? 'selected' : ''; ?>>BS Nursing</option>
                                <option value="BS Psychology" <?php echo ($applicant_data['second_choice'] ?? '') == 'BS Psychology' ? 'selected' : ''; ?>>BS Psychology</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Third Choice Program</label>
                            <select name="third_choice">
                                <option value="">Select Program</option>
                                <option value="BS Computer Science" <?php echo ($applicant_data['third_choice'] ?? '') == 'BS Computer Science' ? 'selected' : ''; ?>>BS Computer Science</option>
                                <option value="BS Information Technology" <?php echo ($applicant_data['third_choice'] ?? '') == 'BS Information Technology' ? 'selected' : ''; ?>>BS Information Technology</option>
                                <option value="BS Business Administration" <?php echo ($applicant_data['third_choice'] ?? '') == 'BS Business Administration' ? 'selected' : ''; ?>>BS Business Administration</option>
                                <option value="BS Accountancy" <?php echo ($applicant_data['third_choice'] ?? '') == 'BS Accountancy' ? 'selected' : ''; ?>>BS Accountancy</option>
                                <option value="BS Nursing" <?php echo ($applicant_data['third_choice'] ?? '') == 'BS Nursing' ? 'selected' : ''; ?>>BS Nursing</option>
                                <option value="BS Psychology" <?php echo ($applicant_data['third_choice'] ?? '') == 'BS Psychology' ? 'selected' : ''; ?>>BS Psychology</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fa-solid fa-user"></i> Personal Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($applicant_data['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($applicant_data['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" value="<?php echo htmlspecialchars($applicant_data['middle_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Suffix</label>
                            <input type="text" name="suffix" placeholder="Jr., Sr., III, etc." value="<?php echo htmlspecialchars($applicant_data['suffix'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Married Name (if applicable)</label>
                            <input type="text" name="married_name" value="<?php echo htmlspecialchars($applicant_data['married_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth <span class="required">*</span></label>
                            <input type="date" name="birthdate" value="<?php echo htmlspecialchars($applicant_data['birthdate'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Place of Birth <span class="required">*</span></label>
                            <input type="text" name="place_of_birth" value="<?php echo htmlspecialchars($applicant_data['place_of_birth'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Gender <span class="required">*</span></label>
                            <select name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($applicant_data['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($applicant_data['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nationality <span class="required">*</span></label>
                            <input type="text" name="nationality" value="<?php echo htmlspecialchars($applicant_data['nationality'] ?? 'Filipino'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Civil Status <span class="required">*</span></label>
                            <select name="civil_status" required>
                                <option value="">Select Status</option>
                                <option value="single" <?php echo ($applicant_data['civil_status'] ?? '') == 'single' ? 'selected' : ''; ?>>Single</option>
                                <option value="married" <?php echo ($applicant_data['civil_status'] ?? '') == 'married' ? 'selected' : ''; ?>>Married</option>
                                <option value="widowed" <?php echo ($applicant_data['civil_status'] ?? '') == 'widowed' ? 'selected' : ''; ?>>Widowed</option>
                                <option value="separated" <?php echo ($applicant_data['civil_status'] ?? '') == 'separated' ? 'selected' : ''; ?>>Separated</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Religion</label>
                            <input type="text" name="religion" value="<?php echo htmlspecialchars($applicant_data['religion'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact Number <span class="required">*</span></label>
                            <input type="tel" name="contact_number" placeholder="+63 912 345 6789" value="<?php echo htmlspecialchars($applicant_data['contact_number'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Disability (if any)</label>
                            <input type="text" name="disability" placeholder="None or specify" value="<?php echo htmlspecialchars($applicant_data['disability'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fa-solid fa-location-dot"></i> Permanent Address</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Region <span class="required">*</span></label>
                            <input type="text" name="perm_region" value="<?php echo htmlspecialchars($applicant_data['perm_region'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Province <span class="required">*</span></label>
                            <input type="text" name="perm_province" value="<?php echo htmlspecialchars($applicant_data['perm_province'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Municipality <span class="required">*</span></label>
                            <input type="text" name="perm_municipality" value="<?php echo htmlspecialchars($applicant_data['perm_municipality'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Barangay <span class="required">*</span></label>
                            <input type="text" name="perm_barangay" value="<?php echo htmlspecialchars($applicant_data['perm_barangay'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Zip Code <span class="required">*</span></label>
                            <input type="text" name="perm_zipcode" maxlength="4" value="<?php echo htmlspecialchars($applicant_data['perm_zipcode'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label>Complete Address <span class="required">*</span></label>
                            <input type="text" name="perm_address" placeholder="House No., Street, Subdivision" value="<?php echo htmlspecialchars($applicant_data['perm_address'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fa-solid fa-envelope"></i> Mailing Address</h2>
                    
                    <div class="form-row">
                        <label class="checkbox">
                            <input type="checkbox" id="sameAddress" onchange="copyAddress()">
                            <span>Same as Permanent Address</span>
                        </label>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Region <span class="required">*</span></label>
                            <input type="text" name="mail_region" id="mail_region" value="<?php echo htmlspecialchars($applicant_data['mail_region'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Province <span class="required">*</span></label>
                            <input type="text" name="mail_province" id="mail_province" value="<?php echo htmlspecialchars($applicant_data['mail_province'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Municipality <span class="required">*</span></label>
                            <input type="text" name="mail_municipality" id="mail_municipality" value="<?php echo htmlspecialchars($applicant_data['mail_municipality'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Barangay <span class="required">*</span></label>
                            <input type="text" name="mail_barangay" id="mail_barangay" value="<?php echo htmlspecialchars($applicant_data['mail_barangay'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Zip Code <span class="required">*</span></label>
                            <input type="text" name="mail_zipcode" id="mail_zipcode" maxlength="4" value="<?php echo htmlspecialchars($applicant_data['mail_zipcode'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label>Complete Address <span class="required">*</span></label>
                            <input type="text" name="mail_address" id="mail_address" placeholder="House No., Street, Subdivision" value="<?php echo htmlspecialchars($applicant_data['mail_address'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-save"></i>
                        <span>Save Application</span>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        function copyAddress() {
            const checkbox = document.getElementById('sameAddress');
            if (checkbox.checked) {
                document.getElementById('mail_region').value = document.querySelector('[name="perm_region"]').value;
                document.getElementById('mail_province').value = document.querySelector('[name="perm_province"]').value;
                document.getElementById('mail_municipality').value = document.querySelector('[name="perm_municipality"]').value;
                document.getElementById('mail_barangay').value = document.querySelector('[name="perm_barangay"]').value;
                document.getElementById('mail_zipcode').value = document.querySelector('[name="perm_zipcode"]').value;
                document.getElementById('mail_address').value = document.querySelector('[name="perm_address"]').value;
            }
        }
    </script>
</body>
</html>








