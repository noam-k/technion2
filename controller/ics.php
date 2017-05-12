<?php

/**
 *
 */
class ics{

    /**
    * @var string
    */
    protected $timeFormat = 'Ymd\THis\Z';

    /**
    * (yyyy-mm-dd hh:ii:ss)
    * @var string
    */
    protected $timeRegex = '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/';

    /**
    * @var string
    */
    protected $startTime;

    /**
    * @var string
    */
    protected $endTime;

    /**
    * @var string
    */
    protected $location;

    /**
    * @var string
    */
    protected $organizerMail;

    /**
    * @var string
    */
    protected $fronName;

    /**
    * @var string
    */
    protected $description;

    /**
    * @var string
    */
    protected $summary;

  /** Order of required parameters:
  * Mail from, start time, end time, location, uniqid, creation time, description, summary
  * @var string
  */
  protected $pattern = 'BEGIN:VCALENDAR
PRODID:-//Google Inc//Google Calendar 70.9054//EN
VERSION:2.0
METHOD:PUBLISH
BEGIN:VEVENT
ORGANIZER:MAILTO:%s
DTSTART:%s
DTEND:%s
LOCATION:%s
TRANSP:OPAQUE
SEQUENCE:0
UID:%s
DTSTAMP:%s
DESCRIPTION:%s
SUMMARY:%s
PRIORITY:5
STATUS:CONFIRMED
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR';

    /**
    * @var $email string
    * @var $name string
    * ׂׂ@return bool
    */
    public function setOrganizerMail($email, $name ='') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
            return false;
        }
        if (!empty($name)) {
            $this->fromName = $name;
        }
        $this->organizerMail = $email;
        return true;
    }

    /**
    * @var $value string
    */
    public function setSummary($value) {
        $this->summary = $value;

    }

    /**
    * @var $value string
    */
    public function setDescription($value=''){
        $this->description = $value;
    }

    /**
    * @var $time DateTime|string
    */
    public function setStartTime($time) {
        if (is_string($time) && preg_match($this->timeRegex, $time)) {
            $this->startTime = $time;
        } else {
            $this->startTime = $time->format($this->timeFormat);
        }
    }

    /**
    * @var $time DateTime|string
    */
    public function setEndTime($time) {
        if (is_string($time) && preg_match($this->timeRegex, $time)) {
            $this->endTime = $time;
        } else {
            $this->endTime = $time->format($this->timeFormat);
        }
    }

    /**
    * @var $location string
    */
    public function setLocation($location) {
        $this->location= $location;
    }


  public function __construct(){
      $this->fromName = '';
      $this->summary = '';
  }

  /**
  * @return string|bool
  */
  public function generate() {
      if (!isset($this->organizerMail) || !isset($this->startTime) || !isset($this->endTime) || !isset($this->location)
      || !isset($this->description)) {
          return false;
      }
      $creationTime = new DateTime();
      $timeString = $creationTime->format($this->timeFormat);
      $uniqid = md5($this->location . $this->startTime . $timeString);
      return sprintf($this->pattern, $this->organizerMail, $this->startTime, $this->endTime, $this->location, $uniqid,
      $timeString, $this->description, $this->summary);
  }

  /**
  * @return string|bool
  *
  */
  public function HTMLBody($name = '') {
      $message = 'Hello '.$name.',<br/>
you are hereby invited to the following event:<br/>'.
$this->summary.'<br/>'.$this->description.'<br/>'.
'<strong>Time:</strong>'.date('Y-m-d H:i:s', strtotime($this->startTime)).' - '
.date('Y-m-d H:i:s', strtotime($this->endTime)).'<br/>'.
'<strong>Location:</strong> '.$this->location.'<br/>'.
'Best regards,'.
'<a href="mailto:'.$this->organizerMail.'">'.$this->fromName.'</a>';
    return $message;
  }

  /**
  * @var name string
  * @return string|bool
  */
  public function AltBody($name = '') {
      return 'Greetings,'.$name.',
you are hereby invited to the following event:
'.$this->summary.': '.$this->description.'.
The event will take place in '.$this->location.' between '.date('Y-m-d H:i:s', strtotime($this->startTime)).' and '.
date('Y-m-d H:i:s', strtotime($this->endTime)).'
For further information, please contact '.$this->fromName.': '.$this->organizerMail;
  }
}
