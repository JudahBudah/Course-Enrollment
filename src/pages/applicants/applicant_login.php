<?php
session_start();
include("../../php/connection.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = mysqli_prepare($con, "SELECT * FROM applicants WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $applicant_data = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $applicant_data['password'])) {
                $_SESSION['applicant_id'] = $applicant_data['applicant_id'];
                header("Location: applicant_home.php");
                die;
            }
        }
        
        $error = "Invalid email or password";
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
    <title>Applicant Login - PLM</title>
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
            <div class="login-tabs">
                <button class="tab-btn active" onclick="showTab('login')">Login</button>
                <button class="tab-btn" onclick="window.location.href='applicant_register.php'">Create Account</button>
            </div>

            <form class="login-form" id="loginForm" method="POST">
                <h2>Welcome Back</h2>
                <p class="login-subtitle">Sign in to check your application status</p>

                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="your.email@example.com" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-login">
                    <span>Sign In</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

                <div class="login-footer">
                    <p style="color: rgba(242,243,242,0.6); font-size: 0.85rem; margin-bottom: 0.5rem;">
                        Don't have an account? <a href="applicant_register.php" class="link">Create Account</a>
                    </p>
                    <a href="../../../index.html" class="link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Remove tab switching functionality
    </script>
</body>
</html>








