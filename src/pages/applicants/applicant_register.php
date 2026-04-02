<?php
session_start();
include("../../php/connection.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            // Check if email already exists
            $check_stmt = mysqli_prepare($con, "SELECT * FROM applicants WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "Email already registered. Please login instead.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = mysqli_prepare($con, "INSERT INTO applicants (email, password, application_status, created_at) VALUES (?, ?, 'incomplete', NOW())");
                mysqli_stmt_bind_param($stmt, "ss", $email, $hashed_password);
                
                if (mysqli_stmt_execute($stmt)) {
                    $applicant_id = mysqli_insert_id($con);
                    $_SESSION['applicant_id'] = $applicant_id;
                    header("Location: applicant_home.php");
                    die;
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } else {
            $error = "Passwords do not match";
        }
    } else {
        $error = "Please fill in all fields";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - PLM Applicant Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_auth.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-brand">
                <img src="../../assets/plm-logo.png" alt="PLM Logo">
                <div>
                    <h1>PLM</h1>
                    <p>Applicant Portal</p>
                </div>
            </div>
            <div class="login-illustration">
                <img src="../../assets/plm-torch.webp" alt="PLM Torch">
            </div>
        </div>

        <div class="login-right">
            <form class="login-form" method="POST">
                <h2>Create Your Account</h2>
                <p class="login-subtitle">Start your journey as an Iskolar ng Bayan</p>

                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fa-solid fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <i class="fa-solid fa-info-circle"></i>
                    <p>After creating your account, you'll be able to fill out your application form and submit required documents.</p>
                </div>

                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="email" placeholder="your.email@example.com" required>
                </div>

                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" placeholder="Create a strong password" minlength="6" required>
                    <small style="color: rgba(242,243,242,0.5); font-size: 0.75rem;">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" placeholder="Re-enter your password" required>
                </div>

                <button type="submit" class="btn-login">
                    <span>Create Account</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

                <div class="login-footer">
                    <p style="color: rgba(242,243,242,0.6); font-size: 0.85rem; margin-bottom: 0.5rem;">
                        Already have an account? <a href="applicant_login.php" class="link">Sign In</a>
                    </p>
                    <a href="../../../index.html" class="link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
