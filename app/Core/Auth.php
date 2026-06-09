<?php

declare(strict_types=1);

namespace App\Core;

use PDOException;

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return true;
    }

    public static function attempt(string $username, string $password): bool
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT id, name, username, password_hash FROM system_users WHERE username = :username AND active = 1 LIMIT 1');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'username' => $user['username'],
                ];
                return true;
            }
        } catch (\Throwable) {
            // Fallback apenas para a primeira execução sem banco/schema importado.
            if ($username === 'admin' && hash_equals('admin123', $password)) {
                session_regenerate_id(true);
                $_SESSION['user'] = ['id' => 0, 'name' => 'Usuário Padrão', 'username' => 'admin'];
                $_SESSION['db_warning'] = 'Login feito em modo provisório. Importe o schema.sql para ativar o login pelo banco.';
                return true;
            }
        }

        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function require(): void
    {
        // Login desativado: nenhuma rota deve redirecionar para /login.
    }
}
