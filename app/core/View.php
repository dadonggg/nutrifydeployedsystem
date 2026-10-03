<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $path = BASE_PATH . '/app/views/' . $view . '.php';

        if (!is_file($path)) {
            $resolvedPath = self::resolveViewPath($view);
            if ($resolvedPath !== null) {
                $path = $resolvedPath;
            } else {
                http_response_code(500);
                echo "View not found: " . htmlspecialchars($view) . " (Tried: " . htmlspecialchars($path) . ")";
                return;
            }
        }

        if (!isset($data['csrf_token'])) {
            $data['csrf_token'] = Controller::csrfToken();
        }
        if (!isset($data['csrf_input'])) {
            $data['csrf_input'] = Controller::csrfInput();
        }

        extract($data, EXTR_SKIP);
        require $path;
    }

    /**
     * Resolve the view path case-insensitively by scanning directories.
     */
    private static function resolveViewPath(string $view): ?string
    {
        $parts = explode('/', str_replace('\\', '/', $view));
        $currentPath = BASE_PATH . '/app/views';

        foreach ($parts as $index => $part) {
            if (!is_dir($currentPath)) {
                return null;
            }

            $partLower = strtolower($part);
            $found = false;

            // Scan directory items
            foreach (scandir($currentPath) as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                if (strtolower($item) === $partLower && is_dir($currentPath . '/' . $item)) {
                    $currentPath .= '/' . $item;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // If it is the last part, check for the filename with .php extension case-insensitively
                if ($index === count($parts) - 1) {
                    $fileLower = $partLower . '.php';
                    foreach (scandir($currentPath) as $item) {
                        if (strtolower($item) === $fileLower && is_file($currentPath . '/' . $item)) {
                            return $currentPath . '/' . $item;
                        }
                    }
                }
                return null;
            }
        }

        $filePath = $currentPath . '.php';
        if (is_file($filePath)) {
            return $filePath;
        }

        return null;
    }
}
