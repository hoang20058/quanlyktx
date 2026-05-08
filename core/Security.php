<?php

declare(strict_types=1);

final class Security
{
    private static bool $sessionStarted = false;

    public static function startSession(): void
    {
        if (self::$sessionStarted || session_status() === PHP_SESSION_ACTIVE) {
            self::$sessionStarted = true;
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$sessionStarted = true;
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function csrfToken(): string
    {
        self::startSession();

        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::e(self::csrfToken()) . '">';
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        self::startSession();

        if (!is_string($token) || $token === '') {
            return false;
        }

        return isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function login(array $user): void
    {
        self::startSession();
        // store minimal user info in session
        $_SESSION['user'] = [
            'id' => $user['id'] ?? null,
            'username' => $user['username'] ?? '',
            'full_name' => $user['full_name'] ?? ''
        ];
        $_SESSION['is_admin'] = false;
    }

    public static function loginAdmin(array $user): void
    {
        self::login($user);
        $_SESSION['is_admin'] = true;
    }

    public static function logout(): void
    {
        self::startSession();
        unset($_SESSION['user']);
        unset($_SESSION['is_admin']);
    }

    public static function user(): ?array
    {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return (bool) (self::user() !== null && !empty(self::user()['id']));
    }

    public static function isAdmin(): bool
    {
        self::startSession();
        return self::isLoggedIn() && (bool) ($_SESSION['is_admin'] ?? false);
    }

    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            $url = getenv('APP_URL') ?: '/';
            header('Location: ' . rtrim($url, '/') . '/login.php');
            exit;
        }
    }

    public static function requireAdminAuth(): void
    {
        if (!self::isAdmin()) {
            $url = getenv('APP_URL') ?: '/';
            header('Location: ' . rtrim($url, '/') . '/admin/login.php');
            exit;
        }
    }
}
