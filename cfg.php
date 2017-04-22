<?php

$smtpConf = array(
    'username' => 'NoamKritenberg',
    'password' => 'Project2FTW!',
    'hostname' => 'mail.smtp2go.com',
    'port' => 2525,
    'authentication' => true,
);

$rulesDatabase = array(
    'username' => 'root',
    'password' => 'root',
    'dsn' => 'mysql:host=localhost;dbname=noam_test',
);

$facultyDatabase = $rulesDatabase; # Change in case the database of this application is not the same as for
