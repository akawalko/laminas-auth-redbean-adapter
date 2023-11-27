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

$authenticationResult = $authAdapter
    ->setIdentity('kira-shanahan@yahoo.com')
    ->setCredential('A,0p3QC!0_') // valid password
    ->authenticate();

if ($authenticationResult->isValid()) {
    echo "Authentication succeeded\n";
    // you can grab all the columns in selected row (default)
    //$authAdapter->getResultRowObject()

    // specify only the columns you want
    //$authAdapter->getResultRowObject(['email', 'is_active']);

    // or specify the columns you want to skip
    //$authAdapter->getResultRowObject(null, ['is_admin']);
} else {
    var_dump($authenticationResult);
    // or use specific method to find out why it failed
    //$authenticationResult->getCode(); // int; check constans in Laminas\Authentication\Result class
    
    //$authenticationResult->getMessages(); // array
}
