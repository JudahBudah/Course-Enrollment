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

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

// ── Helper: check if a class has finalized grades ───────────────────────────
function isFinalized(mysqli $con, int $class_id): bool {
    $r = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT grades_finalized FROM classes WHERE class_id = $class_id"
    ));
    return $r && (int)$r['grades_finalized'] === 1;
}

// ── Save a single cell ──────────────────────────────────────────────────────
if ($action === 'save_cell') {
    $enrollment_id = (int)$_POST['enrollment_id'];
    $student_id    = (int)$_POST['student_id'];
    $class_id      = (int)$_POST['class_id'];
    $field         = $_POST['field'] ?? '';
    $value         = $_POST['value'] === '' ? null : (float)$_POST['value'];

    if (isFinalized($con, $class_id)) {
        echo json_encode(['ok'=>false,'msg'=>'Grades are finalized and cannot be changed.']); die;
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

    $row = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT class_standing, quiz, midterms, finals, computed_grade
         FROM grade_entries WHERE enrollment_id = $enrollment_id"
    ));

    $computed   = $row ? (float)$row['computed_grade'] : null;
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
    if (isFinalized($con, $class_id)) {
        echo json_encode(['ok'=>false,'msg'=>'Already finalized']); die;
    }

    mysqli_query($con,
        "UPDATE classes SET grades_finalized = 1, grades_finalized_at = NOW() WHERE class_id = $class_id"
    );
    echo json_encode(['ok'=>true]);
    die;
}

// ── Load entries for a class ────────────────────────────────────────────────
if ($action === 'load') {
    $class_id = (int)$_POST['class_id'];
    $rows = [];
    $q = mysqli_query($con,
        "SELECT ge.* FROM grade_entries ge WHERE ge.class_id = $class_id"
    );
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
