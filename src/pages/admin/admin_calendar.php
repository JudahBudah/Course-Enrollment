<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

// Ensure table exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS calendar_events (
    event_id    INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    event_date  DATE NOT NULL,
    end_date    DATE DEFAULT NULL,
    event_time  VARCHAR(50) DEFAULT NULL,
    color       VARCHAR(20) DEFAULT '#8C1C24',
    audience    ENUM('all','students','faculty','applicants') DEFAULT 'all',
    image       VARCHAR(255) DEFAULT NULL,
    created_by  INT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Flash messages
$flash = '';
if (isset($_GET['error']))   $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> Please fill in all required fields.</div>';
if (isset($_GET['success'])) {
    $msgs = ['added'=>'Event added.','updated'=>'Event updated.','deleted'=>'Event deleted.'];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Stats
$total_events    = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM calendar_events"))['c'];
$upcoming_events = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM calendar_events WHERE event_date >= CURDATE()"))['c'];
$this_month      = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM calendar_events WHERE MONTH(event_date)=MONTH(CURDATE()) AND YEAR(event_date)=YEAR(CURDATE())"))['c'];

// Filtered list
$filter = $_GET['filter'] ?? 'upcoming';
$where  = "WHERE 1=1";
if ($filter === 'upcoming') $where .= " AND event_date >= CURDATE()";
if ($filter === 'past')     $where .= " AND event_date < CURDATE()";
if ($filter === 'month')    $where .= " AND MONTH(event_date)=MONTH(CURDATE()) AND YEAR(event_date)=YEAR(CURDATE())";

$events = [];
$q = mysqli_query($con, "SELECT * FROM calendar_events $where ORDER BY event_date ASC");
while ($r = mysqli_fetch_assoc($q)) $events[] = $r;

// All events for the JS calendar
$all_events = [];
$qall = mysqli_query($con, "SELECT * FROM calendar_events ORDER BY event_date ASC");
while ($r = mysqli_fetch_assoc($qall)) $all_events[] = $r;

$image_base = '../../uploads/events/';

$event_colors = ['#8C1C24','#D4AF37','#3b82f6','#22c55e','#a855f7','#f97316','#ec4899','#0B1F5B'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_calendar.css">
</head>
<body>

    <!-- ── Top Nav Bar ────────────────────────────────── -->
     <header>
        <div class="nav-section">
            <!-- Mobile toggle -->
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
                <div class="acc-name">
                    <?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </div>

        <!-- ── Side Nav ───────────────────────────────── -->
        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="admin_home.php">
                            <i class="fa-solid fa-house"></i>
                            <span class="li-name">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_applicants.php">
                            <i class="fa-solid fa-user-plus"></i>
                            <span class="li-name">Applicants</span>
                            <?php if ($pending_applicants > 0): ?>
                                <span class="sidebar-badge li-name"><?php echo $pending_applicants; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Student Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="student-records-dropdown">
                            <i class="fa-solid fa-user-graduate"></i>
                            <span class="li-name chev-space">
                                Student Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="student-records-menu">
                            <ul>
                                <li><a href="admin_students.php">Students</a></li>
                                <li><a href="admin_enrollments.php">Enrollments</a></li>
                                <li>
                                    <a href="admin_drop_requests.php">
                                        Drop Requests
                                        <?php if (!empty($GLOBALS['pending_drops'])): ?>
                                            <span class="sidebar-badge"><?php echo $GLOBALS['pending_drops']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Academic Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="acad-records-dropdown">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span class="li-name chev-space">
                                Academic Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="acad-records-menu">
                            <ul>
                                <li><a href="admin_subjects.php">Subjects</a></li>
                                <li><a href="admin_classes.php">Classes</a></li>
                                <li><a href="admin_blocks.php">Blocks</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Personnel Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="personnel-dropdown">
                            <i class="fa-solid fa-users-gear"></i>
                            <span class="li-name chev-space">
                                Personnel
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="personnel-menu">
                            <ul>
                                <li><a href="admin_faculty.php">Faculty</a></li>
                                <?php if (($admin_data['role'] ?? 'admin') === 'superadmin'): ?>
                                    <li><a href="admin_accounts.php">Admin Accounts</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>

                    <!-- Communications Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="comms-dropdown">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span class="li-name chev-space">
                                Communications
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="comms-menu">
                            <ul>
                                <li><a href="admin_announcements.php">Announcements</a></li>
                                <li><a href="admin_calendar.php">Calendar</a></li>
                            </ul>
                        </div>
                    </li>

                    <?php if (($admin_data['role'] ?? '') === 'superadmin'): ?>
                    <li>
                        <a href="admin_settings.php" class="superadmin-link">
                            <i class="fa-solid fa-sliders"></i>
                            <span class="li-name">System Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="../../php/admin_logout.php" class="logout-bg">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="li-name">Logout</span>
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

    <!-- ── Page Body ──────────────────────────────────── -->
    <div class="main-flex">
        <div class="spacer"></div>

        <main>
            <div class="main-content">

                <div class="page-header">
                    <h1>Academic Calendar</h1>
                    <p>Manage school events, deadlines, and important dates</p>
                </div>

                <?php echo $flash; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-calendar-days"></i></div>
                        <div class="stat-content"><h3>Total Events</h3><p class="stat-number"><?php echo $total_events; ?></p></div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="stat-content"><h3>Upcoming</h3><p class="stat-number"><?php echo $upcoming_events; ?></p></div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-calendar"></i></div>
                        <div class="stat-content"><h3>This Month</h3><p class="stat-number"><?php echo $this_month; ?></p></div>
                    </div>
                </div>

                <!-- ── Calendar Widget ────────────────── -->
                <div class="cal-wrap">
                    <div class="cal-header">
                        <h3 id="calMonthLabel"></h3>
                        <div class="cal-header-right">
                            <div class="view-toggle">
                                <button class="view-btn active" id="btnCalView" onclick="setView('cal')">
                                    <i class="fa-solid fa-calendar"></i> Calendar
                                </button>
                                <button class="view-btn" id="btnListView" onclick="setView('list')">
                                    <i class="fa-solid fa-list"></i> List
                                </button>
                            </div>
                            <div class="cal-nav">
                                <button onclick="changeMonth(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                                <button onclick="goToday()">Today</button>
                                <button onclick="changeMonth(1)"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>

                    <div id="calView">
                        <div class="cal-grid" id="calDayNames"></div>
                        <div class="cal-grid" id="calCells" style="margin-top:2px;"></div>
                    </div>
                </div>

                <!-- Add button for calendar view -->
                <div class="cal-add-row" id="calAddBtn">
                    <button class="btn-primary" onclick="openAdd()">
                        <i class="fa-solid fa-plus"></i> Add Event
                    </button>
                </div>

                <!-- ── List View ──────────────────────── -->
                <div id="listView" style="display:none;">
                    <div class="list-toolbar">
                        <div class="filter-tabs">
                            <a href="?filter=upcoming" class="filter-tab <?php echo $filter==='upcoming'?'active':''; ?>">Upcoming</a>
                            <a href="?filter=month"    class="filter-tab <?php echo $filter==='month'?'active':''; ?>">This Month</a>
                            <a href="?filter=past"     class="filter-tab <?php echo $filter==='past'?'active':''; ?>">Past</a>
                            <a href="?filter=all"      class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                        </div>
                        <button class="btn-primary" onclick="openAdd()">
                            <i class="fa-solid fa-plus"></i> Add Event
                        </button>
                    </div>

                    <?php if (empty($events)): ?>
                        <div class="card">
                            <div class="empty-state">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <h2>No Events</h2>
                                <p>No events found for this filter.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($events as $ev):
                            $js = htmlspecialchars(json_encode($ev), ENT_QUOTES);
                            $dt = new DateTime($ev['event_date']);
                        ?>
                        <div class="event-row">
                            <div class="event-color-bar" style="background:<?php echo htmlspecialchars($ev['color']); ?>;"></div>
                            <div class="event-date-badge">
                                <div class="month"><?php echo $dt->format('M'); ?></div>
                                <div class="day"><?php echo $dt->format('j'); ?></div>
                            </div>
                            <div class="event-body">
                                <div class="event-title"><?php echo htmlspecialchars($ev['title']); ?></div>
                                <div class="event-meta">
                                    <?php if ($ev['event_time']): ?>
                                        <span><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($ev['event_time']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($ev['end_date'] && $ev['end_date'] !== $ev['event_date']): ?>
                                        <span><i class="fa-solid fa-arrow-right"></i> <?php echo date('M j, Y', strtotime($ev['end_date'])); ?></span>
                                    <?php endif; ?>
                                    <span class="badge <?php echo $ev['audience']; ?>"><?php echo ucfirst($ev['audience']); ?></span>
                                </div>
                                <?php if ($ev['description']): ?>
                                    <p class="event-desc"><?php echo htmlspecialchars(mb_strimwidth($ev['description'], 0, 180, '…')); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="action-buttons" style="flex-shrink:0;">
                                <button class="btn-icon" title="View" onclick="openView('<?php echo $js; ?>')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button class="btn-icon" title="Edit" onclick="openEdit('<?php echo $js; ?>')">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form method="POST" action="../../php/admin_calendar_handler.php"
                                      style="display:inline;" onsubmit="return confirm('Delete this event?')">
                                    <input type="hidden" name="action"   value="delete">
                                    <input type="hidden" name="event_id" value="<?php echo $ev['event_id']; ?>">
                                    <button type="submit" class="btn-icon danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <!-- ── View Event Modal ───────────────────────────── -->
    <div id="viewModal" class="modal">
        <div class="modal-content view-modal">
            <span class="close" onclick="closeViewModal()">&times;</span>
            <div id="ev_view_banner_wrap"></div>
            <div class="ev-color-strip" id="ev_view_strip"></div>
            <div class="ev-view-title" id="ev_view_title"></div>
            <div class="ev-view-meta"  id="ev_view_meta"></div>
            <div class="ev-view-desc"  id="ev_view_desc"></div>
            <div class="view-modal-actions">
                <button class="btn-primary"   id="ev_view_edit_btn">Edit Event</button>
                <button class="btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- ── Add / Edit Event Modal ─────────────────────── -->
    <div id="eventModal" class="modal">
        <div class="modal-content event-modal">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="font-family: 'DM Serif Display', serif;margin-bottom:1.5rem;" id="eventModalTitle">Add Event</h2>

            <form method="POST" action="../../php/admin_calendar_handler.php" enctype="multipart/form-data">
                <input type="hidden" name="action"   id="ev_action" value="add">
                <input type="hidden" name="event_id" id="ev_id">
                <input type="hidden" name="color"    id="ev_color"  value="#8C1C24">

                <div class="form-group">
                    <label>Title <span style="color:var(--red)">*</span></label>
                    <input type="text" name="title" id="ev_title" required placeholder="Event title">
                </div>

                <div class="form-group">
                    <label>Date Type</label>
                    <div class="duration-toggle">
                        <button type="button" class="dur-btn active" id="durSingle" onclick="setDuration('single')">Single Day</button>
                        <button type="button" class="dur-btn"        id="durRange"  onclick="setDuration('range')">Date Range</button>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label id="ev_date_label">Date <span style="color:var(--red)">*</span></label>
                        <input type="date" name="event_date" id="ev_date" required>
                    </div>
                    <div class="form-group" id="ev_end_date_wrap" style="display:none;">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="ev_end_date">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Time</label>
                        <input type="text" name="event_time" id="ev_time" placeholder="e.g. 9:00 AM – 5:00 PM">
                    </div>
                    <div class="form-group">
                        <label>Audience</label>
                        <select name="audience" id="ev_audience">
                            <option value="all">All</option>
                            <option value="students">Students</option>
                            <option value="faculty">Faculty</option>
                            <option value="applicants">Applicants</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="ev_description" rows="3" placeholder="Optional details…"></textarea>
                </div>

                <div class="form-group">
                    <label>Color</label>
                    <div class="color-options" id="colorOptions">
                        <?php foreach ($event_colors as $c): ?>
                            <div class="color-opt" style="background:<?php echo $c; ?>;"
                                 data-color="<?php echo $c; ?>"
                                 onclick="pickColor('<?php echo $c; ?>')"></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Image <small style="font-weight:400;text-transform:none;">(optional)</small></label>
                    <input type="file" name="image" id="ev_image" accept="image/*" class="ev-image-input">
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-submit" id="evSubmitBtn">Add Event</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pass PHP data to JS -->
    <script>
        window._calEvents  = <?php echo json_encode($all_events, JSON_UNESCAPED_UNICODE); ?>;
        window._imageBase  = '<?php echo $image_base; ?>';
    </script>
    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_calendar.js"></script>
</body>
</html>