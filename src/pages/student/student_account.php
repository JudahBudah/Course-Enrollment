<?php
session_start();
include("../../php/connection.php");
include("../../php/functions.php");

$user_data = check_login($con);

$full_name      = htmlspecialchars(trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')));
$student_number = htmlspecialchars($user_data['student_number'] ?? '');
$first_name     = htmlspecialchars($user_data['first_name']     ?? '');
$last_name      = htmlspecialchars($user_data['last_name']      ?? '');
$middle_name    = htmlspecialchars($user_data['middle_name']    ?? '');
$suffix_name    = htmlspecialchars($user_data['suffix_name']    ?? '');
$email          = htmlspecialchars($user_data['email']          ?? '');
$contact_number = htmlspecialchars($user_data['contact_number'] ?? '');
$birthdate      = htmlspecialchars($user_data['birthdate']      ?? '');
$gender         = htmlspecialchars($user_data['gender']         ?? '');
$account_status = htmlspecialchars($user_data['account_status'] ?? 'active');

$profile_photo  = $user_data['profile_photo'] ?? '';
$profile_src    = $profile_photo
    ? '../../' . $profile_photo
    : '../../assets/test/student-profile.webp';

// TODO (backend): map these fields from $user_data when available in DB
// $lrn             = htmlspecialchars($user_data['lrn']             ?? '');
// $birth_place     = htmlspecialchars($user_data['birth_place']     ?? '');
// $civil_status    = htmlspecialchars($user_data['civil_status']    ?? '');
// $religion        = htmlspecialchars($user_data['religion']        ?? '');
// $nationality     = htmlspecialchars($user_data['nationality']     ?? '');
// $disability      = htmlspecialchars($user_data['disability']      ?? '');
// $perm_region     = htmlspecialchars($user_data['perm_region']     ?? '');
// $perm_province   = htmlspecialchars($user_data['perm_province']   ?? '');
// $perm_municipality = htmlspecialchars($user_data['perm_municipality'] ?? '');
// $perm_street     = htmlspecialchars($user_data['perm_street']     ?? '');
// $perm_barangay   = htmlspecialchars($user_data['perm_barangay']   ?? '');
// $perm_zip        = htmlspecialchars($user_data['perm_zip']        ?? '');
// $mail_region     = htmlspecialchars($user_data['mail_region']     ?? '');
// $mail_province   = htmlspecialchars($user_data['mail_province']   ?? '');
// $mail_municipality = htmlspecialchars($user_data['mail_municipality'] ?? '');
// $mail_street     = htmlspecialchars($user_data['mail_street']     ?? '');
// $mail_barangay   = htmlspecialchars($user_data['mail_barangay']   ?? '');
// $mail_zip        = htmlspecialchars($user_data['mail_zip']        ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Information</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/student/student_account.css">
    <link rel="stylesheet" href="../../css/student/student_main.css">
</head>
<body>
    <header>
        <div class="nav-section">
            <!-- Mobile Nav Button -->
            <button class="nav-button" id="navButton">
                <i class="fa-solid fa-bars trans-bars" id="trans-bars"></i>
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
                <div class="acc-img">
                    <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="Profile">
                </div>
            </div>
        </div>

        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="student_home.php">
                            <i class="fa-solid fa-house"></i>
                            <div class="li-name">Dashboard</div>
                        </a>
                    </li>
                    <li>
                        <a href="student_subjects.php">
                            <i class="fa-solid fa-calendar"></i>
                            <div class="li-name">Schedule</div>
                        </a>
                    </li>
                    <li>
                        <a href="student_enrollment.php">
                            <i class="fa-solid fa-id-card"></i>
                            <div class="li-name">Enrollment</div>
                        </a>
                    </li>
                    <li>
                        <a href="student_grades.php">
                            <i class="fa-solid fa-book"></i>
                            <div class="li-name">Grades</div>
                        </a>
                    </li>
                    <li class="course-dropdown">
                        <a href="#" id="acad-dropdown">
                            <i class="fa-solid fa-school"></i>
                            <div class="li-name chev-space">
                                Academics
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </a>
                        <div class="acad-dropdown-menu" id="acad-dropdown-menu">
                            <ul>
                                <li><a href="student_info-program.php">Program</a></li>
                                <li><a href="student_info-college.php">College</a></li>
                                <li><a href="https://web13.plm.edu.ph/media/courses/Bachelor_of_Science_in_Computer_Engineering.pdf" target="_blank">Curriculum</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <a href="student_account.php" class="active">
                            <i class="fa-solid fa-user"></i>
                            <div class="li-name">Profile</div>
                        </a>
                    </li>
                    <li>
                        <a href="../../php/student_logout.php" class="logout-bg">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <div class="li-name">Logout</div>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Dark Mode Toggle -->
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

    <div class="main-flex">
        <div class="spacer"></div>

        <main>
            <!-- Section Tab Nav -->
            <div class="account-nav">
                <ul>
                    <li><a href="#" class="active">Student Information</a></li>
                    <li><a href="#">Academic Information</a></li>
                    <li><a href="#">Family Information</a></li>
                    <li><a href="#">Documents / ID Photo</a></li>
                </ul>
            </div>

            <!-- Success message -->
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="content-section">
                <form method="POST" action="../../php/update_student_profile.php" enctype="multipart/form-data" id="profile-form">

                    <!-- Hidden file input for photo upload -->
                    <input type="file" name="profile_photo" id="photo-input" accept="image/*" style="display:none;">

                    <!-- Profile Photo + IDs -->
                    <div class="main-info">
                        <div class="img-container">
                            <img src="<?php echo htmlspecialchars($profile_src); ?>" id="profile-img" alt="Profile">
                            <div class="change-photo" id="change-photo-btn">
                                <i class="fa-solid fa-camera"></i>
                                <span>Change Photo</span>
                            </div>
                        </div>

                        <div class="main-info-content">
                            <div class="main-row-1">
                                <div class="info-input">
                                    <label for="student_no">Student Number</label>
                                    <input name="student_no" id="student_no"
                                           value="<?php echo $student_number; ?>" readonly>
                                </div>

                                <div class="info-input">
                                    <label for="lrn">LRN</label>
                                    <!-- TODO (backend): bind $lrn when available -->
                                    <input name="lrn" id="lrn" value="">
                                </div>
                            </div>

                            <div class="main-row-2">
                                <div class="info-input">
                                    <label for="admission_status">Admission Status</label>
                                    <!-- Read-only: driven by account_status from DB -->
                                    <input type="text" id="admission_status"
                                           value="<?php echo ucfirst($account_status); ?>" readonly>
                                </div>

                                <div class="info-input">
                                    <label for="education_level">Education Level</label>
                                    <input type="text" id="education_level" value="Bachelor" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Personal Information -->
                    <div class="personal-info">
                        <h3>Personal Information</h3>

                        <div class="personal-info-content">

                            <div class="full-name">
                                <div class="info-input">
                                    <label for="last_name">Last Name</label>
                                    <input name="last_name" id="last_name" type="text"
                                           value="<?php echo $last_name; ?>" required>
                                </div>
                                <div class="info-input">
                                    <label for="first_name">First Name</label>
                                    <input name="first_name" id="first_name" type="text"
                                           value="<?php echo $first_name; ?>" required>
                                </div>
                                <div class="info-input">
                                    <label for="middle_name">Middle Name</label>
                                    <input name="middle_name" id="middle_name" type="text"
                                           value="<?php echo $middle_name; ?>">
                                </div>
                                <div class="info-input">
                                    <label for="suffix_name">Suffix Name</label>
                                    <select name="suffix_name" id="suffix_name">
                                        <option value="" disabled <?php echo empty($suffix_name) ? 'selected' : ''; ?>>Select Suffix</option>
                                        <option value="none"  <?php echo $suffix_name === 'none'  ? 'selected' : ''; ?>>None</option>
                                        <option value="jr"    <?php echo $suffix_name === 'jr'    ? 'selected' : ''; ?>>Jr.</option>
                                        <option value="sr"    <?php echo $suffix_name === 'sr'    ? 'selected' : ''; ?>>Sr.</option>
                                        <option value="ii"    <?php echo $suffix_name === 'ii'    ? 'selected' : ''; ?>>II</option>
                                        <option value="iii"   <?php echo $suffix_name === 'iii'   ? 'selected' : ''; ?>>III</option>
                                        <option value="iv"    <?php echo $suffix_name === 'iv'    ? 'selected' : ''; ?>>IV</option>
                                        <option value="v"     <?php echo $suffix_name === 'v'     ? 'selected' : ''; ?>>V</option>
                                    </select>
                                </div>
                            </div>

                            <div class="birth-info">
                                <div class="info-input">
                                    <label for="birth">Date of Birth</label>
                                    <input name="birth" id="birth" type="date"
                                           value="<?php echo $birthdate; ?>">
                                </div>
                                <div class="info-input">
                                    <label for="birth-place">Place of Birth</label>
                                    <!-- TODO (backend): bind $birth_place when available -->
                                    <input name="birth-place" id="birth-place" type="text" value="">
                                </div>
                            </div>

                            <div class="sex-status">
                                <div class="info-input">
                                    <label for="sex">Sex</label>
                                    <select name="sex" id="sex">
                                        <option value="" disabled <?php echo empty($gender) ? 'selected' : ''; ?>>Select Sex</option>
                                        <option value="male"   <?php echo strtolower($gender) === 'male'   ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo strtolower($gender) === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="na"     <?php echo strtolower($gender) === 'na'     ? 'selected' : ''; ?>>Prefer not to say</option>
                                    </select>
                                </div>
                                <div class="info-input">
                                    <label for="civil-status">Civil Status</label>
                                    <!-- TODO (backend): bind $civil_status when available -->
                                    <select name="civil-status" id="civil-status">
                                        <option value="" disabled selected>Select Civil Status</option>
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="widowed">Widowed</option>
                                        <option value="separated">Separated</option>
                                    </select>
                                </div>
                            </div>

                            <div class="contact-info">
                                <div class="info-input">
                                    <label for="contact-no">Contact Number</label>
                                    <input name="contact-no" id="contact-no" type="text"
                                           value="<?php echo $contact_number; ?>">
                                </div>
                                <div class="info-input">
                                    <label for="email">Personal Email</label>
                                    <input name="email" id="email" type="email"
                                           value="<?php echo $email; ?>">
                                </div>
                            </div>

                            <div class="background-info">
                                <div class="info-input">
                                    <label for="religion">Religion</label>
                                    <!-- TODO (backend): bind $religion when available -->
                                    <select name="religion" id="religion">
                                        <option value="" disabled selected>Select Religion</option>
                                        <option value="roman-catholic">Roman Catholic</option>
                                        <option value="protestant">Protestant</option>
                                        <option value="iglesia-ni-cristo">Iglesia ni Cristo</option>
                                        <option value="islam">Islam</option>
                                        <option value="buddhism">Buddhism</option>
                                        <option value="hinduism">Hinduism</option>
                                        <option value="none">None</option>
                                        <option value="other">Other</option>
                                        <option value="prefer-not-to-say">Prefer not to say</option>
                                    </select>
                                </div>
                                <div class="info-input">
                                    <label for="nationality">Nationality</label>
                                    <!-- TODO (backend): bind $nationality when available -->
                                    <select name="nationality" id="nationality">
                                        <option value="" disabled selected>Select Nationality</option>
                                        <option value="filipino">Filipino</option>
                                        <option value="american">American</option>
                                        <option value="japanese">Japanese</option>
                                        <option value="korean">Korean</option>
                                        <option value="canadian">Canadian</option>
                                        <option value="australian">Australian</option>
                                        <option value="british">British</option>
                                        <option value="indian">Indian</option>
                                        <option value="chinese">Chinese</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="info-input">
                                    <label for="disability">Disability</label>
                                    <!-- TODO (backend): bind $disability when available -->
                                    <select name="disability" id="disability">
                                        <option value="" disabled selected>Select Option</option>
                                        <option value="none">None</option>
                                        <option value="physical">Physical Disability</option>
                                        <option value="visual">Visual Impairment</option>
                                        <option value="hearing">Hearing Impairment</option>
                                        <option value="intellectual">Intellectual Disability</option>
                                        <option value="psychosocial">Psychosocial Disability</option>
                                        <option value="learning">Learning Disability</option>
                                        <option value="other">Other</option>
                                        <option value="prefer-not-to-say">Prefer not to say</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>

                    <hr>

                    <!-- Permanent Address -->
                    <div class="complete-address">
                        <h3>Permanent Address</h3>

                        <div class="complete-address-content">
                            <div class="address-row-1">
                                <div class="info-input">
                                    <label for="region">Region</label>
                                    <!-- TODO (backend): bind $perm_region + selected state when available -->
                                    <select name="region" id="region" required>
                                        <option value="" disabled selected>Select Region</option>
                                        <option value="ncr">NCR</option>
                                        <option value="region-i">Region I</option>
                                        <option value="region-ii">Region II</option>
                                        <option value="region-iii">Region III</option>
                                        <option value="region-iv-a">Region IV-A</option>
                                        <option value="region-v">Region V</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="info-input">
                                    <label for="province">Province</label>
                                    <!-- TODO (backend): populate dynamically based on region -->
                                    <select name="province" id="province" required>
                                        <option value="" disabled selected>Select Province</option>
                                    </select>
                                </div>
                                <div class="info-input">
                                    <label for="municipality">Municipality</label>
                                    <!-- TODO (backend): populate dynamically based on province -->
                                    <select name="municipality" id="municipality" required>
                                        <option value="" disabled selected>Select Municipality</option>
                                    </select>
                                </div>
                            </div>

                            <div class="address-row-2">
                                <div class="info-input">
                                    <label for="street-address">
                                        Complete Address (House No. / Unit Bldg No. / Street Name)
                                    </label>
                                    <!-- TODO (backend): bind $perm_street when available -->
                                    <input type="text" name="street-address" id="street-address" required>
                                </div>
                            </div>

                            <div class="address-row-3">
                                <div class="info-input">
                                    <label for="barangay">Barangay</label>
                                    <!-- TODO (backend): populate dynamically based on municipality -->
                                    <select name="barangay" id="barangay" required>
                                        <option value="" disabled selected>Select Barangay</option>
                                    </select>
                                </div>
                                <div class="info-input">
                                    <label for="zip-code">Zip Code</label>
                                    <!-- TODO (backend): bind $perm_zip when available -->
                                    <input type="text" name="zip-code" id="zip-code" maxlength="4" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mailing Address -->
                    <div class="mailing-address">
                        <div class="mailing-address-header">
                            <h3>Mailing Address</h3>
                            <div class="same-address-checkbox">
                                <input type="checkbox" name="same-address" id="same-address">
                                <label for="same-address">Same as Permanent Address</label>
                            </div>
                        </div>

                        <div class="complete-address-content" id="mailing-address-content">
                            <div class="address-row-1">
                                <div class="info-input">
                                    <label for="mailing-region">Region</label>
                                    <!-- TODO (backend): bind $mail_region + selected state when available -->
                                    <select name="mailing-region" id="mailing-region" required>
                                        <option value="" disabled selected>Select Region</option>
                                        <option value="ncr">NCR</option>
                                        <option value="region-i">Region I</option>
                                        <option value="region-ii">Region II</option>
                                        <option value="region-iii">Region III</option>
                                        <option value="region-iv-a">Region IV-A</option>
                                        <option value="region-v">Region V</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="info-input">
                                    <label for="mailing-province">Province</label>
                                    <select name="mailing-province" id="mailing-province" required>
                                        <option value="" disabled selected>Select Province</option>
                                    </select>
                                </div>
                                <div class="info-input">
                                    <label for="mailing-municipality">Municipality</label>
                                    <select name="mailing-municipality" id="mailing-municipality" required>
                                        <option value="" disabled selected>Select Municipality</option>
                                    </select>
                                </div>
                            </div>

                            <div class="address-row-2">
                                <div class="info-input">
                                    <label for="mailing-street-address">
                                        Complete Address (House No. / Unit Bldg No. / Street Name)
                                    </label>
                                    <input type="text" name="mailing-street-address" id="mailing-street-address" required>
                                </div>
                            </div>

                            <div class="address-row-3">
                                <div class="info-input">
                                    <label for="mailing-barangay">Barangay</label>
                                    <select name="mailing-barangay" id="mailing-barangay" required>
                                        <option value="" disabled selected>Select Barangay</option>
                                    </select>
                                </div>
                                <div class="info-input">
                                    <label for="mailing-zip-code">Zip Code</label>
                                    <input type="text" name="mailing-zip-code" id="mailing-zip-code" maxlength="4" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="save-changes">
                        <button type="submit" id="save-changes-btn">Save Changes</button>
                    </div>

                </form>
            </div>
        </main>
    </div>

    <script src="../../js/student/student_account.js"></script>
    <script src="../../js/student/student_main.js"></script>
</body>
</html>