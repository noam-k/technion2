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
    const TABLE_USERS = 'users';

    ######################## Database table fields
    const FIELD_TABLE_GROUPID_USERS = 'groupid';
    const FIELD_TABLE_EMAIL_USERS = 'groupid';

    public function __construct() {
        global $labAdminDatabase;
		$this->dbh = new PDO($labAdminDatabase['dsn'], $labAdminDatabase['username'], $labAdminDatabase['password']);
    }

    /**
    * @var $query string
    * @return array
    */
    public function getSelectSet($query) {
        $ret = array();
        $sth = $this->dbh->prepare($query);
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $value) {
            $ret[] = $value;
        }
        return $ret;
    }

    /**
    * @var $id int|string
    * @return array
    */
    public function getGroupEmails($id) {
        if (!ctype_digit($id)) {
            $groups = $this->getLabAdminGroups();
            $id = array_search($id, $groups);
        }
        $query = 'SELECT '.self::FIELD_TABLE_EMAIL_USERS.' FROM '.self::TABLE_USERS.' WHERE '.self::FIELD_TABLE_GROUPID_USERS." = $id";
        return $this->getSelectSet($query);
    }


    /**
    * @return array
    */
    public function getLabAdminGroups() {
        # TODO: write this function such that it returns the different groups, e.g array()
        return array(1 => 'Admins', 14 => 'Supervisors', 15 => 'Assistents', 200 => 'Students'); # Temporary
    }


    /**
    * @var $query string
    * @return int|bool
    */
    public function getCountSelect($query) {
        $query = trim($query);
        if (strcasecmp(substr($query, 0, 5), 'count')) { # equals zero iff $query begins with 'count'
            $query = 'count (*) from ('.$query.')';
        }
        $sth = $this->dbh->prepare($query);
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_BOTH);
        return $result[0];
    }

}
