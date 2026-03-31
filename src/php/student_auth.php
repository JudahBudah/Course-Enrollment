<?php
function student_bootstrap($con){
    $r=mysqli_query($con,"SELECT COUNT(*) AS c FROM students");
    if($r&&($row=mysqli_fetch_assoc($r))){
        if(intval($row["c"])===0){
            $sn="S20240001";
            $fn="Juan";
            $ln="Dela Cruz";
            $em="student@local";
            $pw=password_hash("student123",PASSWORD_DEFAULT);
            $s=mysqli_prepare($con,"INSERT INTO students (student_number, first_name, last_name, email, password) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($s,"sssss",$sn,$fn,$ln,$em,$pw);
            mysqli_stmt_execute($s);
        }
    }
}
function student_login_do($con,$user,$pass){
    $sn=trim($user);
    if($sn===""){ return false; }
    $s=mysqli_prepare($con,"SELECT * FROM students WHERE student_number = ? LIMIT 1");
    mysqli_stmt_bind_param($s,"s",$sn);
    mysqli_stmt_execute($s);
    $res=mysqli_stmt_get_result($s);
    if($res&&mysqli_num_rows($res)>0){
        $u=mysqli_fetch_assoc($res);
        if(password_verify($pass,$u["password"])){
            $_SESSION["student_number"]=$u["student_number"];
            return true;
        }
    }
    return false;
}
