<?php
/**
 * config/env.php
 * Simple .env file loader — membaca key=value dari file .env
 * dan memasukkan ke $_ENV + process environment.
 *
 * Dipanggil sekali di public/index.php (entry point).
 */

function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Lewati baris komentar dan baris kosong
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Lepas tanda kutip di awal/akhir value (misal APP_URL="http://...")
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Hanya set jika belum ada (agar tidak override env yang sudah di-set sistem)
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

loadEnv(APP_ROOT . '/.env');
