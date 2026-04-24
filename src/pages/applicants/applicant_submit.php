<?php
session_start();
include("../../php/connection.php");
include("../../php/applicant_functions.php");

$applicant_data = check_applicant_login($con);

// Ensure upload columns exist
$doc_names = ['form138', 'birth_cert', 'good_moral', 'our_au001', 'our_au002'];
$cols = [];
$col_res = mysqli_query($con, "SHOW COLUMNS FROM applicants");
while ($c = mysqli_fetch_assoc($col_res)) $cols[] = $c['Field'];
foreach ($doc_names as $dn) {
    if (!in_array('doc_' . $dn, $cols))
        mysqli_query($con, "ALTER TABLE applicants ADD COLUMN doc_{$dn} VARCHAR(255) DEFAULT NULL");
}
// Re-fetch after possible ALTER
$applicant_data = check_applicant_login($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicant_id = $applicant_data['applicant_id'];
    $upload_dir   = __DIR__ . "/../../uploads/applicants/{$applicant_id}/";

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $allowed_ext  = ['pdf', 'jpg', 'jpeg', 'png'];
    $max_size     = 5 * 1024 * 1024; // 5 MB
    $saved        = [];
    $errors       = [];

    foreach ($doc_names as $dn) {
        if (empty($_FILES[$dn]['name'])) continue;

        $file     = $_FILES[$dn];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error for {$dn}.";
            continue;
        }
        if (!in_array($ext, $allowed_ext)) {
            $errors[] = "{$dn}: Invalid file type. Allowed: PDF, JPG, PNG.";
            continue;
        }
        if ($file['size'] > $max_size) {
            $errors[] = "{$dn}: File exceeds 5MB limit.";
            continue;
        }

        $filename = $dn . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
            $saved[$dn] = $filename;
        } else {
            $errors[] = "Failed to save {$dn}. Check server permissions.";
        }
    }

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }

    if (!empty($saved)) {
        $set_parts = [];
        $types     = '';
        $values    = [];
        foreach ($saved as $dn => $fname) {
            $set_parts[] = "doc_{$dn} = ?";
            $types      .= 's';
            $values[]    = $fname;
        }
        $set_parts[] = 'documents_submitted = 1';
        $types      .= 'i';
        $values[]    = $applicant_id;

        $stmt = mysqli_prepare($con, "UPDATE applicants SET " . implode(', ', $set_parts) . " WHERE applicant_id = ?");
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        mysqli_stmt_execute($stmt);
        $success = "Documents uploaded successfully!";
        $applicant_data = check_applicant_login($con);
    } elseif (empty($errors)) {
        $error = "Please select at least one file to upload.";
    }
}

$initials  = strtoupper(
    substr($applicant_data['first_name'] ?? 'A', 0, 1) .
    substr($applicant_data['last_name']  ?? 'P', 0, 1)
);
$full_name = htmlspecialchars(
    trim(($applicant_data['first_name'] ?? '') . ' ' . ($applicant_data['last_name'] ?? ''))
);

