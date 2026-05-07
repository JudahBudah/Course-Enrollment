<?php
/**
 * Login rate limiting + Google reCAPTCHA v2 verification helper.
 *
 * Usage in any login page (after session_start()):
 *   require_once '../../php/login_rate_limit.php';
 *   $limit_key = 'login_attempts_faculty';   // unique per portal
 *
 *   // On POST — check rate limit first:
 *   rate_limit_check($limit_key, $error);
 *
 *   // After a failed login:
 *   rate_limit_fail($limit_key);
 *
 *   // After a successful login:
 *   rate_limit_reset($limit_key);
 *
 *   // In the form HTML — show CAPTCHA when needed:
 *   if (needs_captcha($limit_key)) { echo captcha_widget(); }
 */

// ── Config ────────────────────────────────────────────────────────────────────
define('RL_MAX_ATTEMPTS',  5);    // failed attempts before lockout
define('RL_LOCKOUT_SECS',  300);  // lockout duration in seconds (5 min)
define('RL_CAPTCHA_AFTER', 3);    // show CAPTCHA after this many failures

// Get your keys from https://www.google.com/recaptcha/admin
// Use the test keys below for localhost development — they always pass.
define('RECAPTCHA_SITE_KEY',   '6Lf0hdYsAAAAAMHgyTRpLUYkWd76xgWA11XyELms');
define('RECAPTCHA_SECRET_KEY', '6Lf0hdYsAAAAAFftUxP9OsiyqQEu_9P3I3J8Y_F6');

// ── Rate limit check (call at top of POST handler) ───────────────────────────
// Returns true if the request should proceed, false if blocked.
function rate_limit_check(string $key, ?string &$error): bool {
    $attempts  = $_SESSION[$key . '_count']  ?? 0;
    $locked_at = $_SESSION[$key . '_locked'] ?? 0;

    // Still locked out?
    if ($locked_at && (time() - $locked_at) < RL_LOCKOUT_SECS) {
        $remaining = RL_LOCKOUT_SECS - (time() - $locked_at);
        $mins = ceil($remaining / 60);
        $error = "Too many failed attempts. Please wait {$mins} minute(s) before trying again.";
        return false;
    }

    // Lockout expired — reset
    if ($locked_at && (time() - $locked_at) >= RL_LOCKOUT_SECS) {
        $_SESSION[$key . '_count']  = 0;
        $_SESSION[$key . '_locked'] = 0;
        $attempts = 0;
    }

    // Verify reCAPTCHA if required
    if ($attempts >= RL_CAPTCHA_AFTER) {
        $token = $_POST['g-recaptcha-response'] ?? '';
        if (!verify_captcha($token)) {
            $error = 'Please complete the CAPTCHA.';
            return false;
        }
    }

    return true;
}

// ── Record a failed attempt ───────────────────────────────────────────────────
function rate_limit_fail(string $key): void {
    $attempts = ($_SESSION[$key . '_count'] ?? 0) + 1;
    $_SESSION[$key . '_count'] = $attempts;
    if ($attempts >= RL_MAX_ATTEMPTS) {
        $_SESSION[$key . '_locked'] = time();
    }
}

// ── Reset on successful login ─────────────────────────────────────────────────
function rate_limit_reset(string $key): void {
    $_SESSION[$key . '_count']  = 0;
    $_SESSION[$key . '_locked'] = 0;
}

// ── Should CAPTCHA be shown? ──────────────────────────────────────────────────
function needs_captcha(string $key): bool {
    return ($_SESSION[$key . '_count'] ?? 0) >= RL_CAPTCHA_AFTER;
}

// ── Remaining attempts info ───────────────────────────────────────────────────
function attempts_left(string $key): int {
    $attempts = $_SESSION[$key . '_count'] ?? 0;
    return max(0, RL_MAX_ATTEMPTS - $attempts);
}

// ── reCAPTCHA v2 widget HTML ──────────────────────────────────────────────────
function captcha_widget(): string {
    return '<div class="g-recaptcha" data-sitekey="' . RECAPTCHA_SITE_KEY . '" style="margin:0.75rem 0;"></div>'
         . '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}

// ── Verify reCAPTCHA token with Google API ────────────────────────────────────
function verify_captcha(string $token): bool {
    if (empty($token)) return false;
    $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'secret'   => RECAPTCHA_SECRET_KEY,
                'response' => $token,
            ]),
        ],
    ]));
    if (!$response) return false;
    $data = json_decode($response, true);
    return !empty($data['success']);
}
