<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;

final class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/login', ['title' => 'Login'], 'auth/layout');
    }

    public function authenticate(): void
    {
        Csrf::verify();
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (Auth::attempt($username, $password)) {
            $this->redirect('/');
        }

        $this->flash('danger', 'Usuário ou senha inválidos.');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . url('/login'));
        exit;
    }
}
