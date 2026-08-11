<?php

declare(strict_types=1);

namespace Core;

abstract class Controller
{
    /**
     * Render a PHP view (MVC).
     *
     * @param string $view View path relative to app/views without extension.
     * @param array<string,mixed> $data Variables injected into the view.
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = __DIR__ . '/../app/views/' . $view . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            exit("View not found: " . htmlspecialchars($view));
        }

        require $viewFile;
    }

    /**
     * Minimal flash messaging stored in session.
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * Read and clear flash.
     *
     * @return array{type:string,message:string}|null
     */
    protected function pullFlash(): ?array
    {
        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            return null;
        }
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return [
            'type' => (string)($f['type'] ?? 'info'),
            'message' => (string)($f['message'] ?? ''),
        ];
    }

    /**
     * CSRF token helpers.
     */
    protected function csrfToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    protected function requireCsrf(): void
    {
        $token = (string)($_POST['_csrf'] ?? '');
        $sess  = (string)($_SESSION['_csrf'] ?? '');

        if ($token === '' || $sess === '' || !hash_equals($sess, $token)) {
            http_response_code(419);
            exit('Session expirée (CSRF).');
        }
    }

    protected function redirect(string $path): void
    {
        $base = rtrim(BASE_URL, '/');
        header('Location: ' . $base . $path);
        exit;
    }
}
