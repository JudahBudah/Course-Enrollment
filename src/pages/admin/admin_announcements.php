<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

// Ensure media column exists
$col_check = mysqli_query($con, "SHOW COLUMNS FROM announcements LIKE 'media'");
if (mysqli_num_rows($col_check) === 0) {
    mysqli_query($con, "ALTER TABLE announcements ADD COLUMN media JSON DEFAULT NULL AFTER message");
}

// Flash messages
$flash = '';
if (isset($_GET['error'])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> Please fill in all required fields.</div>';
}
if (isset($_GET['success'])) {
    $msgs = [
        'added'   => 'Announcement posted.',
        'updated' => 'Announcement updated.',
        'deleted' => 'Announcement deleted.',
    ];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Stats
$total_ann  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM announcements"))['c'];
$active_ann = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM announcements WHERE status='active'"))['c'];
$urgent_ann = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM announcements WHERE priority='urgent' AND status='active'"))['c'];

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = "WHERE 1=1";
if ($filter === 'active')   $where .= " AND status='active'";
if ($filter === 'archived') $where .= " AND status='archived'";
if ($filter === 'urgent')   $where .= " AND priority='urgent'";

$announcements = [];
$q = mysqli_query($con, "SELECT * FROM announcements $where ORDER BY created_at DESC");
while ($r = mysqli_fetch_assoc($q)) $announcements[] = $r;

$media_base = '../../uploads/announcements/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - PLM Admin</title>
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
    <link rel="stylesheet" href="../../css/admin/admin_announcements.css">
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
                    <h1>Announcements</h1>
                    <p>Post and manage announcements visible on all portals</p>
                </div>

                <?php echo $flash; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-bullhorn"></i></div>
                        <div class="stat-content"><h3>Total</h3><p class="stat-number"><?php echo $total_ann; ?></p></div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-content"><h3>Active</h3><p class="stat-number"><?php echo $active_ann; ?></p></div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="stat-content"><h3>Urgent</h3><p class="stat-number"><?php echo $urgent_ann; ?></p></div>
                    </div>
                </div>

                <!-- Toolbar: filter tabs + new button -->
                <div class="ann-toolbar">
                    <div class="filter-tabs">
                        <a href="?filter=all"      class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                        <a href="?filter=active"   class="filter-tab <?php echo $filter==='active'?'active':''; ?>">Active</a>
                        <a href="?filter=archived" class="filter-tab <?php echo $filter==='archived'?'active':''; ?>">Archived</a>
                        <a href="?filter=urgent"   class="filter-tab <?php echo $filter==='urgent'?'active':''; ?>">Urgent</a>
                    </div>
                    <button class="btn-primary" onclick="openAdd()">
                        <i class="fa-solid fa-plus"></i> New Announcement
                    </button>
                </div>

                <!-- Announcement List -->
                <?php if (empty($announcements)): ?>
                    <div class="card">
                        <div class="empty-state">
                            <i class="fa-solid fa-bullhorn"></i>
                            <h2>No Announcements</h2>
                            <p>Post your first announcement.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $ann):
                        $media = json_decode($ann['media'] ?? '[]', true) ?: [];
                        $js    = htmlspecialchars(json_encode($ann), ENT_QUOTES);
                    ?>
                    <div class="ann-row">
                        <div class="ann-row-header">
                            <div style="flex:1;">
                                <div class="ann-row-title"><?php echo htmlspecialchars($ann['title']); ?></div>
                                <div class="ann-row-meta">
                                    <span><i class="fa-solid fa-calendar"></i><?php echo date('M j, Y g:i A', strtotime($ann['created_at'])); ?></span>
                                    <span><i class="fa-solid fa-users"></i><?php echo ucfirst($ann['target_audience']); ?></span>
                                    <span class="badge <?php echo $ann['priority']; ?>"><?php echo ucfirst($ann['priority']); ?></span>
                                    <span class="badge <?php echo $ann['status']; ?>"><?php echo ucfirst($ann['status']); ?></span>
                                    <?php if (!empty($media)): ?>
                                        <span><i class="fa-solid fa-photo-film"></i> <?php echo count($media); ?> media</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button class="btn-icon" title="Edit"
                                        onclick="openEdit(<?php echo $js; ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form method="POST" action="../../php/admin_announcements_handler.php" style="display:inline;">
                                    <input type="hidden" name="action"          value="toggle">
                                    <input type="hidden" name="announcement_id" value="<?php echo $ann['announcement_id']; ?>">
                                    <button type="submit" class="btn-icon"
                                            title="<?php echo $ann['status']==='active'?'Archive':'Restore'; ?>">
                                        <i class="fa-solid <?php echo $ann['status']==='active'?'fa-box-archive':'fa-rotate-left'; ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" action="../../php/admin_announcements_handler.php" style="display:inline;"
                                      onsubmit="return confirm('Delete this announcement and all its media?')">
                                    <input type="hidden" name="action"          value="delete">
                                    <input type="hidden" name="announcement_id" value="<?php echo $ann['announcement_id']; ?>">
                                    <button type="submit" class="btn-icon danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <p class="ann-row-body"><?php echo htmlspecialchars(mb_strimwidth($ann['message'], 0, 200, '…')); ?></p>

                        <?php if (!empty($media)): ?>
                        <div class="ann-thumbs">
                            <?php foreach ($media as $m): ?>
                                <?php if ($m['type'] === 'image'): ?>
                                    <img class="ann-thumb"
                                         src="<?php echo $media_base . htmlspecialchars($m['file']); ?>" alt="">
                                <?php else: ?>
                                    <div class="ann-thumb-video"><i class="fa-solid fa-play"></i></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <!-- ── Add / Edit Announcement Modal ──────────────── -->
    <div id="annModal" class="modal">
        <div class="modal-content ann-modal">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="font-family: 'DM Serif Display', serif;margin-bottom:1.5rem;" id="annModalTitle">New Announcement</h2>

            <form method="POST" action="../../php/admin_announcements_handler.php"
                  enctype="multipart/form-data" id="annForm">
                <input type="hidden" name="action"          id="ann_action" value="add">
                <input type="hidden" name="announcement_id" id="ann_id">

                <div class="form-group">
                    <label>Title <span style="color:var(--red)">*</span></label>
                    <input type="text" name="title" id="ann_title" required placeholder="Announcement title">
                </div>

                <div class="form-group">
                    <label>Message <span style="color:var(--red)">*</span></label>
                    <textarea name="message" id="ann_message" rows="5" required
                              placeholder="Write your announcement here…"></textarea>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Target Audience</label>
                        <select name="target_audience" id="ann_audience">
                            <option value="all">All Users</option>
                            <option value="students">Students Only</option>
                            <option value="applicants">Applicants Only</option>
                            <option value="faculty">Faculty Only</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" id="ann_priority">
                            <option value="normal">Normal</option>
                            <option value="important">Important</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <!-- Status (edit only) -->
                <div class="form-group" id="ann_status_group" style="display:none;">
                    <label>Status</label>
                    <select name="status" id="ann_status">
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <!-- Existing media (edit mode) -->
                <div id="existing_media_wrap" style="display:none;">
                    <label style="font-size:0.85rem;color:var(--text-label);display:block;margin-bottom:0.5rem;">
                        Current Media
                    </label>
                    <div id="existing_media_list" class="ann-thumbs" style="flex-wrap:wrap;gap:8px;margin-bottom:0.75rem;"></div>
                </div>

                <!-- New media upload -->
                <div class="form-group">
                    <label>Add Photos / Videos</label>
                    <div class="upload-zone" id="uploadZone"
                         onclick="document.getElementById('ann_media').click()">
                        <i class="fa-solid fa-photo-film"></i>
                        Click or drag &amp; drop images/videos here<br>
                        <small>JPG, PNG, GIF, WEBP, MP4, WEBM — multiple allowed</small>
                    </div>
                    <input type="file" name="media[]" id="ann_media"
                           multiple accept="image/*,video/*" style="display:none;">
                    <div class="preview-grid" id="previewGrid"></div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-submit" id="annSubmitBtn">Post Announcement</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pass PHP media base path to JS -->
    <script>window._mediaBase = '<?php echo $media_base; ?>';</script>
    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_announcements.js"></script>
</body>
</html>