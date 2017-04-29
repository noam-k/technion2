<?php

include_once 'model/rules.php';
include_once 'view/view.php';

class RulesController {

    /** error codes */
    const REQUIRED_FIELD_NOT_FOUND = 1;
    const FIELD_VALIDATION_FAILED = 2;

    /**
    * @var bool
    */
    protected $newRuleAdded = false;

    /**
    * @var bool
    */
    protected $ruleDeleted = false;

    /**
    * @var RulesModel
    */
    protected $model;

    /**
    * @var RulesView
    */
    protected $view;

    /**
    * @var array
    */
    protected $notAllowedSQLFunctions = array('UPDATE', 'INSERT', 'DROP', 'CREATE', 'DELETE', 'MERGE', 'COMMIT', 'ALTER', 'TRUNCATE');

    /**
    * @var array
    */
    protected $eventProperties = array('begin_date', 'end_date', 'location', 'organizer_mail', 'summary', 'event_description');

    public function __construct() {
        $this->model = new RulesModel();
        $this->view = new RulesView();
    }

    /**
    * Note that filter_var returns the variable given, which can be evaluated to a value that is equivalent to false
    * (e.g. 0, empty string etc.). By explicitly comparing it to "false" we return the expected result
    * @var $value mixed
    * @var $validationType int defined in RulesModel
    * @return book
    */
    protected function validate($value, $validationType) {
        switch ($validationType) {
            case RulesModel::EMAIL :
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                break;
            case RulesModel::SQL :
                foreach ($this->notAllowedSQLFunctions as $word) {
                    if (stripos($value, $word)!== false) {
                        return false;
                    }
                }
                return true;
                break;
            case RulesModel::FORMULA :
                    if (preg_match('/[a-wyz]/i', $value)) { # match all Latin characters except for x
                        return false;
                    }
                    return true;
                break;
            default:
                return false; # Should not come here
        }
    }
    /**
    * validation of the data, and sending a request to create it in the model (DB)
    */
    public function processBasicInput() {
        #echo '<pre>'; print_r($_POST); die;
        foreach ($this->model->getNewBasicRuleFormData() as $fieldName => $details) {
            if (!empty($details['properties']['required']) == RulesModel::REQUIRED && !isset($_POST[$fieldName])) {
                throw new Exception("Error: required field wasn't found: ".$fieldName, self::REQUIRED_FIELD_NOT_FOUND);
            }
            /*if ($properties['severity'] == RulesModel::REQUIRED && empty($_POST[$fieldName])) {
                throw new Exception("Error: required field wasn't found: ".$fieldName, self::REQUIRED_FIELD_NOT_FOUND);
            }
            if (!empty($properties['validation']) && !$properties['validation']($_POST[$fieldName])) {
                throw new Exception("Error: failed to process data: ".$fieldName, self::FIELD_VALIDATION_FAILED);
            }*/
        }
        $this->newRuleAdded = $this->model->addNewBasicRule();
    }

    public function processFlexibleInput() {
        foreach ($this->model->getNewFlexibleRuleFormData() as $fieldName => $details) {
            if (!empty($details['properties']['required']) == RulesModel::REQUIRED && !isset($_POST[$fieldName])) {
                throw new Exception("Error: required field wasn't found: ".$fieldName, self::REQUIRED_FIELD_NOT_FOUND);
            }
            if (!empty($details['validation']) && isset($_POST[$fieldName])) {
                $valid = $this->validate($_POST[$fieldName], $details['validation']);
                if (!$valid) {
                    throw new Exception("Error: failed to process data: ".$fieldName, self::FIELD_VALIDATION_FAILED);
                }
            }
        }
        $_POST['sqlquery'] = $_POST['sql_query'];
        unset($_POST['sql_query']);
        if ($_POST['condition_or_set'] === 'set') {
            $_POST['formula'] = 'set';
            unset($_POST['condition_or_set']);
        }
        if ($_POST['send_mail_to'] === 'One address') {
            $_POST['sendto'] = $_POST['email_address'];
            unset($_POST['email_address']);
        } elseif ($_POST['send_mail_to'] === 'LabAdmin group') {
            $_POST['sendto'] = $_POST['labadmin_group'];
            unset($_POST['labadmin_group']);
        } else { # SQL defined group
            $_POST['sendto'] = $_POST['SQL_defined_group'];
            unset($_POST['SQL_defined_group']);
        }
        unset($_POST['send_mail_to']);
        if (!empty($_POST['attach_event'][0])) {
            foreach ($this->eventProperties as $property) {
                $eventDetails[$property] = $_POST[$property];
                unset($_POST[$property]);
            }
            $_POST['event'] = serialize($eventDetails);
            unset($_POST['attach_event']);
        }
        if (!empty($_POST['attach_message'][0])){
            $_POST['message'] = $_POST['message_to_attach'];
            unset($_POST['attach_message']);
            unset($_POST['message_to_attach']);
        }
        $this->newRuleAdded = $this->model->addNewFlexibleRule();
    }

    public function renderAddNewBasicRulePage() {
        $displayData = $this->model->getNewBasicRuleFormData();
        $this->view->renderAddNewRulePage($displayData, $this->newRuleAdded, RulesView::INSTRUCTIONS_NEW_BASIC_RULE);
    }

    public function renderAddNewFlexibleRulePage() {
        $displayData = $this->model->getNewFlexibleRuleFormData();
        $this->view->renderAddNewRulePage($displayData, $this->newRuleAdded, RulesView::INSTRUCTIONS_NEW_FLEXIBLE_RULE, true);
    }

    public function renderWelcomePage() {
        $this->view->renderWelcomePage();
    }

    /**
    * @var admin bool if this is true, we also display a button to remove this rule
    */
    public function renderManageExistingRules($admin = false) {
        $headers = $this->model->getRuleFields();
        $rules = $this->model->getRulesData($admin);
        if ($admin) {
            $deleteLink = $this->view->getDeleteLinkTemplate();
            $headers[] = 'delete';
            foreach ($rules as &$rule) {
                $rule[] = sprintf($deleteLink, $rule['id']);
                unset($rule['id']);
            }
        }
        $this->view->renderShowExistingRules($headers, $rules, $this->ruleDeleted);
    }

    /**
    * @var $id int
    */
    public function deleteRule($id) {
        if ($this->model->deleteRule($id)) {
            $this->ruleDeleted = true;
        }
    }
}
