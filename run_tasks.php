<?php

include_once 'model/rules.php';
include_once 'model/labAdmin.php';
include_once 'controller/ics.php';
include_once 'externalLibraries/PHPMailer/PHPMailerAutoload.php';

define('RES_NUM', 'x');
define('EMAIL_SENDER', 'noreply@labadmin.technion.ac.il');

$labadmin = new LabAdminModel();

echo '<pre>'; # debug

$logFile = 'alerts_log.txt';


function labAdminAlert($sendTo, $event = null, $message = null) {
    $mail = new PHPMailer();
    configureSMTP($mail);
    $mail->setFrom(EMAIL_SENDER);
    $mail->Body = $details['message'];
    if(!empty($details['event'])) {
        $mail->Ical = makeIcs(unserialize($details['event']));
    }
    foreach (createEmailList($details['sendto']) as $email) {
        $mail->addAddress($email);
        error_log("Email address added: $email", 3, $logFile);
    }
    if ($mail->send()) {
        $sumMails+= count($emails);
        error_log("Send successfull".PHP_EOL, 3, $logFile);
    } else {
        error_log("Send fail:".$mail->ErrorInfo.PHP_EOL, 3, $logFile);
    }
    return $sumMails;
}

function createEmailList($rawData) {
    if (strpos($rawData, '@')) { # single email address
        return explode(',' $rawData);
    } elseif (is_int($rawData) || ctype_digit($rawData)) { # LabAdmin Group
        return $labAdmin->getGroupEmails(intval($rawData));
    } elseif (preg_match('/^.?+select+/i', $rawData)) {  # SQL query that starts in a select
        return $labadmin->getQueryResults($rawData);
    }
    error_log('Error while parsing "sendto" field'. PHP_EOL, 3, $logFile);
    return array();
}
function configureSMTP($mail) {
    $mail->Host = $smtpConf['hostname'];
    $mail->Username = $smtpConf['username'];
    $mail->Password = $smtpConf['password'];
    $mail->SMTPAuth = $smtpConf['authentication'];
    $mail->Port = $smtpConf['port'];
    $mail->IsSmtp();
}



$rules = new RulesModel;
$admin = true;

echo 'Starting script...<br/>';
error_log('Starting script. Time: '.date('Y-m-d H:i:s').PHP_EOL, 3, $logFile);
$sumMails = 0;
echo 'Basic Rules: <br/>';
foreach ($rules->getRulesData(RulesModel::TABLE_RULES_BASIC, $admin) as $details) {
    break; # TODO: handle
    echo 'Handling basic rule #' .$details['id'].'<br>', PHP_EOL;
    print_r($details); continue;
    $rulesData = getData($details);
    foreach ($rulesData as $entry) {
        sendIcalEmail($entry);
        $sumMails++;
    }
}

echo 'Flexible Rules: <br/>';
foreach ($rules->getRulesData(RulesModel::TABLE_RULES_FLEXIBLE, $admin) as $details) {
    echo 'Handling rule #' .$details['id'].'<br>', PHP_EOL;
    error_log('Handling rule #'.$details['id'].PHP_EOL, 3, $logFile);
    #print_r($details); continue;
    if ($details['formula'] !== 'set') {
        $resultNumber = $labadmin->getCountSelect($details['sqlqeury']);
        $formula = str_replace(RES_NUM, $resultNumber, $details['formula']);
        if (eval($formula)) { # The condition set by the user is fulfilled
            $emailsCount = labAdminAlert($details['sendto'], $details['event'], $details['message']);
        }
    } else {
        foreach ($labAdmin->getSelectSet($details['sqlqeury']) as $row) {
            $emailsCount = labAdminAlert($details['sendto'], $details['event'], $details['message']); # TODO: add placeholders
        }
    }
}

echo 'Total of '.$emailsCount.' emails have been sent <br>', PHP_EOL;
error_log('Script finished. Total of '.$sumMails.' sent. Time: '.date('Y-m-d H:i:s').PHP_EOL, 3, $logFile);
