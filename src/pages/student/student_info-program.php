<?php
session_start();
include("../../php/connection.php");
include("../../php/functions.php");

$user_data = check_login($con);
$profile_src = !empty($user_data['profile_photo'])
    ? '../../' . $user_data['profile_photo']
    : '../../assets/test/student-profile.webp';
$full_name = htmlspecialchars(
    trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''))
);

// Fetch the student's course from the courses table
$course_code = $user_data['course'] ?? '';
$course = get_course_info($con, $course_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Information</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/student/student_main.css" >
    <link rel="stylesheet" href="../../css/student/student_info.css" >
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
                <li class="course-dropdown">
                    <a href="#" id="acad-dropdown">
                        <i class="fa-solid fa-school"></i>
                        <div class="li-name chev-space">Academics <i class="fa-solid fa-chevron-down"></i></div>
                    </a>
                    <div class="acad-dropdown-menu" id="acad-dropdown-menu">
                        <ul>
                            <li><a href="student_info-program.php" class="active">Program</a></li>
                            <li><a href="student_info-college.php">College</a></li>
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
            <h1 class="main-title"><?php echo htmlspecialchars($course['course_name']); ?> (<?php echo htmlspecialchars($course['course_code']); ?>)</h1>
            <hr>

            <?php if (!empty($course['description'])): ?>
            <div class="info-group">
                <div class="college-info"><?php echo nl2br(htmlspecialchars($course['description'])); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($course['program_objectives'])): ?>
            <div class="info-group">
                <h2 class="semi-title">Program Objectives:</h2>
                <div class="college-info"><?php echo nl2br(htmlspecialchars($course['program_objectives'])); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($course['career_opportunities'])): ?>
            <div class="info-group">
                <h2 class="semi-title">Career and Profession:</h2>
                <div class="college-info"><?php echo nl2br(htmlspecialchars($course['career_opportunities'])); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($course['curriculum_url'])): ?>
            <div class="info-group">
                <a href="<?php echo htmlspecialchars($course['curriculum_url']); ?>" target="_blank" class="curriculum-link">
                    <i class="fa-solid fa-file-pdf"></i> View Curriculum
                </a>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <h1 class="main-title">Program Information</h1>
            <hr>
            <div class="info-group">
                <div class="college-info" style="color:var(--text-label);">
                    No program assigned. Please contact your registrar.
                </div>
            </div>
        <?php endif; ?>
    </main>

    </div>
    <script src="../../js/student/student_main.js"></script>
</body>
</html>
