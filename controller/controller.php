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
    * @return bool
    */
    protected function validate($value, $validationType) {
        switch ($validationType) {
            case RulesModel::EMAIL :
                $allAddressesValid = true;
                foreach (explode(',', $value) as $emailAddress) {
                    if (filter_var(trim($emailAddress), FILTER_VALIDATE_EMAIL) === false) {
                        $allAddressesValid = false;
                    }
                }
                return $allAddressesValid;
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
            /*if (!empty($properties['validation']) && !$properties['validation']($_POST[$fieldName])) {
                throw new Exception("Error: failed to process data: ".$fieldName, self::FIELD_VALIDATION_FAILED);
            }*/
        }
        $this->newRuleAdded = $this->model->addNewBasicRule();
    }

    /**
     * Try to add a new flexible rule to the system
     *
     * @throws Exception in case one (or more) of the fields is not valid
     */
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
        if ($_POST['query_handling_method'] === 'set' || $_POST['query_handling_method'] === 'table') {
            $_POST['formula'] = $_POST['query_handling_method'];
            unset($_POST['query_handling_method']);
        }
        if ($_POST['send_mail_to'] === 'Comma separated list') {
            $_POST['sendto'] = $_POST['email_addresses'];
            unset($_POST['email_addresses']);
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
        if (!empty($_POST['number_of_days_to_scan'])){
            $_POST['days'] = $_POST['number_of_days_to_scan'];
            unset($_POST['number_of_days_to_scan']);
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
    * @var $admin bool if this is true, we also display a button to remove this rule
    */
    public function renderManageExistingRules($admin = false) {
        $tables = array(RulesModel::TABLE_RULES_BASIC, RulesModel::TABLE_RULES_FLEXIBLE,);
        $headers[RulesModel::TABLE_RULES_BASIC] = $this->model->getBasicRuleFields();
        $rules[RulesModel::TABLE_RULES_BASIC] = $this->model->getRulesData(RulesModel::TABLE_RULES_BASIC, $admin);
        $headers[RulesModel::TABLE_RULES_FLEXIBLE] = $this->model->getFlexibleRuleFields();
        $rules[RulesModel::TABLE_RULES_FLEXIBLE] = $this->model->getRulesData(RulesModel::TABLE_RULES_FLEXIBLE, $admin);
        foreach ($tables as $table) {
            if ($admin) {
                $deleteLink = $this->view->getDeleteLinkTemplate();
                $headers[$table][] = 'delete';
            }
            foreach ($rules[$table] as &$rule) {
                if (!empty($rule['event'])) {
                    $eventDetails = unserialize($rule['event']);
                    $eventBlock = '';
                    foreach ($eventDetails as $key => $value) {
                        $eventBlock .= $key.': '.$value.'<br/>';
                    }
                    $rule['event'] = sprintf($this->view->getEventHTML(), $eventBlock);
                }
                if (!empty($rule['message'])) {
                    $rule['message'] = sprintf($this->view->getMessageHTML(), $rule['message']);
                }
                if ($admin) {
                    $rule[] = sprintf($deleteLink, $rule['id'], $table);
                }
                unset($rule['id']);
            }
        }
        $this->view->renderShowExistingRules($headers, $rules, $this->ruleDeleted);
    }

    /**
     * Deletes a rule from the database
     *
     * @var $id int
     * @var $table string
     */
    public function deleteRule($id, $table) {
        if ($this->model->deleteRule($id, $table)) {
            $this->ruleDeleted = true;
        }
    }
}
