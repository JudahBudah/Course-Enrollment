<?php
session_start();
include("../php/connection.php");

$error  = null;
$portal = $_POST['portal'] ?? $_GET['portal'] ?? 'applicant';
$view   = $_GET['view'] ?? 'login'; // login | register | verify

// Whitelist
if (!in_array($portal, ['applicant', 'student', 'faculty', 'admin'])) $portal = 'applicant';
if (!in_array($view, ['login','register','verify','forgot','reset-verify','reset-password'])) $view = 'login';
// register/verify only for applicant
if ($portal !== 'applicant' && in_array($view, ['register','verify'])) $view = 'login';
// forgot/reset not for admin
if ($portal === 'admin' && in_array($view, ['forgot','reset-verify','reset-password'])) $view = 'login';
// verify needs pending reg session
if ($view === 'verify' && empty($_SESSION['reg_pending'])) $view = 'register';
// reset-verify/reset-password need pending reset session
if (in_array($view, ['reset-verify','reset-password']) && empty($_SESSION['reset_pending'])) $view = 'forgot';
// reset-password needs verified flag
if ($view === 'reset-password' && empty($_SESSION['reset_pending']['verified'])) $view = 'reset-verify';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password  = $_POST['password'] ?? '';

    switch ($portal) {

        case 'student':
            $identifier = trim($_POST['identifier'] ?? '');
            if (!empty($identifier) && !empty($password)) {
                $stmt = mysqli_prepare($con, "SELECT * FROM students WHERE student_number = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "s", $identifier);
                mysqli_stmt_execute($stmt);
                $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if ($row && password_verify($password, $row['password'])) {
                    $_SESSION['student_id']     = $row['student_id'];
                    $_SESSION['student_number'] = $row['student_number'];
                    header("Location: student/student_home.php");
                    die;
                }
            }
            $error = "Invalid student number or password.";
            break;

        case 'applicant':
            $identifier = trim($_POST['identifier'] ?? '');
            if (!empty($identifier) && !empty($password)) {
                $stmt = mysqli_prepare($con, "SELECT * FROM applicants WHERE email = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "s", $identifier);
                mysqli_stmt_execute($stmt);
                $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if ($row && password_verify($password, $row['password'])) {
                    $_SESSION['applicant_id'] = $row['applicant_id'];
                    header("Location: applicants/applicant_home.php");
                    die;
                }
            }
            $error = "Invalid email or password.";
            break;

        case 'faculty':
            $identifier = trim($_POST['identifier'] ?? '');
            if (!empty($identifier) && !empty($password)) {
                $stmt = mysqli_prepare($con, "SELECT * FROM faculty WHERE email = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "s", $identifier);
                mysqli_stmt_execute($stmt);
                $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if ($row && password_verify($password, $row['password'])) {
                    if ($row['status'] !== 'active') {
                        $error = "Your account is currently " . htmlspecialchars($row['status']) . ". Please contact administration.";
                        break;
                    }
                    $_SESSION['faculty_id'] = $row['faculty_id'];
                    $_SESSION['email']      = $row['email'];
                    header("Location: faculty/faculty_home.php");
                    die;
                }
            }
            $error = $error ?? "Invalid email or password.";
            break;

        case 'admin':
            $identifier = trim($_POST['identifier'] ?? '');
            if (!empty($identifier) && !empty($password)) {
                $stmt = mysqli_prepare($con, "SELECT * FROM admins WHERE username = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "s", $identifier);
                mysqli_stmt_execute($stmt);
                $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if ($row && password_verify($password, $row['password'])) {
                    $_SESSION['admin_id'] = $row['admin_id'];
                    header("Location: admin/admin_home.php");
                    die;
                }
            }
            $error = "Invalid username or password.";
            break;
    }
}

// Portal display config
$portals = [
    'applicant' => ['label' => 'Applicant', 'icon' => 'fa-user-plus',       'id_label' => 'Email Address',       'id_placeholder' => 'your.email@example.com',       'heading' => 'Welcome, <em>Future Iskolar</em>',  'sub' => 'Sign in to check your application status.',
        'brand_sub' => 'Access your application portal. Submit requirements, track your admission status, and manage your application in one secure place.'],
    'student'   => ['label' => 'Student',   'icon' => 'fa-user-graduate',   'id_label' => 'Student ID Number',  'id_placeholder' => 'e.g. 202400000',              'heading' => 'Welcome back, <em>Iskolar</em>',    'sub' => 'Sign in to manage your enrollment and grades.',
        'brand_sub' => 'Access your academic portal. Manage enrollment, grades, and university services in one secure place.'],
    'faculty'   => ['label' => 'Faculty',   'icon' => 'fa-chalkboard-user', 'id_label' => 'Email Address',       'id_placeholder' => 'juan.delacruz@plm.edu.ph',     'heading' => 'Welcome, <em>Faculty Member</em>',  'sub' => 'Sign in to manage your classes and records.',
        'brand_sub' => 'Access your faculty portal. Manage classes, grading, and academic responsibilities in one secure place.'],
    'admin'     => ['label' => 'Admin',     'icon' => 'fa-shield-halved',   'id_label' => 'Username',            'id_placeholder' => 'Enter your admin username',    'heading' => 'Welcome, <em>Administrator</em>',   'sub' => 'Sign in to manage the system.',
        'brand_sub' => 'Access your administration portal. Oversee records, manage system operations, and handle university services in one secure place.'],
];

$current  = $portals[$portal];
$saved_id = isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : '';

// Page title per view
$titles = [
    'login'          => $current['heading'],
    'register'       => 'Create an <em>Account</em>',
    'verify'         => 'Verify your <em>Email</em>',
    'forgot'         => 'Forgot <em>Password</em>',
    'reset-verify'   => 'Enter Reset <em>Code</em>',
    'reset-password' => 'Set New <em>Password</em>',
];
$subs = [
    'login'          => $current['sub'],
    'register'       => 'Start your journey as an Iskolar ng Bayan.',
    'verify'         => 'Enter the 6-digit code sent to ' . htmlspecialchars($_SESSION['reg_pending']['email'] ?? ''),
    'forgot'         => 'Enter your ' . strtolower($current['id_label']) . ' and we\'ll send a reset code.',
    'reset-verify'   => 'Enter the 6-digit code sent to ' . htmlspecialchars($_SESSION['reset_pending']['email'] ?? ''),
    'reset-password' => 'Choose a new password for your account.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLM | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" href="../assets/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;1,300&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/login_hub.css">
    <link rel="stylesheet" href="../css/plm_loader.css">
</head>
<body>

    <!-- Loading Screen -->
    <div id="plm-loader">
        <div id="plm-loader-bar"></div>
        <div class="plm-loader-logo">
            <img src="../assets/plm-logo.png" alt="PLM">
            <div class="plm-loader-name">
                <p>PLM</p>
                <p>Pamantasan ng Lungsod ng Maynila</p>
            </div>
            <div class="plm-loader-dots">
                <span></span><span></span><span></span>
            </div>
            <p class="plm-loader-status" id="plm-loader-status">Authenticating...</p>
        </div>
    </div>

    <div class="bg"></div>

    <div class="page">

        <header>
            <a href="../../index.html">
                <div class="nav-logo">
                    <div class="nav-logo-emblem">
                        <img src="../assets/plm-logo.png" alt="PLM Logo">
                    </div>
                    <div class="nav-logo-text">
                        <div>PLM</div>
                        <div>EST. 1965</div>
                    </div>
                </div>
            </a>
        </header>

        <!-- LEFT: Branding Panel -->
        <div class="panel-left">
            <h1 class="brand-title">
                <span class="line"><span>PAMANTASAN</span></span>
                <span class="line"><span>NG LUNGSOD</span></span>
                <span class="line"><span>ng Maynila</span></span>
            </h1>
            <p class="brand-sub" id="brand-sub">
                <?php echo htmlspecialchars($current['brand_sub']); ?>
            </p>
        </div>

        <!-- RIGHT: Login Form Panel -->
        <div class="panel-right">
            <div class="login-card">

                <div class="login-card-header">
                    <h2><?php echo $titles[$view]; ?></h2>
                    <p><?php echo $subs[$view]; ?></p>
                </div>

                <!-- Portal Tabs (login view only) -->
                <?php if ($view === 'login'): ?>
                <div class="portal-wrapper">
                    <div class="portal-tabs">
                        <?php foreach ($portals as $key => $p): ?>
                            <button
                                class="portal-tab <?php echo $portal === $key ? 'active' : ''; ?>"
                                type="button"
                                data-portal="<?php echo $key; ?>">
                                <i class="fa-solid <?php echo $p['icon']; ?>"></i> <?php echo $p['label']; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── LOGIN FORM ── -->
                <?php if ($view === 'login'): ?>
                <form method="POST" id="login-form">
                    <input type="hidden" name="portal" id="portal-input" value="<?php echo htmlspecialchars($portal); ?>">

                    <?php if ($error): ?>
                        <div class="alert-error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="field-group">
                        <label class="field-label" for="identifier" id="id-label">
                            <?php echo htmlspecialchars($current['id_label']); ?>
                        </label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid <?php echo $portal === 'student' ? 'fa-id-card' : ($portal === 'admin' ? 'fa-user-shield' : 'fa-envelope'); ?>" id="id-icon"></i>
                            <input
                                type="<?php echo ($portal === 'applicant' || $portal === 'faculty') ? 'email' : 'text'; ?>"
                                id="identifier" name="identifier"
                                placeholder="<?php echo htmlspecialchars($current['id_placeholder']); ?>"
                                value="<?php echo $saved_id; ?>"
                                autocomplete="username" required>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="password">Password</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-lock"></i>
                            <input type="password" id="password" name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password" required>
                            <button class="toggle-pw" type="button" onclick="togglePw('password','pw-icon')">
                                <i class="fa-regular fa-eye" id="pw-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="login-help-container adjust">
                        <?php if ($portal === 'applicant'): ?>
                            <p class="login-help" >
                                <a href="login_hub.php?portal=applicant&view=forgot" class="link">Forgot password?</a>
                            </p>
                            <p class="login-help">
                                Don't have an account?
                                <a href="login_hub.php?portal=applicant&view=register" class="link space">Create Account</a>
                            </p>
                        <?php elseif ($portal !== 'admin'): ?>
                            <p class="login-help">
                                <a href="login_hub.php?portal=<?php echo $portal; ?>&view=forgot" class="link">Forgot password?</a>
                            </p>
                        <?php endif; ?>
                    </div>
                    

                    <button type="submit" class="btn-login">
                        <span>Sign In &nbsp;<i class="fa-solid fa-arrow-right"></i></span>
                    </button>
                </form>
                <div class="login-divider"><span>Powered by</span><img src="../assets/harinode.webp" ></div>
                <?php endif; ?>



                <!-- ── REGISTER FORM ── -->
                <?php if ($view === 'register'): ?>
                <form id="register-form" onsubmit="submitRegister(event)">
                    <div id="reg-alert" class="alert-error" style="display:none;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span id="reg-alert-msg"></span>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Email Address</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-envelope"></i>
                            <input type="email" id="reg-email" name="email"
                                placeholder="your.email@example.com" required>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Password</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-lock"></i>
                            <input type="password" id="reg-password" name="password"
                                placeholder="Minimum 6 characters" minlength="6" required>
                            <button class="toggle-pw" type="button" onclick="togglePw('reg-password','reg-pw-icon')">
                                <i class="fa-regular fa-eye" id="reg-pw-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Confirm Password</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-lock"></i>
                            <input type="password" id="reg-confirm" name="confirm_password"
                                placeholder="Re-enter your password" required>
                            <button class="toggle-pw" type="button" onclick="togglePw('reg-confirm','reg-confirm-icon')">
                                <i class="fa-regular fa-eye" id="reg-confirm-icon"></i>
                            </button>
                        </div>
                    </div>

                    <p class="login-help adjust">
                        Already have an account?
                        <a href="login_hub.php?portal=applicant" class="link space">Sign In</a>
                    </p>

                    <button type="submit" class="btn-login" id="reg-btn">
                        <span>Send Verification Code &nbsp;<i class="fa-solid fa-paper-plane"></i></span>
                    </button>
                </form>

                <div class="login-divider"><span>Powered by</span><img src="../assets/harinode.webp" ></div>

                <?php endif; ?>

                <!-- ── VERIFY FORM ── -->
                <?php if ($view === 'verify'): ?>
                <form id="verify-form" onsubmit="submitVerify(event)">
                    <div id="ver-alert" class="alert-error" style="display:none;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span id="ver-alert-msg"></span>
                    </div>

                    <div class="field-group">
                        <label class="field-label">6-Digit Verification Code</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-key"></i>
                            <input type="text" id="ver-code" name="code"
                                placeholder="e.g. 123456"
                                maxlength="6" inputmode="numeric"
                                autocomplete="one-time-code" required>
                        </div>
                        <div class="login-help-container">
                            <p class="login-help">
                                Wrong email?
                                <a href="login_hub.php?portal=applicant&view=register" class="link space">
                                    Start over
                                </a>
                            </p>
                            <div style="margin-top:0.5rem;font-size:0.72rem;color:rgba(242,243,242,0.35);">
                                Code expires in <span id="countdown">10:00</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="ver-btn">
                        <span>Verify &amp; Create Account &nbsp;<i class="fa-solid fa-check"></i></span>
                    </button>
                </form>

                <div class="login-divider"><span>Powered by</span><img src="../assets/harinode.webp" ></div>
                <?php endif; ?>


                <!-- ── FORGOT PASSWORD FORM ── -->
                <?php if ($view === 'forgot'): ?>
                <form id="forgot-form" onsubmit="submitForgot(event)">
                    <div id="fgt-alert" class="alert-error" style="display:none;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span id="fgt-alert-msg"></span>
                    </div>
                    <div id="fgt-success" class="alert-success" style="display:none;">
                        <i class="fa-solid fa-circle-check"></i>
                        <span id="fgt-success-msg"></span>
                    </div>

                    <div class="field-group">
                        <label class="field-label"><?php echo htmlspecialchars($current['id_label']); ?></label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid <?php echo $portal === 'student' ? 'fa-id-card' : 'fa-envelope'; ?>"></i>
                            <input type="<?php echo $portal === 'student' ? 'text' : 'email'; ?>"
                                id="fgt-identifier" placeholder="<?php echo htmlspecialchars($current['id_placeholder']); ?>" required>
                        </div>
                    </div>
                    <p class="login-help right">
                        <a href="login_hub.php?portal=<?php echo $portal; ?>" class="link"> 
                            <i class="fa-solid fa-arrow-left"></i> Back to Sign In
                        </a>
                    </p>

                    <button type="submit" class="btn-login" id="fgt-btn">
                        <span>Send Reset Code &nbsp;<i class="fa-solid fa-paper-plane"></i></span>
                    </button>
                </form>
                <div class="login-divider"><span>Powered by</span><img src="../assets/harinode.webp" ></div>
                <?php endif; ?>


                <!-- ── RESET VERIFY FORM ── -->
                <?php if ($view === 'reset-verify'): ?>
                <form id="reset-verify-form" onsubmit="submitResetVerify(event)">
                    <div id="rv-alert" class="alert-error" style="display:none;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span id="rv-alert-msg"></span>
                    </div>

                    <div class="field-group">
                        <label class="field-label">6-Digit Reset Code</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-key"></i>
                            <input type="text" id="rv-code" placeholder="e.g. 123456"
                                maxlength="6" inputmode="numeric" autocomplete="one-time-code" required>
                        </div>
                        <div class="login-help-container" style="margin-top: 5px;">
                            <p class="login-help">
                                <a href="login_hub.php?portal=<?php echo $portal; ?>&view=forgot" class="link">
                                    <i class="fa-solid fa-arrow-left"></i> Start over
                                </a>
                            </p>
                            <div class="login-help">
                                Code expires in <span id="rv-countdown">10:00</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="rv-btn">
                        <span>Verify Code &nbsp;<i class="fa-solid fa-check"></i></span>
                    </button>
                </form>
                <div class="login-divider"><span>Powered by</span><img src="../assets/harinode.webp" ></div>
                <?php endif; ?>

                <!-- ── RESET PASSWORD FORM ── -->
                <?php if ($view === 'reset-password'): ?>
                <form id="reset-pw-form" onsubmit="submitResetPassword(event)">
                    <div id="rp-alert" class="alert-error" style="display:none;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span id="rp-alert-msg"></span>
                    </div>

                    <div class="field-group">
                        <label class="field-label">New Password</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-lock"></i>
                            <input type="password" id="rp-password" placeholder="Minimum 6 characters" minlength="6" required>
                            <button class="toggle-pw" type="button" onclick="togglePw('rp-password','rp-pw-icon')">
                                <i class="fa-regular fa-eye" id="rp-pw-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Confirm New Password</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-lock"></i>
                            <input type="password" id="rp-confirm" placeholder="Re-enter new password" required>
                            <button class="toggle-pw" type="button" onclick="togglePw('rp-confirm','rp-confirm-icon')">
                                <i class="fa-regular fa-eye" id="rp-confirm-icon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="rp-btn">
                        <span>Reset Password &nbsp;<i class="fa-solid fa-arrow-right"></i></span>
                    </button>
                </form>
                <div class="login-divider"><span>Powered by</span><img src="../assets/harinode.webp" ></div>
                <p class="login-help">
                    <a href="login_hub.php?portal=<?php echo $portal; ?>" class="link">
                        <i class="fa-solid fa-arrow-left"></i> Back to Sign In
                    </a>
                </p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
        // ── Portal tab switching ──
        const portalConfig = {
            student:   { idLabel: 'Student ID Number',  idPlaceholder: 'e.g. 202400000',           idType: 'text',  idIcon: 'fa-id-card',     heading: 'Welcome back, <em>Iskolar</em>',      sub: 'Sign in to manage your enrollment and grades.',     brandSub: 'Access your academic portal. Manage enrollment, grades, and university services in one secure place.' },
            applicant: { idLabel: 'Email Address',       idPlaceholder: 'your.email@example.com',    idType: 'email', idIcon: 'fa-envelope',    heading: 'Welcome, <em>Future Iskolar</em>',    sub: 'Sign in to check your application status.', brandSub: 'Access your application portal. Submit requirements, track your admission status, and manage your application in one secure place.' },
            faculty:   { idLabel: 'Email Address',       idPlaceholder: 'juan.delacruz@plm.edu.ph',  idType: 'email', idIcon: 'fa-envelope',    heading: 'Welcome, <em>Faculty Member</em>',    sub: 'Sign in to manage your classes and records.', brandSub: 'Access your faculty portal. Manage classes, grading, and academic responsibilities in one secure place.' },
            admin:     { idLabel: 'Username',            idPlaceholder: 'Enter your admin username', idType: 'text',  idIcon: 'fa-user-shield', heading: 'Welcome, <em>Administrator</em>',               sub: 'Sign in to manage the system.', brandSub: 'Access your administration portal. Oversee records, manage system operations, and handle university services in one secure place.' },
        };

        function switchPortal(key) {
            const cfg = portalConfig[key];
            if (!cfg) return;
            document.querySelectorAll('.portal-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.portal-tab[data-portal="${key}"]`).classList.add('active');
            document.getElementById('portal-input').value = key;
            const idInput = document.getElementById('identifier');
            const idLabel = document.getElementById('id-label');
            const idIcon  = document.getElementById('id-icon');
            if (idLabel) idLabel.textContent = cfg.idLabel;
            if (idInput) { idInput.placeholder = cfg.idPlaceholder; idInput.type = cfg.idType; idInput.value = ''; }
            if (idIcon)  idIcon.className = 'field-icon fa-solid ' + cfg.idIcon;
            document.querySelector('.login-card-header h2').innerHTML  = cfg.heading;
            document.querySelector('.login-card-header p').textContent = cfg.sub;
        }

        document.querySelectorAll('.portal-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const key = tab.dataset.portal;
                window.location.href = `login_hub.php?portal=${key}`;
            });
        });

        // Activate tab from URL param OR from failed POST portal
        const activePortal = '<?php echo htmlspecialchars($portal); ?>';
        if (portalConfig[activePortal]) switchPortal(activePortal);

        // ── Password toggle ──
        function togglePw(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // ── Register form submit ──
        async function submitRegister(e) {
            e.preventDefault();
            const btn      = document.getElementById('reg-btn');
            const alert    = document.getElementById('reg-alert');
            const alertMsg = document.getElementById('reg-alert-msg');
            alert.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span>Sending... &nbsp;<i class="fa-solid fa-spinner fa-spin"></i></span>';

            const data = new FormData();
            data.append('action', 'send_code');
            data.append('email',            document.getElementById('reg-email').value);
            data.append('password',         document.getElementById('reg-password').value);
            data.append('confirm_password', document.getElementById('reg-confirm').value);

            const showError = (msg) => {
                if (window.plmLoader) plmLoader.hide();
                alertMsg.textContent = msg;
                alert.style.display  = 'flex';
                btn.disabled         = false;
                btn.innerHTML        = '<span>Send Verification Code &nbsp;<i class="fa-solid fa-paper-plane"></i></span>';
            };

            try {
                const res  = await fetch('register_handler.php', { method: 'POST', body: data });
                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch { showError('Server error. Please try again.'); return; }
                if (json.success) {
                    if (window.plmLoader) plmLoader.show('Sending verification code...');
                    window.location.href = 'login_hub.php?portal=applicant&view=verify';
                } else {
                    showError(json.message || 'An error occurred.');
                }
            } catch {
                showError('Network error. Please try again.');
            }
        }

        // ── Verify form submit ──
        async function submitVerify(e) {
            e.preventDefault();
            const btn      = document.getElementById('ver-btn');
            const alert    = document.getElementById('ver-alert');
            const alertMsg = document.getElementById('ver-alert-msg');
            alert.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span>Verifying... &nbsp;<i class="fa-solid fa-spinner fa-spin"></i></span>';

            const data = new FormData();
            data.append('action', 'verify_code');
            data.append('code', document.getElementById('ver-code').value);

            try {
                const res  = await fetch('register_handler.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    window.location.href = json.redirect;
                } else {
                    alertMsg.textContent = json.message;
                    alert.style.display  = 'flex';
                    btn.disabled         = false;
                    btn.innerHTML        = '<span>Verify &amp; Create Account &nbsp;<i class="fa-solid fa-check"></i></span>';
                }
            } catch {
                alertMsg.textContent = 'Network error. Please try again.';
                alert.style.display  = 'flex';
                btn.disabled         = false;
                btn.innerHTML        = '<span>Verify &amp; Create Account &nbsp;<i class="fa-solid fa-check"></i></span>';
            }
        }

        // ── Forgot password ──
        async function submitForgot(e) {
            e.preventDefault();
            const btn     = document.getElementById('fgt-btn');
            const alertEl = document.getElementById('fgt-alert');
            const successEl = document.getElementById('fgt-success');
            alertEl.style.display = successEl.style.display = 'none';
            btn.disabled  = true;
            btn.innerHTML = '<span>Sending... &nbsp;<i class="fa-solid fa-spinner fa-spin"></i></span>';

            const data = new FormData();
            data.append('action',     'send_reset');
            data.append('portal',     '<?php echo $portal; ?>');
            data.append('identifier', document.getElementById('fgt-identifier').value);

            try {
                const res  = await fetch('forgot_handler.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    window.location.href = 'login_hub.php?portal=<?php echo $portal; ?>&view=reset-verify';
                } else {
                    document.getElementById('fgt-alert-msg').textContent = json.message;
                    alertEl.style.display = 'flex';
                    btn.disabled  = false;
                    btn.innerHTML = '<span>Send Reset Code &nbsp;<i class="fa-solid fa-paper-plane"></i></span>';
                }
            } catch {
                document.getElementById('fgt-alert-msg').textContent = 'Network error. Please try again.';
                alertEl.style.display = 'flex';
                btn.disabled  = false;
                btn.innerHTML = '<span>Send Reset Code &nbsp;<i class="fa-solid fa-paper-plane"></i></span>';
            }
        }

        // ── Reset verify code ──
        async function submitResetVerify(e) {
            e.preventDefault();
            const btn     = document.getElementById('rv-btn');
            const alertEl = document.getElementById('rv-alert');
            alertEl.style.display = 'none';
            btn.disabled  = true;
            btn.innerHTML = '<span>Verifying... &nbsp;<i class="fa-solid fa-spinner fa-spin"></i></span>';

            const data = new FormData();
            data.append('action', 'verify_reset');
            data.append('code',   document.getElementById('rv-code').value);

            try {
                const res  = await fetch('forgot_handler.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    window.location.href = 'login_hub.php?portal=<?php echo $portal; ?>&view=reset-password';
                } else {
                    document.getElementById('rv-alert-msg').textContent = json.message;
                    alertEl.style.display = 'flex';
                    btn.disabled  = false;
                    btn.innerHTML = '<span>Verify Code &nbsp;<i class="fa-solid fa-check"></i></span>';
                }
            } catch {
                document.getElementById('rv-alert-msg').textContent = 'Network error. Please try again.';
                alertEl.style.display = 'flex';
                btn.disabled  = false;
                btn.innerHTML = '<span>Verify Code &nbsp;<i class="fa-solid fa-check"></i></span>';
            }
        }

        // ── Reset password ──
        async function submitResetPassword(e) {
            e.preventDefault();
            const btn     = document.getElementById('rp-btn');
            const alertEl = document.getElementById('rp-alert');
            alertEl.style.display = 'none';
            btn.disabled  = true;
            btn.innerHTML = '<span>Saving... &nbsp;<i class="fa-solid fa-spinner fa-spin"></i></span>';

            const data = new FormData();
            data.append('action',   'do_reset');
            data.append('password', document.getElementById('rp-password').value);
            data.append('confirm',  document.getElementById('rp-confirm').value);

            try {
                const res  = await fetch('forgot_handler.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    window.location.href = 'login_hub.php?portal=' + json.portal;
                } else {
                    document.getElementById('rp-alert-msg').textContent = json.message;
                    alertEl.style.display = 'flex';
                    btn.disabled  = false;
                    btn.innerHTML = '<span>Reset Password &nbsp;<i class="fa-solid fa-arrow-right"></i></span>';
                }
            } catch {
                document.getElementById('rp-alert-msg').textContent = 'Network error. Please try again.';
                alertEl.style.display = 'flex';
                btn.disabled  = false;
                btn.innerHTML = '<span>Reset Password &nbsp;<i class="fa-solid fa-arrow-right"></i></span>';
            }
        }

        // ── Countdown timer (verify + reset-verify views) ──
        const countdownEl = document.getElementById('countdown') || document.getElementById('rv-countdown');
        if (countdownEl) {
            const expiredBtnId  = countdownEl.id === 'countdown' ? 'ver-btn'   : 'rv-btn';
            const expiredAlertId = countdownEl.id === 'countdown' ? 'ver-alert' : 'rv-alert';
            const expiredMsgId  = countdownEl.id === 'countdown' ? 'ver-alert-msg' : 'rv-alert-msg';
            let secs = 600;
            const tick = setInterval(() => {
                secs--;
                if (secs <= 0) {
                    clearInterval(tick);
                    countdownEl.textContent = '0:00';
                    countdownEl.style.color = '#ef4444';
                    document.getElementById(expiredBtnId).disabled = true;
                    document.getElementById(expiredMsgId).textContent = 'Code expired. Please start over.';
                    document.getElementById(expiredAlertId).style.display = 'flex';
                    return;
                }
                const m = Math.floor(secs / 60);
                const s = String(secs % 60).padStart(2, '0');
                countdownEl.textContent = `${m}:${s}`;
                if (secs <= 60) countdownEl.style.color = '#ef4444';
            }, 1000);
        }

        // bfcache handled by plm_loader.js
    </script>
    <script src="../js/plm_loader.js"></script>
</body>
</html>
