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
        $sth = $this->dbh->prepare($query);
        if (!$sth->execute()) {
            throw new Exception($sth->errorInfo()[2]);
        } else {
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        }
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
            $query = 'SELECT COUNT(*) as number FROM ('.$query.') as tempTable';
        }
        $sth = $this->dbh->prepare($query);
        $sth->execute();
        if (!$sth->execute()) {
            throw new Exception($sth->errorInfo()[2]);
        }
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        return intval($result[0]['number']);
    }
}
