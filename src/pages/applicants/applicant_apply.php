<?php
session_start();
include("../../php/connection.php");
include("../../php/applicant_functions.php");

$applicant_data = check_applicant_login($con);
$applicant_id   = $applicant_data['applicant_id'];

$locked = !in_array($applicant_data['application_status'] ?? 'incomplete', ['incomplete', '']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($locked) {
        $error = "Your application is under review and can no longer be edited.";
    } else {
    $lrn              = $_POST['lrn'];
    if (!preg_match('/^\d{12}$/', $lrn)) {
        $error = "LRN must be exactly 12 digits and contain numbers only.";
    } else {
    $first_choice     = $_POST['first_choice'];
    $second_choice    = $_POST['second_choice'];
    $third_choice     = $_POST['third_choice'];
    $department       = $_POST['department'] ?? '';
    $last_name        = $_POST['last_name'];
    $first_name       = $_POST['first_name'];
    $middle_name      = $_POST['middle_name'];
    $suffix           = $_POST['suffix'];
    $married_name     = $_POST['married_name'];
    $birthdate        = $_POST['birthdate'];
    $nationality      = $_POST['nationality'];
    $place_of_birth   = $_POST['place_of_birth'];
    $civil_status     = $_POST['civil_status'];
    $contact_number   = $_POST['contact_number'];
    $religion         = $_POST['religion'];
    $gender           = $_POST['gender'];
    $disability       = $_POST['disability'];
    $perm_region      = $_POST['perm_region'];
    $perm_province    = $_POST['perm_province'];
    $perm_municipality= $_POST['perm_municipality'];
    $perm_barangay    = $_POST['perm_barangay'];
    $perm_address     = $_POST['perm_address'];
    $perm_zipcode     = $_POST['perm_zipcode'];
    $mail_region      = $_POST['mail_region'];
    $mail_province    = $_POST['mail_province'];
    $mail_municipality= $_POST['mail_municipality'];
    $mail_barangay    = $_POST['mail_barangay'];
    $mail_address     = $_POST['mail_address'];
    $mail_zipcode     = $_POST['mail_zipcode'];

    $stmt = mysqli_prepare($con,
        "UPDATE applicants SET
            lrn=?, first_choice=?, second_choice=?, third_choice=?, department=?,
            last_name=?, first_name=?, middle_name=?, suffix=?, married_name=?,
            birthdate=?, nationality=?, place_of_birth=?, civil_status=?,
            contact_number=?, religion=?, gender=?, disability=?,
            perm_region=?, perm_province=?, perm_municipality=?, perm_barangay=?,
            perm_address=?, perm_zipcode=?,
            mail_region=?, mail_province=?, mail_municipality=?, mail_barangay=?,
            mail_address=?, mail_zipcode=?,
            application_status='pending'
        WHERE applicant_id=?"
    );

    mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssssssssssssi",
        $lrn, $first_choice, $second_choice, $third_choice, $department,
        $last_name, $first_name, $middle_name, $suffix, $married_name,
        $birthdate, $nationality, $place_of_birth, $civil_status,
        $contact_number, $religion, $gender, $disability,
        $perm_region, $perm_province, $perm_municipality, $perm_barangay,
        $perm_address, $perm_zipcode,
        $mail_region, $mail_province, $mail_municipality, $mail_barangay,
        $mail_address, $mail_zipcode,
        $applicant_id
    );

    if (mysqli_stmt_execute($stmt)) {
        $success = "Application form saved successfully!";
        $applicant_data = check_applicant_login($con);
    } else {
        $error = "Failed to save application. Please try again.";
    }
    }
    }
}

// Helpers
$initials  = strtoupper(
    substr($applicant_data['first_name'] ?? 'A', 0, 1) .
    substr($applicant_data['last_name']  ?? 'P', 0, 1)
);
$full_name = htmlspecialchars(
    trim(($applicant_data['first_name'] ?? '') . ' ' . ($applicant_data['last_name'] ?? ''))
);

// Shortcut for pre-filling selects
function sel($field, $value, $applicant_data) {
    return ($applicant_data[$field] ?? '') === $value ? 'selected' : '';
}

