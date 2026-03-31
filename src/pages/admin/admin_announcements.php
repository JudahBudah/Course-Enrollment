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

$flash = '';
if (isset($_GET['error'])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> Please fill in all required fields.</div>';
}
if (isset($_GET['success'])) {
    $msgs = ['added'=>'Announcement posted.','updated'=>'Announcement updated.','deleted'=>'Announcement deleted.'];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Stats
$total_ann    = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM announcements"))['c'];
$active_ann   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM announcements WHERE status='active'"))['c'];
$urgent_ann   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM announcements WHERE priority='urgent' AND status='active'"))['c'];

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .modal-content { max-width:680px; max-height:92vh; overflow-y:auto; }
        .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }
        /* Priority badges */
        .badge.urgent    { background:rgba(239,68,68,0.2);   color:#ef4444; }
        .badge.important { background:rgba(245,158,11,0.2);  color:#f59e0b; }
        .badge.normal    { background:rgba(59,130,246,0.2);  color:#60a5fa; }
        .badge.archived  { background:rgba(156,163,175,0.2); color:#9ca3af; }
        /* Ann card in admin list */
        .ann-row { background:var(--gray); border:1px solid rgba(212,175,55,0.1); border-radius:8px; padding:1.25rem; margin-bottom:1rem; }
        .ann-row-header { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; }
        .ann-row-title { font-family:'Playfair Display',serif; font-size:1.05rem; margin-bottom:0.3rem; }
        .ann-row-meta  { font-size:0.78rem; color:rgba(242,243,242,0.5); display:flex; gap:0.75rem; flex-wrap:wrap; }
        .ann-row-body  { font-size:0.88rem; color:rgba(242,243,242,0.7); margin:0.6rem 0; line-height:1.6; white-space:pre-wrap; }
        /* Media thumbnails in admin list */
        .ann-thumbs { display:flex; gap:6px; flex-wrap:wrap; margin-top:0.6rem; }
        .ann-thumb  { width:80px; height:60px; object-fit:cover; border-radius:4px; border:1px solid rgba(212,175,55,0.2); }
        .ann-thumb-video { width:80px; height:60px; border-radius:4px; border:1px solid rgba(212,175,55,0.2); background:#000; display:flex; align-items:center; justify-content:center; color:rgba(242,243,242,0.5); font-size:1.4rem; }
        /* Upload drop zone */
        .upload-zone { border:2px dashed rgba(212,175,55,0.3); border-radius:6px; padding:1.5rem; text-align:center; cursor:pointer; transition:0.2s; color:rgba(242,243,242,0.5); font-size:0.85rem; }
        .upload-zone:hover, .upload-zone.drag-over { border-color:var(--gold); color:var(--gold); background:rgba(212,175,55,0.05); }
        .upload-zone i { font-size:2rem; display:block; margin-bottom:0.5rem; }
        .preview-grid { display:flex; gap:8px; flex-wrap:wrap; margin-top:0.75rem; }
        .preview-item { position:relative; width:90px; height:70px; border-radius:4px; overflow:hidden; border:1px solid rgba(212,175,55,0.2); }
        .preview-item img, .preview-item video { width:100%; height:100%; object-fit:cover; }
        .preview-remove { position:absolute; top:2px; right:2px; background:rgba(239,68,68,0.85); border:none; color:#fff; border-radius:50%; width:18px; height:18px; font-size:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
        /* Existing media in edit */
        .existing-media-item { position:relative; display:inline-block; }
        .existing-media-item img { width:80px; height:60px; object-fit:cover; border-radius:4px; }
        .existing-remove { position:absolute; top:2px; right:2px; background:rgba(239,68,68,0.85); border:none; color:#fff; border-radius:50%; width:18px; height:18px; font-size:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
        .existing-remove.marked { background:rgba(239,68,68,1); }
        .existing-media-item.marked-del img { opacity:0.35; }
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
        <a href="admin_announcements.php" class="sidebar-link active"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
        <a href="admin_calendar.php" class="sidebar-link"><i class="fa-solid fa-calendar-days"></i><span>Calendar</span></a>
        <a href="admin_accounts.php" class="sidebar-link"><i class="fa-solid fa-user-shield"></i><span>Admin Accounts</span></a>
        <a href="../../php/admin_logout.php" class="sidebar-link logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Announcements</h1>
            <p>Post and manage announcements visible on all portals</p>
        </div>

        <?php echo $flash; ?>

        <!-- Stats -->
        <div class="stats-grid" style="margin-bottom:1.5rem;">
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

        <!-- Toolbar -->
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
            <div class="filter-tabs" style="margin:0;border:none;padding:0;">
                <a href="?filter=all"      class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                <a href="?filter=active"   class="filter-tab <?php echo $filter==='active'?'active':''; ?>">Active</a>
                <a href="?filter=archived" class="filter-tab <?php echo $filter==='archived'?'active':''; ?>">Archived</a>
                <a href="?filter=urgent"   class="filter-tab <?php echo $filter==='urgent'?'active':''; ?>">Urgent</a>
            </div>
            <button class="btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> New Announcement</button>
        </div>

        <!-- List -->
        <?php if (empty($announcements)): ?>
        <div class="card"><div class="empty-state"><i class="fa-solid fa-bullhorn"></i><h2>No Announcements</h2><p>Post your first announcement.</p></div></div>
        <?php else: ?>
        <?php foreach ($announcements as $ann):
            $media = json_decode($ann['media'] ?? '[]', true) ?: [];
            $js = htmlspecialchars(json_encode($ann), ENT_QUOTES);
        ?>
        <div class="ann-row">
            <div class="ann-row-header">
                <div style="flex:1;">
                    <div class="ann-row-title"><?php echo htmlspecialchars($ann['title']); ?></div>
                    <div class="ann-row-meta">
                        <span><i class="fa-solid fa-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($ann['created_at'])); ?></span>
                        <span><i class="fa-solid fa-users"></i> <?php echo ucfirst($ann['target_audience']); ?></span>
                        <span class="badge <?php echo $ann['priority']; ?>"><?php echo ucfirst($ann['priority']); ?></span>
                        <span class="badge <?php echo $ann['status']; ?>"><?php echo ucfirst($ann['status']); ?></span>
                        <?php if (!empty($media)): ?>
                            <span><i class="fa-solid fa-photo-film"></i> <?php echo count($media); ?> media</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="action-buttons">
                    <button class="btn-icon" title="Edit" onclick="openEdit(<?php echo $js; ?>)"><i class="fa-solid fa-pen-to-square"></i></button>
                    <form method="POST" action="../../php/admin_announcements_handler.php" style="display:inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="announcement_id" value="<?php echo $ann['announcement_id']; ?>">
                        <button type="submit" class="btn-icon" title="<?php echo $ann['status']==='active'?'Archive':'Restore'; ?>">
                            <i class="fa-solid <?php echo $ann['status']==='active'?'fa-box-archive':'fa-rotate-left'; ?>"></i>
                        </button>
                    </form>
                    <form method="POST" action="../../php/admin_announcements_handler.php" style="display:inline;"
                        onsubmit="return confirm('Delete this announcement and all its media?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="announcement_id" value="<?php echo $ann['announcement_id']; ?>">
                        <button type="submit" class="btn-icon" style="color:#ef4444;border-color:#ef4444;" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <p class="ann-row-body"><?php echo htmlspecialchars(mb_strimwidth($ann['message'], 0, 200, '…')); ?></p>
            <?php if (!empty($media)): ?>
            <div class="ann-thumbs">
                <?php foreach ($media as $m): ?>
                    <?php if ($m['type'] === 'image'): ?>
                        <img class="ann-thumb" src="<?php echo $media_base . htmlspecialchars($m['file']); ?>" alt="">
                    <?php else: ?>
                        <div class="ann-thumb-video"><i class="fa-solid fa-play"></i></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<!-- ADD / EDIT MODAL -->
<div id="annModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;" id="annModalTitle">New Announcement</h2>

        <form method="POST" action="../../php/admin_announcements_handler.php" enctype="multipart/form-data" id="annForm">
            <input type="hidden" name="action" id="ann_action" value="add">
            <input type="hidden" name="announcement_id" id="ann_id">

            <div class="form-group">
                <label>Title <span style="color:var(--red)">*</span></label>
                <input type="text" name="title" id="ann_title" required placeholder="Announcement title">
            </div>

            <div class="form-group">
                <label>Message <span style="color:var(--red)">*</span></label>
                <textarea name="message" id="ann_message" rows="5" required placeholder="Write your announcement here…"></textarea>
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
                <label style="font-size:0.85rem;color:rgba(242,243,242,0.7);display:block;margin-bottom:0.5rem;">Current Media</label>
                <div id="existing_media_list" class="ann-thumbs" style="flex-wrap:wrap;gap:8px;margin-bottom:0.75rem;"></div>
            </div>

            <!-- New media upload -->
            <div class="form-group">
                <label>Add Photos / Videos</label>
                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('ann_media').click()">
                    <i class="fa-solid fa-photo-film"></i>
                    Click or drag & drop images/videos here<br>
                    <small style="font-size:0.75rem;">JPG, PNG, GIF, WEBP, MP4, WEBM — multiple allowed</small>
                </div>
                <input type="file" name="media[]" id="ann_media" multiple accept="image/*,video/*" style="display:none;">
                <div class="preview-grid" id="previewGrid"></div>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button type="submit" class="btn-submit" style="flex:1;" id="annSubmitBtn">Post Announcement</button>
                <button type="button" class="btn-secondary" onclick="closeModal()" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('annModal');
window.onclick = e => { if (e.target === modal) closeModal(); };

function closeModal() { modal.style.display = 'none'; resetForm(); }

function resetForm() {
    document.getElementById('annForm').reset();
    document.getElementById('previewGrid').innerHTML = '';
    document.getElementById('existing_media_wrap').style.display = 'none';
    document.getElementById('existing_media_list').innerHTML = '';
    document.getElementById('ann_status_group').style.display = 'none';
    _newFiles = [];
}

function openAdd() {
    resetForm();
    document.getElementById('annModalTitle').textContent = 'New Announcement';
    document.getElementById('annSubmitBtn').textContent  = 'Post Announcement';
    document.getElementById('ann_action').value = 'add';
    document.getElementById('ann_id').value = '';
    modal.style.display = 'block';
}

function openEdit(ann) {
    resetForm();
    document.getElementById('annModalTitle').textContent = 'Edit Announcement';
    document.getElementById('annSubmitBtn').textContent  = 'Save Changes';
    document.getElementById('ann_action').value = 'edit';
    document.getElementById('ann_id').value     = ann.announcement_id;
    document.getElementById('ann_title').value  = ann.title;
    document.getElementById('ann_message').value= ann.message;
    document.getElementById('ann_audience').value = ann.target_audience;
    document.getElementById('ann_priority').value = ann.priority;
    document.getElementById('ann_status').value   = ann.status;
    document.getElementById('ann_status_group').style.display = '';

    // Render existing media
    let media = [];
    try { media = JSON.parse(ann.media || '[]') || []; } catch(e){}
    if (media.length) {
        document.getElementById('existing_media_wrap').style.display = '';
        const list = document.getElementById('existing_media_list');
        list.innerHTML = '';
        media.forEach(m => {
            const wrap = document.createElement('div');
            wrap.className = 'existing-media-item';
            wrap.dataset.file = m.file;

            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = ''; // not removing by default

            if (m.type === 'image') {
                const img = document.createElement('img');
                img.src = '<?php echo $media_base; ?>' + m.file;
                img.className = 'ann-thumb';
                wrap.appendChild(img);
            } else {
                const vd = document.createElement('div');
                vd.className = 'ann-thumb-video';
                vd.innerHTML = '<i class="fa-solid fa-play"></i>';
                wrap.appendChild(vd);
            }

            const btn = document.createElement('button');
            btn.type = 'button'; btn.className = 'existing-remove'; btn.innerHTML = '&times;';
            btn.title = 'Remove this file';
            btn.onclick = () => {
                const marked = wrap.classList.toggle('marked-del');
                if (marked) {
                    hidden.name = 'remove_media[]';
                    hidden.value = m.file;
                    wrap.appendChild(hidden);
                    btn.classList.add('marked');
                } else {
                    hidden.name = '';
                    btn.classList.remove('marked');
                }
            };
            wrap.appendChild(btn);
            list.appendChild(wrap);
        });
    }

    modal.style.display = 'block';
}

// File preview
let _newFiles = [];
const fileInput = document.getElementById('ann_media');
const previewGrid = document.getElementById('previewGrid');

fileInput.addEventListener('change', () => addFiles(fileInput.files));

function addFiles(files) {
    Array.from(files).forEach(f => {
        _newFiles.push(f);
        const item = document.createElement('div');
        item.className = 'preview-item';
        const btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'preview-remove'; btn.innerHTML = '&times;';
        const idx = _newFiles.length - 1;
        btn.onclick = () => { _newFiles.splice(idx, 1); item.remove(); syncFileInput(); };

        if (f.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(f);
            item.appendChild(img);
        } else {
            const vid = document.createElement('video');
            vid.src = URL.createObjectURL(f);
            item.appendChild(vid);
        }
        item.appendChild(btn);
        previewGrid.appendChild(item);
    });
    syncFileInput();
}

function syncFileInput() {
    const dt = new DataTransfer();
    _newFiles.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
}

// Drag & drop
const zone = document.getElementById('uploadZone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('drag-over');
    addFiles(e.dataTransfer.files);
});
</script>
</body>
</html>
