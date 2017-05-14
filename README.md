# LabAdmin alert system

## Synopsis

This system aim to send alerts to LabAdmin related emails, according to rules set by the user

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

## Configuration instructions

## People

## Contact info
