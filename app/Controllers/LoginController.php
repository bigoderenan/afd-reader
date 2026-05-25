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
        if (isset($_SESSION['user'])) {
            header('Location: index.php?page=upload');
            exit;
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('login', ['error' => $error]);
    }

    /**
     * Authenticate the user using the posted username and password.
     */
    public function authenticate(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $config = include __DIR__ . '/../../config/auth.php';
        if ($username === $config['user'] && password_verify($password, $config['pass_hash'])) {
            $_SESSION['user'] = $username;
            header('Location: index.php?page=upload');
            exit;
        }
        $_SESSION['login_error'] = 'Usuário ou senha inválidos';
        header('Location: index.php?page=login');
        exit;
    }

    /**
     * Terminate the session and redirect to the login page.
     */
    public function logout(): void
    {
        unset($_SESSION['user']);
        header('Location: index.php?page=login');
        exit;
    }
}