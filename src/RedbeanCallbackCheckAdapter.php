<?php

declare(strict_types=1);

namespace LaminasAuthRedbeanAdapter;

use Exception;
use Laminas\Authentication\Adapter\AbstractAdapter;
use Laminas\Authentication\Adapter\DbTable\Exception\InvalidArgumentException;
use Laminas\Authentication\Result as AuthenticationResult;
use NilPortugues\Sql\QueryBuilder\Builder\BuilderInterface;
use NilPortugues\Sql\QueryBuilder\Manipulation\QueryInterface;
use RedBeanPHP\Adapter;
use RuntimeException;
use stdClass;

class RedbeanCallbackCheckAdapter extends AbstractAdapter
{
    protected Adapter $dbAdapter;

    protected BuilderInterface $queryBuilder;

    protected ?QueryInterface $selectQuery = null;

    /**
     * $tableName - the table name to check
     *
     * @var string
     */
    protected ?string $tableName = null;

    /**
     * $identityColumn - the column to use as the identity
     *
     * @var string
     */
    protected ?string $identityColumn = null;

    /**
     * $credentialColumns - columns to be used as the credentials
     *
     * @var string
     */
    protected ?string $credentialColumn = null;

    /**
     * $credentialValidationCallback - provide a callback that allows for validation to happen in code
     *
     * @var callable
     */
    protected $credentialValidationCallback = null;

    /**
     * $authenticateResultInfo
     *
     * @var array
     */
    protected array $authenticateResultInfo;

    /**
     * $resultRow - Results of database authentication query
     *
     * @var array|null
     */
    protected ?array $resultRow = null;

    /**
     * $ambiguityIdentity - Flag to indicate same Identity can be used with
     * different credentials. Default is FALSE and need to be set to true to
     * allow ambiguity usage.
     *
     * @var bool
     */
    protected bool $ambiguityIdentity = false;

    public function __construct(
        Adapter $dbAdapter,
        BuilderInterface $queryBuilder,
        ?string $tableName = null,
        ?string $identityColumn = null,
        ?string $credentialColumn = null,
        ?callable $credentialValidationCallback = null
    )
    {
        $this->dbAdapter = $dbAdapter;
        $this->queryBuilder = $queryBuilder;
        $this->tableName = $tableName;
        $this->identityColumn = $identityColumn;
        $this->credentialColumn = $credentialColumn;

        if (null !== $credentialValidationCallback) {
            $this->setCredentialValidationCallback($credentialValidationCallback);
        } else {
            $this->setCredentialValidationCallback(function ($a, $b) {
                return $a === $b;
            });
        }
    }

