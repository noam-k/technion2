<?php

require_once 'model/rules.php';
require_once 'model/labAdmin.php';
require_once 'externalLibraries/PHPMailer/PHPMailerAutoload.php';
require_once 'controller/ics.php';

class labAdminTaskRunner {

    const RES_NUM = 'x';

    const EMAIL_SENDER = 'noreply@labadmin.technion.ac.il';

    /**
    * @var string path to log file
    */
    protected $logFile = 'taskRunnerLog.txt';

    /**
    * @var RulesModel
    */
    protected $rules;

    /**
    * @var LabAdminModel
    */
    protected $labadmin;

    /**
    * @var bool
    */
    protected $admin;

    /**
    * Ready for expansion to include different access levels
    * @return bool
    */
    protected function isAdmin() {
        return true;
    }

    /**
    * Parses the data from the DB into nicely shaped array
    * @var $rawData string
    * @return array
    */
    protected function createEmailList($rawData) {
        if (strpos($rawData, '@')) { # single email address
            return explode(',', $rawData);
        } elseif (is_int($rawData) || ctype_digit($rawData)) { # LabAdmin Group
            return $this->labAdmin->getGroupEmails(intval($rawData));
        } elseif (preg_match('/^.?+select+/i', $rawData)) {  # SQL query that starts in a select
            return $this->labadmin->getSelectSet($rawData);
        }
        error_log('Error while parsing "sendto" field'. PHP_EOL, 3, $this->logFile);
        return array();
    }

    /**
    * @var $event array
    * @return string
    */
    protected function makeIcs($event = array()) {
        $ics = new ics();
        $ics->setSummary($event['summary']);
        $ics->setOrganizerMail($event['organizer_mail']);
        $ics->setDescription($event['event_description']);
        $ics->setStartTime($event['begin_date']);
        $ics->setEndTime($event['end_date']);
        $ics->setLocation($event['location']);
        return $ics->generate();
    }

    /**
    * @var $mail PHPMailer
    */
    protected function configureSMTP($mail) {
        $mail->Host = $smtpConf['hostname'];
        $mail->Username = $smtpConf['username'];
        $mail->Password = $smtpConf['password'];
        $mail->SMTPAuth = $smtpConf['authentication'];
        $mail->Port = $smtpConf['port'];
        $mail->IsSmtp();
    }

    /**
    * @var $sendTo string
    * @var $event array|null
    * @var $message string|null
    * @var return int number of emails sent
    */
    protected function labAdminAlert($sendTo, $event = null, $message = null) {
        $mail = new PHPMailer();
        $this->configureSMTP($mail);
        $mail->setFrom(srlf::EMAIL_SENDER);
        $mail->Body = $message;
        if(!empty($event)) {
            $mail->Ical = $this->makeIcs(unserialize($event));
        }
        $emails = $this->createEmailList($sendTo);
        foreach ($emails as $email) {
            $mail->addAddress($email);
            error_log("Email address added: $email".PHP_EOL, 3, $this->logFile);
        }
        echo '<div style="font-size:big;color:red">Debug: DONE</div>'; return 1;
        if ($mail->send()) {
            $sumMails+= count($emails);
            error_log("Send successfull".PHP_EOL, 3, $this->logFile);
        } else {
            error_log("Send fail:".$mail->ErrorInfo.PHP_EOL, 3, $this->logFile);
        }
        return $sumMails;
    }

    public function __construct() {
        $this->rules = new RulesModel;
        $this->labadmin = new LabAdminModel();
        $this->admin = $this->isAdmin();
    }

    public function invoke() {
        echo '<pre>';
        echo 'Starting script...<br/>';
        error_log('Starting script. Time: '.date('Y-m-d H:i:s').PHP_EOL, 3, $this->logFile);
        $sumMails = 0;
        /*TODO: if basic rules are to be included:
            1. Uncomment following lines
            2. For each $recipients, check if they already recieved a mail (make a table in rules DB)
                2.1. if not, send a mail
        */
        /*echo 'Basic Rules: <br/>';
        foreach ($this->rules->getRulesData(RulesModel::TABLE_RULES_BASIC, $this->admin) as $details) {
            echo 'Handling basic rule #' .$details['id'].'<br>';
            error_log('Handling basic rule #' .$details['id']. PHP_EOL, 3, $this->logFile);
            $recipients = $this->labadmin->getGroupEmails($details['recipients']);
            print_r($details); continue;
        }
        die;*/

        echo 'Flexible Rules: <br/>';
        foreach ($this->rules->getRulesData(RulesModel::TABLE_RULES_FLEXIBLE, $this->admin) as $details) {
            #var_dump($details); die;
            echo 'Handling rule #' .$details['id'].'<br>';
            error_log('Handling rule #'.$details['id'].PHP_EOL, 3, $this->logFile);
            if ($details['formula'] !== 'set') {
                $resultNumber = $this->labadmin->getCountSelect($details['sqlquery']);
                $formula = str_replace(self::RES_NUM, $resultNumber, $details['formula']);
                if (eval($formula)) { # The condition set by the user is fulfilled
                    $sumMails = $this->labAdminAlert($details['sendto'], $details['event'], $details['message']);
                }
            } else {
                foreach ($this->labadmin->getSelectSet($details['sqlquery']) as $row) {
                    $sumMails = $this->labAdminAlert($details['sendto'], $details['event'], $details['message']); # TODO: add placeholders
                }
            }
        }

        echo 'Total of '.$sumMails.' emails have been sent <br>';
        error_log('Script finished. Total of '.$sumMails.' sent. Time: '.date('Y-m-d H:i:s').PHP_EOL, 3, $this->logFile);
    }
}
