<?php
function reset_bootstrap($con){
    mysqli_query($con,"CREATE TABLE IF NOT EXISTS password_resets (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, token VARCHAR(64) UNIQUE, expires_at DATETIME NOT NULL, created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}
function reset_create($con,$student_id){
    $token=bin2hex(random_bytes(32));
    $exp=date('Y-m-d H:i:s', time()+3600);
    $s=mysqli_prepare($con,"INSERT INTO password_resets (student_id, token, expires_at) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($s,"iss",$student_id,$token,$exp);
    mysqli_stmt_execute($s);
    return $token;
}
function reset_find($con,$token){
    $s=mysqli_prepare($con,"SELECT * FROM password_resets WHERE token=? LIMIT 1");
    mysqli_stmt_bind_param($s,"s",$token);
    mysqli_stmt_execute($s);
    $res=mysqli_stmt_get_result($s);
    if($res&&mysqli_num_rows($res)>0){
        $row=mysqli_fetch_assoc($res);
        if(strtotime($row["expires_at"])>time()){ return $row; }
    }
    return null;
}
function reset_consume($con,$token){
    $s=mysqli_prepare($con,"DELETE FROM password_resets WHERE token=?");
    mysqli_stmt_bind_param($s,"s",$token);
    mysqli_stmt_execute($s);
}
function send_reset_mail($to,$link){
    $sub="Reset your PLM Student Portal password";
    $msg="Click the link to reset your password:\r\n\r\n".$link."\r\n\r\nIf you did not request this, ignore this email.";
    $mailer=__DIR__."/mailer.php";
    $ok=false;
    if(file_exists($mailer)){
        require_once $mailer;
        $ok=mailer_send($to,$sub,$msg);
    }
    if(!$ok){
        $log=__DIR__."/reset_dev.log";
        @file_put_contents($log, date('c')." ".$to." ".$link.PHP_EOL, FILE_APPEND);
    }
    return $ok;
}
