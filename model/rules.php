<?php

class RulesModel {

    const REQUIRED = 1;

    /**
    * @var PDO
    */
    protected $dbh;

    /**
    * @var string
    */
    protected $rulesTable = 'rules';

    /**
    * @var string
    */
    protected $rulesIdCol = 'ruleid';

    /**
    * @var array
    */
    protected $recipientsOptions = array('assistents', 'students',);

    /**
    * @var array
    */
    protected $condition_or_setOptions = array('formula', 'set',);

    /**
    * @var array
    */
    protected $send_mail_toOptions = array('One address', 'LabAdmin group', 'SQL defined group');

    public function __construct() {
        require_once 'cfg.php';
		$this->dbh = new PDO($rulesDatabase['dsn'], $rulesDatabase['username'], $rulesDatabase['password']);
        $this->labadmin_groupOptions = $this->getLabAdminGroups();
	}

    /**
    * @return array
    */
    protected function getLabAdminGroups() {
        # TODO: write this function such that it returns the different groups, e.g array()
        return array(1 => 'Admins', 14 => 'Supervisors', 15 => 'Assistents', 200 => 'Students'); # Temporary
    }

	public function getStudents() {
		$ret = array();
		foreach ($this->dbh->query('SELECT * FROM students') as $value) {
			$ret[] = $value;
		}
		return $ret;
	}

    /**
    * @return array a list of the fields saved for each rule and are PHP relevant
    */
    public function getRuleFields() {
        return array_keys($this->getNewBasicRuleFormData());
    }

    /**
    * @return array all rules' data saved in the system
    */
    public function getRulesData($withId = false) {
        $ret = array();
        $selectFields = ($withId ? $this->rulesIdCol.',' : '');
        $selectFields .= implode(',', $this->getRuleFields());
        $query = 'SELECT '.$selectFields.' FROM '.$this->rulesTable;
        $sth = $this->dbh->prepare($query);
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $value) {
            $value['id'] = $value[$this->rulesIdCol];
            unset($value[$this->rulesIdCol]);
            $ret[] = $value;
        }
        return $ret;
    }

    /**
    * Configure the "new rule" HTML form elements
    * Structure:
    * 'name_words_separated_with_underscore' => array(
    *   'type' => 'form_type',
    *   'properties' => PFBC properties array,
    *   'options' => array(), # options, in case this element is a select, radio etc.
    * )
    * The order the elements are placed here should be the same order to display them
    * @return array
    */
    public function getNewBasicRuleFormData() {
        return array(
            'description' => array(
                'type' => 'textarea',
                'properties' => array('required' => 1, 'longDesc' => 'Rule description'),
            ),
            'recipients' => array(
                'type' => 'select',
                'options' => $this->recipientsOptions,
                'properties' => array('required' => 1, 'longDesc' => 'To whom should the evnet be sent'),
            ),
            'additional_message' => array(
                'type' => 'textarea',
                'properties' => array('longDesc' => 'Will be added to the email sent to the recipients (optional)'),
            ),
            'number_of_days_ahead_to_scan' => array(
                'type' => 'number',
                'properties' => array('required' => 1, 'longDesc' => 'This amount of days, starting today, will be
                    checked for events with participants who haven\'t gotten a message from the system about it',),
            ),
        );
    }

    public function getNewFlexibleRuleFormData() {
        return array(
            'sql_query' => array(
                'type' => 'textarea',
                'properties' => array('required' => 1, 'longDesc' => 'SQL Query that will serve as the rule\'s base'),
            ),
            'condition_or_set' => array(
                'type' => 'radio',
                'options' => $this->condition_or_setOptions,
                'properties' => array('required' => 1, 'longeDesc' => '<strong>"Set"</stgong> means the rule will apply
                 to every line of SQL result. <strong>"Formula"</strong> means the SQL result will be counted and
                 treated as a number, and the rule will apply only if the <strong>formula</strong> will be satisfied by
                 this number. ')
            ),
            'formula' => array(
                'type' => 'textbox',
                'properties' => array('required' => 1, 'disabled' => 1), # enabled when condition_or_set = formula
            ),
            'send_mail_to' => array(
                'type' => 'radio',
                'options' => $this->send_mail_toOptions,
                'properties' => array('required' => 1),
            ),
            'email_address' => array(
                'type' => 'textbox',
                'properties' => array('required' => 1, 'disabled' => 1) # enable on send_mail_to = address
            ),
            'labadmin_group' => array(
                'type' => 'select',
                'options' => $this->labadmin_groupOptions,
                'properties' => array('required' => 1, 'disabled' => 1), # enable on send_mail_to = labadmin_group
            ),
            'SQL_defined_group' => array(
                'type' => 'textarea',
                'properties' => array('label' => 'SQL defined group', 'required' => 1, 'disabled' => 1), # enable on send_mail_to = sql
            )
        );
    }

    /**
    * Security check (against SQL injections) is done here with $dbh->prepare and bindParam()
    * Data is being transferred via $_POST
    * @return bool whether an addition was successful
    */
    public function addNewBasicRule() {
        $_POST['recipients'] = $this->recipientsOptions[$_POST['recipients']]; # A little ugly
        $fields = $this->getRuleFields();
        $fieldsList = implode(', ', $fields);
        $bindingList = implode(', :', $fields);
        $query = 'INSERT INTO '.$this->rulesTable.' ('.$fieldsList.') VALUES (:'. $bindingList .')';
        $stmt = $this->dbh->prepare($query);
        foreach ($fields as $field) {
            $stmt->bindParam(':'.$field, $values[$field]);
        }
        foreach ($fields as $field) {
            $values[$field] = $_POST[$field]; # This filters $_POST entries that are not in $this->getRuleFields();
        }
        return $stmt->execute();
    }

    /**
    * @var $id int
    * @return bool
    */
    public function deleteRule($id) {
        $res = $this->dbh->exec('DELETE FROM '.$this->rulesTable.' WHERE '.$this->rulesIdCol.' = '. intval($id));
        return $res === 1;
    }
}
