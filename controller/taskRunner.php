<?php

require_once 'model/rules.php';
require_once 'model/labAdmin.php';
require_once 'externalLibraries/PHPMailer/PHPMailerAutoload.php';
require_once 'controller/ics.php';

class labAdminTaskRunner {

    const RES_NUM = 'x';

    const EMAIL_SENDER = 'noreply@labadmin.technion.ac.il';

    /**
     * @var array holds the raw data from the rules DB
     */
    private $currentSetEntry = array();

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
     * @param $lastRun string
     * @param $days int
     * @return bool
     */
    protected function ruleDidRunInTheLastDefinedPeriod($lastRun, $days) {
        $lastRunUnixTimestamp = new DateTime($lastRun);

        $secondsInADay = 60 * 60 * 24;

        return (time() - $lastRunUnixTimestamp > $days * $secondsInADay);
    }

    /**
     * @param $ruleDetails array
     * @return bool
     */
    protected function doesRuleNeedToRun($ruleDetails) {
        return !$this->ruleDidRunInTheLastDefinedPeriod($ruleDetails['lastrun'], $ruleDetails['days']);
    }

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
        if (strpos($rawData, '@')) { # Comma separated
            return explode(',', $rawData);
        } elseif (is_int($rawData) || ctype_digit($rawData)) { # LabAdmin Group
            return $this->labadmin->getGroupEmails(intval($rawData));
        } elseif (preg_match('/^.?+select.+/i', $rawData)) {  # SQL query that starts in a select
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
        global $smtpConf;
        $mail->Host = $smtpConf['hostname'];
        $mail->Username = $smtpConf['username'];
        $mail->Password = $smtpConf['password'];
        $mail->SMTPAuth = $smtpConf['authentication'];
        $mail->Port = $smtpConf['port'];
        if (isset($smtpConf['smtpsecure'])) {
            $mail->SMTPSecure = $smtpConf['smtpsecure'];
        }
        $mail->IsSmtp();
    }

    /**
     * @param $table array - an SQL query represented as an array
     * @param $context - the message in which the table should be put
     * @return string
     */
    protected function putTableIntoContext($table, $context) {
        $buildTable = '';
        foreach ($table as $row) {
            foreach ($row as $column => $value) {
                $buildTable .= $column . ': '. $value . ' ; ';
            }
            $buildTable .= '\n';
        }
        return str_replace('{table}', $buildTable, $context);
    }

    /**
    * @var $sendTo string
    * @var $event string|null
    * @var $message string|null
    * @var $title string|null
    * @return int number of emails sent
    */
    protected function labAdminAlert($sendTo, $event = null, $message = null, $title = null) {
        $mail = new PHPMailer();
        $this->configureSMTP($mail);
        $mail->setFrom(self::EMAIL_SENDER);
        $mail->Subject = $title;
        if (!empty($this->currentSetEntry)) {
            foreach ($this->currentSetEntry as $field => $value) {
                $message = str_replace('{' . $field . '}', $value, $message);
            }
        }
        if(!empty($event)) {
            $eventArray = unserialize($event);
            foreach ($eventArray as $key => $value) {
                if (preg_match('/\{(.*)\}/', $value, $matches)) {
                    $field = $matches[1];
                    $placeHolder = $matches[0];
                    $eventArray[$key] = str_replace($placeHolder, $this->currentSetEntry[$field], $value);
                }
            }
            $mail->Ical = $this->makeIcs($eventArray);
        }
        $emails = $this->createEmailList($sendTo);
        if (!empty($emails)) {
            foreach ($emails as $email) {
                $mail->addAddress($email);
                error_log("Email address added: $email".PHP_EOL, 3, $this->logFile);
            }
        }
        $mail->Body = $message;
        #echo '<div style="font-size:big;color:red">Debug: DONE</div>'; return count($emails);
        if ($mail->send()) {
            $sumMails = count($emails);
            error_log("Send successfull".PHP_EOL, 3, $this->logFile);
            return $sumMails;
        } else {
            error_log("Send fail:".$mail->ErrorInfo.PHP_EOL, 3, $this->logFile);
        }
        return 0;
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
        /**
         * If basic rules are to be included:
         *   1. Uncomment following lines
         *   2. For each $recipients, check if they already received a mail (make a table in rules DB)
         *       2.1. if not, send a mail
         */
        /*
        echo 'Basic Rules: <br/>';
        foreach ($this->rules->getRulesData(RulesModel::TABLE_RULES_BASIC, $this->admin) as $details) {
            echo 'Handling basic rule #' .$details['id'].'<br>';
            error_log('Handling basic rule #' .$details['id']. PHP_EOL, 3, $this->logFile);
            $recipients = $this->labadmin->getGroupEmails($details['recipients']);
        }
        */

        echo 'Flexible Rules: <br/>';
        foreach ($this->rules->getRulesData(RulesModel::TABLE_RULES_FLEXIBLE, $this->admin) as $details) {
            if (!$this->doesRuleNeedToRun($details)) {
                continue;
            }
            try {
                echo 'Handling rule #' .$details['id'].'<br>';
                error_log('Handling rule #'.$details['id'].PHP_EOL, 3, $this->logFile);
                if ($details['formula'] === 'set') {
                    foreach ($this->labadmin->getSelectSet($details['sqlquery']) as $row) {
                        $this->currentSetEntry = $row;
                        $sumMails += $this->labAdminAlert($details['sendto'], $details['event'], $details['message'], $details['title']);
                    }
                }
                elseif ($details['formula'] === 'table') {
                    $sqlResults = $this->labadmin->getSelectSet($details['sqlquery']);
                    $message = $this->putTableIntoContext($sqlResults, $details['message']);
                    $this->labAdminAlert($details['sendto'], $details['event'], $message, $details['title']);

                } else {
                    $resultNumber = $this->labadmin->getCountSelect($details['sqlquery']);
                    $formula = 'return '.str_replace(self::RES_NUM, $resultNumber, $details['formula']) .';';
                    if (eval($formula)) { # The condition set by the user is fulfilled
                        $sumMails += $this->labAdminAlert($details['sendto'], $details['event'], $details['message'], $details['title']);
                    }
                }
                $this->rules->updateRunTime($details['id']);
            } catch (Exception $e) {
                echo 'Failed to execute rule #' .$details['id'].': '.$e->getMessage().'<br>';
                error_log('Error: Rule #'.$details['id'].' failed: '.$e->getMessage().PHP_EOL, 3, $this->logFile);
            }
        }

        echo 'Total of '.$sumMails.' emails have been sent <br>';
        error_log('Script finished. Total of '.$sumMails.' emails sent. Time: '.date('Y-m-d H:i:s').PHP_EOL, 3, $this->logFile);
    }
}
