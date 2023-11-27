<?php

declare(strict_types=1);

use RedBeanPHP\R as R;
use RedBeanPHP\ToolBox;

return function (): ToolBox {
    $dsn = sprintf(
        '%1$s:host=%2$s; port=%3$s; dbname=%4$s; charset=%5$s',
        'mysql', // db type
        'localhost',
        3306,
        'redbean',
        'utf8mb4'
    );
    $username = 'test_app_user';
    $password = 'Nu2U3vr93jFK';
    return R::setup($dsn, $username, $password);
};
