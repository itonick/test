<?php
declare(strict_types=1);

/**
 * N+1問題を実測する
 *
 * 「投稿一覧に投稿者名を表示する」を3つの方法で実装し、
 * クエリ回数と実行時間を比較する。
 *
 *   1. N+1版   … ループの中で1件ずつ取得
 *   2. JOIN版  … 1回のクエリで取得
 *   3. IN版    … 2回のクエリで取得（まとめて取ってPHPで振り分け）
 *
 * 使い方: php n-plus-one.php [投稿件数]
 *
 * ※ 環境を問わず動かせるよう SQLite を使っていますが、
 *    考え方と結果の傾向は MySQL でも同じです。
 */

// ==========================================================
// クエリ回数を数えるラッパー
// ==========================================================

final class CountingPdo
{
    public int $count = 0;

    public function __construct(private readonly PDO $pdo) {}

    public function prepare(string $sql): PDOStatement
    {
        $this->count++;
        return $this->pdo->prepare($sql);
    }

    public function query(string $sql): PDOStatement
    {
        $this->count++;
        return $this->pdo->query($sql);
    }

    public function exec(string $sql): int|false
    {
        return $this->pdo->exec($sql);   // セットアップ用。カウントしない
    }

    public function reset(): void
    {
        $this->count = 0;
    }
}

// ==========================================================
// 準備
// ==========================================================

$post_count = max(1, (int) ($argv[1] ?? 100));
$user_count = 20;

$raw = new PDO("sqlite::memory:", null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$db = new CountingPdo($raw);

$db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT NOT NULL)");
$db->exec("CREATE TABLE posts (
    id      INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    body    TEXT NOT NULL
)");
$db->exec("CREATE INDEX idx_posts_user_id ON posts(user_id)");

$raw->beginTransaction();

$stmt = $raw->prepare("INSERT INTO users (name) VALUES (?)");
for ($i = 1; $i <= $user_count; $i++) {
    $stmt->execute(["ユーザー{$i}"]);
}

$stmt = $raw->prepare("INSERT INTO posts (user_id, body) VALUES (?, ?)");
for ($i = 1; $i <= $post_count; $i++) {
    $stmt->execute([random_int(1, $user_count), "投稿本文 {$i}"]);
}

$raw->commit();

// ==========================================================
// ① N+1版：ループの中で1件ずつ取得する
// ==========================================================

function fetch_n_plus_one(CountingPdo $db, int $limit): array
{
    $stmt = $db->query("SELECT id, user_id, body FROM posts ORDER BY id LIMIT {$limit}");
    $posts = $stmt->fetchAll();

    foreach ($posts as &$post) {
        // ★ ここが問題：ループのたびにクエリが飛ぶ
        $s = $db->prepare("SELECT name FROM users WHERE id = ?");
        $s->execute([$post["user_id"]]);
        $post["user_name"] = $s->fetchColumn();
    }
    unset($post);

    return $posts;
}

// ==========================================================
// ② JOIN版：1回のクエリで取得する
// ==========================================================

function fetch_with_join(CountingPdo $db, int $limit): array
{
    $stmt = $db->query("
        SELECT p.id, p.user_id, p.body, u.name AS user_name
        FROM posts p
        LEFT JOIN users u ON u.id = p.user_id
        ORDER BY p.id
        LIMIT {$limit}
    ");
    return $stmt->fetchAll();
}

// ==========================================================
// ③ IN版：2回のクエリで取得し、PHPで振り分ける
// ==========================================================

function fetch_with_in(CountingPdo $db, int $limit): array
{
    $posts = $db->query("SELECT id, user_id, body FROM posts ORDER BY id LIMIT {$limit}")
                ->fetchAll();

    if ($posts === []) {
        return [];
    }

    // 必要なユーザーIDだけを集める（重複は除く）
    $ids = array_values(array_unique(array_column($posts, "user_id")));

    // プレースホルダを個数分作る： ?, ?, ?
    $placeholders = implode(",", array_fill(0, count($ids), "?"));

    $stmt = $db->prepare("SELECT id, name FROM users WHERE id IN ({$placeholders})");
    $stmt->execute($ids);

    // id => name の対応表を作る
    $names = [];
    foreach ($stmt->fetchAll() as $row) {
        $names[(int) $row["id"]] = $row["name"];
    }

    foreach ($posts as &$post) {
        $post["user_name"] = $names[(int) $post["user_id"]] ?? null;
    }
    unset($post);

    return $posts;
}

// ==========================================================
// 計測
// ==========================================================

/**
 * @param callable(CountingPdo, int): array $fn
 * @return array{name: string, queries: int, ms: float, rows: int}
 */
function measure(string $name, callable $fn, CountingPdo $db, int $limit): array
{
    $db->reset();
    $start = hrtime(true);

    $rows = $fn($db, $limit);

    $ms = (hrtime(true) - $start) / 1_000_000;

    return [
        "name"    => $name,
        "queries" => $db->count,
        "ms"      => $ms,
        "rows"    => count($rows),
    ];
}

$results = [];
foreach ([10, 50, $post_count] as $limit) {
    $limit = min($limit, $post_count);
    $results[$limit] = [
        measure("N+1版",  "fetch_n_plus_one", $db, $limit),
        measure("JOIN版", "fetch_with_join",  $db, $limit),
        measure("IN版",   "fetch_with_in",    $db, $limit),
    ];
}

// ==========================================================
// 表示
// ==========================================================

function w(string $s): int
{
    $n = 0;
    foreach (preg_split("//u", $s, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $c) {
        $n += strlen($c) > 1 ? 2 : 1;
    }
    return $n;
}

function p(string $s, int $to): string
{
    return $s . str_repeat(" ", max(0, $to - w($s)));
}

echo "投稿 {$post_count} 件 / ユーザー {$user_count} 人 のデータで計測\n\n";

foreach ($results as $limit => $rows) {
    echo "--- {$limit} 件を表示する場合 ---\n";
    echo p("方法", 10) . p("クエリ回数", 14) . p("実行時間", 12) . "取得行数\n";
    echo str_repeat("-", 48) . "\n";

    foreach ($rows as $r) {
        echo p($r["name"], 10)
           . p((string) $r["queries"] . " 回", 14)
           . p(number_format($r["ms"], 2) . " ms", 12)
           . $r["rows"] . "\n";
    }
    echo "\n";
}

echo "※ N+1版のクエリ回数は「1 + 表示件数」になる。\n";
echo "※ 実際のWebアプリではDBが別サーバーにあるため、\n";
echo "   1回の通信往復に数ミリ秒かかる。差はこの計測よりずっと大きくなる。\n";
