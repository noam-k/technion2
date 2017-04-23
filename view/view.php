<?php

include 'externalLibraries/PFBC/Form.php';

class RulesView {

    const PH_SUB_TITLE = '{page_sub_title}';
    const PH_HEADER = '{page_header}';
    const PH_CONTENT = '{page_content}';

    const PAGE_NEW_RULE_TITLE = 'Add new rule';
    const PAGE_NEW_RULE_HEADER = 'New rule to add:';
    const PAGE_EXISTING_RULES_TITLE = 'Rules already in the system';
    const PAGE_EXISTING_RULES_HEADER = 'List of rules:';
    const PAGE_WELCOME_TITLE = 'Home Page';
    const PAGE_WELCOME_HEADER = 'Welcome to alert system for LabAdmin';

    const WELCOME_CONTENT = '<p>This website was built as part of the Technion course 044169, project B (פרוייקט ב).</p>
    <p>The project description is available <a href="%s">here</a>. For more details, please contact %s</p>';

    const INSTRUCTIONS_NEW_BASIC_RULE = '';

    const INSTRUCTIONS_NEW_FLEXIBLE_RULE = '<ol>
    <li>Create an SQL query</li>
    <li>Choose what to do with the results:</li>
    <ol>
	    <li>Refer to the results as a number</li>
        <ol>
		     <li>Write a formula</li>
		     <li>If the <b>number of entries</b> in the result satisfies the formula - continue to the next step</li>
        </ol>
    <li>Refer to the results as a set of entries. Eexcute the next step for each one entry</li>
    </ol>
    <li>Choose who should get an email alert</li>
    <ol>
        <li>A specific email address</li>
        <li>A pre-definied group of people (e.g. all LabAdmin users, all instructors of some laboratory etc.)</li>
        <li>A set of emails that are the result of another SQL Query</li>
    </ol>
    <li>Set an email structure</li>
    <ol>
        <li>Should an invitation be sent? If yes:</li>
        <ol>
            <li>Create a one time invitation</li>
            <li>Send an invitation that is based on the query\'s result (from clause number 2)</li>
        </ol>
	    <li>Should a massage be added? Placeholders from the query\'s result can be added</li>
    </ol>
    </ol>';

    const TR_CLASS_ODD = 'odd';
    const TR_CLASS_EVEN = 'even';

    /**
    * @var string
    */
    protected $creatorMailLink = '<a href="">Roy Mitrany</a>, or the project programmer,
    <a href="">Noam Kritenberg</a>';

    /**
    * @var string
    */
    protected $projectBookLink = 'projectBook.rtf';

    /**
    * @var string
    */
    protected $template = 'view/template.html';

    /**
    * @var string
    */
    protected $existingRulesTableId = 'existingRulesTable';

    /**
    * @var string
    */
    protected $announcement = '';

    /**
    * @var string
    */
    protected $explaination = '';

    /**
    * @var string
    */
    protected $deleteLink = '<a href="%s?deleteRule=%s"><img src="img/icon_del.gif"/></a>';

    protected function renderPage($title, $header, $content) {
        $header = '<h1>'.$header.'</h1>';
        if ($this->announcement != '') {
            $header= $this->announce($this->announcement).$header;
        }
        if ($this->explaination != '') {
            $header.=$this->explain($this->explaination);
        }
        $template = file_get_contents($this->template);
        $template = str_replace(self::PH_SUB_TITLE, $title, $template);
        $template = str_replace(self::PH_HEADER, $header, $template);
        $template = str_replace(self::PH_CONTENT, $content, $template);
        echo $template;
    }

    protected function announce($announcement) {
        return '<div id="announcement">'.$announcement.'</div>';
    }

    protected function explain($explaination) {
        $explainationButton = '<a id="explainationLink" href="javascript:void(0)"
        onClick="toggleDiv(\'explainationContent\', \'show_button\');">
        <img id="show_button" src="img/show_more.png"> instructions</a>';
        return '<div id="explaination">'.$explainationButton.'<div id="explainationContent">'.$explaination.'</div></div>';
    }

    public function getDeleteLinkTemplate() {
        return sprintf($this->deleteLink, $_SERVER['PHP_SELF'], '%s');
    }

