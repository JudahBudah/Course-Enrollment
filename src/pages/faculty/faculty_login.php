<?php
session_start();
include("../../php/connection.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = mysqli_prepare($con, "SELECT * FROM faculty WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);

            if (password_verify($password, $user_data['password'])) {
                if ($user_data['status'] === 'active') {
                    $_SESSION['email'] = $user_data['email'];
                    $_SESSION['faculty_id'] = $user_data['faculty_id'];
                    header("Location: faculty_home.php");
                    die;
                } else {
                    $error = "Your account is currently " . htmlspecialchars($user_data['status']) . ". Please contact administration.";
                }
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Please enter both email and password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Login — PLM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" href="../../assets/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;1,300&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/faculty/faculty_login.css">
</head>
<body>

    <div class="bg"></div>

    <div class="page">

        <header>
            <a href="../../../index.html">
                <div class="nav-logo">
                    <div class="nav-logo-emblem">
                        <img src="../../assets/plm-logo.png" alt="PLM Logo">
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

            <p class="brand-sub">
                Access your faculty portal. Manage classes, grading, and academic responsibilities in one secure place.
            </p>

        </div>

        <!-- RIGHT: Login Form Panel -->
        <div class="panel-right">
            <div class="login-card">

                <div class="login-card-header">
                    <h2>Welcome, <em>Faculty Member</em></h2>
                    <p>Sign in to continue to your account</p>
                </div>

                <!-- Portal Tabs — hook data-portal for future routing -->
                <div class="portal-wrapper">
                    <div class="portal-tabs">
                        <button class="portal-tab" type="button" data-portal="applicant">
                            <i class="fa-solid fa-user-plus"></i> Applicant
                        </button>
                        <button class="portal-tab" type="button" data-portal="student">
                            <i class="fa-solid fa-user-graduate"></i> Student
                        </button>
                        <button class="portal-tab active" type="button" data-portal="faculty">
                            <i class="fa-solid fa-chalkboard-user"></i> Faculty
                        </button>
                        <button class="portal-tab" type="button" data-portal="admin">
                            <i class="fa-solid fa-shield-halved"></i> Admin
                        </button>
                    </div>
                </div>

                <form method="POST" id="login-form">

                    <?php if (isset($error)): ?>
                        <div class="alert-error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Email -->
                    <div class="field-group">
                        <label class="field-label" for="email">Email Address</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-envelope"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="e.g. juan.dela.cruz@plm.edu.ph"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                autocomplete="username"
                                required>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="field-group">
                        <label class="field-label" for="password">Password</label>
                        <div class="field-wrap">
                            <i class="field-icon fa-solid fa-lock"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required>
                            <button class="toggle-pw" type="button" onclick="togglePw()">
                                <i class="fa-regular fa-eye" id="pw-icon"></i>
                            </button>
                        </div>
                        <div class="form-options">
                            <div class="form-checkbox">
                                <label class="checkbox">
                                    <input type="checkbox" name="remember">
                                    <span>Remember me</span>
                                </label>
                            </div>
                            <div class="field-meta">
                                <a href="faculty_forgot.php" class="link">Forgot password?</a>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>Sign In &nbsp;<i class="fa-solid fa-arrow-right"></i></span>
                    </button>

                </form>

                <div class="login-divider"><span>PLM Portal</span></div>

                <p class="login-help">
                    Having trouble? Contact ICT Support or visit the Registrar's Office.
                </p>

                <div class="login-footer">
                    <a href="../../../index.html" class="link">
                        <i class="fa-solid fa-arrow-left"></i> Back to Home
                    </a>
                </div>

            </div>
        </div>

    </div>

    <script>
        // Portal tab switching — data-portal attribute ready for future routing logic
        document.querySelectorAll('.portal-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.portal-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });

        // Password visibility toggle
        function togglePw() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('pw-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>

</body>
</html>
