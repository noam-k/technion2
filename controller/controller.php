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

    public function __construct() {
        $this->model = new RulesModel();
        $this->view = new RulesView();
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
