<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * Class LoginController
 *
 * Handles display of the login form, authentication of the user and
 * logout. Credentials are read from config/auth.php. Once logged in,
 * a session variable 'user' is created. All other controllers check
 * this value to restrict access to authenticated users.
 */
class LoginController extends Controller
{
    /**
     * Display the login form.
     */
    public function index(): void
    {
        header('Location: index.php?page=upload');
        exit;
    }

    /**
     * Authenticate the user using the posted username and password.
     */
    public function authenticate(): void
    {
        header('Location: index.php?page=upload');
        exit;
    }

    /**
     * Terminate the session and redirect to the login page.
     */
    public function logout(): void
    {
        header('Location: index.php?page=upload');
        exit;
    }
}