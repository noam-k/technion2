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
            return $this->labadmin->getQueryResults($rawData);
        }
        error_log('Error while parsing "sendto" field'. PHP_EOL, 3, $this->logFile);
        return array();
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
        foreach ($this->createEmailList($sendTo) as $email) {
            $mail->addAddress($email);
            error_log("Email address added: $email", 3, $this->logFile);
        }
        echo '<div style="font-size:big;color:red">DONE</div>'; die;
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
        $this->ladadmin = new LabAdminModel();
        $this->admin = $this->isAdmin();
    }

    public function invoke() {
        echo '<pre>';
        echo 'Starting script...<br/>'.PHP_EOL;
        error_log('Starting script. Time: '.date('Y-m-d H:i:s').PHP_EOL, 3, $this->logFile);
        $sumMails = 0;
        echo 'Basic Rules: <br/>'.PHP_EOL;
        foreach ($this->rules->getRulesData(RulesModel::TABLE_RULES_BASIC, $this->admin) as $details) {
            break; # TODO: handle
            echo 'Handling basic rule #' .$details['id'].'<br>', PHP_EOL;
            print_r($details); continue;
        }

        echo 'Flexible Rules: <br/>';
        foreach ($this->rules->getRulesData(RulesModel::TABLE_RULES_FLEXIBLE, $this->admin) as $details) {
            echo 'Handling rule #' .$details['id'].'<br>', PHP_EOL;
            error_log('Handling rule #'.$details['id'].PHP_EOL, 3, $this->logFile);
            #print_r($details); continue;
            if ($details['formula'] !== 'set') {
                $resultNumber = $this->labadmin->getCountSelect($details['sqlqeury']);
                $formula = str_replace(self::RES_NUM, $resultNumber, $details['formula']);
                if (eval($formula)) { # The condition set by the user is fulfilled
                    $emailsCount = $this->labAdminAlert($details['sendto'], $details['event'], $details['message']);
                }
            } else {
                foreach ($this->labadmin->getSelectSet($details['sqlqeury']) as $row) {
                    $emailsCount = $this->labAdminAlert($details['sendto'], $details['event'], $details['message']); # TODO: add placeholders
                }
            }
        }

        echo 'Total of '.$emailsCount.' emails have been sent <br>', PHP_EOL;
        error_log('Script finished. Total of '.$sumMails.' sent. Time: '.date('Y-m-d H:i:s').PHP_EOL, 3, $this->logFile);
    }
}
