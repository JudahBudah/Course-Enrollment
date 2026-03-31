<?php
session_start();
include("../../php/connection.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $student_number = $_POST['student_number'];
    $password = $_POST['password'];

    if (!empty($student_number) && !empty($password)) {
        $stmt = mysqli_prepare($con, "SELECT * FROM students WHERE student_number = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $student_number);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user_data['password'])) {
                $_SESSION['student_number'] = $user_data['student_number'];
                header("Location: student_home.php");
                die;
            }
        }
        
        $error = "Invalid student number or password";
    } else {
        $error = "Please enter both student number and password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - PLM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/student/student_login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-brand">
                <img src="../../assets/plm-logo.png" alt="PLM Logo">
                <div>
                    <h1>PLM</h1>
                    <p>Student Portal</p>
                </div>
            </div>
            <div class="login-illustration">
                <img src="../../assets/plm-torch.webp" alt="PLM Torch">
            </div>
        </div>

        <div class="login-right">
            <form class="login-form" method="POST">
                <h2>Welcome Back</h2>
                <p class="login-subtitle">Sign in to access your student portal</p>

                <?php if (isset($error)): ?>
                    <div style="background: rgba(220,38,38,0.2); color: #ef4444; padding: 0.75rem; margin-bottom: 1rem; border-radius: 4px; font-size: 0.85rem;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Student Number</label>
                    <input type="text" name="student_number" placeholder="2024-00000" required>
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
                    <a href="student_forgot.php" class="link">Forgot password?</a>
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