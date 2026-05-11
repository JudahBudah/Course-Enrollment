<?php
session_start();
include("connection.php");

if (!isset($_SESSION['faculty_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    die;
}

// Ensure grade_entries table exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS grade_entries (
    entry_id      INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    class_id      INT NOT NULL,
    student_id    INT NOT NULL,
    class_standing DECIMAL(5,2) DEFAULT NULL,
    quiz           DECIMAL(5,2) DEFAULT NULL,
    midterms       DECIMAL(5,2) DEFAULT NULL,
    finals         DECIMAL(5,2) DEFAULT NULL,
    computed_grade DECIMAL(5,2) GENERATED ALWAYS AS (
        ROUND(
            COALESCE(class_standing,0)*0.30 +
            COALESCE(quiz,0)*0.30 +
            COALESCE(midterms,0)*0.20 +
            COALESCE(finals,0)*0.20
        , 2)
    ) STORED,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enroll (enrollment_id)
)");

// Ensure grade_history table exists with unique constraint on (class_id, student_id)
mysqli_query($con, "CREATE TABLE IF NOT EXISTS grade_history (
    history_id     INT AUTO_INCREMENT PRIMARY KEY,
    class_id       INT NOT NULL,
    faculty_id     INT NOT NULL,
    subject_code   VARCHAR(50) NOT NULL,
    subject_name   VARCHAR(255) NOT NULL,
    section        VARCHAR(100) NOT NULL,
    semester       VARCHAR(20) NOT NULL,
    school_year    VARCHAR(20) NOT NULL,
    student_id     INT NOT NULL,
    student_number VARCHAR(50) NOT NULL,
    student_name   VARCHAR(255) NOT NULL,
    class_standing DECIMAL(5,2) DEFAULT NULL,
    quiz           DECIMAL(5,2) DEFAULT NULL,
    midterms       DECIMAL(5,2) DEFAULT NULL,
    finals         DECIMAL(5,2) DEFAULT NULL,
    computed_grade DECIMAL(5,2) DEFAULT NULL,
    point_grade    VARCHAR(10) DEFAULT NULL,
    remarks        VARCHAR(20) DEFAULT NULL,
    finalized_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_class   (class_id),
    KEY idx_faculty (faculty_id),
    UNIQUE KEY uq_class_student (class_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add unique key to existing grade_history table if it doesn't exist yet
$idx_check = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) as c FROM information_schema.statistics
     WHERE table_schema = DATABASE()
       AND table_name = 'grade_history'
       AND index_name = 'uq_class_student'"
));
if ((int)($idx_check['c'] ?? 0) === 0) {
    // First deduplicate: keep only the latest row per (class_id, student_id)
    mysqli_query($con,
        "DELETE gh FROM grade_history gh
         INNER JOIN grade_history gh2
           ON gh.class_id = gh2.class_id
          AND gh.student_id = gh2.student_id
          AND gh.history_id < gh2.history_id"
    );
    // Then add the unique key
    mysqli_query($con, "ALTER TABLE grade_history ADD UNIQUE KEY uq_class_student (class_id, student_id)");
}

// Fix computed_grade column to use simple weighted sum (missing fields = 0)
$col_expr = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT GENERATION_EXPRESSION as expr FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grade_entries' AND COLUMN_NAME = 'computed_grade'"
));
$correct_expr = 'ROUND(COALESCE(class_standing,0)*0.30+COALESCE(quiz,0)*0.30+COALESCE(midterms,0)*0.20+COALESCE(finals,0)*0.20,2)';
if ($col_expr && trim(str_replace(' ', '', $col_expr['expr'] ?? '')) !== trim(str_replace(' ', '', $correct_expr))) {
    mysqli_query($con,
        "ALTER TABLE grade_entries MODIFY COLUMN computed_grade DECIMAL(5,2) GENERATED ALWAYS AS (
            ROUND(COALESCE(class_standing,0)*0.30+COALESCE(quiz,0)*0.30+COALESCE(midterms,0)*0.20+COALESCE(finals,0)*0.20,2)
        ) STORED"
    );
}

header('Content-Type: application/json');

// ── CSRF validation ─────────────────────────────────────────────────────────
$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Invalid CSRF token']);
    die;
}

$action = $_POST['action'] ?? '';

