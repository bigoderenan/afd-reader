<?php

/**
 * Database configuration.
 *
 * In this simplified version of the AFD reader, the database
 * configuration is not actively used, since the project focuses on
 * file parsing and display. However, the array returned here
 * illustrates how connection details could be stored for future use
 * when persisting data to MySQL or MariaDB via PDO.
 */

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'afd',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
];