    /**
     * setTableName() - set the table name to be used in the select query
     *
     * @param  string $tableName
     * @return self Provides a fluent interface
     */
    public function setTableName($tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * setIdentityColumn() - set the column name to be used as the identity column
     *
     * @param  string $identityColumn
     * @return self Provides a fluent interface
     */
    public function setIdentityColumn($identityColumn): self
    {
        $this->identityColumn = $identityColumn;
        return $this;
    }

    /**
     * setCredentialColumn() - set the column name to be used as the credential column
     *
     * @param  string $credentialColumn
     * @return self Provides a fluent interface
     */
    public function setCredentialColumn($credentialColumn): self
    {
        $this->credentialColumn = $credentialColumn;
        return $this;
    }

    /**
     * setCredentialValidationCallback() - allows the developer to use a callback as a way of checking the
     * credential.
     *
     * @param callable $validationCallback
     * @return self Provides a fluent interface
     * @throws InvalidArgumentException
     */
    public function setCredentialValidationCallback(callable $validationCallback): self
    {
        if (! is_callable($validationCallback)) {
            throw new InvalidArgumentException('Invalid callback provided');
        }
        $this->credentialValidationCallback = $validationCallback;
        return $this;
    }

    /**
     * setAmbiguityIdentity() - sets a flag for usage of identical identities
     * with unique credentials. It accepts integers (0, 1) or boolean (true,
     * false) parameters. Default is false.
     *
     * @param  int|bool $flag
     * @return self Provides a fluent interface
     */
    public function setAmbiguityIdentity($flag): self
    {
        if (is_int($flag)) {
            $this->ambiguityIdentity = 1 === $flag;
        } elseif (is_bool($flag)) {
            $this->ambiguityIdentity = $flag;
        }
        return $this;
    }

    /**
     * getAmbiguityIdentity() - returns TRUE for usage of multiple identical
     * identities with different credentials, FALSE if not used.
     *
     * @return bool
     */
    public function getAmbiguityIdentity(): bool
    {
        return $this->ambiguityIdentity;
    }

    /**
     * @param BuilderInterface $queryBuilder
     * @return self Provides a fluent interface
     */
    public function setQueryBuilder(BuilderInterface $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    /**
     * getDbSelect() - Return the preauthentication QueryInterface object for userland select query modification
     *
     * @return QueryInterface
     */
    public function getDbSelect(): QueryInterface
    {
        if ($this->selectQuery === null) {
            // not passing a column list (as 2nd argument) will select all columns
            $this->selectQuery = $this->queryBuilder->select($this->tableName);
        }

        return $this->selectQuery;
    }

    /**
     * getResultRowObject() - Returns the result row as a stdClass object
     *
     * @param  string|array $returnColumns
     * @param  string|array $omitColumns
     * @return stdClass|bool
     */
    public function getResultRowObject($returnColumns = null, $omitColumns = null)
    {
        if (! $this->resultRow) {
            return false;
        }

        $returnObject = new stdClass();

        if (null !== $returnColumns) {
            $availableColumns = array_keys($this->resultRow);
            foreach ((array) $returnColumns as $returnColumn) {
                if (in_array($returnColumn, $availableColumns)) {
                    $returnObject->{$returnColumn} = $this->resultRow[$returnColumn];
                }
            }
            return $returnObject;
        } elseif (null !== $omitColumns) {
            $omitColumns = (array) $omitColumns;
            foreach ($this->resultRow as $resultColumn => $resultValue) {
                if (! in_array($resultColumn, $omitColumns)) {
                    $returnObject->{$resultColumn} = $resultValue;
                }
            }
            return $returnObject;
        }

        foreach ($this->resultRow as $resultColumn => $resultValue) {
            $returnObject->{$resultColumn} = $resultValue;
        }
        return $returnObject;
    }

    /**
     * This method is called to attempt an authentication. Previous to this
     * call, this adapter would have already been configured with all
     * necessary information to successfully connect to a database table and
     * attempt to find a record matching the provided identity.
     *
     * @throws RuntimeException If answering the authentication query is impossible.
     * @return AuthenticationResult
     */
    public function authenticate()
    {
        $this->authenticateSetup();
        $resultIdentities = $this->authenticateQuerySelect($this->authenticateCreateSelect());

        if (($authResult = $this->authenticateValidateResultSet($resultIdentities)) instanceof AuthenticationResult) {
            return $authResult;
        }

        // At this point, ambiguity is already done. Loop, check and break on success.
        foreach ($resultIdentities as $identity) {
            $authResult = $this->authenticateValidateResult($identity);
            if ($authResult->isValid()) {
                break;
            }
        }

        return $authResult;
    }

    /**
     * _authenticateSetup() - This method abstracts the steps involved with
     * making sure that this adapter was indeed setup properly with all
     * required pieces of information.
     *
     * @throws RuntimeException In the event that setup was not done properly.
     * @return bool
     */
    protected function authenticateSetup()
    {
        $exception = null;

        if ((string) $this->tableName === '') {
            $exception = 'A table must be supplied for the Redbean authentication adapter.';
        } elseif ((string) $this->identityColumn === '') {
            $exception = 'An identity column must be supplied for the Redbean authentication adapter.';
        } elseif ((string) $this->credentialColumn === '') {
            $exception = 'A credential column must be supplied for the Redbean authentication adapter.';
        } elseif ((string) $this->identity === '') {
            $exception = 'A value for the identity was not provided prior to authentication with Redbean.';
        } elseif ($this->credential === null) {
            $exception = 'A credential value was not provided prior to authentication with Redbean.';
        }

        if (null !== $exception) {
            throw new RuntimeException($exception);
        }

        $this->authenticateResultInfo = [
            'code'     => AuthenticationResult::FAILURE,
            'identity' => $this->identity,
            'messages' => [],
        ];

        return true;
    }

    /**
     * _authenticateQuerySelect() - This method accepts a QueryInterface object and
     * performs a query against the database with that object.
     *
     * @throws RuntimeException When an invalid select object is encountered.
     * @return array
     */
    protected function authenticateQuerySelect(QueryInterface $selectQuery)
    {
        try {
            $resultIdentities = $this->dbAdapter->get(
                $this->queryBuilder->write($selectQuery),
                $this->queryBuilder->getValues()
            );
        } catch (Exception $e) {
            throw new RuntimeException(
                'The supplied parameters to DbTable failed to '
                . 'produce a valid sql statement, please check table and column names '
                . 'for validity.',
                0,
                $e
            );
        }
        return $resultIdentities;
    }

    protected function authenticateCreateSelect(): QueryInterface
    {
        $selectQuery = clone $this->getDbSelect();
        // will select all columns unless you specify them yourself via $this->getDbSelect()->setColumns(array)
        return $selectQuery
            ->setTable($this->tableName)
            ->where()
                ->equals($this->identityColumn, $this->identity)
            ->end()
            ;
    }

    /**
     * _authenticateValidateResultSet() - This method attempts to make
     * certain that only one record was returned in the resultset
     *
     * @param  array $resultIdentities
     * @return bool|AuthenticationResult
     */
    protected function authenticateValidateResultSet(array $resultIdentities)
    {
        if (! $resultIdentities) {
            $this->authenticateResultInfo['code']       = AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND;
            $this->authenticateResultInfo['messages'][] = 'A record with the supplied identity could not be found.';
            return $this->authenticateCreateAuthResult();
        } elseif (count($resultIdentities) > 1 && false === $this->getAmbiguityIdentity()) {
            $this->authenticateResultInfo['code']       = AuthenticationResult::FAILURE_IDENTITY_AMBIGUOUS;
            $this->authenticateResultInfo['messages'][] = 'More than one record matches the supplied identity.';
            return $this->authenticateCreateAuthResult();
        }

        return true;
    }

    /**
     * _authenticateValidateResult() - This method attempts to validate that
     * the record in the resultset is indeed a record that matched the
     * identity provided to this adapter.
     *
     * @param  array $resultIdentity
     * @return AuthenticationResult
     */
    protected function authenticateValidateResult($resultIdentity): AuthenticationResult
    {
        try {
            $callbackResult = call_user_func(
                $this->credentialValidationCallback,
                $resultIdentity[$this->credentialColumn],
                $this->credential
            );
        } catch (Exception $e) {
            $this->authenticateResultInfo['code']       = AuthenticationResult::FAILURE_UNCATEGORIZED;
            $this->authenticateResultInfo['messages'][] = $e->getMessage();
            return $this->authenticateCreateAuthResult();
        }
        if ($callbackResult !== true) {
            $this->authenticateResultInfo['code']       = AuthenticationResult::FAILURE_CREDENTIAL_INVALID;
            $this->authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
            return $this->authenticateCreateAuthResult();
        }

        $this->resultRow = $resultIdentity;

        $this->authenticateResultInfo['code']       = AuthenticationResult::SUCCESS;
        $this->authenticateResultInfo['messages'][] = 'Authentication successful.';
        return $this->authenticateCreateAuthResult();
    }

    /**
     * Creates a Laminas\Authentication\Result object from the information that
     * has been collected during the authenticate() attempt.
     *
     * @return AuthenticationResult
     */
    protected function authenticateCreateAuthResult()
    {
        return new AuthenticationResult(
            $this->authenticateResultInfo['code'],
            $this->authenticateResultInfo['identity'],
            $this->authenticateResultInfo['messages']
        );
    }
}
