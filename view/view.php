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
		     <li>The rule will be executed if the <b>number of entries</b> in the result satisfies the formula</li>
        </ol>
    <li>Refer to the results as a set of entries. The rule will be excuted for each row of the result</li>
    </ol>
    <li>Choose who should get an email alert</li>
    <ol>
        <li>Pre-definied email address(es) - one, or more with comma separation</li>
        <li>A pre-definied LabAdmin group of people (e.g. all users, all instructors etc.)</li>
        <li>A set of emails that are the result of another SQL Query</li>
    </ol>
    <li>Set an email structure</li>
    <ol>
        <li>Should an event invitation be sent? If yes, add the following:</li>
        <ol>
            <li>start time, end time, location, an email of the organizer, summary and description</li>
        </ol>
	    <li>Should a massage be added? Placeholders from the query\'s result can be added</li>
        <li>Email subject</li>
    </ol>
    </ol>';

    const TR_CLASS_ODD = 'odd';
    const TR_CLASS_EVEN = 'even';

    /**
    * @var string
    */
    protected $creatorMailLink = '<a href="">Hovav Gazit</a>, or the project programmer,
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
    protected $eventImg = 'img/icon_date.gif';

    /**
    * @var string
    */
    protected $messageImg = 'img/icon_text.gif';

    /**
    * @var string
    */
    protected $deleteLink = '<a href="%s?deleteRule=%s&table=%s"><img src="img/icon_del.gif"/></a>';

    /**
    * Simply works with template.html to show pages in a conformal way
    * @var $title string
    * @var $header string
    * @var $content string
    */
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

    /**
    * Wrap a message in a div to be "announced" (currently show in red for a few seconds)
    * @var $announcement string
    * @return string
    */
    protected function announce($announcement) {
        return '<div id="announcement">'.$announcement.'</div>';
    }

    /**
    * Wrap a message in a div to serve as instructions
    * @var $announcement string
    * @return string
    */
    protected function explain($explaination) {
        $explainationButton = '<a id="explainationLink" href="javascript:void(0)"
        onClick="toggleDiv(\'explainationContent\', \'show_button\');">
        <img id="show_button" src="img/show_more.png"> instructions</a>';
        return '<div id="explaination">'.$explainationButton.'<div id="explainationContent">'.$explaination.'</div></div>';
    }

    /**
    * @return string an event icon / hover details
    */
    public function getEventHTML() {
        $src = $this->eventImg;
        return "<a><img src=$src> (show event details)</a><div class='hiddenText'>%s</div>";
    }

    /**
    * @return string a message icon / hover details
    */
    public function getMessageHTML() {
        $src = $this->messageImg;
        return "<a><img src=$src> (show message)</a><div class='hiddenText'>%s</div>";
    }

    /**
    * @return string a rule delete link
    */
    public function getDeleteLinkTemplate() {
        return sprintf($this->deleteLink, $_SERVER['PHP_SELF'], '%s', '%s');
    }

    /**
    * @return string javascript functions (in a script tag)
    */
    protected function setFlexibleRulesEvents() {
        return '<script>
        function disable(prop) {
            var elem = document.getElementsByName(prop)[0];
            elem.parentNode.parentNode.style.display = "none";
            elem.disabled = true;
            elem.value = "";
            elem.required = false;
        }
        function enable(prop) {
            var elem = document.getElementsByName(prop)[0];
            elem.parentNode.parentNode.style.display = "block";
            elem.disabled = false;
            elem.required = true;
        }
        window.onload = function(){
            function disableAsDefault(prop) {
                var elem = document.getElementsByName(prop)[0];
                elem.parentNode.parentNode.style.display = "none";
            }
            disableAsDefault("formula");
            disableAsDefault("email_addresses");
            disableAsDefault("labadmin_group");
            disableAsDefault("SQL_defined_group");
            disableAsDefault("begin_date");
            disableAsDefault("end_date");
            disableAsDefault("location");
            disableAsDefault("organizer_mail");
            disableAsDefault("summary");
            disableAsDefault("event_description");
            disableAsDefault("message_to_attach");

            document.getElementsByName("query_handling_method")[0].onclick = function(){
                enable("formula");
            }
            document.getElementsByName("query_handling_method")[1].onclick = function(){
                disable("formula");
            }
            document.getElementsByName("query_handling_method")[2].onclick = function() {
                disable("formula");
            }
            document.getElementsByName("send_mail_to")[0].onclick = function(){
                enable("email_addresses");
                disable("labadmin_group");
                disable("SQL_defined_group");
            }
            document.getElementsByName("send_mail_to")[1].onclick = function(){
                disable("email_addresses");
                enable("labadmin_group");
                disable("SQL_defined_group");
            }
            document.getElementsByName("send_mail_to")[2].onclick = function(){
                disable("email_addresses");
                disable("labadmin_group");
                enable("SQL_defined_group");
            }
            document.getElementsByName("attach_event[]")[0].onclick = function(){
                if (this.checked) {
                    enable("begin_date");
                    enable("end_date");
                    enable("location");
                    enable("organizer_mail");
                    enable("summary");
                    enable("event_description");
                } else {
                    disable("begin_date");
                    disable("end_date");
                    disable("location");
                    disable("organizer_mail");
                    disable("summary");
                    disable("event_description");
                }
            }
            document.getElementsByName("attach_message[]")[0].onclick = function(){
                if (this.checked) {
                    enable("message_to_attach");
                } else {
                    disable("message_to_attach");
                }
            }
        }
    </script>';
    }

    /**
    * @var $file string a php file name to execute as an ajax call
    * @var $querySource an element from which to read the request
    * @var $lable button lable
    * @return string an ajax button + previewer
    */
    protected function ajaxPreview($file, $querySource, $lable) {
        $previewDiv = $querySource.'_preview';
        $button = '<button class="ajaxCall" onClick="ajaxGetSQLSelect('.$querySource.',\''.$previewDiv.'\'); return false;">'.$lable.'</button>';
        $preview = '<div id="'.$previewDiv.'" class="ajaxPreviewDiv"></div>';
        return $button.$preview;
    }

    /**
    * @return string HTML element - warn users about the invalidity of basic rules
    */
    protected function warnBasicRules() {
        return '<p style="color:red; text-decoration: underline;">
         NOTE: Basic rules page is merley for demonstration purposes.<br/>
         The rules generated in this page will have no affect</p>';
    }
    /**
    * @var $data array
    * @var $newRuleAdded bool
    * @var $explaination string
    * @var $flexible bool
    * prints a page with the content of "new page to add"
    */
    public function renderAddNewRulePage($data = array(), $newRuleAdded = false, $explaination = '', $flexible = false) {
        $this->explaination = $explaination;
        $content = '';
        if ($flexible) {
            $content .= $this->setFlexibleRulesEvents();
        } else {
            $content .= $this->warnBasicRules();
        }
        if ($newRuleAdded) {
            $this->announcement = 'New rule added successfully';
        }
        $form = new Form('myForm');
        $form->configure(array("prevent" => array("bootstrap")));
        $form->addElement(new Element_HTML('<legend>Rule\'s settings</legend>'));
        foreach ($data as $name => $details) {
            $elementType = 'Element_'.ucfirst($details['type']);
            $lable = ucfirst(strtolower(str_replace('_', ' ', $name)));
            if (!isset($details['properties'])) {
                $details['properties'] = null;
            }
            if (!empty($details['options'])) {
                if ($elementType === 'Element_Select') {
                    $details['options'] = array('' => '(select)') + $details['options'];
                }
                $form->addElement(new $elementType($lable.':', $name, $details['options'], $details['properties']));
            } else {
                $form->addElement(new $elementType($lable.':', $name, $details['properties']));
            }
            if (isset($details['ajaxPreview']) && is_file($details['ajaxPreview']['call'].'.php')) {
                $preview = $this->ajaxPreview($details['ajaxPreview']['call'], $name, $details['ajaxPreview']['lable']);
                $form->addElement(new Element_HTML($preview));
            }
        }
        $form->addElement(new Element_Button);
        $content .= $form->render(true);
        $this->renderPage(self::PAGE_NEW_RULE_TITLE, self::PAGE_NEW_RULE_HEADER, $content);
    }

    /**
    * @var $headers array
    * @var $rules array
    * @var $ruleDeleted bool
    */
    public function renderShowExistingRules($headers, $rules = array(), $ruleDeleted = false) {
        if ($ruleDeleted) {
            $this->announcement = 'Rule deleted successfully';
        }
        $returnValue = '<hr/><div id="'.$this->existingRulesTableId.'">';
        foreach ($headers as $key => $headerLine) {
            $returnValue .= '<h3>'.$key.'</h3>';
            $returnValue .= '<table><thead><tr>';
            foreach ($headerLine as $column) {
                $column = str_replace('_', ' ', $column);
                $column = ucfirst(strtolower($column));
                $returnValue.= '<th>'.$column.'</th>';
            }
            $returnValue.='</tr></thead><tbody>';
            $odd = true;
            #echo '<pre>'; print_r($rules[$key]); die;
            foreach ($rules[$key] as $rule) {
                $returnValue.='<tr class="'.($odd? self::TR_CLASS_ODD : self::TR_CLASS_EVEN).'">';
                foreach ($rule as $cell) {
                    $returnValue.='<td>'.$cell.'</td>';
                }
                $returnValue.='</tr>';
                $odd = !$odd;
            }
            $returnValue .= '</tbody></table>';
        }
        $returnValue .= '</div>';
        $this->renderPage(self::PAGE_EXISTING_RULES_TITLE, self::PAGE_EXISTING_RULES_HEADER, $returnValue);
    }

    /**
    * just prints the welcome page
    */
    public function renderWelcomePage(){
        $content = sprintf(self::WELCOME_CONTENT, $this->projectBookLink, $this->creatorMailLink);
        $this->renderPage(self::PAGE_WELCOME_TITLE, self::PAGE_WELCOME_HEADER, $content);
    }
}
