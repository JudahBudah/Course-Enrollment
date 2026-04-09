<?php
// Include after connection.php — clears remember me cookie and DB token
$cookie = $_COOKIE['plm_remember'] ?? '';
if (!empty($cookie)) {
    $parts = explode(':', $cookie, 2);
    $token = $parts[1] ?? '';
    if (!empty($token)) {
        $del = mysqli_prepare($con, "DELETE FROM remember_tokens WHERE token = ?");
        mysqli_stmt_bind_param($del, "s", $token);
        mysqli_stmt_execute($del);
    }
}
setcookie('plm_remember', '', ['expires' => time() - 3600, 'path' => '/Course-Enrollment-main/']);
