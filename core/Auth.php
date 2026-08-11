<?php

declare(strict_types=1);

namespace Core;

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    // IMPORTANT: check solide (misoroka loop)
    public static function check(): bool
    {
        return isset($_SESSION['user'])
            && is_array($_SESSION['user'])
            && isset($_SESSION['user']['iduser'], $_SESSION['user']['role'])
            && $_SESSION['user']['iduser'] !== null
            && trim((string)$_SESSION['user']['role']) !== '';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();

        $u = self::user() ?? [];
        $stored = strtolower(trim((string)($u['role'] ?? '')));
        $need   = strtolower(trim($role));

        if ($stored !== $need) {
            http_response_code(403);
            exit('Accès refusé');
        }
    }

    public static function redirectAfterLogin(): void
    {
        self::requireLogin();

        $role = strtolower(trim((string)($_SESSION['user']['role'] ?? '')));

        if ($role === 'admin') {
            header('Location: ' . BASE_URL . '/admin/dashboard');
            exit;
        }

        if ($role === 'gerant') {
            header('Location: ' . BASE_URL . '/gerant/dashboard');
            exit;
        }

        self::logoutAndRedirect();
    }

    public static function logoutAndRedirect(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool)($params['secure'] ?? false),
                (bool)($params['httponly'] ?? true)
            );
        }

        session_destroy();

        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
