
<?php
    session_start(); 
    
    include("../php/connection.php");
    include("../php/functions.php");

    $user_data = check_login($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../css/student_home.css">
    <link rel="stylesheet" href="../css/student_main.css">
</head>
<body>
    <header>
        <nav>
            <div class="mobile-menu-container">
                <button class="mobile-burger" id="mobile-burger">
                    <i class="fa-solid fa-bars trans-bars" id="trans-bars"></i>
                    <i class="fa-solid fa-xmark trans-xmark" id="trans-xmark"></i>
                </button>

                <div class="mobile-menu" id="mobile-menu"> 
                    <ul>
                        <li><a href="student_home.php">HOME</a></li>
                        <li><a href="#">ENROLLMENT</a></li>
                        <li><a href="student_subjects.html">SUBJECTS</a></li>
                        <li><a href="#">COURSE</a></li>
                        <li><a href="#">GRADES</a></li>
                    </ul>
                </div>
            </div>

            <div class="logo-container">
                <img src="../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
            </div>
            <ul class="main-nav">
                <li><a href="student_home.php">HOME</a></li>
                <li><a href="#">ENROLLMENT</a></li>
                <li><a href="student_subjects.html">SUBJECTS</a></li>
                <li class="course-dropdown">
                    <a href="#" id="course-dropdown">
                        COURSE<i class="fa-solid fa-chevron-down" id="course-dropdown-icon"></i>
                    </a>
                    <div class="course-dropdown-menu" id="course-dropdown-menu">
                        <ul>
                            <li class="course-option1"><a href="student_info-program.html">PROGRAM</a></li>
                            <li class="course-option2"><a href="student_info-college.html">COLLEGE</a></li>
                            <li class="course-option3"><a href="https://web13.plm.edu.ph/media/courses/Bachelor_of_Science_in_Computer_Engineering.pdf" target="_blank">CURRICULUM</a></li>
                        </ul>
                    </div>
                </li>
                <li><a href="#">GRADES</a></li>
            </ul>

            <div class="account-container" id="account-button">
                <button>
                    <i class="fa-solid fa-user"></i>
                </button>

                <div class="account-menu" id="account-menu">
                    <ul>
                        <li class="account-option1"><a href="student_account.html">ACCOUNT</a></li>
                        <li class="account-option2"><a href="../php/logout.php">LOG-OUT</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="student-calendar-container">
            <div class="student-info-container">
                <div class="student-title">
                    <div class="img-container">
                        <img src="../assets/test/student-profile.webp">
                    </div>
                    <div class="student-title-content">
                        <h1><?php echo $user_data["user_name"]; ?></h1>
                        <p>202412680</p>
                        <p>jindelacruz2024@plm.edu.ph</p>
                    </div>
                </div>
                <hr>
                <div class="student-content">
                    <div class="info-row">
                        <p class="info-heading">PROGRAM</p>
                        <p class="info-input">Bachelor of Science in Computer Engineering</p>
                    </div>
                    <div class="info-row">
                        <p class="info-heading">YEAR</p>
                        <p class="info-input">Second Year</p>
                    </div>
                    <div class="info-row">
                        <p class="info-heading">REGISTRATION STATUS</p>
                        <p class="info-input">Regular</p>
                    </div>
                    <div class="info-row">
                        <p class="info-heading">ENROLLMENT STATUS</p>
                        <p class="info-input">Enrolled</p>
                    </div>
                </div>
            </div>

            <div class="calendar-container">
                <div class="calendar-header">
                    <h2 id="cal-month-label"></h2>
                    <div class="cal-nav">
                        <button id="cal-prev"><i class="fa-solid fa-chevron-left"></i></button>
                        <button id="cal-next"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="calendar-days-of-week">
                    <span>SUN</span><span>MON</span><span>TUE</span>
                    <span>WED</span><span>THU</span><span>FRI</span><span>SAT</span>
                </div>
                <div class="calendar-grid" id="cal-grid"></div>
                <div class="calendar-footer" id="cal-footer">No date selected</div>
            </div>

        </div>

        <div class="schedule-container">

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
                            <img src="../assets/student_home_slider/slider-sample01.webp" alt="PLM Campus" loading="lazy">

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
                            <img src="../assets/student_home_slider/slider-sample02.webp" alt="PLM Campus" loading="lazy">

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
                            <img src="../assets/student_home_slider/slider-sample03.webp" alt="PLM Campus" loading="lazy">

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
                            <img src="../assets/student_home_slider/slider-sample04.webp" alt="PLM Campus" loading="lazy">

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
                            <img src="../assets/student_home_slider/slider-sample05.webp" alt="PLM Campus" loading="lazy">

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

    <footer>

    </footer>

    <script src="../js/student_home.js"></script>
    <script src="../js/student_main.js"></script>
</body>
</html>