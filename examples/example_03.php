<?php
require __DIR__ . '/../vendor/autoload.php';

use LaminasAuthRedbeanAdapter\RedbeanCallbackCheckAdapter;
use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;

// Before you run this example, make sure you have created a database and a user account that provides access to it,
// and have also executed the SQL commands from the db_structure.sql and db_entries.sql files.

$redbean = (require_once 'db_connection.php')();

$authAdapter = new RedbeanCallbackCheckAdapter(
    $redbean->getDatabaseAdapter(),
    new GenericBuilder(),
    'user',
    'email',
    'password',
    fn(string $hash, string $password) => password_verify($password, $hash)
);

// email                           password             is_active       is_admin
// dorian-conn@yahoo.com           f;qc35lyp0wAd-       1               0
// dorian-conn@yahoo.com           277hpQ#5G"Md!        1               1

// Looking at the above data, you can see that there are two records with the same email, but different passwords
// and more importantly, with different permissions (is_admin column).
// Laminas Authentication by default allows the use of a non-unique value for the user identity column
// and this adapter honors this rule. There are 2 ways to take advantage of this

// METHOD 1
$authenticationResult = $authAdapter
    ->setIdentity('dorian-conn@yahoo.com')
    ->setCredential('f;qc35lyp0wAd-') // password for account WITHOUT admin permission
    ->setAmbiguityIdentity(true)    // Setting this is IMPORTANT
    ->authenticate()
;

// Because there are 2 entries in the database with the same email,
// without AmbiguityIdentity set to true, authentication would fail with
// code = -2 and message = 'More than one record matches the supplied identity.'
// With this in place, the code loops through the results and runs a user-defined function
// that compares the provided password to its encrypted version stored in the database.

var_dump($authenticationResult->isValid()); // will return true

echo "\n\n";

// METHOD 2
// You can modify the query to take into account only those users whose "is_admin" column has the value 1.
$authAdapter
    ->getDbSelect()
    ->where()
        ->equals('is_admin', 1) // both int and boolean value works the same
    ->end();

// This way you don't have to set AmbiguityIdentity to true. This is a good solution for the admin login form,
// but not so much for the login form that all users use.

$authenticationResult = $authAdapter
    ->setIdentity('dorian-conn@yahoo.com')
    //->setCredential('f;qc35lyp0wAd-') // password for account WITHOUT admin permission WILL FAIL
    ->setCredential('277hpQ#5G"Md!') // password for account WITH admin permission WILL SUCCEED
    ->authenticate()
;

var_dump($authenticationResult->isValid()); // will return true
