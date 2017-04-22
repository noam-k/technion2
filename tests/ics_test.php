<?php

include('../controller/ics.php');
include('../externalLibraries/PHPMailer/PHPMailerAutoload.php');

$allParamsRequired = '-- generating should not be possible befoe setting all parameters';
$testEmailAddress = 'snaring@campus.technion.ac.il';

define('MAILER_SMTP_HOSTNAME', 'mail.smtp2go.com');
define('MAILER_SMTP_USERNAME', 'NoamKritenberg');
define('MAILER_SMTP_PASSWORD', 'Project2FTW!');

/**
* @var $mail PHPMailer
*/
function configureSMTP($mail) {
    $mail->Host = MAILER_SMTP_HOSTNAME;
    $mail->Username = MAILER_SMTP_USERNAME;
    $mail->Password = MAILER_SMTP_PASSWORD;
    $mail->SMTPAuth = true;
    $mail->Port = 2525;
    $mail->IsSmtp();
}

function expectFail($icsTest) {
    return;
    $icsFileString = $icsTest->generate();
    if ($icsFileString) {
        echo 'Error!', PHP_EOL, '<br/>';
        echo $allParamsRequired, PHP_EOL, '<br/>';
        exit();
    } else {
        echo 'OK', PHP_EOL, '<br/>';
    }
}

echo 'Begin ICS creation test...', PHP_EOL, '<br/>';
$icsTest = new ics();
expectFail($icsTest);
$icsTest->setSummary('Summary2');
expectFail($icsTest);
$icsTest->setOrganizerMail('organizer@email.server');
expectFail($icsTest);
$icsTest->setDescription('Description2');
$dateTime = DateTime::createFromFormat('d-m-Y H:i:s', '02-05-2017 12:00:00');
$icsTest->setStartTime($dateTime);
expectFail($icsTest);
$dateTime->add(DateInterval::createFromDateString('2 hours'));
$icsTest->setEndTime($dateTime);
expectFail($icsTest);
$icsTest->setLocation('Haifa');

$mail = new PHPMailer();
configureSMTP($mail);
$mail->addAddress($testEmailAddress);
$mail->setFrom($testEmailAddress);
$mail->Body = $icsTest->HTMLBody();
$mail->Subject = 'ICS Test';
$mail->AltBody = $icsTest->AltBody();
$mail->Ical = $icsTest->generate(); //Your manually created ical code

echo $mail->AltBody;
echo '<br>';
echo $mail->Body;

die;

if ($mail->send()) {
    echo 'The event was sent to '.$testEmailAddress, PHP_EOL, '<br/>';
} else {
    echo "Mailer Error: " . $mail->ErrorInfo, PHP_EOL, '<br/>';
}

echo 'Test finished', PHP_EOL, '<br/>';
