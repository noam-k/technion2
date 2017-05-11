<?php

require_once 'model/labAdmin.php';
$query = $_GET['query'];

$notAllowedSQLFunctions = array('UPDATE', 'INSERT', 'DROP', 'CREATE', 'DELETE', 'MERGE', 'COMMIT', 'ALTER', 'TRUNCATE');

function showError($error) {
    echo '<font color="red">Disallowed word detected in the query: '.$error.'</div>';
}

foreach ($notAllowedSQLFunctions as $word) {
    if (stripos($query, $word)) {
        showError($word); die;
    }
}

$labAdmin = new LabAdminModel();
try {
    $result = $labAdmin->getSelectSet($query);
    #print_r($result); die;
    $body = '';
    foreach ($result as $row) {
        $head = '<tr><th>'.implode('</th><th>', array_keys($row)).'</th></tr>';
        $body.= '<tr><td>'.implode('</td><td>',$row).'</td></tr>';
    }
    echo '<table>'.$head.$body.'</table>';
} catch (Exception $e) {
    echo '<font color="red">'.$e->getMessage().'</div>';
}
