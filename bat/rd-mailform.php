<?php

$formConfigFile = file_get_contents("rd-mailform.config.json");
$formConfig = json_decode($formConfigFile, true);

date_default_timezone_set('Etc/UTC');

try {
    require './phpmailer/PHPMailerAutoload.php';

    $recipients = $formConfig['recipientEmail'];

    preg_match_all("/([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)/", $recipients, $addresses, PREG_OFFSET_CAPTURE);

    if (!count($addresses[0])) {
        die('MF001');
    }

    function getRemoteIPAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];

        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    if (preg_match('/^(127\.|192\.168\.)/', getRemoteIPAddress())) {
        die('MF002');
    }

    $template = file_get_contents('rd-mailform.tpl');

    if (isset($_POST['form-type'])) {
        switch ($_POST['form-type']){
            case 'contact':
                $subject = 'A message from your site visitor';
                break;
            case 'subscribe':
                $subject = 'Subscribe request';
                break;
            case 'order':
                $subject = 'Order request';
                break;
            default:
                $subject = 'A message from your site visitor';
                break;
        }
    }else{
        die('MF004');
    }

    if (isset($_POST['email'])) {
        $template = str_replace(
            array("<!-- #{FromState} -->", "<!-- #{FromEmail} -->"),
            array("Email:", $_POST['email']),
            $template);
    }

    if (isset($_POST['message'])) {
        $template = str_replace(
            array("<!-- #{MessageState} -->", "<!-- #{MessageDescription} -->"),
            array("Message:", $_POST['message']),
            $template);
    }

    preg_match("/(<!-- #{BeginInfo} -->)(.|\n)+(<!-- #{EndInfo} -->)/", $template, $tmp, PREG_OFFSET_CAPTURE);
    foreach ($_POST as $key => $value) {
        if ($key != "email" && $key != "message" && $key != "form-type" && $key != "g-recaptcha-response" && !empty($value)){
            $info = str_replace(
                array("<!-- #{BeginInfo} -->", "<!-- #{InfoState} -->", "<!-- #{InfoDescription} -->"),
                array("", ucfirst($key) . ':', $value),
                $tmp[0][0]);

            $template = str_replace("<!-- #{EndInfo} -->", $info, $template);
        }
    }

    $template = str_replace(
        array("<!-- #{Subject} -->", "<!-- #{SiteName} -->"),
        array($subject, $_SERVER['SERVER_NAME']),
        $template);

    $mail = new PHPMailer(true); // Hata ayıklama özelliği etkinleştirildi

    if ($formConfig['useSmtp']) {
        $mail->isSMTP();
        $mail->SMTPDebug = 2; // Hata ayıklama düzeyi
        $mail->Debugoutput = 'html';
        $mail->Host = $formConfig['host'];
        $mail->Port = $formConfig['port'];
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "ssl";
        $mail->Username = $formConfig['username'];
        $mail->Password = $formConfig['password'];
    }

    $mail->From = $addresses[0][0][0];

    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $mail->AddAttachment($_FILES['file']['tmp_name'], $_FILES['file']['name']);
    }

    if (isset($_POST['name'])){
        $mail->FromName = $_POST['name'];
    }else{
        $mail->FromName = "Site Visitor";
    }

    foreach ($addresses[0] as $key => $value) {
       Hata ayıklama özelliğini etkinleştirdikten sonra, PHPMailer'ın SMTP hata mesajlarını gösterecektir. Hata ayıklama düzeyini belirlemek için `$mail->SMTPDebug` değişkenini kullanabilirsiniz. Örneğin, `$mail->SMTPDebug = 2;` olarak ayarladığınızda, hata mesajları ekranda görüntülenecektir.

Aşağıdaki güncellenmiş kod, hata ayıklama özelliğini etkinleştirir ve hata mesajlarını görüntüler:

```php
$mail = new PHPMailer(true); // Hata ayıklama özelliği etkinleştirildi
$mail->SMTPDebug = 2; // Hata ayıklama düzeyi
$mail->Debugoutput = 'html';

try {
    // E-posta gönderme kodu
} catch (Exception $e) {
    echo 'E-posta gönderme hatası: ' . $mail->ErrorInfo;
}