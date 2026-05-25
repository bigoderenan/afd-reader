<?php

/**
 * Authentication configuration.
 *
 * This file returns an array containing the default username and
 * password hash used for the simple login system. The password hash
 * corresponds to the password "admin123" hashed using PHP's
 * password_hash() function with the default algorithm. To change
 * the default credentials, generate a new hash using
 * password_hash() and update the values below accordingly.
 */

return [
    'user'      => 'admin',
    'pass_hash' => '$2y$10$eCAOc0mVzpZFf8QHmKmsqeoE/PCg4IvSI9uMO1ATbD/jz3xPo3bya',
];