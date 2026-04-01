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
            
            // Check if password is correct
            if (password_verify($password, $user_data['password'])) {
                // Check if faculty account is active
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
    <title>Faculty Login - PLM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/faculty/faculty_login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-brand">
                <img src="../../assets/plm-logo.png" alt="PLM Logo">
                <div>
                    <h1>PLM</h1>
                    <p>Faculty Portal</p>
                </div>
            </div>
            <div class="login-illustration">
                <img src="../../assets/plm-torch.webp" alt="PLM Torch">
            </div>
        </div>

        <div class="login-right">
            <form class="login-form" method="POST">
                <h2>Welcome Back</h2>
                <p class="login-subtitle">Sign in to access your faculty portal</p>

                <?php if (isset($error)): ?>
                    <div style="background: rgba(220,38,38,0.2); color: #ef4444; padding: 0.75rem; margin-bottom: 1rem; border-radius: 4px; font-size: 0.85rem;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="form-options">
                    <label class="checkbox">
                        <input type="checkbox">
                        <span>Remember me</span>
                    </label>
                    <a href="faculty_forgot.php" class="link">Forgot password?</a>
                </div>

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










