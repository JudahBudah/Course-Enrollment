<?php
session_start();
include("../../php/connection.php");

if (!isset($_SESSION['faculty_id'])) { header("Location: faculty_login.php"); die; }
$faculty_id = (int)$_SESSION['faculty_id'];

$faculty = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT * FROM faculty WHERE faculty_id = $faculty_id LIMIT 1"
));
if (!$faculty) { session_destroy(); header("Location: faculty_login.php"); die; }

$profile_src = !empty($faculty['profile_photo'])
    ? '../../' . htmlspecialchars($faculty['profile_photo'])
    : '../../uploads/default.jpg';

function val($faculty, $key) {
    return htmlspecialchars($faculty[$key] ?? '');
}
function sel($faculty, $key, $option) {
    return ($faculty[$key] ?? '') === $option ? 'selected' : '';
}
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
    <link rel="stylesheet" href="../../css/faculty/faculty_profile.css">
    <link rel="stylesheet" href="../../css/faculty/faculty_main.css">
    <style>
        .toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 999;
            padding: 0.6rem 1.2rem; border-radius: 6px; font-size: 0.82rem;
            font-weight: 600; color: #fff; opacity: 0; transition: opacity 0.3s;
            pointer-events: none;
        }
        .toast.show { opacity: 1; }
        .toast.ok   { background: #1a6b3c; }
        .toast.err  { background: #8C1C24; }
        .photo-uploading { opacity: 0.5; pointer-events: none; }
        .change-photo input[type=file] { display: none; }
    </style>
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
                <div class="acc-name"><?php echo val($faculty, 'first_name') . ' ' . val($faculty, 'last_name'); ?></div>
                <div class="acc-img">
                    <img src="<?php echo $profile_src; ?>" id="nav-photo" alt="Profile">
                </div>
            </div>
        </div>

        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="faculty_home.php">
                            <i class="fa-solid fa-house"></i>
                            <div class="li-name">Dashboard</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_load.php">
                            <i class="fa-solid fa-calendar"></i>
                            <div class="li-name">Schedule</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_classlist.php">
                            <i class="fa-solid fa-list"></i>
                            <div class="li-name">Class List</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_spreadsheet.php">
                            <i class="fa-solid fa-table"></i>
                            <div class="li-name">Spreadsheet</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_gradebook.php">
                            <i class="fa-solid fa-book"></i>
                            <div class="li-name">Gradebook</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_grade_history.php">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <div class="li-name">Grade History</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_profile.php" class="active">
                            <i class="fa-solid fa-user"></i>
                            <div class="li-name">Profile</div>
                        </a>
                    </li>
                    <li>
                        <a href="../../php/faculty_logout.php" class="logout-bg">
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
                    <li><a href="#section-faculty" class="tab-link active" data-section="section-faculty">Faculty Information</a></li>
                    <li><a href="#section-personal" class="tab-link" data-section="section-personal">Personal Information</a></li>
                    <li><a href="#section-address" class="tab-link" data-section="section-address">Address</a></li>
                    <li><a href="#section-educational" class="tab-link" data-section="section-educational">Educational Background</a></li>
                    <li><a href="#section-emergency" class="tab-link" data-section="section-emergency">Emergency Contact</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="content-section">

                <!-- ── SECTION: Faculty Information ── -->
                <div id="section-faculty" class="profile-section">
                <div class="main-info">
                    <div class="img-container">
                        <img src="<?php echo $profile_src; ?>" id="profile-img" alt="Profile Photo">
                        <div class="change-photo" id="change-photo-btn" title="Upload new photo">
                            <i class="fa-solid fa-camera"></i>
                            <span>Change Photo</span>
                            <input type="file" id="photo-input" accept="image/*" style="display: none;">
                        </div>
                    </div>

                    <div class="main-info-content">
                        <div class="main-row-1">
                            <div class="info-input">
                                <label>Faculty ID</label>
                                <input value="<?php echo val($faculty, 'employee_id'); ?>" disabled style="background:var(--white)" >
                                <small style="color:var(--text);font-size:.72rem;">Assigned by admin — cannot be changed</small>
                            </div>
                            <div class="info-input">
                                <label>Position</label>
                                <input value="<?php echo val($faculty, 'position'); ?>" disabled style="background:var(--white)" >
                                <small style="color:var(--text);font-size:.72rem;">Assigned by admin — cannot be changed</small>
                            </div>
                        </div>
                        <div class="main-row-2">
                            <div class="info-input">
                                <label>College</label>
                                <input value="<?php echo val($faculty, 'college'); ?>" disabled style="background:var(--white)" >
                                <small style="color:var(--text);font-size:.72rem;">Assigned by admin — cannot be changed</small>
                            </div>
                            <div class="info-input">
                                <label>Department</label>
                                <input value="<?php echo val($faculty, 'department'); ?>" disabled style="background:var(--white)" >
                                <small style="color:var(--text);font-size:.72rem;">Assigned by admin — cannot be changed</small>
                            </div>
                        </div>
                        <div class="main-row-1">
                            <div class="info-input">
                                <label>Employment Status</label>
                                <input value="<?php echo ucfirst(val($faculty, 'employment_status')); ?>" disabled style="background:var(--white)" >
                                <small style="color:var(--text);font-size:.72rem;">Assigned by admin — cannot be changed</small>
                            </div>
                            <div class="info-input">
                                <label>PLM Email</label>
                                <input value="<?php echo val($faculty, 'email'); ?>" disabled style="background:var(--white)" >
                                <small style="color:var(--text);font-size:.72rem;">Assigned by admin — cannot be changed</small>
                            </div>
                        </div>
                    </div>
                </div>
                </div><!-- /#section-faculty -->

                <!-- ── SECTION: Personal Information ── -->
                <div id="section-personal" class="profile-section" style="display:none;">
                    <h3>Personal Information</h3>

                    <div class="personal-info-content">
                        <div class="full-name">
                            <div class="info-input">
                                <label for="f_last_name">Last Name</label>
                                <input name="last_name" id="f_last_name" type="text" placeholder="Dela Cruz" value="<?php echo val($faculty, 'last_name'); ?>" required>
                            </div>

                            <div class="info-input">
                                <label for="f_first_name">First Name</label>
                                <input name="first_name" id="f_first_name" type="text" placeholder="Juan" value="<?php echo val($faculty, 'first_name'); ?>" required>
                            </div>

                            <div class="info-input">
                                <label for="f_middle_name">Middle Name</label>
                                <input name="middle_name" id="f_middle_name" type="text" placeholder="Santos" value="<?php echo val($faculty, 'middle_name'); ?>">
                            </div>

                            <div class="info-input">
                                <label for="f_suffix_name">Suffix Name</label>
                                <select name="suffix_name" id="f_suffix_name">
                                    <option value="" disabled selected>Select Suffix</option>
                                    <option value="none"  <?php echo sel($faculty, 'suffix_name', 'none'); ?>>None</option>
                                    <option value="Jr."   <?php echo sel($faculty, 'suffix_name', 'Jr.'); ?>>Jr.</option>
                                    <option value="Sr."   <?php echo sel($faculty, 'suffix_name', 'Sr.'); ?>>Sr.</option>
                                    <option value="II"    <?php echo sel($faculty, 'suffix_name', 'II'); ?>>II</option>
                                    <option value="III"   <?php echo sel($faculty, 'suffix_name', 'III'); ?>>III</option>
                                    <option value="IV"    <?php echo sel($faculty, 'suffix_name', 'IV'); ?>>IV</option>
                                    <option value="V"     <?php echo sel($faculty, 'suffix_name', 'V'); ?>>V</option>
                                </select>
                            </div>
                        </div>

                        <div class="birth-info">
                            <div class="info-input">
                                <label for="f_date_of_birth">Date of Birth</label>
                                <input name="date_of_birth" id="f_date_of_birth" type="date" value="<?php echo val($faculty, 'date_of_birth'); ?>">
                            </div>
                            <div class="info-input">
                                <label for="f_place_of_birth">Place of Birth</label>
                                <input name="place_of_birth" id="f_place_of_birth" type="text" value="<?php echo val($faculty, 'place_of_birth'); ?>">
                            </div>
                        </div>

                        <div class="sex-status">
                            <div class="info-input">
                                <label for="f_sex">Sex</label>
                                <select name="sex" id="f_sex">
                                    <option value="">Select Sex</option>
                                    <option value="Male"   <?php echo sel($faculty, 'sex', 'Male'); ?>>Male</option>
                                    <option value="Female" <?php echo sel($faculty, 'sex', 'Female'); ?>>Female</option>
                                    <option value="Other"  <?php echo sel($faculty, 'sex', 'Other'); ?>>Other</option>
                                    <option value="na"     <?php echo sel($faculty, 'sex', 'na'); ?>>Prefer not to say</option>
                                </select>
                            </div>
                            <div class="info-input">
                                <label for="f_civil_status">Civil Status</label>
                                <select name="civil_status" id="f_civil_status">
                                    <option value="">Select Civil Status</option>
                                    <option value="Single"    <?php echo sel($faculty, 'civil_status', 'Single'); ?>>Single</option>
                                    <option value="Married"   <?php echo sel($faculty, 'civil_status', 'Married'); ?>>Married</option>
                                    <option value="Widowed"   <?php echo sel($faculty, 'civil_status', 'Widowed'); ?>>Widowed</option>
                                    <option value="Separated" <?php echo sel($faculty, 'civil_status', 'Separated'); ?>>Separated</option>
                                    <option value="Divorced"  <?php echo sel($faculty, 'civil_status', 'Divorced'); ?>>Divorced</option>
                                </select>
                            </div>
                        </div>

                        <div class="contact-info">
                            <div class="info-input">
                                <label for="f_phone">Contact Number</label>
                                <input name="phone" id="f_phone" value="<?php echo val($faculty, 'phone'); ?>">
                            </div>
                            <div class="info-input">
                                <label for="f_personal_email">Personal Email</label>
                                <input name="personal_email" id="f_personal_email" type="email" value="<?php echo val($faculty, 'personal_email'); ?>">
                            </div>
                        </div>

                        <div class="background-info">
                            <div class="info-input">
                                <label for="f_religion">Religion</label>
                                <input type="text" name="religion" id="f_religion" value="<?php echo val($faculty, 'religion'); ?>">
                            </div>
                            <div class="info-input">
                                <label for="f_nationality">Nationality</label>
                                <input type="text" name="nationality" id="f_nationality" value="<?php echo val($faculty, 'nationality'); ?>">
                            </div>
                            <div class="info-input">
                                <label for="f_disability">Disability</label>
                                <input type="text" name="disability" id="f_disability" value="<?php echo val($faculty, 'disability'); ?>" placeholder="None">
                            </div>
                        </div>
                    </div>

                    <div class="save-changes">
                        <button type="button" id="save-changes-btn" onclick="saveProfile()">
                            <i class="fa-solid fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </div><!-- /#section-personal -->

                <!-- ── SECTION: Address ── -->
                <div id="section-address" class="profile-section" style="display:none;">

                    <!-- Permanent Address -->
                    <div class="complete-address">
                        <h3>Permanent Address</h3>
                        <div class="complete-address-content">
                            <div class="address-row-1">
                                <div class="info-input">
                                    <label for="f_permanent_region">Region</label>
                                    <input type="text" name="permanent_region" id="f_permanent_region" value="<?php echo val($faculty, 'permanent_region'); ?>">
                                </div>
                                <div class="info-input">
                                    <label for="f_permanent_province">Province</label>
                                    <input type="text" name="permanent_province" id="f_permanent_province" value="<?php echo val($faculty, 'permanent_province'); ?>">
                                </div>
                                <div class="info-input">
                                    <label for="f_permanent_municipality">Municipality</label>
                                    <input type="text" name="permanent_municipality" id="f_permanent_municipality" value="<?php echo val($faculty, 'permanent_municipality'); ?>">
                                </div>
                            </div>
                            <div class="address-row-2">
                                <div class="info-input">
                                    <label for="f_permanent_address">Complete Address (House No. / Unit Bldg No. / Street Name)</label>
                                    <input type="text" name="permanent_address" id="f_permanent_address" value="<?php echo val($faculty, 'permanent_address'); ?>">
                                </div>
                            </div>
                            <div class="address-row-3">
                                <div class="info-input">
                                    <label for="f_permanent_barangay">Barangay</label>
                                    <input type="text" name="permanent_barangay" id="f_permanent_barangay" value="<?php echo val($faculty, 'permanent_barangay'); ?>">
                                </div>
                                <div class="info-input">
                                    <label for="f_permanent_zip_code">Zip Code</label>
                                    <input type="text" name="permanent_zip_code" id="f_permanent_zip_code" value="<?php echo val($faculty, 'permanent_zip_code'); ?>" maxlength="10">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mailing Address -->
                    <div class="mailing-address">
                        <div class="mailing-address-header">
                            <h3>Mailing Address</h3>
                            <div class="same-address-checkbox">
                                <input type="checkbox" id="same-address" autocomplete="off" <?php echo (int)($faculty['mailing_same_as_permanent'] ?? 0) === 1 ? 'checked' : ''; ?>>
                                <label for="same-address">Same as Permanent Address</label>
                            </div>
                        </div>
                        <div class="complete-address-content" id="mailing-address-content">
                            <div class="address-row-1">
                                <div class="info-input">
                                    <label for="f_mailing_region">Region</label>
                                    <input type="text" name="mailing_region" id="f_mailing_region" value="<?php echo val($faculty, 'mailing_region'); ?>">
                                </div>
                                <div class="info-input">
                                    <label for="f_mailing_province">Province</label>
                                    <input type="text" name="mailing_province" id="f_mailing_province" value="<?php echo val($faculty, 'mailing_province'); ?>">
                                </div>
                                <div class="info-input">
                                    <label for="f_mailing_municipality">Municipality</label>
                                    <input type="text" name="mailing_municipality" id="f_mailing_municipality" value="<?php echo val($faculty, 'mailing_municipality'); ?>">
                                </div>
                            </div>
                            <div class="address-row-2">
                                <div class="info-input">
                                    <label for="f_mailing_address">Complete Address (House No. / Unit Bldg No. / Street Name)</label>
                                    <input type="text" name="mailing_address" id="f_mailing_address" value="<?php echo val($faculty, 'mailing_address'); ?>">
                                </div>
                            </div>
                            <div class="address-row-3">
                                <div class="info-input">
                                    <label for="f_mailing_barangay">Barangay</label>
                                    <input type="text" name="mailing_barangay" id="f_mailing_barangay" value="<?php echo val($faculty, 'mailing_barangay'); ?>">
                                </div>
                                <div class="info-input">
                                    <label for="f_mailing_zip_code">Zip Code</label>
                                    <input type="text" name="mailing_zip_code" id="f_mailing_zip_code" value="<?php echo val($faculty, 'mailing_zip_code'); ?>" maxlength="10">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="save-changes">
                        <button type="button" id="save-changes-btn" onclick="saveProfile()">
                            <i class="fa-solid fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </div><!-- /#section-address -->

                <!-- ── SECTION: Educational Background ── -->
                <div id="section-educational" class="profile-section" style="display:none;">
                    <h3>Educational Background</h3>
                    <p style="color:var(--text-label);font-size:0.88rem;margin-bottom:1.5rem;">
                        <i class="fa-solid fa-circle-info"></i>
                        Educational background records are managed by the admin. Please contact the admin to update this information.
                    </p>
                    <div class="personal-info-content">
                        <div class="full-name">
                            <div class="info-input">
                                <label>Highest Educational Attainment</label>
                                <input type="text" value="<?php echo val($faculty, 'highest_education'); ?>" disabled style="background:var(--white)" >
                            </div>
                            <div class="info-input">
                                <label>Degree / Course</label>
                                <input type="text" value="<?php echo val($faculty, 'degree'); ?>" disabled style="background:var(--white)" >
                            </div>
                        </div>
                        <div class="full-name">
                            <div class="info-input">
                                <label>School / University</label>
                                <input type="text" value="<?php echo val($faculty, 'school'); ?>" disabled style="background:var(--white)" >
                            </div>
                            <div class="info-input">
                                <label>Year Graduated</label>
                                <input type="text" value="<?php echo val($faculty, 'year_graduated'); ?>" disabled style="background:var(--white)" >
                            </div>
                        </div>
                        <div class="full-name">
                            <div class="info-input">
                                <label>Specialization</label>
                                <input type="text" value="<?php echo val($faculty, 'specialization'); ?>" disabled style="background:var(--white)" >
                            </div>
                            <div class="info-input">
                                <label>Date Hired</label>
                                <input type="text" value="<?php echo val($faculty, 'date_hired'); ?>" disabled style="background:var(--white)" >
                            </div>
                        </div>
                    </div>
                </div><!-- /#section-educational -->

                <!-- ── SECTION: Emergency Contact ── -->
                <div id="section-emergency" class="profile-section" style="display:none;">
                    <h3>Emergency Contact</h3>
                    <div class="personal-info-content">
                        <div class="full-name">
                            <div class="info-input">
                                <label for="f_emergency_name">Contact Name</label>
                                <input type="text" name="emergency_name" id="f_emergency_name" value="<?php echo val($faculty, 'emergency_name'); ?>" placeholder="Full name">
                            </div>
                            <div class="info-input">
                                <label for="f_emergency_relationship">Relationship</label>
                                <input type="text" name="emergency_relationship" id="f_emergency_relationship" value="<?php echo val($faculty, 'emergency_relationship'); ?>" placeholder="e.g. Spouse, Parent">
                            </div>
                        </div>
                        <div class="full-name">
                            <div class="info-input">
                                <label for="f_emergency_phone">Contact Number</label>
                                <input type="text" name="emergency_phone" id="f_emergency_phone" value="<?php echo val($faculty, 'emergency_phone'); ?>" placeholder="09XXXXXXXXX">
                            </div>
                            <div class="info-input">
                                <label for="f_emergency_address">Address</label>
                                <input type="text" name="emergency_address" id="f_emergency_address" value="<?php echo val($faculty, 'emergency_address'); ?>" placeholder="Complete address">
                            </div>
                        </div>
                    </div>
                    <div class="save-changes">
                        <button type="button" id="save-changes-btn" onclick="saveProfile()">
                            <i class="fa-solid fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </div><!-- /#section-emergency -->

            </div><!-- /.content-section -->

    <div class="toast" id="toast"></div>

    <script>
    const HANDLER = '../../php/faculty_profile_handler.php';

    // ── Toast ─────────────────────────────────────────────────────────────────
    let _toastTimer;
    function showToast(msg, ok = true) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast show ' + (ok ? 'ok' : 'err');
        clearTimeout(_toastTimer);
        _toastTimer = setTimeout(() => t.classList.remove('show'), 2500);
    }

    // ── Photo upload ──────────────────────────────────────────────────────────
    document.getElementById('change-photo-btn').addEventListener('click', () => {
        document.getElementById('photo-input').click();
    });

    document.getElementById('photo-input').addEventListener('change', function () {
        if (!this.files[0]) return;
        const file = this.files[0];
        if (!file.type.startsWith('image/')) { showToast('Please select an image file.', false); return; }
        if (file.size > 5 * 1024 * 1024)    { showToast('Image must be under 5MB.', false); return; }

        // Preview immediately
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('profile-img').src = e.target.result;
            document.getElementById('nav-photo').src   = e.target.result;
        };
        reader.readAsDataURL(file);

        // Upload
        const btn = document.getElementById('change-photo-btn');
        btn.classList.add('photo-uploading');
        const fd = new FormData();
        fd.append('action', 'upload_photo');
        fd.append('photo', file);

        fetch(HANDLER, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                btn.classList.remove('photo-uploading');
                if (d.ok) {
                    document.getElementById('profile-img').src = d.path;
                    document.getElementById('nav-photo').src   = d.path;
                    showToast('Photo updated!');
                } else {
                    showToast(d.msg || 'Upload failed.', false);
                }
            })
            .catch(() => { btn.classList.remove('photo-uploading'); showToast('Upload failed.', false); });
    });

    // ── Same address checkbox — blur/copy behaviour ───────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const sameChk      = document.getElementById('same-address');
        const mailingBlock = document.getElementById('mailing-address-content');

        // PHP-injected DB value — hard override any browser form-state restoration
        const DB_SAME = <?php echo (int)($faculty['mailing_same_as_permanent'] ?? 0) === 1 ? 'true' : 'false'; ?>;
        sameChk.checked = DB_SAME;

        const fieldPairs = [
            ['f_mailing_region',       'f_permanent_region'],
            ['f_mailing_province',     'f_permanent_province'],
            ['f_mailing_municipality', 'f_permanent_municipality'],
            ['f_mailing_address',      'f_permanent_address'],
            ['f_mailing_barangay',     'f_permanent_barangay'],
            ['f_mailing_zip_code',     'f_permanent_zip_code'],
        ];

        function syncMailing() {
            fieldPairs.forEach(([mId, pId]) => {
                const mEl = document.getElementById(mId);
                const pEl = document.getElementById(pId);
                if (mEl && pEl) mEl.value = pEl.value;
            });
        }

        function clearMailing() {
            fieldPairs.forEach(([mId]) => {
                const el = document.getElementById(mId);
                if (el) el.value = '';
            });
        }

        function applyMailingState(checked, syncValues = true) {
            mailingBlock.querySelectorAll('input, select').forEach(el => el.disabled = checked);
            mailingBlock.classList.toggle('sync-disabled', checked);
            if (checked && syncValues) syncMailing();
            if (!checked && syncValues) clearMailing();
        }

        sameChk.addEventListener('change', function () { applyMailingState(this.checked, true); });

        // Live sync while permanent fields are edited with checkbox on
        fieldPairs.forEach(([, pId]) => {
            const el = document.getElementById(pId);
            if (!el) return;
            el.addEventListener('input',  () => { if (sameChk.checked) syncMailing(); });
            el.addEventListener('change', () => { if (sameChk.checked) syncMailing(); });
        });

        // Apply initial blur/disable state from DB — no value overwrite
        applyMailingState(DB_SAME, false);
    });

    // ── Save profile ──────────────────────────────────────────────────────────
    function saveProfile() {
        const fields = [
            'first_name', 'middle_name', 'last_name', 'suffix_name',
            'date_of_birth', 'place_of_birth', 'sex', 'civil_status',
            'religion', 'nationality', 'disability',
            'phone', 'personal_email',
            'permanent_region', 'permanent_province', 'permanent_municipality',
            'permanent_barangay', 'permanent_address', 'permanent_zip_code',
            'mailing_region', 'mailing_province', 'mailing_municipality',
            'mailing_barangay', 'mailing_address', 'mailing_zip_code',
            'emergency_name', 'emergency_relationship', 'emergency_phone', 'emergency_address',
        ];

        const fd = new FormData();
        fd.append('action', 'save_profile');
        fd.append('mailing_same_as_permanent', document.getElementById('same-address').checked ? '1' : '0');

        fields.forEach(f => {
            const el = document.getElementById('f_' + f);
            if (el) fd.append(f, el.value);
        });

        const btn = document.getElementById('save-changes-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Saving…';

        fetch(HANDLER, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Changes';
                showToast(d.ok ? 'Changes saved!' : (d.msg || 'Save failed.'), d.ok);
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Changes';
                showToast('Save failed.', false);
            });
    }
    </script>

    <script src="../../js/faculty/faculty_profile.js"></script>
    <script src="../../js/faculty/faculty_main.js"></script>
</body>
</html>
