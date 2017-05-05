<?php

require 'cfg.php';

class LabAdminModel{

    ######################### Internal model vars

    /**
    * @var PDO
    */
    protected $dbh;

    ######################## Database tables

    const TABLE_STUDENTS = 'students';
    const TABLE_ASSISTANTS = 'assistants';
    const TABLE_EXPERIMENTS = 'lab_experiments';
    const TABLE_FACULTIES = 'faculties';

    public function __construct() {
        global $labAdminDatabase;
		$this->dbh = new PDO($labAdminDatabase['dsn'], $labAdminDatabase['username'], $labAdminDatabase['password']);
    }

    /**
    * @var $query string
    * @return array
    */
    public function getSelectSet($query) {

    }
}
