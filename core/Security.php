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

    /**
     * Admin login - only admin login is supported
     */
    public static function loginAdmin(array $user): void
    {
        self::startSession();
        $_SESSION['admin'] = [
            'id' => $user['id'] ?? null,
            'username' => $user['username'] ?? '',
            'full_name' => $user['full_name'] ?? ''
        ];
    }

    public static function logout(): void
    {
        self::startSession();
        unset($_SESSION['admin']);
    }

    public static function admin(): ?array
    {
        self::startSession();
        return $_SESSION['admin'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return (bool) (self::admin() !== null && !empty(self::admin()['id']));
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
