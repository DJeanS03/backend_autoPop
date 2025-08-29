<?php

require_once __DIR__."/../../vendor/autoload.php";

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv::createImmutable(realpath(dirname(__FILE__) . '/..')."/../");
$dotenv->load();

function emailSend($from, $replyTo, $to, $subject, $body){
  $mail = new PHPMailer(true);

  try{
    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USER'];
    $mail->Password = $_ENV['MAIL_PSWD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $_ENV['MAIL_PORT'];
    $mail->CharSet = $_ENV['MAIL_CHARSET'];

    $mail->setFrom($from, 'Auto Pop');
    $mail->addAddress($to);
    $mail->addReplyTo($replyTo, 'Auto Pop');

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;

    $mail->send();
  }
  catch(phpmailerException $e){
    echo $e->errorMessage();
  }
  catch(Exception $e){
    echo $e->getMessage();
  }
}