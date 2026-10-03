<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['_csrf_token'];
    }

    public static function csrfInput(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(self::csrfToken()) . '">';
    }

    protected function validateCsrf(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }

        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        $postToken = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (empty($sessionToken) || empty($postToken) || !hash_equals((string)$sessionToken, (string)$postToken)) {
            return false;
        }

        return true;
    }

    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function redirect(string $route): void
    {
        $config = Container::get('config');
        $baseUrl = $config['app']['base_url'] ?? '';
        // Do NOT urlencode — extra params like &gym_id=48 must stay as real query params
        header('Location: ' . rtrim($baseUrl, '/') . '/index.php?r=' . $route);
        exit;
    }
}