    protected function setFlexibleRulesEvents() {
        return '<script>
        function disable(prop) {
            var elem = document.getElementsByName(prop)[0];
            elem.parentNode.parentNode.style.display = "none";
            elem.disabled = true;
        }
        function enable(prop) {
            var elem = document.getElementsByName(prop)[0];
            elem.parentNode.parentNode.style.display = "block";
            elem.disabled = false;
        }
        window.onload = function(){
            function disableAsDefault(prop) {
                var elem = document.getElementsByName(prop)[0];
                elem.parentNode.parentNode.style.display = "none";
            }
            disableAsDefault("formula");
            disableAsDefault("email_address");
            disableAsDefault("labadmin_group");
            disableAsDefault("SQL_defined_group");
            document.getElementsByName("condition_or_set")[0].onclick = function(){
                enable("formula");
            }
            document.getElementsByName("condition_or_set")[1].onclick = function(){
                disable("formula");
            }
            document.getElementsByName("send_mail_to")[0].onclick = function(){
                enable("email_address");
                disable("labadmin_group");
                disable("SQL_defined_group");
            }
            document.getElementsByName("send_mail_to")[1].onclick = function(){
                disable("email_address");
                enable("labadmin_group");
                disable("SQL_defined_group");
            }
            document.getElementsByName("send_mail_to")[2].onclick = function(){
                disable("email_address");
                disable("labadmin_group");
                enable("SQL_defined_group");
            }
        }
    </script>';
    }

    /**
    * @param $data array
    * prints a page with the content of "new page to add"
    */
    public function renderAddNewRulePage($data = array(), $newRuleAdded = false, $explaination = '', $flexible = false) {
        $this->explaination = $explaination;
        $content = '';
        if ($flexible) {
            $content .= $this->setFlexibleRulesEvents();
        }
        if ($newRuleAdded) {
            $this->announcement = 'New rule added successfully';
        }
        $form = new Form('myForm');
        $form->configure(array("prevent" => array("bootstrap")));
        $form->addElement(new Element_HTML('<legend>Rule\'s settings</legend>'));
        foreach ($data as $name => $details) {
            $elementType = 'Element_'.ucfirst(strtolower($details['type']));
            $lable = ucfirst(strtolower(str_replace('_', ' ', $name)));
            if (!empty($details['options'])) {
                if ($elementType === 'Element_Select') {
                    $details['options'] = array('' => '(select)') + $details['options'];
                }
                $form->addElement(new $elementType($lable.':', $name, $details['options'], $details['properties']));
            } else {
                $form->addElement(new $elementType($lable.':', $name, $details['properties']));
            }
        }
        $form->addElement(new Element_Button);
        $content .= $form->render(true);
        $this->renderPage(self::PAGE_NEW_RULE_TITLE, self::PAGE_NEW_RULE_HEADER, $content);
    }

/*
    public function renderAddNewFlexibleRulePage($data = array(), $newRuleAdded = false) {
        echo 'HELLO'; die;
        $this->explaination = self::INSTRUCTIONS_NEW_FLEXIBLE_RULE;
        $form = new Form('myForm');
        $form->configure(array("prevent" => array("bootstrap")));
        $content = '';
        $this->renderPage(self::PAGE_NEW_RULE_TITLE, self::PAGE_NEW_RULE_HEADER, $content);
    }
*/

    /**
    * @var $headers array
    * @var $rules array
    * @var $ruleDeleted bool
    */
    public function renderShowExistingRules($headers, $rules = array(), $ruleDeleted = false) {
        if ($ruleDeleted) {
            $this->announcement = 'Rule deleted successfully';
        }
        $returnValue = '<div id="'.$this->existingRulesTableId.'"><table><thead><tr>';
        foreach ($headers as $column) {
            $column = str_replace('_', ' ', $column);
            $column = ucfirst(strtolower($column));
            $returnValue.= '<th>'.$column.'</th>';
        }
        $returnValue.='</tr></thead><tbody>';

        $odd = true;
        foreach ($rules as $rule) {
            $returnValue.='<tr class="'.($odd? self::TR_CLASS_ODD : self::TR_CLASS_EVEN).'">';
            foreach ($rule as $cell) {
                $returnValue.='<td>'.$cell.'</td>';
            }
            $returnValue.='</tr>';
            $odd = !$odd;
        }
        $returnValue .= '</tbody></table></div>';
        $this->renderPage(self::PAGE_EXISTING_RULES_TITLE, self::PAGE_EXISTING_RULES_HEADER, $returnValue);
    }

    public function renderWelcomePage(){
        $content = sprintf(self::WELCOME_CONTENT, $this->projectBookLink, $this->creatorMailLink);
        $this->renderPage(self::PAGE_WELCOME_TITLE, self::PAGE_WELCOME_HEADER, $content);
    }
}
