<?php
declare(strict_types=1);

/**
 * SQL練習用の簡易環境（SQLite版）
 *
 * MySQL が動かない環境でも SQL の練習ができるようにするためのものです。
 * schema.sql / seed.sql と同じ構造・同じデータを、SQLite 上に作ります。
 *
 * 使い方:
 *   php sqlite-playground.php                    ← 動作確認（サンプルクエリを実行）
 *   php sqlite-playground.php "SELECT * FROM posts LIMIT 3"
 *
 * ⚠️ 本編は MySQL を前提としています。これは「SQLの構文を試す」ための補助です。
 *    MySQL 固有の機能（AUTO_INCREMENT の書き方、ENGINE、REPEAT など）は
 *    SQLite では書き方が異なります。下の create_schema() を見比べてください。
 */

// ==========================================================
// 接続
// ==========================================================

/**
 * メモリ上に SQLite データベースを作って返す（実行するたびにまっさらになる）
 */
function connect(): PDO
{
    $pdo = new PDO("sqlite::memory:", null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // SQLite は既定で外部キー制約が無効なので有効にする
    $pdo->exec("PRAGMA foreign_keys = ON");
    return $pdo;
}

// ==========================================================
// スキーマ
// ==========================================================

/**
 * MySQL の schema.sql と同じ構造を SQLite で作る
 *
 * MySQL との書き方の違い:
 *   INT AUTO_INCREMENT PRIMARY KEY  →  INTEGER PRIMARY KEY AUTOINCREMENT
 *   DATETIME                        →  TEXT（SQLiteに日付型はない）
 *   BOOLEAN                         →  INTEGER（0 / 1）
 *   ENGINE=InnoDB CHARSET=...        →  不要
 */
function create_schema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT    NOT NULL,
            email         TEXT    NOT NULL UNIQUE,
            password_hash TEXT    NOT NULL,
            avatar        TEXT        NULL,
            bio           TEXT        NULL,
            is_admin      INTEGER NOT NULL DEFAULT 0,
            created_at    TEXT    NOT NULL,
            updated_at    TEXT        NULL
        )
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE posts (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER     NULL REFERENCES users(id) ON DELETE SET NULL,
            name       TEXT    NOT NULL,
            body       TEXT    NOT NULL,
            edit_token TEXT    NOT NULL,
            created_at TEXT    NOT NULL,
            updated_at TEXT        NULL
        )
    SQL);
    $pdo->exec("CREATE INDEX idx_posts_created_at ON posts(created_at)");
    $pdo->exec("CREATE INDEX idx_posts_user_id ON posts(user_id)");

    $pdo->exec(<<<SQL
        CREATE TABLE comments (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id    INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
            name       TEXT    NOT NULL,
            body       TEXT    NOT NULL,
            edit_token TEXT    NOT NULL,
            created_at TEXT    NOT NULL
        )
    SQL);
    $pdo->exec("CREATE INDEX idx_comments_post_id ON comments(post_id)");

    $pdo->exec(<<<SQL
        CREATE TABLE tags (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        )
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE post_tag (
            post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
            tag_id  INTEGER NOT NULL REFERENCES tags(id)  ON DELETE CASCADE,
            PRIMARY KEY (post_id, tag_id)
        )
    SQL);
}

// ==========================================================
// テストデータ（seed.sql と同じ内容）
// ==========================================================

