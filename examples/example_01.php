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

// email                           password         is_active       is_admin
// kira-shanahan@yahoo.com         A,0p3QC!0_       1               0

$authenticationResult = $authAdapter
    ->setIdentity('kira-shanahan@yahoo.com')
    ->setCredential('A,0p3QC!0_') // valid password
    ->authenticate(); // Will execute query: SELECT user.* FROM user WHERE (user.email = :v1)

if ($authenticationResult->isValid()) {
    echo "Authentication succeeded\n";
    // you can grab all the columns in selected row (default)
    //$authAdapter->getResultRowObject()

    // specify only the columns you want
    //$authAdapter->getResultRowObject(['email', 'is_active']);

    // or specify the columns you want to skip
    //$authAdapter->getResultRowObject(null, ['is_admin']);
} else {
    echo "Authentication failed\n";
    var_dump($authenticationResult);
    // or use specific method to find out why it failed
    //$authenticationResult->getCode(); // int; check constans in Laminas\Authentication\Result class
    
    //$authenticationResult->getMessages(); // array
}

echo "\n\n";

// If your table with users contains too many columns and you want to slim down the default query
// that retrieves all the columns, you can do it as follows
$authAdapter
    ->getDbSelect()
    ->setColumns(['email', 'password']);

// Columns you set (via constructor or setter) as identityColumn and credentialColumn are bare minimum.

$authenticationResult = $authAdapter
    ->setIdentity('kira-shanahan@yahoo.com')
    ->setCredential('A,0p3QC!0_') // valid password
    ->authenticate(); // will execute: SELECT user.email, user.password FROM user WHERE (user.email = :v1)

var_dump($authenticationResult->isValid()); // will return true