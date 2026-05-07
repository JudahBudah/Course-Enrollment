<?php
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/no_cache.php';

function check_admin_login($con)
{
    if (isset($_SESSION['admin_id'])) {
        $id   = $_SESSION['admin_id'];
        $stmt = mysqli_prepare($con, 'SELECT * FROM admins WHERE admin_id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $admin_data = mysqli_fetch_assoc($result);

            // Cache username in session for log_activity()
            if (empty($_SESSION['admin_username'])) {
                $_SESSION['admin_username'] = $admin_data['username'];
            }

            // Make pending drop count globally available
            $dr = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM enrollments WHERE status = 'drop_requested'"));
            $GLOBALS['pending_drops'] = (int)($dr['c'] ?? 0);

            return $admin_data;
        }
    }

    header('Location: ../../pages/login_hub.php?portal=admin');
    die;
}

function log_activity($con, string $action, string $entity = '', string $detail = ''): void
{
    $admin_id       = $_SESSION['admin_id']       ?? null;
    $admin_username = $_SESSION['admin_username'] ?? 'unknown';
    $detail         = mb_substr(trim($detail), 0, 255);

    // Ensure table exists (safe to call multiple times — MySQL no-ops if exists)
    if (empty($_SESSION['logs_table_checked'])) {
        mysqli_query($con, "CREATE TABLE IF NOT EXISTS admin_logs (
            log_id         INT NOT NULL AUTO_INCREMENT,
            admin_id       INT DEFAULT NULL,
            admin_username VARCHAR(50) DEFAULT NULL,
            action         VARCHAR(100) NOT NULL,
            entity         VARCHAR(50)  DEFAULT NULL,
            detail         VARCHAR(255) DEFAULT NULL,
            created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (log_id),
            KEY idx_created_at (created_at),
            KEY idx_admin_id   (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $_SESSION['logs_table_checked'] = true;
    }

    $stmt = mysqli_prepare($con,
        'INSERT INTO admin_logs (admin_id, admin_username, action, entity, detail) VALUES (?,?,?,?,?)');
    mysqli_stmt_bind_param($stmt, 'issss', $admin_id, $admin_username, $action, $entity, $detail);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function is_superadmin(): bool
{
    return ($_SESSION['admin_role'] ?? '') === 'superadmin';
}

function require_superadmin($con): void
{
    $id  = $_SESSION['admin_id'] ?? 0;
    $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT role FROM admins WHERE admin_id = $id LIMIT 1"));
    if (!$row || $row['role'] !== 'superadmin') {
        header('Location: admin_home.php?error=unauthorized');
        die;
    }
}

// ── System Settings ─────────────────────────────────────────

function ensure_settings_table($con): void
{
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS system_settings (
        setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL,
        updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function get_setting($con, string $key, string $default = ''): string
{
    ensure_settings_table($con);
    $stmt = mysqli_prepare($con, "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ? $row['setting_value'] : $default;
}

function save_setting($con, string $key, string $value): void
{
    ensure_settings_table($con);
    $stmt = mysqli_prepare($con,
        "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Returns the current enrollment period status.
 * 'enrollment'      — regular enrollment window is open
 * 'late_enrollment' — late enrollment + add/drop window is open
 * 'closed'          — outside all windows
 *
 * Falls back to the legacy 'enrollment_open' toggle when no dates are set.
 */
function get_enrollment_period($con): string
{
    $enroll_start      = get_setting($con, 'enrollment_start',      '');
    $enroll_end        = get_setting($con, 'enrollment_end',        '');
    $late_start        = get_setting($con, 'late_enrollment_start', '');
    $late_end          = get_setting($con, 'late_enrollment_end',   '');

    // If no dates configured, default to closed
    if (empty($enroll_start) && empty($enroll_end) && empty($late_start) && empty($late_end)) {
        return 'closed';
    }

    $now = time();

    if (!empty($enroll_start) && !empty($enroll_end)) {
        if ($now >= strtotime($enroll_start) && $now <= strtotime($enroll_end)) {
            return 'enrollment';
        }
    }

    if (!empty($late_start) && !empty($late_end)) {
        if ($now >= strtotime($late_start) && $now <= strtotime($late_end)) {
            return 'late_enrollment';
        }
    }

    return 'closed';
}