// ── Helper: check if a class has finalized grades ───────────────────────────
function isFinalized(mysqli $con, int $class_id): bool {
    $stmt = mysqli_prepare($con, "SELECT grades_finalized FROM classes WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $class_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $r && (int)$r['grades_finalized'] === 1;
}

// ── Helper: verify faculty owns the class ───────────────────────────────────
function facultyOwnsClass(mysqli $con, int $class_id, int $faculty_id): bool {
    $stmt = mysqli_prepare($con, "SELECT class_id FROM classes WHERE class_id = ? AND faculty_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $class_id, $faculty_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return (bool)$r;
}

// ── Save a single cell ──────────────────────────────────────────────────────
if ($action === 'save_cell') {
    $enrollment_id = (int)$_POST['enrollment_id'];
    $student_id    = (int)$_POST['student_id'];
    $class_id      = (int)$_POST['class_id'];
    $faculty_id    = (int)$_SESSION['faculty_id'];
    $field         = $_POST['field'] ?? '';
    $value         = $_POST['value'] === '' ? null : (float)$_POST['value'];

    if (!facultyOwnsClass($con, $class_id, $faculty_id)) {
        echo json_encode(['ok'=>false,'msg'=>'Unauthorized']); die;
    }

    $allowed = ['class_standing','quiz','midterms','finals'];
    if (!in_array($field, $allowed)) {
        echo json_encode(['ok'=>false,'msg'=>'Invalid field']); die;
    }
    if ($value !== null && ($value < 0 || $value > 100)) {
        echo json_encode(['ok'=>false,'msg'=>'Value must be 0–100']); die;
    }

    $stmt = mysqli_prepare($con,
        "INSERT INTO grade_entries (enrollment_id, class_id, student_id, $field)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE $field = ?"
    );
    $bind_type = $value === null ? 'iiiss' : 'iiidd';
    mysqli_stmt_bind_param($stmt, $bind_type, $enrollment_id, $class_id, $student_id, $value, $value);
    mysqli_stmt_execute($stmt);

    $row_stmt = mysqli_prepare($con, "SELECT class_standing, quiz, midterms, finals FROM grade_entries WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($row_stmt, 'i', $enrollment_id);
    mysqli_stmt_execute($row_stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($row_stmt));

    if ($row) {
        $computed = round(
            (float)($row['class_standing'] ?? 0) * 0.30 +
            (float)($row['quiz']           ?? 0) * 0.30 +
            (float)($row['midterms']       ?? 0) * 0.20 +
            (float)($row['finals']         ?? 0) * 0.20
        , 2);
    } else {
        $computed = null;
    }
    $transmuted = $computed !== null ? transmute($computed) : null;
    $point      = $transmuted !== null ? pointGrade($transmuted) : null;
    $remark     = $point !== null ? remark($point) : 'pending';

    echo json_encode([
        'ok'         => true,
        'computed'   => $computed !== null ? number_format($computed, 2) : null,
        'transmuted' => $transmuted,
        'point'      => $point,
        'remark'     => $remark,
    ]);
    die;
}

// ── Finalize grades for a class ─────────────────────────────────────────────
if ($action === 'finalize_class') {
    $class_id   = (int)$_POST['class_id'];
    $faculty_id = (int)$_SESSION['faculty_id'];

    // Verify this faculty owns the class
    $owns = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT class_id FROM classes WHERE class_id = $class_id AND faculty_id = $faculty_id"
    ));
    if (!$owns) {
        echo json_encode(['ok'=>false,'msg'=>'Unauthorized']); die;
    }
    // Get class info for grades table
    $class_info = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT semester, school_year, subject_id FROM classes WHERE class_id = $class_id"
    ));

    // Process each student's grade entry
    $entries = mysqli_query($con,
        "SELECT e.enrollment_id, e.student_id,
                COALESCE(ge.computed_grade, 0) as computed_grade,
                ge.enrollment_id as has_entry
         FROM enrollments e
         LEFT JOIN grade_entries ge ON ge.enrollment_id = e.enrollment_id
         WHERE e.class_id = $class_id AND e.status IN ('confirmed','ongoing')"
    );
    while ($entry = mysqli_fetch_assoc($entries)) {
        $student_id = (int)$entry['student_id'];

        // No grade entry at all — mark INC
        if ($entry['has_entry'] === null) {
            $stmt = mysqli_prepare($con,
                "INSERT INTO grades (student_id, subject_id, grade, status, semester, school_year)
                 VALUES (?, ?, 'INC', 'Incomplete', ?, ?)
                 ON DUPLICATE KEY UPDATE grade = 'INC', status = 'Incomplete'"
            );
            mysqli_stmt_bind_param($stmt, 'iiss',
                $student_id, $class_info['subject_id'],
                $class_info['semester'], $class_info['school_year']
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            mysqli_query($con, "UPDATE students SET registration_status = 'Irregular' WHERE student_id = $student_id");
            continue;
        }

        $computed   = (float)$entry['computed_grade'];
        $point      = pointGrade(transmute($computed));
        $status     = $point === '5.00' ? 'Failed' : 'Passed';

        // Write final grade to grades table so admin can see it
        $stmt = mysqli_prepare($con,
            "INSERT INTO grades (student_id, subject_id, grade, status, semester, school_year)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE grade = VALUES(grade), status = VALUES(status)"
        );
        mysqli_stmt_bind_param($stmt, 'iissss',
            $student_id, $class_info['subject_id'],
            $point, $status,
            $class_info['semester'], $class_info['school_year']
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Keep enrollment as 'confirmed' — students remain in the class record.
        // The class itself is closed; enrollment status stays confirmed so
        // students can still see their subjects until the next semester.

        // TC024: auto-flag student as Irregular if they failed
        if ($point === '5.00') {
            mysqli_query($con,
                "UPDATE students SET registration_status = 'Irregular'
                 WHERE student_id = $student_id"
            );
        }
    }

    mysqli_query($con,
        "UPDATE classes SET grades_finalized = 1, grades_finalized_at = NOW(), status = 'closed' WHERE class_id = $class_id"
    );

    // ── Snapshot full classlist into grade_history ──────────────────────────
    $class_full = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT c.section, c.semester, c.school_year, s.subject_code, s.subject_name
         FROM classes c JOIN subjects s ON c.subject_id = s.subject_id
         WHERE c.class_id = $class_id"
    ));
    $snap_stmt = mysqli_prepare($con,
        "INSERT IGNORE INTO grade_history
            (class_id, faculty_id, subject_code, subject_name, section, semester, school_year,
             student_id, student_number, student_name,
             class_standing, quiz, midterms, finals, computed_grade, point_grade, remarks)
         VALUES (?,?,?,?,?,?,?, ?,?,?, ?,?,?,?,?,?,?)"
    );
    $all_students = mysqli_query($con,
        "SELECT e.student_id,
                s.student_number,
                CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_name IS NOT NULL AND s.middle_name != '', CONCAT(' ', LEFT(s.middle_name,1), '.'), '')) AS student_name,
                ge.class_standing, ge.quiz, ge.midterms, ge.finals, ge.computed_grade
         FROM enrollments e
         JOIN students s ON e.student_id = s.student_id
         LEFT JOIN grade_entries ge ON ge.enrollment_id = e.enrollment_id
         WHERE e.class_id = $class_id"
    );
    while ($st = mysqli_fetch_assoc($all_students)) {
        $cg  = $st['computed_grade'] !== null ? (float)$st['computed_grade'] : null;
        $pg  = $cg !== null ? pointGrade(transmute($cg)) : null;
        $rm  = $pg !== null ? remark($pg) : 'pending';
        mysqli_stmt_bind_param($snap_stmt, 'iisssssissdddddss',
            $class_id, $faculty_id,
            $class_full['subject_code'], $class_full['subject_name'],
            $class_full['section'], $class_full['semester'], $class_full['school_year'],
            $st['student_id'], $st['student_number'], $st['student_name'],
            $st['class_standing'], $st['quiz'], $st['midterms'], $st['finals'],
            $cg, $pg, $rm
        );
        mysqli_stmt_execute($snap_stmt);
    }
    mysqli_stmt_close($snap_stmt);

    // Do NOT change enrollment statuses — students remain 'confirmed' in the
    // closed class so they can still see their subjects this semester.
    // Enrollments will naturally disappear for students when the system
    // semester/year advances and they enroll in new classes.

    echo json_encode(['ok'=>true]);
    die;
}

