<?php

include_once 'model/rules.php';

echo '<pre>'; # debug

echo 'Starting script...<br>', PHP_EOL;
$rules = getRules();
$sumMails = 0;
foreach ($rules as $details) {
    echo 'Handling rule' .$details['id'].'<br>', PHP_EOL;
    print_r($details); die;
    $rulesData = getData($details);
    foreach ($rulesData as $entry) {
        sendIcalEmail($entry);
        $sumMails++;
    }
}

echo 'Total of '.$sumMails.' emails have been sent <br>', PHP_EOL;

function getRules() {
    $model = new RulesModel;
    return $model->getRulesData(true);
}

function getData($rule) { # Static so we don't instantiate LabAdminModel() every time
    static $labadmin = new LabAdminModel();
    return $rule;
}
