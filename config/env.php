<?php
class EnvConfig {
    private static $vars = [];
    private static $loaded = false;

    public static function load($envFile = null) {
        if (self::$loaded) return;

        if (!$envFile) {
            $envFile = __DIR__ . '/../.env.local';
            if (!file_exists($envFile)) {
                $envFile = __DIR__ . '/../.env';
            }
        }

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) continue;

                // Parse KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remove quotes if present
                    if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                        (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                        $value = substr($value, 1, -1);
                    }

                    self::$vars[$key] = $value;
                    $_ENV[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    public static function get($key, $default = null) {
        if (!self::$loaded) self::load();
        return isset(self::$vars[$key]) ? self::$vars[$key] : $default;
    }

    public static function all() {
        if (!self::$loaded) self::load();
        return self::$vars;
    }
}

// Auto-load on include
EnvConfig::load();

// Helper function
function env($key, $default = null) {
    return EnvConfig::get($key, $default);
}
?>
