<?php
function admin_bootstrap($con){
    $q="CREATE TABLE IF NOT EXISTS admins (admin_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE, email VARCHAR(100) UNIQUE, password VARCHAR(255) NOT NULL, role VARCHAR(20) DEFAULT 'admin', created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($con,$q);
    $r=mysqli_query($con,"SELECT COUNT(*) AS c FROM admins");
    $count=0;
    if($r&&($tmp=mysqli_fetch_assoc($r))){ $count=intval($tmp["c"]); }
    if($count===0){
        $u="admin";
        $e="admin@local";
        $p=password_hash("admin123",PASSWORD_DEFAULT);
        $s=mysqli_prepare($con,"INSERT INTO admins (username,email,password) VALUES (?,?,?)");
        mysqli_stmt_bind_param($s,"sss",$u,$e,$p);
        mysqli_stmt_execute($s);
    }
}
function admin_login($con,$user,$pass){
    $s=mysqli_prepare($con,"SELECT * FROM admins WHERE username=? OR email=? LIMIT 1");
    mysqli_stmt_bind_param($s,"ss",$user,$user);
    mysqli_stmt_execute($s);
    $res=mysqli_stmt_get_result($s);
    if($res&&mysqli_num_rows($res)>0){
        $a=mysqli_fetch_assoc($res);
        if(password_verify($pass,$a["password"])){
            $_SESSION["admin_id"]=$a["admin_id"];
            return true;
        }
    }
    return false;
}
function admin_current($con){
    if(isset($_SESSION["admin_id"])){
        $id=intval($_SESSION["admin_id"]);
        $s=mysqli_prepare($con,"SELECT * FROM admins WHERE admin_id=? LIMIT 1");
        mysqli_stmt_bind_param($s,"i",$id);
        mysqli_stmt_execute($s);
        $res=mysqli_stmt_get_result($s);
        if($res&&mysqli_num_rows($res)>0){return mysqli_fetch_assoc($res);}
    }
    return null;
}
function admin_require_login(){
    if(!isset($_SESSION["admin_id"])){
        header("Location: admin_login.php");
        die;
    }
}
function admin_logout(){
    unset($_SESSION["admin_id"]);
}
