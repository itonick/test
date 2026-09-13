<?php
declare(strict_types=1);

/**
 * .env ファイルを読み込んで連想配列で返す
 */
function load_env(string $path): array
{
    if (!is_file($path)) {
        return [];   // .env がなければ空（config 側で既定値を使う）
    }

    $env = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "" || str_starts_with($line, "#") || !str_contains($line, "=")) {
            continue;
        }
        [$key, $value] = explode("=", $line, 2);
        $env[trim($key)] = trim($value, " \t\"'");
    }

    return $env;
}
