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
        $this->redirect('/');
    }

    public function authenticate(): void
    {
        $this->redirect('/');
    }

    public function logout(): void
    {
        $this->redirect('/');
    }
}
