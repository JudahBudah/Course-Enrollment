<?php
require_once __DIR__ . '/no_cache.php';

function check_admin_login($con) 
{
    if (isset($_SESSION["admin_id"])) 
    {
        $id = $_SESSION["admin_id"];
        $stmt = mysqli_prepare($con, "SELECT * FROM admins WHERE admin_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0)
        {
            $admin_data = mysqli_fetch_assoc($result);
            return $admin_data;
        }
    }

    header("Location: /SoftDes/kevin/src/pages/login_hub.php?portal=admin");
    die;
}

function is_superadmin(): bool {
    return ($_SESSION['admin_role'] ?? '') === 'superadmin';
}

function require_superadmin($con): void {
    $id = $_SESSION['admin_id'] ?? 0;
    $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT role FROM admins WHERE admin_id = $id LIMIT 1"));
    if (!$row || $row['role'] !== 'superadmin') {
        header("Location: admin_home.php?error=unauthorized");
        die;
    }
}
?>
