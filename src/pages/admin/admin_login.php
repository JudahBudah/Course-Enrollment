<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");
require_once("../../php/login_rate_limit.php");

$rl_key = 'login_attempts_admin';
$error  = null;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (rate_limit_check($rl_key, $error)) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!empty($username) && !empty($password)) {
            $stmt = mysqli_prepare($con, "SELECT * FROM admins WHERE username = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $admin_data = mysqli_fetch_assoc($result);

                if (password_verify($password, $admin_data['password'])) {
                    rate_limit_reset($rl_key);
                    session_regenerate_id(true);
                    $_SESSION['admin_id']       = $admin_data['admin_id'];
                    $_SESSION['admin_username'] = $admin_data['username'];
                    $_SESSION['admin_role']     = $admin_data['role'] ?? 'admin';
                    log_activity($con, 'Admin logged in', 'auth', $admin_data['username']);
                    header("Location: admin_home.php");
                    die;
                }
            }

            rate_limit_fail($rl_key);
            $error = "Invalid username or password";
        } else {
            $error = "Please enter both username and password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PLM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/admin/admin_login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-brand">
                <img src="../../assets/plm-logo.png" alt="PLM Logo">
                <div>
                    <h1>PLM</h1>
                    <p>Admin Portal</p>
                </div>
            </div>
            <div class="login-illustration">
                <img src="../../assets/plm-torch.webp" alt="PLM Torch">
            </div>
        </div>

        <div class="login-right">
            <form class="login-form" method="POST">
                <h2>Admin Access</h2>
                <p class="login-subtitle">Sign in to manage the system</p>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fa-solid fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php $left = attempts_left($rl_key); if ($left <= 2 && $left > 0): ?>
                    <div class="error-message" style="background:rgba(180,100,0,0.12);border-color:#b46400;color:#b46400;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <?php echo $left; ?> attempt(s) remaining before lockout.
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <?php if (needs_captcha($rl_key)) echo captcha_widget(); ?>

                <button type="submit" class="btn-login">
                    <span>Sign In</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

                <div class="login-footer">
                    <a href="../../../index.html" class="link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>







