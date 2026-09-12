<?php

declare(strict_types=1);

/**
 * Autoload PSR-4 propio para el namespace `App\` (sin Composer).
 * Traduce `App\Core\Router` a `src/Core/Router.php`.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
