<?php
function mail_log($msg){
    $log = __DIR__ . "/mail_error.log";
    @file_put_contents($log, date('c') . " " . $msg . PHP_EOL, FILE_APPEND);
}

function mailer_send($to,$subject,$body,$options=[]){

    // Try Composer first
    $autoload = __DIR__ . "/../../vendor/autoload.php";
    if(file_exists($autoload)){
        require_once $autoload;
    } else {

        // PHPMailer location
        $srcDir = __DIR__ . "/../lib/PHPMailer/src";

        if(file_exists($srcDir . "/PHPMailer.php")){
            require_once $srcDir . "/Exception.php";
            require_once $srcDir . "/PHPMailer.php";
            require_once $srcDir . "/SMTP.php";
        } else {
            mail_log("PHPMailer not found at: " . $srcDir);
            return false;
        }
    }

    if(!class_exists("PHPMailer\\PHPMailer\\PHPMailer")){
        mail_log("PHPMailer class not available after require");
        return false;
    }

    $cfg_file = __DIR__ . "/mail_config.php";
    if(!file_exists($cfg_file)){
        mail_log("mail_config.php missing");
        return false;
    }

    $cfg = include $cfg_file;

    $required=["host","port","username","password","encryption","from_email","from_name"];
    foreach($required as $k){
        if(!isset($cfg[$k]) || $cfg[$k]===""){
            mail_log("missing config key: ".$k);
            return false;
        }
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    $is_html   = $options["is_html"] ?? false;
    $reply_to  = $options["reply_to"] ?? null;
    $reply_name= $options["reply_name"] ?? null;
    $cc        = $options["cc"] ?? [];
    $bcc       = $options["bcc"] ?? [];
    $attachments = $options["attachments"] ?? [];

    try{
        $mail->isSMTP();
        $mail->Host       = $cfg["host"];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg["username"];
        $mail->Password   = $cfg["password"];
        $mail->SMTPSecure = $cfg["encryption"];
        $mail->Port       = $cfg["port"];
        $mail->CharSet    = "UTF-8";

        $mail->setFrom($cfg["from_email"],$cfg["from_name"]);

        if(is_array($to)){
            foreach($to as $addr){
                if($addr) $mail->addAddress($addr);
            }
        } else {
            $mail->addAddress($to);
        }

        if($reply_to){
            $mail->addReplyTo($reply_to,$reply_name ?: $reply_to);
        }

        foreach($cc as $addr){
            if($addr) $mail->addCC($addr);
        }

        foreach($bcc as $addr){
            if($addr) $mail->addBCC($addr);
        }

        foreach($attachments as $file){
            if($file && file_exists($file)){
                $mail->addAttachment($file);
            }
        }

        $mail->Subject = $subject;

        if($is_html){
            $mail->isHTML(true);
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);
        } else {
            $mail->Body    = $body;
            $mail->AltBody = $body;
        }

        $mail->send();
        return true;

    } catch(Throwable $e){
        mail_log($e->getMessage() . " | " . $mail->ErrorInfo);
        return false;
    }
}