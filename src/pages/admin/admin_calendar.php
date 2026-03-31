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

// Filter
$filter = $_GET['filter'] ?? 'upcoming';
$where  = "WHERE 1=1";
if ($filter === 'upcoming') $where .= " AND event_date >= CURDATE()";
if ($filter === 'past')     $where .= " AND event_date < CURDATE()";
if ($filter === 'month')    $where .= " AND MONTH(event_date)=MONTH(CURDATE()) AND YEAR(event_date)=YEAR(CURDATE())";

$events = [];
$q = mysqli_query($con, "SELECT * FROM calendar_events $where ORDER BY event_date ASC");
while ($r = mysqli_fetch_assoc($q)) $events[] = $r;

// All events for calendar JSON
$all_events = [];
$qall = mysqli_query($con, "SELECT * FROM calendar_events ORDER BY event_date ASC");
while ($r = mysqli_fetch_assoc($qall)) $all_events[] = $r;

$image_base = '../../uploads/events/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .modal-content { max-width:600px; max-height:92vh; overflow-y:auto; }
        .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }

        /* Calendar grid */
        .cal-wrap { background:var(--gray); border:1px solid rgba(212,175,55,0.1); border-radius:8px; padding:1.5rem; margin-bottom:1.5rem; }
        .cal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; }
        .cal-header h3 { font-family:'Playfair Display',serif; font-size:1.2rem; }
        .cal-nav { display:flex; gap:0.5rem; }
        .cal-nav button { background:rgba(212,175,55,0.1); border:1px solid rgba(212,175,55,0.2); color:var(--gold); padding:0.4rem 0.75rem; cursor:pointer; border-radius:4px; transition:0.2s; }
        .cal-nav button:hover { background:rgba(212,175,55,0.2); }
        .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; }
        .cal-day-name { text-align:center; font-size:0.72rem; color:rgba(242,243,242,0.4); padding:0.4rem 0; font-weight:600; }
        .cal-cell { min-height:72px; background:rgba(255,255,255,0.02); border:1px solid rgba(212,175,55,0.06); border-radius:4px; padding:4px; position:relative; }
        .cal-cell.other-month { opacity:0.3; }
        .cal-cell.today { border-color:var(--gold); background:rgba(212,175,55,0.06); }
        .cal-date { font-size:0.75rem; color:rgba(242,243,242,0.5); margin-bottom:2px; }
        .cal-cell.today .cal-date { color:var(--gold); font-weight:700; }
        .cal-event-dot { font-size:0.68rem; padding:1px 4px; border-radius:3px; margin-bottom:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; cursor:pointer; color:#fff; }

        /* Event list */
        .event-row { background:var(--gray); border:1px solid rgba(212,175,55,0.1); border-radius:8px; padding:1.25rem; margin-bottom:0.75rem; display:flex; gap:1.25rem; align-items:flex-start; }
        .event-date-badge { min-width:56px; text-align:center; background:rgba(212,175,55,0.08); border:1px solid rgba(212,175,55,0.15); border-radius:6px; padding:0.5rem 0.25rem; }
        .event-date-badge .month { font-size:0.65rem; color:var(--gold); text-transform:uppercase; font-weight:700; }
        .event-date-badge .day   { font-family:'Playfair Display',serif; font-size:1.6rem; line-height:1; color:var(--white); }
        .event-body { flex:1; }
        .event-title { font-family:'Playfair Display',serif; font-size:1rem; margin-bottom:0.3rem; }
        .event-meta  { font-size:0.78rem; color:rgba(242,243,242,0.5); display:flex; gap:0.75rem; flex-wrap:wrap; }
        .event-desc  { font-size:0.85rem; color:rgba(242,243,242,0.65); margin-top:0.4rem; }
        .event-color-bar { width:4px; border-radius:2px; align-self:stretch; flex-shrink:0; }

        /* Color picker */
        .color-options { display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.4rem; }
        .color-opt { width:28px; height:28px; border-radius:50%; cursor:pointer; border:2px solid transparent; transition:0.2s; }
        .color-opt.selected, .color-opt:hover { border-color:var(--white); transform:scale(1.15); }

        /* Audience badges */
        .badge.all        { background:rgba(212,175,55,0.2); color:var(--gold); }
        .badge.students   { background:rgba(59,130,246,0.2); color:#60a5fa; }
        .badge.faculty    { background:rgba(168,85,247,0.2); color:#c084fc; }
        .badge.applicants { background:rgba(34,197,94,0.2);  color:#4ade80; }

        .view-toggle { display:flex; gap:0.5rem; }
        .view-btn { padding:0.4rem 0.9rem; background:transparent; border:1px solid rgba(212,175,55,0.2); color:rgba(242,243,242,0.6); cursor:pointer; border-radius:4px; font-size:0.82rem; transition:0.2s; }
        .view-btn.active { background:rgba(212,175,55,0.1); border-color:var(--gold); color:var(--gold); }

        /* View modal */
        .view-modal-content { max-width:560px; }
        .ev-view-banner { width:100%; height:180px; object-fit:cover; border-radius:6px; margin-bottom:1.25rem; display:block; }
        .ev-view-title { font-family:'Playfair Display',serif; font-size:1.4rem; margin-bottom:0.5rem; }
        .ev-view-meta { display:flex; flex-wrap:wrap; gap:0.6rem; margin-bottom:1rem; font-size:0.82rem; color:rgba(242,243,242,0.6); }
        .ev-view-meta span { display:flex; align-items:center; gap:0.35rem; }
        .ev-view-desc { font-size:0.9rem; color:rgba(242,243,242,0.75); line-height:1.7; white-space:pre-wrap; }
        .ev-color-strip { height:4px; border-radius:2px; margin-bottom:1.25rem; }

        /* Duration toggle */
        .duration-toggle { display:flex; gap:0.5rem; margin-bottom:0.75rem; }
        .dur-btn { padding:0.35rem 0.9rem; background:transparent; border:1px solid rgba(212,175,55,0.2); color:rgba(242,243,242,0.6); cursor:pointer; border-radius:4px; font-size:0.8rem; transition:0.2s; font-family:'DM Sans',sans-serif; }
        .dur-btn.active { background:rgba(212,175,55,0.1); border-color:var(--gold); color:var(--gold); }
    </style>
</head>
<body class="dashboard">
<nav class="dashboard-nav">
    <div class="nav-brand">
        <img src="../../assets/plm-logo.png" alt="PLM">
        <span>PLM Admin Portal</span>
    </div>
    <div class="nav-user">
        <span><?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?></span>
        <div class="user-avatar"><?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?></div>
    </div>
</nav>

<div class="dashboard-container">
    <aside class="sidebar">
        <a href="admin_home.php" class="sidebar-link"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="admin_applicants.php" class="sidebar-link">
            <i class="fa-solid fa-user-plus"></i><span>Applicants</span>
            <?php if ($pending_applicants > 0): ?><span class="badge"><?php echo $pending_applicants; ?></span><?php endif; ?>
        </a>
        <a href="admin_students.php" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Students</span></a>
        <a href="admin_blocks.php" class="sidebar-link"><i class="fa-solid fa-layer-group"></i><span>Blocks</span></a>
        <a href="admin_faculty.php" class="sidebar-link"><i class="fa-solid fa-chalkboard-user"></i><span>Faculty</span></a>
        <a href="admin_subjects.php" class="sidebar-link"><i class="fa-solid fa-book"></i><span>Subjects</span></a>
        <a href="admin_classes.php" class="sidebar-link"><i class="fa-solid fa-door-open"></i><span>Classes</span></a>
        <a href="admin_enrollments.php" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Enrollments</span></a>
        <a href="admin_announcements.php" class="sidebar-link"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
        <a href="admin_calendar.php" class="sidebar-link active"><i class="fa-solid fa-calendar-days"></i><span>Calendar</span></a>
        <a href="admin_accounts.php" class="sidebar-link"><i class="fa-solid fa-user-shield"></i><span>Admin Accounts</span></a>
        <a href="../../php/admin_logout.php" class="sidebar-link logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Academic Calendar</h1>
            <p>Manage school events, deadlines, and important dates</p>
        </div>

        <?php echo $flash; ?>

        <!-- Stats -->
        <div class="stats-grid" style="margin-bottom:1.5rem;">
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

        <!-- Calendar View -->
        <div class="cal-wrap">
            <div class="cal-header">
                <h3 id="calMonthLabel"></h3>
                <div style="display:flex;gap:0.75rem;align-items:center;">
                    <div class="view-toggle">
                        <button class="view-btn active" id="btnCalView" onclick="setView('cal')"><i class="fa-solid fa-calendar"></i> Calendar</button>
                        <button class="view-btn" id="btnListView" onclick="setView('list')"><i class="fa-solid fa-list"></i> List</button>
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

        <!-- List View / Toolbar -->
        <div id="listView" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
                <div class="filter-tabs" style="margin:0;border:none;padding:0;">
                    <a href="?filter=upcoming" class="filter-tab <?php echo $filter==='upcoming'?'active':''; ?>">Upcoming</a>
                    <a href="?filter=month"    class="filter-tab <?php echo $filter==='month'?'active':''; ?>">This Month</a>
                    <a href="?filter=past"     class="filter-tab <?php echo $filter==='past'?'active':''; ?>">Past</a>
                    <a href="?filter=all"      class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                </div>
                <button class="btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Add Event</button>
            </div>

            <?php if (empty($events)): ?>
            <div class="card"><div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><h2>No Events</h2><p>No events found for this filter.</p></div></div>
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
                        <?php if ($ev['event_time']): ?><span><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($ev['event_time']); ?></span><?php endif; ?>
                        <?php if ($ev['end_date'] && $ev['end_date'] !== $ev['event_date']): ?><span><i class="fa-solid fa-arrow-right"></i> <?php echo date('M j, Y', strtotime($ev['end_date'])); ?></span><?php endif; ?>
                        <span class="badge <?php echo $ev['audience']; ?>"><?php echo ucfirst($ev['audience']); ?></span>
                    </div>
                    <?php if ($ev['description']): ?><p class="event-desc"><?php echo htmlspecialchars(mb_strimwidth($ev['description'], 0, 180, '…')); ?></p><?php endif; ?>
                </div>
                <div class="action-buttons" style="flex-shrink:0;">
                    <button class="btn-icon" title="View" onclick="openView('<?php echo $js; ?>')"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn-icon" title="Edit" onclick="openEdit('<?php echo $js; ?>')"><i class="fa-solid fa-pen-to-square"></i></button>
                    <form method="POST" action="../../php/admin_calendar_handler.php" style="display:inline;" onsubmit="return confirm('Delete this event?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="event_id" value="<?php echo $ev['event_id']; ?>">
                        <button type="submit" class="btn-icon" title="Delete" style="color:#ef4444;border-color:#ef4444;"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add button for calendar view -->
        <div id="calAddBtn" style="display:flex;justify-content:flex-end;margin-top:-0.5rem;margin-bottom:1rem;">
            <button class="btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Add Event</button>
        </div>
    </main>
</div>

<!-- VIEW MODAL -->
<div id="viewModal" class="modal">
    <div class="modal-content view-modal-content">
        <span class="close" onclick="closeViewModal()">&times;</span>
        <div id="ev_view_banner_wrap"></div>
        <div class="ev-color-strip" id="ev_view_strip"></div>
        <div class="ev-view-title" id="ev_view_title"></div>
        <div class="ev-view-meta" id="ev_view_meta"></div>
        <div class="ev-view-desc" id="ev_view_desc"></div>
        <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
            <button class="btn-submit" style="flex:1;" id="ev_view_edit_btn">Edit Event</button>
            <button class="btn-secondary" onclick="closeViewModal()" style="flex:1;">Close</button>
        </div>
    </div>
</div>

<!-- ADD / EDIT MODAL -->
<div id="eventModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;" id="eventModalTitle">Add Event</h2>

        <form method="POST" action="../../php/admin_calendar_handler.php" enctype="multipart/form-data">
            <input type="hidden" name="action" id="ev_action" value="add">
            <input type="hidden" name="event_id" id="ev_id">
            <input type="hidden" name="color" id="ev_color" value="#8C1C24">

            <div class="form-group">
                <label>Title <span style="color:var(--red)">*</span></label>
                <input type="text" name="title" id="ev_title" required placeholder="Event title">
            </div>

            <div class="form-group">
                <label>Date Type</label>
                <div class="duration-toggle">
                    <button type="button" class="dur-btn active" id="durSingle" onclick="setDuration('single')">Single Day</button>
                    <button type="button" class="dur-btn" id="durRange" onclick="setDuration('range')">Date Range</button>
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
                    <?php foreach (['#8C1C24','#D4AF37','#3b82f6','#22c55e','#a855f7','#f97316','#ec4899','#0B1F5B'] as $c): ?>
                    <div class="color-opt" style="background:<?php echo $c; ?>;" data-color="<?php echo $c; ?>" onclick="pickColor('<?php echo $c; ?>')"></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Image <small style="color:rgba(242,243,242,0.4);">(optional)</small></label>
                <input type="file" name="image" id="ev_image" accept="image/*" style="background:var(--gray-lt);border:1px solid rgba(212,175,55,0.2);color:var(--white);padding:0.5rem;width:100%;border-radius:4px;">
            </div>

            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button type="submit" class="btn-submit" style="flex:1;" id="evSubmitBtn">Add Event</button>
                <button type="button" class="btn-secondary" onclick="closeModal()" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const allEvents  = <?php echo json_encode($all_events); ?>;
const imageBase  = '<?php echo $image_base; ?>';
let curYear, curMonth, currentView = 'cal';

function setView(v) {
    currentView = v;
    document.getElementById('calView').style.display    = v === 'cal'  ? '' : 'none';
    document.getElementById('calAddBtn').style.display  = v === 'cal'  ? 'flex' : 'none';
    document.getElementById('listView').style.display   = v === 'list' ? '' : 'none';
    document.getElementById('btnCalView').classList.toggle('active', v === 'cal');
    document.getElementById('btnListView').classList.toggle('active', v === 'list');
}

function buildCalendar(year, month) {
    curYear = year; curMonth = month;
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('calMonthLabel').textContent = months[month] + ' ' + year;

    const dayNames = document.getElementById('calDayNames');
    dayNames.innerHTML = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].map(d => `<div class="cal-day-name">${d}</div>`).join('');

    const cells = document.getElementById('calCells');
    cells.innerHTML = '';

    const first = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrev  = new Date(year, month, 0).getDate();
    const today = new Date();

    // Build event map, expanding multi-day ranges
    const evMap = {};
    const localKey = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    allEvents.forEach(ev => {
        const start = ev.event_date ? ev.event_date.substring(0,10) : null;
        const end   = ev.end_date   ? ev.end_date.substring(0,10)   : start;
        if (!start) return;
        let cur = new Date(start + 'T00:00:00');
        const endD = new Date((end||start) + 'T00:00:00');
        while (cur <= endD) {
            const key = localKey(cur);
            if (!evMap[key]) evMap[key] = [];
            evMap[key].push(ev);
            cur.setDate(cur.getDate()+1);
        }
    });

    // Prev month padding
    for (let i = 0; i < first; i++) {
        const d = daysInPrev - first + 1 + i;
        cells.innerHTML += `<div class="cal-cell other-month"><div class="cal-date">${d}</div></div>`;
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === d;
        const dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        const dayEvs  = evMap[dateStr] || [];
        let dots = dayEvs.slice(0, 3).map(ev =>
            `<div class="cal-event-dot" style="background:${ev.color}" title="${ev.title}" onclick="openView('${escJs(ev)}')">${ev.title}</div>`
        ).join('');
        if (dayEvs.length > 3) dots += `<div style="font-size:0.65rem;color:rgba(242,243,242,0.4);padding:1px 4px;">+${dayEvs.length-3} more</div>`;
        cells.innerHTML += `<div class="cal-cell${isToday?' today':''}"><div class="cal-date">${d}</div>${dots}</div>`;
    }

    // Next month padding
    const total = first + daysInMonth;
    const remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (let i = 1; i <= remaining; i++) {
        cells.innerHTML += `<div class="cal-cell other-month"><div class="cal-date">${i}</div></div>`;
    }
}

function escJs(ev) {
    return JSON.stringify(ev).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function changeMonth(dir) { curMonth += dir; if (curMonth > 11) { curMonth=0; curYear++; } if (curMonth < 0) { curMonth=11; curYear--; } buildCalendar(curYear, curMonth); }
function goToday() { const t = new Date(); buildCalendar(t.getFullYear(), t.getMonth()); }

// Init
const _now = new Date();
buildCalendar(_now.getFullYear(), _now.getMonth());

/* ── View Modal ── */
const viewModal = document.getElementById('viewModal');
const modal     = document.getElementById('eventModal');
window.onclick  = e => {
    if (e.target === viewModal) closeViewModal();
    if (e.target === modal)     closeModal();
};
function closeViewModal() { viewModal.style.display = 'none'; }
function closeModal()     { modal.style.display = 'none'; }

function openView(raw) {
    const ev = typeof raw === 'string' ? JSON.parse(raw.replace(/&quot;/g, '"')) : raw;
    const bannerWrap = document.getElementById('ev_view_banner_wrap');
    bannerWrap.innerHTML = ev.image
        ? `<img src="${imageBase}${ev.image}" class="ev-view-banner" alt="">`
        : '';
    document.getElementById('ev_view_strip').style.background = ev.color || '#8C1C24';
    document.getElementById('ev_view_title').textContent = ev.title;
    const startFmt = ev.event_date
        ? new Date(ev.event_date + 'T00:00:00').toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})
        : '';
    const endFmt = ev.end_date && ev.end_date.substring(0,10) !== ev.event_date.substring(0,10)
        ? ' – ' + new Date(ev.end_date + 'T00:00:00').toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})
        : '';
    const audLabel = {all:'All Users',students:'Students',faculty:'Faculty',applicants:'Applicants'};
    let meta = `<span><i class="fa-solid fa-calendar"></i> ${startFmt}${endFmt}</span>`;
    if (ev.event_time) meta += `<span><i class="fa-solid fa-clock"></i> ${ev.event_time}</span>`;
    meta += `<span><i class="fa-solid fa-users"></i> ${audLabel[ev.audience] || ev.audience}</span>`;
    document.getElementById('ev_view_meta').innerHTML = meta;
    document.getElementById('ev_view_desc').textContent = ev.description || '';
    document.getElementById('ev_view_edit_btn').onclick = () => { closeViewModal(); openEdit(ev); };
    viewModal.style.display = 'block';
}

