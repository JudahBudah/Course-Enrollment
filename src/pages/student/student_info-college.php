<?php
session_start();
include("../../php/connection.php");
include("../../php/functions.php");

$user_data = check_login($con);
$profile_src = !empty($user_data['profile_photo'])
    ? '../../' . $user_data['profile_photo']
    : '../../uploads/default.jpg';
$full_name = htmlspecialchars(
    trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''))
);

$course_code = $user_data['course'] ?? '';
$course = get_course_info($con, $course_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Information</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/student/student_main.css">
    <link rel="stylesheet" href="../../css/student/student_info.css">
</head>
<body>
    <header>
        <div class="nav-section">
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
                <li><a href="student_home.php"><i class="fa-solid fa-house"></i><div class="li-name">Dashboard</div></a></li>
                <li><a href="student_subjects.php"><i class="fa-solid fa-calendar"></i><div class="li-name">Schedule</div></a></li>
                <li><a href="student_enrollment.php"><i class="fa-solid fa-id-card"></i><div class="li-name">Enrollment</div></a></li>
                <li><a href="student_grades.php"><i class="fa-solid fa-book"></i><div class="li-name">Grades</div></a></li>
                <li><a href="student_my_subjects.php"><i class="fa-solid fa-layer-group"></i><div class="li-name">My Subjects</div></a></li>
                <li class="course-dropdown">
                    <a href="#" id="acad-dropdown">
                        <i class="fa-solid fa-school"></i>
                        <div class="li-name chev-space">Academics <i class="fa-solid fa-chevron-down"></i></div>
                    </a>
                    <div class="acad-dropdown-menu" id="acad-dropdown-menu">
                        <ul>
                            <li><a href="student_info-program.php">Program</a></li>
                            <li><a href="student_info-college.php" class="active">College</a></li>
                            <?php if (!empty($course['curriculum_url'])): ?>
                                <li><a href="<?php echo htmlspecialchars($course['curriculum_url']); ?>" target="_blank">Curriculum</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <li><a href="student_account.php"><i class="fa-solid fa-user"></i><div class="li-name">Profile</div></a></li>
                <li><a href="../../php/student_logout.php" class="logout-bg"><i class="fa-solid fa-arrow-right-from-bracket"></i><div class="li-name">Logout</div></a></li>
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

    <div class="main-flex">
    <div class="spacer"></div>

    <main>
        <?php if ($course): ?>

            <!-- Page Header Banner -->
            <div class="info-page-header">
                <div class="info-page-header-icon">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="info-page-header-text">
                    <div class="info-page-breadcrumb">Academics &rsaquo; College</div>
                    <h1><?php echo htmlspecialchars($course['college_name']); ?></h1>
                    <div class="info-page-meta">
                        <span class="info-badge"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($course['college_code'] ?? ''); ?></span>
                        <?php if (!empty($course['college_location'])): ?>
                            <span class="info-badge"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($course['college_location']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($course['college_local_number'])): ?>
                            <span class="info-badge"><i class="fa-solid fa-phone"></i> Local <?php echo htmlspecialchars($course['college_local_number']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <?php if (!empty($course['college_description'])): ?>
            <div class="info-section">
                <div class="college-info"><?php echo nl2br(htmlspecialchars($course['college_description'])); ?></div>
            </div>
            <?php endif; ?>

            <!-- History -->
            <?php if (!empty($course['college_history'])): ?>
            <div class="info-section">
                <div class="info-section-header">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <h2>History</h2>
                </div>
                <div class="college-info"><?php echo nl2br(htmlspecialchars($course['college_history'])); ?></div>
            </div>
            <?php endif; ?>

            <!-- Vision -->
            <?php if (!empty($course['college_vision'])): ?>
            <div class="info-section">
                <div class="info-section-header">
                    <i class="fa-solid fa-eye"></i>
                    <h2>Vision</h2>
                </div>
                <div class="college-info"><?php echo nl2br(htmlspecialchars($course['college_vision'])); ?></div>
            </div>
            <?php endif; ?>

            <!-- Mission -->
            <?php if (!empty($course['college_mission'])): ?>
            <div class="info-section">
                <div class="info-section-header">
                    <i class="fa-solid fa-flag"></i>
                    <h2>Mission</h2>
                </div>
                <div class="college-info"><?php echo nl2br(htmlspecialchars($course['college_mission'])); ?></div>
            </div>
            <?php endif; ?>

            <!-- Objectives -->
            <?php if (!empty($course['college_objectives'])): ?>
            <div class="info-section">
                <div class="info-section-header">
                    <i class="fa-solid fa-bullseye"></i>
                    <h2>Objectives</h2>
                </div>
                <div class="college-info"><?php echo nl2br(htmlspecialchars($course['college_objectives'])); ?></div>
            </div>
            <?php endif; ?>

            <!-- Empty state when all content fields are null -->
            <?php if (
                empty($course['college_description']) &&
                empty($course['college_history']) &&
                empty($course['college_vision']) &&
                empty($course['college_mission']) &&
                empty($course['college_objectives'])
            ): ?>
            <div class="info-section info-empty">
                <i class="fa-solid fa-circle-info"></i>
                <p>Detailed college information is not yet available. Please check back later or contact your registrar.</p>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="info-page-header">
                <div class="info-page-header-icon">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="info-page-header-text">
                    <div class="info-page-breadcrumb">Academics &rsaquo; College</div>
                    <h1>College Information</h1>
                </div>
            </div>
            <div class="info-section info-empty">
                <i class="fa-solid fa-circle-exclamation"></i>
                <p>No college information available. Please contact your registrar.</p>
            </div>
        <?php endif; ?>
    </main>

    </div>
    <script src="../../js/student/student_main.js"></script>
</body>
</html>
