<?php
namespace App\Helpers;

/**
 * Simple file-based logger for the AFD Reader application.
 *
 * Logs messages with timestamps to a file under storage/logs/app.log. The
 * log directory is created on-demand. This logger is intentionally
 * lightweight and does not rotate logs; for production use you might
 * integrate a more robust logging library.
 */
class Logger
{
    /**
     * Append a message to the log file with a timestamp.
     *
     * @param string $message Message to log
     */
    public static function log(string $message): void
    {
        // Determine the logs directory relative to this file (../../storage/logs)
        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            // Attempt to create the directory with 0775 permissions
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/app.log';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND);
    }
}