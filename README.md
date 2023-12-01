# laminas-auth-redbean-adapter

## What is this

It's an authentication adapter for a great library called [Laminas\Authentication](https://github.com/laminas/laminas-authentication)

It focuses on replacing the default database adapter, Laminas\Db\Adapter\Adapter from library [Laminas\Db](https://github.com/laminas/laminas-db),
with an adapter derived from a lightweight ORM called [RedBeanPHP](https://github.com/gabordemooij/redbean "ORM layer that creates models, config and database on the fly").

Replacing the database adapter necessitated replacing Laminas\Db\Sql\Select (part of the same [Laminas\Db](https://github.com/laminas/laminas-db) library)
with something that would provide similar functionality. To avoid wasting dozens of hours writing my custom SQL query builder,
I opted to use an excellent library that does not require establishing a connection to the database to generate a query.

[SQL Query Builder](https://github.com/nilportugues/php-sql-query-builder) by Nil Portugués Calderó provides
an elegant lightweight and efficient SQL Query Builder with fluid interface SQL syntax supporting bindings
and complicated query generation.

Apart from replacing the database adapter and the SQL query builder, the main improvement over the original
is the ability to have full control over the number of columns returned from the database.
[Example no. 3](https://github.com/akawalko/laminas-auth-redbean-adapter/blob/main/examples/example_03.php) shows how this can be done.
This can be a big advantage for tables that have a large number of columns. The minimum number of columns returned is 2, one for the identity column,
the other for the password. For obvious reasons, a column with user ID will also be useful, but it is not required.

## Install
### Require
- php: >=7.4
- gabordemooij/redbean (can be installed in 2 ways, that's why I omitted this package in composer.json)


```
composer require akawalko/laminas-auth-redbean-adapter
```

## Usage
After installing the package, read the Laminas\Authentication documentation at [https://docs.laminas.dev/laminas-authentication/](https://docs.laminas.dev/laminas-authentication/)

Be sure to also check out the folder with examples I have prepared for you: https://github.com/akawalko/laminas-auth-redbean-adapter/tree/main/examples

Before you run any example, make sure you have created a database and a user account that provides access to it,
and have also executed the SQL commands from the db_structure_mysql.sql (or db_structure_sqlite.sql) and db_entries.sql files.
