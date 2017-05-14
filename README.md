# LabAdmin alert system

## Synopsis

This system aim to send alerts to [LabAdmin](https://labadmin.ef.technion.ac.il/) related emails, according to rules set by the user

## Example

Within the "flexible rule" page, set rules that you want to be scanned and executed.

#### Example:

1. We want to invite all students that have a project lecture in 7 days exactly - not too long ahead, but not too late either.

> **SQL query:** SELECT email_address FROM students WHERE student_id IN (SELECT student_id FROM project WHERE (lecture_time < NOW + 7 DAY) AND (lecture_time > NOW + 6 DAY)

Inner SQL query fetches all student id numbers from the table "project", that have a scheduled lecture in exactly a week from now.
The whole query fetches the e-mail addresses of these students.

>**Condition or set:** set

>**Send mail to:** comma separated list

>**Email addresses:** {email_address}

Note the placeholder syntax - the word "email_address" is one (the only) returned value of the SQL query, so when running the rule, it will send it to the returned value of the query.

>**Attach event:** [ ]

>**Attach message:** [x]

>**Message to attach:** Don't forget to come to the lecture in a week from today.

>**Title:** invitation

After a rule has been added, use a task scheduler to run it. E.g. in crontab:

```
30 0 * * * php <root_dir>/run_tasks.php
```
## Description of the different modules

There was a use of the [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture in this project.

#### Model

There are 2 model files. LabAdmin model and Rules model.

1. LabAdmin model is a connection to the LabAdmin database. This connection is read-only. Since the purpose of the system is to perform actions regarding the LabAdmin data, but not to change them, the connection will be set to read-only from the LabAdmin side.

2. Rules model - the connection to the internal system database. Using this model we will save the rules that we can later execute.

#### View

The view is in charge of displaying the web pages to the user. The main pages are:

1. newFleibleRule.php
2. rulesList.php

#### Controller

The controller runs "behind the curtains" to validate the data, controlling what should be shown and when, and passing data from the model to the viewer and vice versa.

## Configuration instructions

#### Prerequisites

1. [PHP](http://php.net/manual/en/install.php)
2. SQL ([MySQL](https://dev.mysql.com/downloads/installer/) recommended)
3. An SMTP service provider

#### Changes
1. Add / Edit a configuration file: cfg.php under the root directory, with all of the settings in this example:
```
<?php

$smtpConf = array(
    'username' => 'username',
    'password' => 'password',
    'hostname' => 'hostname',
    'port' => 2525, # Depends on your SMTP service provider
    'authentication' => true, # here too
);

$rulesDatabase = array(
    'username' => 'root',
    'password' => 'root',
    'dsn' => 'mysql:host=localhost;dbname=rules',
);

$labAdminDatabase = array(
    'username' => 'root',
    'password' => 'root',
    'dsn' => 'mysql:host=labadmin;dbname=database',
);
```

## People

## Contact info
