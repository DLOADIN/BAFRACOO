<?php
/**
 * Simple .env file loader
 * 
 * Reads key=value pairs from a .env file and sets them as environment
 * variables via putenv() so that getenv() picks them up everywhere.
 * 
 * This file is safe to require_once from multiple places — 
 * it only loads the .env once per request.
 */

if (!defined('ENV_LOADED')) {
    define('ENV_LOADED', true);

    // Walk up from the current file's directory to find .env at project root
    $env_path = __DIR__ . '/.env';

    // If called from a subdirectory (e.g. USERS/), also check one level up
    if (!file_exists($env_path)) {
        $env_path = dirname(__DIR__) . '/.env';
    }

    if (file_exists($env_path)) {
        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            // Only process lines with an = sign
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key   = trim($key);
                $value = trim($value);
                // Remove surrounding quotes if present
                if (strlen($value) >= 2 && 
                    (($value[0] === '"' && $value[strlen($value)-1] === '"') ||
                     ($value[0] === "'" && $value[strlen($value)-1] === "'"))) {
                    $value = substr($value, 1, -1);
                }
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}
?>
