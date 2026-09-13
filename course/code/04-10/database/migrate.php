<?php
declare(strict_types=1);

/**
 * テーブルを作成する（マイグレーション）
 *
 *   php database/migrate.php
 *
 * MySQL / SQLite の両対応。config/database.php の設定に従う。
 * ※ 本番運用では Laravel のマイグレーション（第5部）のような
 *   仕組みを使うが、ここでは仕組みの理解のため手書きする。
 */

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../lib/env.php";

$pdo = db();
$env = load_env(__DIR__ . "/../.env");
$driver = $env["DB_DRIVER"] ?? "mysql";

if ($driver === "sqlite") {
    // SQLite 版のスキーマ
    $pdo->exec("PRAGMA foreign_keys = ON");

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT NOT NULL,
            email         TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at    TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
        )
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS posts (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
            name       TEXT NOT NULL,
            body       TEXT NOT NULL,
            edit_token TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            updated_at TEXT NULL
        )
    SQL);
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id)");

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS comments (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id    INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
            name       TEXT NOT NULL,
            body       TEXT NOT NULL,
            edit_token TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
        )
    SQL);
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_post_id ON comments(post_id)");

    echo "SQLite: テーブルを作成しました\n";
} else {
    // MySQL 版は schema.mysql.sql を流し込む
    $sql = file_get_contents(__DIR__ . "/schema.mysql.sql");
    // USE 文などが含まれるため、そのまま実行する
    $pdo->exec($sql);
    echo "MySQL: テーブルを作成しました\n";
}