$documents = [
    [
        'name'  => 'form138',
        'label' => 'Report Card (Form 138)',
        'desc'  => 'Senior High School Report Card',
    ],
    [
        'name'  => 'birth_cert',
        'label' => 'PSA Issued Birth Certificate',
        'desc'  => 'Original or certified true copy',
    ],
    [
        'name'  => 'good_moral',
        'label' => 'Good Moral Character',
        'desc'  => 'Certificate from previous school',
    ],
    [
        'name'  => 'our_au001',
        'label' => 'OUR Waiver and Notice of Undertaking',
        'desc'  => 'OUR AU001 Form',
    ],
    [
        'name'  => 'our_au002',
        'label' => 'OUR Admission Data & Personal Information',
        'desc'  => 'OUR AU002 Form',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Documents – PLM Applicant Portal</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/applicant/applicant_main.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_submit.css">
</head>
<body>

<!-- ── Top Nav ─────────────────────────────────────────── -->
<header>
    <div class="nav-section">

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
            <div class="acc-name"><?php echo $full_name; ?></div>
            <div class="acc-initials"><?php echo $initials; ?></div>
        </div>

    </div>

    <!-- ── Side Nav ──────────────────────────────────────── -->
    <nav class="main-nav" id="navMenu">
        <div class="nav-wrapper">
            <ul class="main-ul">
                <li>
                    <a href="applicant_home.php">
                        <i class="fa-solid fa-house"></i>
                        <span class="li-name">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="applicant_apply.php">
                        <i class="fa-solid fa-file-pen"></i>
                        <span class="li-name">Application Form</span>
                    </a>
                </li>
                <li>
                    <a href="applicant_submit.php" class="active">
                        <i class="fa-solid fa-file-arrow-up"></i>
                        <span class="li-name">Submit Documents</span>
                    </a>
                </li>
                <li>
                    <a href="applicant_exam.php">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span class="li-name">Exam Schedule</span>
                    </a>
                </li>
                <li>
                    <a href="../../php/applicant_logout.php" class="logout-link">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="li-name">Logout</span>
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


<!-- ── Page Body ───────────────────────────────────────── -->
<div class="main-flex">
    <div class="spacer"></div>

    <main>

        <div class="page-header">
            <h1>Submit Required Documents</h1>
            <p>Upload all required documents for your application</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="success-message">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($applicant_data['documents_submitted'] ?? 0): ?>
            <div class="info-message">
                <i class="fa-solid fa-circle-info"></i>
                You have already submitted your documents. You can re-upload if needed.
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <!-- Required Documents -->
            <div class="card">
                <div class="card-header">
                    <h2>Required Documents</h2>
                </div>

                <div class="documents-list">
                    <?php foreach ($documents as $doc): 
                        $uploaded = $applicant_data['doc_' . $doc['name']] ?? null;
                    ?>
                    <div class="document-item">
                        <div class="document-info">
                            <i class="fa-solid fa-file"></i>
                            <div>
                                <h4><?php echo htmlspecialchars($doc['label']); ?></h4>
                                <p><?php echo htmlspecialchars($doc['desc']); ?></p>
                                <?php if ($uploaded): ?>
                                    <p style="color:var(--text);font-size:0.85rem;margin-top:0.25rem;">
                                        <i class="fa-solid fa-check-circle"></i> Uploaded: <?php echo htmlspecialchars($uploaded); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="document-upload">
                            <?php if ($uploaded): ?>
                                <button type="button" class="upload-btn view-btn"
                                    data-src="../../uploads/applicants/<?php echo $applicant_data['applicant_id']; ?>/<?php echo htmlspecialchars($uploaded); ?>"
                                    data-name="<?php echo htmlspecialchars($uploaded); ?>"
                                    data-ext="<?php echo strtolower(pathinfo($uploaded, PATHINFO_EXTENSION)); ?>"
                                    onclick="openFileModal(this)">
                                    <i class="fa-solid fa-eye"></i> View File
                                </button>
                            <?php endif; ?>
                            <input type="file"
                                   name="<?php echo $doc['name']; ?>"
                                   id="<?php echo $doc['name']; ?>"
                                   accept=".pdf,.jpg,.jpeg,.png">
                            <label for="<?php echo $doc['name']; ?>" class="upload-btn">
                                <i class="fa-solid fa-upload"></i>
                                <?php echo $uploaded ? 'Replace File' : 'Choose File'; ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Important Notes -->
            <div class="card">
                <div class="card-header">
                    <h2>Important Notes</h2>
                </div>
                <div class="notes-list">
                    <div class="note-item">
                        <i class="fa-solid fa-circle-check"></i>
                        <p>All documents must be clear and readable</p>
                    </div>
                    <div class="note-item">
                        <i class="fa-solid fa-circle-check"></i>
                        <p>Accepted formats: PDF, JPG, PNG (Max 5MB per file)</p>
                    </div>
                    <div class="note-item">
                        <i class="fa-solid fa-circle-check"></i>
                        <p>Ensure all information is visible and not cut off</p>
                    </div>
                    <div class="note-item">
                        <i class="fa-solid fa-circle-check"></i>
                        <p>Submit all documents within 7 days of application</p>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Submit Documents</span>
                </button>
            </div>

        </form>
    </main>
</div><!-- /.main-flex -->

<!-- ── File Preview Modal ──────────────────────────────── -->
<div class="file-modal" id="fileModal">
    <div class="file-modal-box">
        <div class="file-modal-header">
            <span id="fileModalName"></span>
            <button class="file-modal-close" onclick="closeFileModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="file-modal-body" id="fileModalBody"></div>
    </div>
</div>

<script src="../../js/applicant/applicant_main.js"></script>
<script src="../../js/applicant/applicant_submit.js"></script>
<script>
    function openFileModal(btn) {
        const src  = btn.dataset.src;
        const name = btn.dataset.name;
        const ext  = btn.dataset.ext;
        document.getElementById('fileModalName').textContent = name;
        const body = document.getElementById('fileModalBody');
        body.innerHTML = ext === 'pdf'
            ? `<iframe src="${src}"></iframe>`
            : `<img src="${src}" alt="${name}">`;
        document.getElementById('fileModal').classList.add('open');
    }
    function closeFileModal() {
        document.getElementById('fileModal').classList.remove('open');
        document.getElementById('fileModalBody').innerHTML = '';
    }
    document.getElementById('fileModal').addEventListener('click', function(e) {
        if (e.target === this) closeFileModal();
    });
</script>
</body>
</html>