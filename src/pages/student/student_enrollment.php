<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/student/student_enrollment.css">
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
                <div class="acc-name">
                    Judah Isaiah dela Cruz
                </div>
                <div class="acc-img">
                    <img  src="../../assets/test/student-profile.webp">
                </div>
            </div>
        </div>
        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
            <ul class="main-ul">
                <li>
                    <a href="student_home.php" >
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
                    <a href="student_enrollment.php" class="active">
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
                    <a href="student_account.php">
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
        <!-- STUDENT INFO CARD -->
        <div class="table-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2>Student Information</h2>
                </div>
                <div class="student-body">
                    <div class="avatar-wrap">
                        <img src="../../assets/test/student-profile.webp">
                    </div>
                    <div class="student-details">
                        <div class="detail-item">
                            <label>Full Name</label>
                            <span>Ako si Tiu</span>
                        </div>
                        <div class="detail-item">
                            <label>Student Number</label>
                            <span>2022511047</span>
                        </div>
                        <div class="detail-item">
                            <label>Program</label>
                            <span>Professional Scavenger</span>
                        </div>
                        <div class="detail-item">
                            <label>School Year</label>
                            <span>2025 - 2026</span>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <span>Regular</span>
                        </div>
                        <div class="detail-item">
                            <label>Semester</label>
                            <span>1st Semester</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- REGISTERED SUBJECTS -->
        <div class="card">
            <div class="table-section-head">
                Registered Subjects For the Semester
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="select-all"/></th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th class="center">Hrs</th>
                            <th class="center">Units</th>
                            <th>Schedule</th>
                            <th>Professor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">CPE 0221</span></td>
                            <td>Numerical Methods</td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td><div class="sched-cell"><span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span></div></td>
                            <td class="faculty-name">Spongebob</td>
                        </tr>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">CPE 0222</span></td>
                            <td>Software Design<span class="type-tag">Lecture</span></td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td><div class="sched-cell"><span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span></div></td>
                            <td class="faculty-name">Patrick</td>
                        </tr>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">CPE 0222.1</span></td>
                            <td>Software Design<span class="type-tag">Laboratory</span></td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td><div class="sched-cell"><span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span></div></td>
                            <td class="faculty-name">Squidward</td>
                        </tr>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">ETH 0008</span></td>
                            <td>Ethics</td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td>
                                <div class="sched-cell">
                                <span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span>
                                <span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · MS Teams</span>
                                </div>
                            </td>
                            <td class="faculty-name">Plankton</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- SUBJECTS FOR RETAKE -->
        <div class="card">
            <div class="table-section-head">
                <div class="section-title">
                    Subjects Needed for Retake
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="select-all"/></th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th class="center">Hrs</th>
                            <th class="center">Units</th>
                            <th>Schedule</th>
                            <th>Professor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">CPE 0221</span></td>
                            <td>Numerical Methods</td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td><div class="sched-cell"><span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span></div></td>
                            <td class="faculty-name">Spongebob</td>
                        </tr>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">CPE 0222</span></td>
                            <td>Software Design<span class="type-tag">Lecture</span></td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td><div class="sched-cell"><span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span></div></td>
                            <td class="faculty-name">Patrick</td>
                        </tr>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">CPE 0222.1</span></td>
                            <td>Software Design<span class="type-tag">Laboratory</span></td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td><div class="sched-cell"><span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span></div></td>
                            <td class="faculty-name">Squidward</td>
                        </tr>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">ETH 0008</span></td>
                            <td>Ethics</td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td>
                                <div class="sched-cell">
                                <span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span>
                                <span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · MS Teams</span>
                                </div>
                            </td>
                            <td class="faculty-name">Plankton</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        


        <!-- SEARCH + SUBJECT TABLE -->
        
        <div class="card">
            <div class="search-bar-wrap">
                <label>Search subjects:</label>
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Subject code or name…" id="searchInput"/>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="searchTable">
                    <thead>
                        <tr>
                            <th class="center">#</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th class="center">Hrs</th>
                            <th class="center">Units</th>
                            <th>Schedule</th>
                            <th>Professor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">CPE 0221</span></td>
                            <td>Numerical Methods</td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td><div class="sched-cell"><span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span></div></td>
                            <td class="faculty-name">Spongebob</td>
                        </tr>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">CPE 0222</span></td>
                            <td>Software Design<span class="type-tag">Lecture</span></td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td><div class="sched-cell"><span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span></div></td>
                            <td class="faculty-name">Patrick</td>
                        </tr>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">CPE 0222.1</span></td>
                            <td>Software Design<span class="type-tag">Laboratory</span></td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td><div class="sched-cell"><span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span></div></td>
                            <td class="faculty-name">Squidward</td>
                        </tr>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code">ETH 0008</span></td>
                            <td>Ethics</td>
                            <td class="center">3</td>
                            <td class="center">3</td>
                            <td>
                                <div class="sched-cell">
                                <span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · GV 208</span>
                                <span class="sched-tag">BSCpE 2-1 · Sat 2:00PM - 5:00PM · MS Teams</span>
                                </div>
                            </td>
                            <td class="faculty-name">Plankton</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    </div>

    <script src="../../js/student/student_enrollment.js"></script>
    <script src="../../js/student/student_main.js"></script>
</body>
</html>