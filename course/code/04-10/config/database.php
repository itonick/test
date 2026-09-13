<?php
declare(strict_types=1);

/**
 * データベース接続
 *
 * 本番（MySQL）と、テスト用（SQLite）を切り替えられるようにしてある。
 * .env の DB_DRIVER で選ぶ。既定は mysql。
 *
 * ★ 本編は MySQL を前提としています。SQLite 対応は、
 *   MySQL が用意できない環境でも動かせるようにするための補助です。
 */

require_once __DIR__ . "/../lib/env.php";

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $env    = load_env(__DIR__ . "/../.env");
    $driver = $env["DB_DRIVER"] ?? "mysql";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    try {
        if ($driver === "sqlite") {
            // テスト用：ファイルベースの SQLite
            $path = $env["DB_PATH"] ?? (__DIR__ . "/../database/board.sqlite");
            $pdo  = new PDO("sqlite:{$path}", null, null, $options);
            $pdo->exec("PRAGMA foreign_keys = ON");   // SQLite は既定で外部キーが無効
        } else {
            // 本番：MySQL
            $host   = $env["DB_HOST"] ?? "localhost";
            $port   = $env["DB_PORT"] ?? "3306";
            $dbname = $env["DB_NAME"] ?? "board_app";
            $user   = $env["DB_USER"] ?? "root";
            $pass   = $env["DB_PASS"] ?? "";

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, $options);
        }
    } catch (PDOException $e) {
        // ★ 接続情報を含むエラーメッセージを画面に出さない
        error_log("DB接続に失敗しました: " . $e->getMessage());
        http_response_code(503);
        exit("ただいまシステムが混み合っています。しばらくしてからお試しください。");
    }

    return $pdo;
}