function seed(PDO $pdo): void
{
    $hash = '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18SBtoFTLUbGTLUBhFCyMK';

    $users = [
        ["山田太郎", "taro@example.com",   1, "2026-03-01 10:00:00"],
        ["佐藤花子", "hanako@example.com", 0, "2026-03-05 14:20:00"],
        ["鈴木一郎", "ichiro@example.com", 0, "2026-03-20 09:00:00"],
        ["田中美咲", "misaki@example.com", 0, "2026-04-02 18:30:00"],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, is_admin, created_at)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($users as [$name, $email, $admin, $created]) {
        $stmt->execute([$name, $email, $hash, $admin, $created]);
    }

    $posts = [
        [1,    "山田太郎",   "はじめまして。よろしくお願いします。",       "2026-04-10 09:15:00"],
        [null, "名無しさん", "テスト投稿です。",                           "2026-04-10 14:30:00"],
        [2,    "佐藤花子",   "このサイト、使いやすいですね。",             "2026-04-11 08:00:00"],
        [3,    "鈴木一郎",   "質問があります。\nどこに書けばいいですか？", "2026-04-11 19:45:00"],
        [null, "名無しさん", "↑ここでいいと思います",                      "2026-04-12 07:20:00"],
        [4,    "田中美咲",   "週末のイベント、参加します！",               "2026-04-12 12:10:00"],
        [1,    "山田太郎",   "私も参加します。",                           "2026-04-12 21:00:00"],
        [null, "高橋健",     "よろしくお願いします 😊",                    "2026-04-13 10:05:00"],
        [null, "名無しさん", "テスト",                                     "2026-04-13 15:40:00"],
        [2,    "佐藤花子",   "写真を投稿できる機能が欲しいです。",         "2026-04-14 11:30:00"],
        [null, "伊藤大輔",   "スマホからでも見やすくて助かります。",       "2026-04-14 16:55:00"],
        [null, "名無しさん", "こんにちは",                                 "2026-04-15 08:30:00"],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO posts (user_id, name, body, edit_token, created_at)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($posts as $i => [$user_id, $name, $body, $created]) {
        $stmt->execute([$user_id, $name, $body, str_repeat(chr(97 + $i), 32), $created]);
    }

    $comments = [
        [1,  "佐藤花子",   "こちらこそよろしくお願いします。", "2026-04-10 10:00:00"],
        [1,  "名無しさん", "ようこそ",                         "2026-04-10 11:30:00"],
        [1,  "鈴木一郎",   "はじめまして！",                   "2026-04-10 13:00:00"],
        [4,  "山田太郎",   "ここで大丈夫ですよ。",             "2026-04-11 20:00:00"],
        [4,  "田中美咲",   "私も最初は迷いました。",           "2026-04-11 22:15:00"],
        [6,  "山田太郎",   "楽しみですね。",                   "2026-04-12 13:00:00"],
        [10, "名無しさん", "同じことを思っていました。",       "2026-04-14 12:00:00"],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO comments (post_id, name, body, edit_token, created_at)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($comments as $i => [$post_id, $name, $body, $created]) {
        $stmt->execute([$post_id, $name, $body, str_repeat(chr(109 + $i), 32), $created]);
    }

    foreach (["質問", "雑談", "お知らせ", "要望"] as $tag) {
        $pdo->prepare("INSERT INTO tags (name) VALUES (?)")->execute([$tag]);
    }

    $pairs = [[1, 2], [4, 1], [6, 3], [6, 2], [10, 4], [10, 1]];
    $stmt = $pdo->prepare("INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)");
    foreach ($pairs as [$p, $t]) {
        $stmt->execute([$p, $t]);
    }
}

// ==========================================================
// 結果を表として表示する
// ==========================================================

/** 表示幅（全角を2として数える） */
function width(string $text): int
{
    $w = 0;
    foreach (preg_split("//u", $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $c) {
        $w += strlen($c) > 1 ? 2 : 1;
    }
    return $w;
}

function pad(string $text, int $to): string
{
    return $text . str_repeat(" ", max(0, $to - width($text)));
}

function render_table(array $rows): string
{
    if (count($rows) === 0) {
        return "(0 rows)\n";
    }

    $headers = array_keys($rows[0]);
    $widths  = [];
    foreach ($headers as $h) {
        $widths[$h] = width($h);
    }
    foreach ($rows as $row) {
        foreach ($row as $k => $v) {
            $text = str_replace("\n", "\\n", (string) ($v ?? "NULL"));
            $widths[$k] = max($widths[$k], width($text));
        }
    }

    $line = "+" . implode("+", array_map(fn($h) => str_repeat("-", $widths[$h] + 2), $headers)) . "+";

    $out = [$line];
    $out[] = "| " . implode(" | ", array_map(fn($h) => pad($h, $widths[$h]), $headers)) . " |";
    $out[] = $line;

    foreach ($rows as $row) {
        $cells = [];
        foreach ($headers as $h) {
            $text = str_replace("\n", "\\n", (string) ($row[$h] ?? "NULL"));
            $cells[] = pad($text, $widths[$h]);
        }
        $out[] = "| " . implode(" | ", $cells) . " |";
    }
    $out[] = $line;
    $out[] = sprintf("(%d row%s)", count($rows), count($rows) === 1 ? "" : "s");

    return implode("\n", $out) . "\n";
}

// ==========================================================
// 実行
// ==========================================================

$pdo = connect();
create_schema($pdo);
seed($pdo);

$sql = $argv[1] ?? null;

if ($sql !== null) {
    // 引数で渡されたSQLを実行する
    echo "SQL> {$sql}\n\n";
    echo render_table($pdo->query($sql)->fetchAll());
    exit;
}

// 引数がなければ、教材で扱う代表的なクエリを順に実行する
$samples = [
    "件数を数える" =>
        "SELECT COUNT(*) AS total FROM posts",

    "新しい順に3件" =>
        "SELECT id, name, created_at FROM posts ORDER BY created_at DESC LIMIT 3",

    "「テスト」を含む投稿" =>
        "SELECT id, name, body FROM posts WHERE body LIKE '%テスト%'",

    "投稿者名ごとの投稿数（多い順）" =>
        "SELECT name, COUNT(*) AS cnt FROM posts
         GROUP BY name ORDER BY cnt DESC, name ASC",

    "会員の投稿だけ（INNER JOIN）" =>
        "SELECT p.id, u.name AS user_name, p.body
         FROM posts p
         INNER JOIN users u ON p.user_id = u.id
         ORDER BY p.id LIMIT 5",

    "ゲスト投稿も含める（LEFT JOIN）" =>
        "SELECT p.id, p.name, u.email
         FROM posts p
         LEFT JOIN users u ON p.user_id = u.id
         ORDER BY p.id LIMIT 5",

    "返信が2件以上ある投稿（HAVING）" =>
        "SELECT p.id, p.body, COUNT(c.id) AS comment_count
         FROM posts p
         INNER JOIN comments c ON c.post_id = p.id
         GROUP BY p.id, p.body
         HAVING COUNT(c.id) >= 2
         ORDER BY comment_count DESC",

    "投稿とタグ（多対多）" =>
        "SELECT p.id, p.body, t.name AS tag
         FROM posts p
         INNER JOIN post_tag pt ON pt.post_id = p.id
         INNER JOIN tags t      ON t.id = pt.tag_id
         ORDER BY p.id, t.id",

    "返信が1件もない投稿（LEFT JOIN + IS NULL）" =>
        "SELECT p.id, p.name, p.body
         FROM posts p
         LEFT JOIN comments c ON c.post_id = p.id
         WHERE c.id IS NULL
         ORDER BY p.id LIMIT 5",
];

foreach ($samples as $title => $query) {
    echo "=== {$title} ===\n";
    echo trim(preg_replace('/\s+/', " ", $query)) . "\n\n";
    echo render_table($pdo->query($query)->fetchAll());
    echo "\n";
}