// Fetch courses from DB grouped by college
$courses_result = mysqli_query($con, "SELECT course_name, college_name FROM courses WHERE status='active' ORDER BY college_name, course_name");
$courses = [];
$course_college_map = [];
while ($row = mysqli_fetch_assoc($courses_result)) {
    $courses[$row['college_name']][] = $row['course_name'];
    $course_college_map[$row['course_name']] = $row['college_name'];
}

function programOptions($field, $applicant_data, $courses) {
    $current = $applicant_data[$field] ?? '';
    $out = '<option value="">Select Program</option>';
    foreach ($courses as $college => $programs) {
        $out .= '<optgroup label="' . htmlspecialchars($college) . '">';
        foreach ($programs as $p) {
            $sel = $current === $p ? ' selected' : '';
            $out .= '<option value="' . htmlspecialchars($p) . '" data-college="' . htmlspecialchars($college) . '"' . $sel . '>' . htmlspecialchars($p) . '</option>';
        }
        $out .= '</optgroup>';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Form – PLM Applicant Portal</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_main.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_apply.css">
</head>
<body>

<!-- ── Top Nav ─────────────────────────────────────────── -->
<header>
    <div class="nav-section">

        <button class="nav-button" id="navButton">
            <i class="fa-solid fa-bars" id="trans-bars"></i>
        </button>

        <div class="logo-container">
            <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
            <div class="title-container">
                <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                <div class="logo-sub">University of the City of Manila</div>
            </div>
        </div>

        <div class="acc-display-container">
            <div class="acc-name"><?php echo $full_name; ?></div>
            <div class="acc-initials"><?php echo $initials; ?></div>
        </div>

    </div>

    <!-- ── Side Nav ──────────────────────────────────────── -->
    <nav class="main-nav" id="navMenu">
        <div class="nav-wrapper">
            <ul class="main-ul">
                <li>
                    <a href="applicant_home.php">
                        <i class="fa-solid fa-house"></i>
                        <span class="li-name">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="applicant_apply.php" class="active">
                        <i class="fa-solid fa-file-pen"></i>
                        <span class="li-name">Application Form</span>
                    </a>
                </li>
                <li>
                    <a href="applicant_submit.php">
                        <i class="fa-solid fa-file-arrow-up"></i>
                        <span class="li-name">Submit Documents</span>
                    </a>
                </li>
                <li>
                    <a href="applicant_exam.php">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span class="li-name">Exam Schedule</span>
                    </a>
                </li>
                <li>
                    <a href="../../php/applicant_logout.php" class="logout-link">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="li-name">Logout</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="drk-mode-container">
            <div class="drk-label">
                <i class="fa-solid fa-moon" id="modeIcon"></i>
                <span class="li-name" id="modeLabel">Dark Mode</span>
            </div>
            <div class="toggle-track li-name" id="toggleTrack">
                <div class="toggle-thumb"></div>
            </div>
        </div>
    </nav>
</header>


<!-- ── Page Body ───────────────────────────────────────── -->
<div class="main-flex">
    <div class="spacer"></div>

    <main>

        <div class="page-header">
            <h1>Application Form</h1>
            <p>Fill out your admission application form</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="success-message">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($locked): ?>
            <div class="error-message">
                <i class="fa-solid fa-lock"></i>
                Your application is under review and can no longer be edited.
            </div>
        <?php else: ?>
            <div class="info-message">
                <i class="fa-solid fa-circle-info"></i>
                Your progress is saved each time you click Save Application. You can exit and continue later.
            </div>
        <?php endif; ?>

        <form method="POST" class="application-form" <?php if ($locked) echo 'onsubmit="return false;"'; ?>>
<?php $dis = $locked ? 'disabled' : ''; ?>

            <!-- ── Basic Information ──────────────────────── -->
            <div class="form-section">
                <h2><i class="fa-solid fa-id-card"></i> Basic Information</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>LRN (Learner Reference Number) <span class="required">*</span></label>
                        <input type="text" name="lrn" placeholder="12-digit LRN"
                               inputmode="numeric" pattern="\d{12}" maxlength="12"
                               oninput="this.value=this.value.replace(/\D/g,'')"
                               value="<?php echo htmlspecialchars($applicant_data['lrn'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>First Choice Program <span class="required">*</span></label>
                        <select name="first_choice" id="first_choice" required <?php echo $dis; ?>>
                            <?php echo programOptions('first_choice', $applicant_data, $courses); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Second Choice Program</label>
                        <select name="second_choice" id="second_choice" <?php echo $dis; ?>>
                            <?php echo programOptions('second_choice', $applicant_data, $courses); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Third Choice Program</label>
                        <select name="third_choice" id="third_choice" <?php echo $dis; ?>>
                            <?php echo programOptions('third_choice', $applicant_data, $courses); ?>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="department" id="department" value="<?php echo htmlspecialchars($applicant_data['department'] ?? $course_college_map[$applicant_data['first_choice'] ?? ''] ?? ''); ?>">
            </div>

            <!-- ── Personal Information ───────────────────── -->
            <div class="form-section">
                <h2><i class="fa-solid fa-user"></i> Personal Information</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name"
                               value="<?php echo htmlspecialchars($applicant_data['last_name'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name"
                               value="<?php echo htmlspecialchars($applicant_data['first_name'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name"
                               value="<?php echo htmlspecialchars($applicant_data['middle_name'] ?? ''); ?>" <?php echo $dis; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Suffix</label>
                        <input type="text" name="suffix" placeholder="Jr., Sr., III, etc."
                               value="<?php echo htmlspecialchars($applicant_data['suffix'] ?? ''); ?>" <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Married Name <span style="font-weight:400;text-transform:none;letter-spacing:0;">(if applicable)</span></label>
                        <input type="text" name="married_name"
                               value="<?php echo htmlspecialchars($applicant_data['married_name'] ?? ''); ?>" <?php echo $dis; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth <span class="required">*</span></label>
                        <input type="date" name="birthdate"
                               value="<?php echo htmlspecialchars($applicant_data['birthdate'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Place of Birth <span class="required">*</span></label>
                        <input type="text" name="place_of_birth"
                               value="<?php echo htmlspecialchars($applicant_data['place_of_birth'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Gender <span class="required">*</span></label>
                        <select name="gender" required <?php echo $dis; ?>>
                            <option value="">Select Gender</option>
                            <option value="male"   <?php echo sel('gender','male',  $applicant_data); ?>>Male</option>
                            <option value="female" <?php echo sel('gender','female',$applicant_data); ?>>Female</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nationality <span class="required">*</span></label>
                        <input type="text" name="nationality"
                               value="<?php echo htmlspecialchars($applicant_data['nationality'] ?? 'Filipino'); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Civil Status <span class="required">*</span></label>
                        <select name="civil_status" required <?php echo $dis; ?>>
                            <option value="">Select Status</option>
                            <option value="single"    <?php echo sel('civil_status','single',   $applicant_data); ?>>Single</option>
                            <option value="married"   <?php echo sel('civil_status','married',  $applicant_data); ?>>Married</option>
                            <option value="widowed"   <?php echo sel('civil_status','widowed',  $applicant_data); ?>>Widowed</option>
                            <option value="separated" <?php echo sel('civil_status','separated',$applicant_data); ?>>Separated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Religion</label>
                        <input type="text" name="religion"
                               value="<?php echo htmlspecialchars($applicant_data['religion'] ?? ''); ?>" <?php echo $dis; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number <span class="required">*</span></label>
                        <input type="tel" name="contact_number" placeholder="09123456789"
                               maxlength="11" pattern="[0-9]{11}" inputmode="numeric"
                               oninput="this.value=this.value.replace(/\D/g,'')"
                               value="<?php echo htmlspecialchars($applicant_data['contact_number'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Disability <span style="font-weight:400;text-transform:none;letter-spacing:0;">(if any)</span></label>
                        <input type="text" name="disability" placeholder="None or specify"
                               value="<?php echo htmlspecialchars($applicant_data['disability'] ?? ''); ?>" <?php echo $dis; ?>>
                    </div>
                </div>
            </div>

            <!-- ── Permanent Address ──────────────────────── -->
            <div class="form-section">
                <h2><i class="fa-solid fa-location-dot"></i> Permanent Address</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Region <span class="required">*</span></label>
                        <input type="text" name="perm_region"
                               value="<?php echo htmlspecialchars($applicant_data['perm_region'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Province <span class="required">*</span></label>
                        <input type="text" name="perm_province"
                               value="<?php echo htmlspecialchars($applicant_data['perm_province'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Municipality <span class="required">*</span></label>
                        <input type="text" name="perm_municipality"
                               value="<?php echo htmlspecialchars($applicant_data['perm_municipality'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Barangay <span class="required">*</span></label>
                        <input type="text" name="perm_barangay"
                               value="<?php echo htmlspecialchars($applicant_data['perm_barangay'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Zip Code <span class="required">*</span></label>
                        <input type="text" name="perm_zipcode" maxlength="4"
                               value="<?php echo htmlspecialchars($applicant_data['perm_zipcode'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Complete Address <span class="required">*</span></label>
                        <input type="text" name="perm_address" placeholder="House No., Street, Subdivision"
                               value="<?php echo htmlspecialchars($applicant_data['perm_address'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                </div>
            </div>

            <!-- ── Mailing Address ────────────────────────── -->
            <div class="form-section">
                <h2><i class="fa-solid fa-envelope"></i> Mailing Address</h2>

                <div class="form-row" style="margin-bottom:1.25rem;">
                    <label class="checkbox">
                        <input type="checkbox" id="sameAddress" onchange="copyAddress()" <?php echo $dis; ?>>
                        <span>Same as Permanent Address</span>
                    </label>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Region <span class="required">*</span></label>
                        <input type="text" name="mail_region" id="mail_region"
                               value="<?php echo htmlspecialchars($applicant_data['mail_region'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Province <span class="required">*</span></label>
                        <input type="text" name="mail_province" id="mail_province"
                               value="<?php echo htmlspecialchars($applicant_data['mail_province'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Municipality <span class="required">*</span></label>
                        <input type="text" name="mail_municipality" id="mail_municipality"
                               value="<?php echo htmlspecialchars($applicant_data['mail_municipality'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Barangay <span class="required">*</span></label>
                        <input type="text" name="mail_barangay" id="mail_barangay"
                               value="<?php echo htmlspecialchars($applicant_data['mail_barangay'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                    <div class="form-group">
                        <label>Zip Code <span class="required">*</span></label>
                        <input type="text" name="mail_zipcode" id="mail_zipcode" maxlength="4"
                               value="<?php echo htmlspecialchars($applicant_data['mail_zipcode'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Complete Address <span class="required">*</span></label>
                        <input type="text" name="mail_address" id="mail_address"
                               placeholder="House No., Street, Subdivision"
                               value="<?php echo htmlspecialchars($applicant_data['mail_address'] ?? ''); ?>" required <?php echo $dis; ?>>
                    </div>
                </div>
            </div>

            <?php if (!$locked): ?>
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Application</span>
                </button>
            </div>
            <?php endif; ?>

        </form>
    </main>
</div><!-- /.main-flex -->

<script>
    const courseCollegeMap = <?php echo json_encode($course_college_map); ?>;

    document.getElementById('first_choice')?.addEventListener('change', function() {
        document.getElementById('department').value = courseCollegeMap[this.value] || '';
    });

    function copyAddress() {
        if (!document.getElementById('sameAddress').checked) return;
        const pairs = [
            ['perm_region',       'mail_region'],
            ['perm_province',     'mail_province'],
            ['perm_municipality', 'mail_municipality'],
            ['perm_barangay',     'mail_barangay'],
            ['perm_zipcode',      'mail_zipcode'],
            ['perm_address',      'mail_address'],
        ];
        pairs.forEach(([src, dst]) => {
            const srcEl = document.querySelector(`[name="${src}"]`);
            const dstEl = document.getElementById(dst);
            if (srcEl && dstEl) dstEl.value = srcEl.value;
        });
    }
</script>

<script src="../../js/applicant/applicant_main.js"></script>
</body>
</html>