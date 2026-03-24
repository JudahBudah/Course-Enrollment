<!--
<?php
    session_start(); 
    
    include("../../php/connection.php");
    include("../../php/functions.php");

    $user_data = check_login($con);
?>
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/student/student_home.css">
    <link rel="stylesheet" href="../../css/student/student_main.css">
</head>
<body>
    <header>
        <div class="nav-section">
            <div class="logo-container">
                <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
                <div class="title-container">
                    <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                    <div class="logo-sub">University of the City of Manila</div>
                </div>
            </div>

            <div class="acc-display-container">
                <div class="acc-name">
                    Judah Isaiah dela Cruz
                </div>
                <div class="acc-img">
                    <img  src="../../assets/test/student-profile.webp">
                </div>
            </div>
            <!-- Integrate Later
            <button class="nav-button">
                <i class="fa-solid fa-bars trans-bars" id="trans-bars"></i>
            </button>
            -->
        </div>
        <nav>
            <div class="nav-wrapper">
            <ul class="main-ul">
                <li>
                    <a href="student_home.php" class="active">
                        <i class="fa-solid fa-house"></i>
                        <div class="li-name">Dashboard</div>
                    </a>
                </li>
                <li>
                    <a href="student_subjects.html">
                        <i class="fa-solid fa-calendar"></i>
                        <div class="li-name">Schedule</div>
                    </a>
                </li>
                <li>
                    <a href="student_enrollment.html">
                        <i class="fa-solid fa-id-card"></i>
                        <div class="li-name">Enrollment</div>
                    </a>
                </li>
                <li>
                    <a href="student_grades.html">
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
                            <li><a href="student_info-program.html">Program</a></li>
                            <li><a href="student_info-college.html">College</a></li>
                            <li><a href="https://web13.plm.edu.ph/media/courses/Bachelor_of_Science_in_Computer_Engineering.pdf" target="_blank">Curriculum</a></li>
                        </ul>
                    </div>
                </li>
                <li>
                    <a href="student_account.html">
                        <i class="fa-solid fa-user"></i>
                        <div class="li-name">Profile</div>
                    </a>
                </li>
                <li>
                    <a href="../../php/logout.php" class="logout-bg">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <div class="li-name">Logout</div>
                    </a>
                </li>
            </ul> 
            </div>

            <div class="drk-mode-container">
                <div class="drk-label">
                    <i class="fa-solid fa-moon" id="modeIcon"></i>
                    <span id="modeLabel">Dark Mode</span>
                </div>
                <div class="toggle-track" id="toggleTrack">
                    <div class="toggle-thumb"></div>
                </div>
            </div>
        </nav>
    </header>
    <div class="main-flex">
    <div class="spacer"></div>

    <main>
        <!-- STUDENT INFO CARD -->
        <div class="card">
            <div class="card-header">
                <h2>Student Information</h2>
            </div>
            <div class="student-body">
                <div class="avatar-wrap">
                    <img src="../../assets/test/student-profile.webp">
                </div>
                <div class="student-title-content">
                    <h3><?php echo $user_data["user_name"]; ?></h3>
                    <p>202412680</p>
                    <p>jindelacruz2024@plm.edu.ph</p>
                </div>
                <div class="student-divider"></div>
                <div class="student-details">
                    <div class="detail-item">
                        <label>Program</label>
                        <span>Bachelor of Science in Computer Engineering</span>
                    </div>
                    <div class="detail-item">
                        <label>Year</label>
                        <span>2nd Year</span>
                    </div>
                    <div class="detail-item">
                        <label>Registration Status</label>
                        <span>Regular</span>
                    </div>
                    <div class="detail-item">
                        <label>Enrollment Status</label>
                        <span>Enrolled</span>
                    </div>
                </div>
            </div>
        </div>

            

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2>Today's Schedule</h2>
                    <a href="student_schedule.php" class="link-small">View All</a>
                </div>
                <div class="schedule-list">
                    <div class="schedule-item">
                        <div class="schedule-time">08:00 - 10:00</div>
                        <div class="schedule-details">
                            <h4>Data Structures and Algorithms</h4>
                            <p>Room 301 • Prof. Maria Santos</p>
                        </div>
                    </div>
                    <div class="schedule-item">
                        <div class="schedule-time">10:30 - 12:30</div>
                        <div class="schedule-details">
                            <h4>Database Management Systems</h4>
                            <p>Room 205 • Prof. Roberto Cruz</p>
                        </div>
                    </div>
                    <div class="schedule-item">
                        <div class="schedule-time">14:00 - 16:00</div>
                        <div class="schedule-details">
                            <h4>Software Engineering</h4>
                            <p>Room 402 • Prof. Ana Reyes</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Calendar</h2>
                    <div class="cal-nav">
                        <button class="cal-nav-btn" onclick="changeMonth(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                        <span class="cal-month-label" id="cal-month-label"></span>
                        <button class="cal-nav-btn" onclick="changeMonth(1)"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>

                <div class="calendar-wrap">
                    <div class="cal-grid-header">
                        <span>Sun</span><span>Mon</span><span>Tue</span>
                        <span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    <div class="cal-grid" id="cal-grid"></div>
                </div>

                <!-- Event tooltip -->
                <div class="cal-event-peek" id="cal-event-peek">
                    <p id="cal-event-text"></p>
                </div>
            </div>
        </div>

        <div class="news-events-section">
            <div class="news-events-header">
                <div class="news-events-title">
                    <i class="fa-regular fa-newspaper"></i>
                    <span>News & Events</span>
                </div>

                <div class="nav-buttons">
                    <button>
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <hr>

            <div class="news-events-card-wrapper">
                <div class="news-events-container">

                    <div class="news-events-card">
                        <a href="#">
                            <img src="../../assets/student_home_slider/slider-sample01.webp" alt="PLM Campus" loading="lazy">

                            <div class="card-content">
                                <div class="card-title">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit.
                                    </p>
                                </div>
                                <div class="card-date">
                                    <p>
                                        January 1, 2026
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="news-events-card">
                        <a href="#">
                            <img src="../../assets/student_home_slider/slider-sample02.webp" alt="PLM Campus" loading="lazy">

                            <div class="card-content">
                                <div class="card-title">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit.
                                    </p>
                                </div>
                                <div class="card-date">
                                    <p>
                                        January 1, 2026
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="news-events-card">
                        <a href="#">
                            <img src="../../assets/student_home_slider/slider-sample03.webp" alt="PLM Campus" loading="lazy">

                            <div class="card-content">
                                <div class="card-title">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit.
                                    </p>
                                </div>
                                <div class="card-date">
                                    <p>
                                        January 1, 2026
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="news-events-card">
                        <a href="#">
                            <img src="../../assets/student_home_slider/slider-sample04.webp" alt="PLM Campus" loading="lazy">

                            <div class="card-content">
                                <div class="card-title">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit.
                                    </p>
                                </div>
                                <div class="card-date">
                                    <p>
                                        January 1, 2026
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="news-events-card">
                        <a href="#">
                            <img src="../../assets/student_home_slider/slider-sample05.webp" alt="PLM Campus" loading="lazy">

                            <div class="card-content">
                                <div class="card-title">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit.
                                    </p>
                                </div>
                                <div class="card-date">
                                    <p>
                                        January 1, 2026
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                </div>
            </div>

        </div>
    </main>

    </div>
    <script src="../../js/student/student_home.js"></script>
    <script src="../../js/student/student_main.js"></script>
</body>
</html>