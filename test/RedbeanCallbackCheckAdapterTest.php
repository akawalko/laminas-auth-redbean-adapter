<?php

declare(strict_types=1);

namespace LaminasAuthRedbeanAdapter\Test;

use Laminas\Authentication\Adapter\DbTable\Exception\InvalidArgumentException;
use Laminas\Authentication\Result as AuthenticationResult;
use LaminasAuthRedbeanAdapter\RedbeanCallbackCheckAdapter;
use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;
use NilPortugues\Sql\QueryBuilder\Manipulation\QueryInterface;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\R as R;
use RedBeanPHP\ToolBox;
use RuntimeException;
use stdClass;

class RedbeanCallbackCheckAdapterTest extends TestCase
{
    const KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL = 'kira-shanahan@yahoo.com';
    const KIRA_SHANAHAN_YAHOO_COM__VALID_PASSWORD = 'A,0p3QC!0_';
    const DORIAN_CONN_YAHOO_COM__VALID_EMAIL = 'dorian-conn@yahoo.com';
    const DORIAN_CONN_YAHOO_COM__VALID_PASSWORD = 'f;qc35lyp0wAd-';
    const DORIAN_CONN_YAHOO_COM__VALID_SECOND_ACCOUNT_PASSWORD = '277hpQ#5G"Md!';

    protected ?ToolBox $redbean = null;
    protected ?RedbeanCallbackCheckAdapter $authAdapter = null;

    protected function setUp(): void
    {
        $this->setupDbAdapter();
        $this->prepareFixtures();
        $this->setupAuthAdapter();
    }

    protected function tearDown(): void
    {
        $this->authAdapter = null;

        if ($this->redbean instanceof ToolBox) {
            $this->redbean->getDatabaseAdapter()->exec('DROP TABLE user');
            $this->redbean = null;
            R::removeToolBoxByKey('default');
        }
    }

    protected function setupDbAdapter(): void
    {
        if ($this->redbean === null) {
            $this->redbean = R::setup('sqlite::memory:');
        }
    }

    protected function prepareFixtures(): void
    {
        $createTableSnippet = file_get_contents(__DIR__ . '/../examples/db_structure_sqlite.sql');
        $this->redbean->getDatabaseAdapter()->exec($createTableSnippet);

        $insertUsersSnippet = file_get_contents(__DIR__ . '/../examples/db_entries.sql');
        $this->redbean->getDatabaseAdapter()->exec($insertUsersSnippet);
    }

    protected function setupAuthAdapter(): void
    {
        $this->authAdapter = new RedbeanCallbackCheckAdapter(
            $this->redbean->getDatabaseAdapter(),
            new GenericBuilder(),
            'user',
            'email',
            'password',
            fn(string $hash, string $password) => password_verify($password, $hash)
        );
    }

    /** @test */
    public function authenticate_success(): void
    {
        $this->authAdapter->setIdentity(self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL);
        $this->authAdapter->setCredential(self::KIRA_SHANAHAN_YAHOO_COM__VALID_PASSWORD);
        $result = $this->authAdapter->authenticate();
        $this->assertTrue($result->isValid());
    }

    /** @test */
    public function authenticate_failure_invalid_credential(): void
    {
        $this->authAdapter->setIdentity(self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL);
        $this->authAdapter->setCredential('wrong_password_given');
        $result = $this->authAdapter->authenticate();
        $this->assertFalse($result->isValid());
    }

