<?php
session_start();
include("src/php/connection.php");
include("src/php/reset_utils.php");
reset_bootstrap($con);
$token=$_GET["token"]??($_POST["token"]??"");
$row=$token?reset_find($con,$token):null;
$done=false;
if($_SERVER["REQUEST_METHOD"]==="POST"&&$row){
    $pwd=trim($_POST["password"]??"");
    $pwd2=trim($_POST["password2"]??"");
    if($pwd!==""&&$pwd===$pwd2&&strlen($pwd)>=6){
        $hash=password_hash($pwd,PASSWORD_DEFAULT);
        $s=mysqli_prepare($con,"UPDATE students SET password=? WHERE student_id=?");
        $sid=(int)$row["student_id"];
        mysqli_stmt_bind_param($s,"si",$hash,$sid);
        mysqli_stmt_execute($s);
        reset_consume($con,$token);
        $done=true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
</head>
<body>
  <header>
    <nav>
      <div class="nav-logo">
        <div class="nav-logo-emblem"><img src="../assets/plm-logo.png" alt="PLM"></div>
        <div class="nav-logo-text"><div>PLM</div><div>STUDENT</div></div>
      </div>
    </nav>
  </header>
  <main style="padding-top:90px;display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 90px)">
    <div style="min-width:320px;max-width:420px;width:100%;padding:24px;border:1px solid rgba(212,175,55,0.1);background:rgba(242,243,242,0.03);display:grid;gap:12px">
      <h1 style="font-family:'Playfair Display',serif;margin:0 0 8px 0">Reset Password</h1>
      <?php if(!$row&&!$done){ ?>
        <div style="color:#ff7b7b">Invalid or expired link</div>
        <a class="btn-secondary" href="student_forgot.php" style="text-decoration:none">Request a new link</a>
      <?php } elseif($done){ ?>
        <div style="color:#8fd19e">Password updated</div>
        <a class="btn-primary" href="student_login.php" style="text-decoration:none"><span>BACK TO LOGIN</span></a>
      <?php } else { ?>
        <form method="post" style="display:grid;gap:12px">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
          <input name="password" type="password" placeholder="New Password" style="padding:10px;background:#14110d;color:#f2f3f2;border:1px solid rgba(212,175,55,0.2)">
          <input name="password2" type="password" placeholder="Confirm Password" style="padding:10px;background:#14110d;color:#f2f3f2;border:1px solid rgba(212,175,55,0.2)">
          <button class="btn-primary" type="submit"><span>SET PASSWORD</span></button>
        </form>
      <?php } ?>
    </div>
  </main>
</body>
</html>