// ── Load entries for a class ────────────────────────────────────────────────
if ($action === 'load') {
    $class_id   = (int)$_POST['class_id'];
    $faculty_id = (int)$_SESSION['faculty_id'];
    if (!facultyOwnsClass($con, $class_id, $faculty_id)) {
        echo json_encode(['ok'=>false,'msg'=>'Unauthorized']); die;
    }
    $rows = [];
    $stmt = mysqli_prepare($con, "SELECT ge.* FROM grade_entries ge WHERE ge.class_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $class_id);
    mysqli_stmt_execute($stmt);
    $q = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($q)) $rows[$r['enrollment_id']] = $r;
    echo json_encode(['ok'=>true,'entries'=>$rows]);
    die;
}

echo json_encode(['ok'=>false,'msg'=>'Unknown action']);

// ── Helpers ─────────────────────────────────────────────────────────────────
function transmute(float $g): int {
    if ($g >= 97) return 99;
    if ($g >= 94) return 96;
    if ($g >= 91) return 93;
    if ($g >= 88) return 90;
    if ($g >= 85) return 87;
    if ($g >= 82) return 84;
    if ($g >= 79) return 81;
    if ($g >= 76) return 78;
    if ($g >= 73) return 75;
    if ($g >= 70) return 72;
    if ($g >= 67) return 69;
    if ($g >= 64) return 66;
    if ($g >= 61) return 63;
    if ($g >= 55) return 60;
    return 55;
}

function pointGrade(int $t): string {
    if ($t >= 97) return '1.00';
    if ($t >= 94) return '1.25';
    if ($t >= 91) return '1.50';
    if ($t >= 88) return '1.75';
    if ($t >= 85) return '2.00';
    if ($t >= 82) return '2.25';
    if ($t >= 79) return '2.50';
    if ($t >= 76) return '2.75';
    if ($t >= 73) return '3.00';
    if ($t >= 70) return '4.00';
    return '5.00';
}

function remark(string $p): string {
    if ($p === '5.00') return 'failed';
    if ($p === '4.00') return 'conditional';
    return 'passed';
}
?>