    /** @test */
    public function authenticate_callback_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid callback provided');
        $this->authAdapter->setCredentialValidationCallback('This is not a valid callback');
    }

    /** @test */
    public function authenticate_failure_identity_not_found(): void
    {
        $this->authAdapter->setIdentity('non_existent_username');
        $this->authAdapter->setCredential('my_password');

        $result = $this->authAdapter->authenticate();
        $this->assertEquals(AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
    }

    /** @test */
    public function authenticate_failure_identity_ambiguous(): void
    {
        $this->authAdapter
            ->setIdentity(self::DORIAN_CONN_YAHOO_COM__VALID_EMAIL)
            ->setCredential(self::DORIAN_CONN_YAHOO_COM__VALID_PASSWORD);

        $result = $this->authAdapter->authenticate();
        $this->assertEquals(AuthenticationResult::FAILURE_IDENTITY_AMBIGUOUS, $result->getCode());
    }

    /** @test */
    public function authenticate_success_identity_ambiguous_method_1(): void
    {
        // test if user 1 can authenticate
        $this->authAdapter
            ->setIdentity(self::DORIAN_CONN_YAHOO_COM__VALID_EMAIL)
            ->setCredential(self::DORIAN_CONN_YAHOO_COM__VALID_PASSWORD)
            ->setAmbiguityIdentity(true);

        $result = $this->authAdapter->authenticate();
        $this->assertEquals(AuthenticationResult::SUCCESS, $result->getCode());

        // test if user 2 can authenticate
        $this->authAdapter
            ->setIdentity(self::DORIAN_CONN_YAHOO_COM__VALID_EMAIL)
            ->setCredential(self::DORIAN_CONN_YAHOO_COM__VALID_SECOND_ACCOUNT_PASSWORD)
            ->setAmbiguityIdentity(true);

        $result = $this->authAdapter->authenticate();
        $this->assertEquals(AuthenticationResult::SUCCESS, $result->getCode());
    }

    /** @test */
    public function authenticate_success_identity_ambiguous_method_2(): void
    {
        $this->authAdapter
            ->getDbSelect()
            ->where()
                ->equals('is_admin', 0)
            ->end();

        $this->authAdapter
            ->setIdentity(self::DORIAN_CONN_YAHOO_COM__VALID_EMAIL)
            ->setCredential(self::DORIAN_CONN_YAHOO_COM__VALID_PASSWORD);

        $result = $this->authAdapter->authenticate();
        $this->assertEquals(AuthenticationResult::SUCCESS, $result->getCode());
    }

    /** @test */
    public function get_result_row(): void
    {
        $this->authAdapter->setIdentity(self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL);
        $this->authAdapter->setCredential(self::KIRA_SHANAHAN_YAHOO_COM__VALID_PASSWORD);
        $this->authAdapter->authenticate();

        $resultRow = $this->authAdapter->getResultRowObject();
        $this->assertEquals($resultRow->email, self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL);
    }

    /** @test */
    public function get_specific_result_row(): void
    {
        $this->authAdapter->setIdentity(self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL);
        $this->authAdapter->setCredential(self::KIRA_SHANAHAN_YAHOO_COM__VALID_PASSWORD);
        $this->authAdapter->authenticate();

        $resultRow = $this->authAdapter->getResultRowObject(['email', 'is_admin']);
        $this->assertEquals(
            'O:8:"stdClass":2:{s:5:"email";s:23:"kira-shanahan@yahoo.com";s:8:"is_admin";s:1:"0";}',
            serialize($resultRow)
        );
    }

    /** @test */
    public function get_omitted_result_row(): void
    {
        $this->authAdapter->setIdentity(self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL);
        $this->authAdapter->setCredential(self::KIRA_SHANAHAN_YAHOO_COM__VALID_PASSWORD);
        $this->authAdapter->authenticate();

        $resultRow = $this->authAdapter->getResultRowObject(null, 'password');
        $expected = new stdClass();
        $expected->id = 1;
        $expected->email = self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL;
        $expected->is_active = '1';
        $expected->is_admin = '0';
        $this->assertEquals($expected, $resultRow);
    }

    /** @test */
    public function adapter_can_return_db_select_object(): void
    {
        $this->assertInstanceOf(QueryInterface::class, $this->authAdapter->getDbSelect());
    }

    /** @test */
    public function adapter_can_use_modified_db_select_object(): void
    {
        $select = $this->authAdapter->getDbSelect();
        $select
            ->where()
                ->equals('id', 9999) // there is no record with such ID
            ->end();
        $this->authAdapter->setIdentity(self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL);
        $this->authAdapter->setCredential(self::KIRA_SHANAHAN_YAHOO_COM__VALID_PASSWORD);

        $result = $this->authAdapter->authenticate();
        $this->assertEquals(AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
    }

    /** @test */
    public function adapter_can_limit_the_number_of_columns_fetched_via_db_select_object(): void
    {
        $select = $this->authAdapter->getDbSelect();
        $select->setColumns([
            'email',
            'password',
        ]);
        $this->authAdapter->setIdentity(self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL);
        $this->authAdapter->setCredential(self::KIRA_SHANAHAN_YAHOO_COM__VALID_PASSWORD);
        $this->authAdapter->authenticate();

        $resultRow = $this->authAdapter->getResultRowObject();
        $expected = new stdClass();
        $expected->email = self::KIRA_SHANAHAN_YAHOO_COM__VALID_EMAIL;
        $expected->password = '$2y$10$CwKgjqgHsM8DMQALfZCvIuo282a8VGoaA2h3V5FuVr89dhJPvsBlm'; // hashed password
        $this->assertEquals($expected, $resultRow);
    }

    /** @test */
    public function catch_exception_no_table(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A table must be supplied for');
        $adapter = new RedbeanCallbackCheckAdapter(
            $this->redbean->getDatabaseAdapter(),
            new GenericBuilder()
        );
        $adapter->authenticate();
    }

    /** @test */
    public function catch_exception_no_identity_column(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An identity column must be supplied for the');
        $adapter = new RedbeanCallbackCheckAdapter(
            $this->redbean->getDatabaseAdapter(),
            new GenericBuilder(),
            'user'
        );
        $adapter->authenticate();
    }

    /** @test */
    public function catch_exception_no_credential_column(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A credential column must be supplied');
        $adapter = new RedbeanCallbackCheckAdapter(
            $this->redbean->getDatabaseAdapter(),
            new GenericBuilder(),
            'user',
            'email'
        );
        $adapter->authenticate();
    }

    /** @test */
    public function catch_exception_no_identity(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A value for the identity was not provided prior');
        $this->authAdapter->authenticate();
    }

    /** @test */
    public function catch_exception_no_credential(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A credential value was not provided prior');
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->authenticate();
    }

    /** @test */
    public function catch_exception_bad_sql(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The supplied parameters to');
        $this->authAdapter->setTableName('bad_table_name');
        $this->authAdapter->setIdentity('value');
        $this->authAdapter->setCredential('value');
        $this->authAdapter->authenticate();
    }
}