/* ── Add / Edit Modal ── */
let _durMode = 'single';
function setDuration(mode) {
    _durMode = mode;
    document.getElementById('durSingle').classList.toggle('active', mode === 'single');
    document.getElementById('durRange').classList.toggle('active',  mode === 'range');
    document.getElementById('ev_date_label').innerHTML = mode === 'range'
        ? 'Start Date <span style="color:var(--red)">*</span>'
        : 'Date <span style="color:var(--red)">*</span>';
    document.getElementById('ev_end_date_wrap').style.display = mode === 'range' ? '' : 'none';
    if (mode === 'single') document.getElementById('ev_end_date').value = '';
}

function openAdd(dateStr) {
    document.getElementById('eventModalTitle').textContent = 'Add Event';
    document.getElementById('evSubmitBtn').textContent = 'Add Event';
    document.getElementById('ev_action').value = 'add';
    document.getElementById('ev_id').value = '';
    document.getElementById('ev_title').value = '';
    document.getElementById('ev_date').value = dateStr || '';
    document.getElementById('ev_time').value = '';
    document.getElementById('ev_description').value = '';
    document.getElementById('ev_audience').value = 'all';
    setDuration('single');
    pickColor('#8C1C24');
    modal.style.display = 'block';
}

function openEdit(raw) {
    const ev = typeof raw === 'string' ? JSON.parse(raw.replace(/&quot;/g, '"')) : raw;
    document.getElementById('eventModalTitle').textContent = 'Edit Event';
    document.getElementById('evSubmitBtn').textContent = 'Save Changes';
    document.getElementById('ev_action').value = 'edit';
    document.getElementById('ev_id').value = ev.event_id;
    document.getElementById('ev_title').value = ev.title;
    document.getElementById('ev_date').value = ev.event_date ? ev.event_date.substring(0,10) : '';
    document.getElementById('ev_time').value = ev.event_time || '';
    document.getElementById('ev_description').value = ev.description || '';
    document.getElementById('ev_audience').value = ev.audience || 'all';
    pickColor(ev.color || '#8C1C24');
    const hasRange = ev.end_date && ev.end_date.substring(0,10) !== ev.event_date.substring(0,10);
    setDuration(hasRange ? 'range' : 'single');
    if (hasRange) document.getElementById('ev_end_date').value = ev.end_date.substring(0,10);
    modal.style.display = 'block';
}

function pickColor(c) {
    document.getElementById('ev_color').value = c;
    document.querySelectorAll('.color-opt').forEach(el => el.classList.toggle('selected', el.dataset.color === c));
}
pickColor('#8C1C24');
</script>
</body>
</html>
