<?php
session_start();
include("../../php/connection.php");
include("../../php/functions.php");

$user_data = check_login($con);

$full_name      = htmlspecialchars(trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')));
$student_number = htmlspecialchars($user_data['student_number'] ?? '');
$lrn            = htmlspecialchars($user_data['lrn']            ?? '');
$first_name     = htmlspecialchars($user_data['first_name']     ?? '');
$last_name      = htmlspecialchars($user_data['last_name']      ?? '');
$middle_name    = htmlspecialchars($user_data['middle_name']    ?? '');
$suffix_name    = htmlspecialchars($user_data['suffix_name']    ?? '');
$email          = htmlspecialchars($user_data['email']          ?? '');
$contact_number = htmlspecialchars($user_data['contact_number'] ?? '');
$birthdate      = htmlspecialchars($user_data['birthdate']      ?? '');
$place_of_birth = htmlspecialchars($user_data['place_of_birth'] ?? '');
$gender         = htmlspecialchars($user_data['gender']         ?? '');
$civil_status   = htmlspecialchars($user_data['civil_status']   ?? '');
$religion       = htmlspecialchars($user_data['religion']       ?? '');
$nationality    = htmlspecialchars($user_data['nationality']    ?? '');
$disability     = htmlspecialchars($user_data['disability']     ?? '');
$account_status = htmlspecialchars($user_data['account_status'] ?? 'active');
$perm_region       = htmlspecialchars($user_data['perm_region']       ?? '');
$perm_province     = htmlspecialchars($user_data['perm_province']     ?? '');
$perm_municipality = htmlspecialchars($user_data['perm_municipality'] ?? '');
$perm_barangay     = htmlspecialchars($user_data['perm_barangay']     ?? '');
$perm_address      = htmlspecialchars($user_data['perm_address']      ?? '');
$perm_zipcode      = htmlspecialchars($user_data['perm_zipcode']      ?? '');
$mail_region       = htmlspecialchars($user_data['mail_region']       ?? '');
$mail_province     = htmlspecialchars($user_data['mail_province']     ?? '');
$mail_municipality = htmlspecialchars($user_data['mail_municipality'] ?? '');
$mail_barangay     = htmlspecialchars($user_data['mail_barangay']     ?? '');
$mail_address      = htmlspecialchars($user_data['mail_address']      ?? '');
$mail_zipcode      = htmlspecialchars($user_data['mail_zipcode']      ?? '');

$profile_photo  = $user_data['profile_photo'] ?? '';
$profile_src    = $profile_photo
    ? '../../' . $profile_photo
    : '../../uploads/default.jpg';

// Fetch curriculum URL for the student's course
$curriculum_url = '';
$course_info = get_course_info($con, $user_data['course'] ?? '');
$curriculum_url = $course_info['curriculum_url'] ?? '';

// TODO (backend): map these fields from $user_data when available in DB
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Information</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
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
                    <li>
                        <a href="student_my_subjects.php">
                            <i class="fa-solid fa-layer-group"></i>
                            <div class="li-name">My Subjects</div>
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
                                <?php if ($curriculum_url): ?><li><a href="<?php echo htmlspecialchars($curriculum_url); ?>" target="_blank">Curriculum</a></li><?php endif; ?>
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
                    <li><a href="#section-student" class="active">Student Information</a></li>
                    <li><a href="#section-academic">Personal Information</a></li>
                    <li><a href="#section-family">Address</a></li>
                    <li><a href="#section-documents">Documents</a></li>
                </ul>
            </div>

            <!-- Success message -->
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="content-section">
                <form method="POST" action="../../php/update_student_profile.php" enctype="multipart/form-data" id="profile-form">

                    <!-- Hidden file input for photo upload only -->
                    <input type="file" name="profile_photo" id="photo-input" accept="image/*" style="display:none;">
                    <input type="hidden" name="photo_only" value="1">

                    <!-- Profile Photo + IDs -->
                    <div class="main-info" id="section-student">
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
                                    <input id="lrn" value="<?php echo $lrn; ?>" readonly>
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

                    <!-- Personal Information -->
                    <div class="personal-info" id="section-academic">
                        <h3>Personal Information</h3>

                        <div class="personal-info-content">

                            <div class="full-name">
                                <div class="info-input"><label>Last Name</label><input value="<?php echo $last_name; ?>" readonly></div>
                                <div class="info-input"><label>First Name</label><input value="<?php echo $first_name; ?>" readonly></div>
                                <div class="info-input"><label>Middle Name</label><input value="<?php echo $middle_name; ?>" readonly></div>
                                <div class="info-input"><label>Suffix Name</label><input value="<?php echo $suffix_name ?: 'None'; ?>" readonly></div>
                            </div>

                            <div class="birth-info">
                                <div class="info-input"><label>Date of Birth</label><input value="<?php echo $birthdate; ?>" readonly></div>
                                <div class="info-input"><label>Place of Birth</label><input value="<?php echo $place_of_birth; ?>" readonly></div>
                            </div>

                            <div class="sex-status">
                                <div class="info-input"><label>Sex</label><input value="<?php echo ucfirst($gender); ?>" readonly></div>
                                <div class="info-input"><label>Civil Status</label><input value="<?php echo ucfirst($civil_status); ?>" readonly></div>
                            </div>

                            <div class="contact-info">
                                <div class="info-input"><label>Contact Number</label><input value="<?php echo $contact_number; ?>" readonly></div>
                                <div class="info-input"><label>Personal Email</label><input value="<?php echo $email; ?>" readonly></div>
                            </div>

                            <div class="background-info">
                                <div class="info-input"><label>Religion</label><input value="<?php echo ucfirst(str_replace('-', ' ', $religion)); ?>" readonly></div>
                                <div class="info-input"><label>Nationality</label><input value="<?php echo ucfirst($nationality); ?>" readonly></div>
                                <div class="info-input"><label>Disability</label><input value="<?php echo ucfirst($disability ?: 'None'); ?>" readonly></div>
                            </div>

                        </div>
                    </div>

                    <!-- Permanent Address -->
                    <div class="complete-address" id="section-family">
                        <h3>Permanent Address</h3>
                        <div class="complete-address-content">
                            <div class="address-row-1">
                                <div class="info-input"><label>Region</label><input value="<?php echo $perm_region; ?>" readonly></div>
                                <div class="info-input"><label>Province</label><input value="<?php echo $perm_province; ?>" readonly></div>
                                <div class="info-input"><label>Municipality</label><input value="<?php echo $perm_municipality; ?>" readonly></div>
                            </div>
                            <div class="address-row-2">
                                <div class="info-input"><label>Complete Address</label><input value="<?php echo $perm_address; ?>" readonly></div>
                            </div>
                            <div class="address-row-3">
                                <div class="info-input"><label>Barangay</label><input value="<?php echo $perm_barangay; ?>" readonly></div>
                                <div class="info-input"><label>Zip Code</label><input value="<?php echo $perm_zipcode; ?>" readonly></div>
                            </div>
                        </div>
                    </div>

                    <!-- Mailing Address -->
                    <div class="mailing-address" id="section-mailing">
                        <h3>Mailing Address</h3>
                        <div class="complete-address-content">
                            <div class="address-row-1">
                                <div class="info-input"><label>Region</label><input value="<?php echo $mail_region; ?>" readonly></div>
                                <div class="info-input"><label>Province</label><input value="<?php echo $mail_province; ?>" readonly></div>
                                <div class="info-input"><label>Municipality</label><input value="<?php echo $mail_municipality; ?>" readonly></div>
                            </div>
                            <div class="address-row-2">
                                <div class="info-input"><label>Complete Address</label><input value="<?php echo $mail_address; ?>" readonly></div>
                            </div>
                            <div class="address-row-3">
                                <div class="info-input"><label>Barangay</label><input value="<?php echo $mail_barangay; ?>" readonly></div>
                                <div class="info-input"><label>Zip Code</label><input value="<?php echo $mail_zipcode; ?>" readonly></div>
                            </div>
                        </div>
                    </div>

                </form>

                <!-- Documents Section -->
                <?php
                $docs = [
                    'doc_form138'   => 'Form 138',
                    'doc_birth_cert'=> 'Birth Certificate',
                    'doc_good_moral'=> 'Good Moral',
                    'doc_our_au001' => 'OUR AU001',
                    'doc_our_au002' => 'OUR AU002',
                ];
                // Resolve applicant_id linked to this student (stored after conversion)
                $applicant_id = $user_data['applicant_id'] ?? null;
                $doc_data = [];
                if ($applicant_id) {
                    $doc_stmt = mysqli_prepare($con, "SELECT " . implode(',', array_keys($docs)) . " FROM applicants WHERE applicant_id = ? LIMIT 1");
                    mysqli_stmt_bind_param($doc_stmt, 'i', $applicant_id);
                    mysqli_stmt_execute($doc_stmt);
                    $doc_data = mysqli_fetch_assoc(mysqli_stmt_get_result($doc_stmt)) ?? [];
                }
                ?>
                <div id="section-documents" class="documents-section">
                    <h3>Submitted Documents</h3>
                    <div class="doc-list">
                    <?php foreach ($docs as $key => $label):
                        $filename = $doc_data[$key] ?? null;
                    ?>
                        <div class="doc-item">
                            <div class="doc-info">
                                <?php if ($filename): ?>
                                    <i class="fa-solid fa-file-image"></i>
                                    <div>
                                        <strong><?php echo $label; ?></strong>
                                        <span><?php echo htmlspecialchars($filename); ?></span>
                                    </div>
                                <?php else: ?>
                                    <i class="fa-solid fa-file" style="opacity:0.3;"></i>
                                    <div>
                                        <strong><?php echo $label; ?></strong>
                                        <span class="doc-not-uploaded">Not uploaded</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($filename): ?>
                                <?php
                                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                $file_path = '../../uploads/applicants/' . $applicant_id . '/' . $filename;
                                ?>
                                <button type="button" class="doc-view-btn" onclick="viewDoc('<?php echo htmlspecialchars($file_path); ?>', '<?php echo htmlspecialchars($filename); ?>', '<?php echo $ext; ?>')">
                                    <i class="fa-solid fa-eye"></i> View
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>

                <!-- Document Preview Modal -->
                <div id="docPreviewModal" class="doc-preview-modal" style="display:none;">
                    <div class="doc-preview-content">
                        <div class="doc-preview-header">
                            <span id="docPreviewName"></span>
                            <button onclick="closeDocPreview()">&times;</button>
                        </div>
                        <div class="doc-preview-body" id="docPreviewBody"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../js/student/student_account.js"></script>
    <script src="../../js/student/student_main.js"></script>
</body>
</html>