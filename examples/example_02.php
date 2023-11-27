<?php
require __DIR__ . '/../vendor/autoload.php';

use LaminasAuthRedbeanAdapter\RedbeanCallbackCheckAdapter;
use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;

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

// Looking at the above data, you can see that this person does not have administrator rights.
// So if you would like to implement a separate form for logging in as admin,
// you should modify the query to take into account the "is_admin" column.
$authAdapter
    ->getDbSelect()
    ->where()
        ->equals('is_admin', 1) // both int and boolean value works the same
    ->end();

$authenticationResult = $authAdapter
    ->setIdentity('kira-shanahan@yahoo.com')
    ->setCredential('A,0p3QC!0_') // valid password
    ->authenticate();

// It will fail due to no record matching the criteria
// SELECT user.* FROM user WHERE (user.is_admin = :v1) AND (user.email = :v2)
if ($authenticationResult->isValid()) {
    echo "Authentication succeeded\n";
} else {
    echo "Authentication failed\n";
    var_dump($authenticationResult);
    // or use specific method to find out why it failed
    //$authenticationResult->getCode(); // int; check constans in Laminas\Authentication\Result class
    
    //$authenticationResult->getMessages(); // array
}
