# 4-10 【総合演習】掲示板をDB化する

> 🎯 **このレッスンのゴール**
> - 第3部で作ったファイル保存の掲示板を、**データベースに載せ替える**
> - 第4部で学んだ設計・SQL・PDO・インジェクション対策・JOIN・インデックスを1つのアプリに統合する
> - **リポジトリパターン**で「保存先を差し替えても、画面側はほぼ変えない」を体験する

所要 180分 / 難度 🔴
完成コード: [`code/04-10/`](../code/04-10/) ← **動作検証済み（SQLiteで通し確認済み）**

---

## 📸 完成イメージ

見た目は第3部（3-12）の掲示板とほぼ同じです。**変わるのは「裏側」だけ**。

```
┌────────────────────────────────────────────┐
│  ひとこと掲示板              124件の投稿    │
├────────────────────────────────────────────┤
│  ┌──────────────────────────────────────┐  │
│  │ 投稿する                              │  │
│  │ お名前（省略可） [____________]        │  │
│  │ 本文 [必須]      [____________]        │  │
│  │            [ 投稿する ]                │  │
│  └──────────────────────────────────────┘  │
│  [投稿を検索          ] [検索]             │
│  ┌──────────────────────────────────────┐  │
│  │ 山田太郎  会員   返信2件・3分前        │  │
│  │ はじめまして。                  [削除] │  │
│  └──────────────────────────────────────┘  │
│         前へ  1  [2]  3 … 13  次へ         │
└────────────────────────────────────────────┘
```

**第3部版との違い（＝このレッスンで作るもの）**

| | 第3部（3-12） | 第4部（今回） |
| --- | --- | --- |
| 保存先 | JSONファイル + 排他制御 | **データベース（MySQL / SQLite）** |
| 一覧取得 | 全件読んでPHPで絞る | **SQLの `WHERE` / `LIMIT` / `ORDER BY`** |
| 検索 | `array_filter` | **`LIKE`（ワイルドカードはエスケープ）** |
| 件数・ページ | `count()` + 配列スライス | **`COUNT(*)` + `LIMIT/OFFSET`** |
| 投稿者名・返信数 | 持てない | **`JOIN` と相関サブクエリで付与** |
| インジェクション対策 | （SQLなし） | **プレースホルダ** |

---

## 📖 なぜ「リポジトリパターン」なのか

第3部で、投稿の保存・取得は `PostRepository` というクラスに閉じ込めました。
今回やることは、実は**この1クラスの中身を JSON操作から SQL操作に差し替えるだけ**です。

```
        画面（index.php）
              │  $repository->paginate(...) / save(...) / find(...) / delete(...)
              ▼
   ┌────────────────────────┐
   │   PostRepository        │  ← このクラスの「入口（メソッド名・引数・戻り値）」は同じ
   │                         │
   │  第3部: JSONを読み書き    │  ← 中身だけ
   │  第4部: PDOでSQL実行  ★  │     差し替える
   └────────────────────────┘
```

> 🧠 **これがリポジトリパターンの狙い**です。「データの出し入れ」を1か所に集約しておくと、
> 保存先（ファイル→DB、いずれDBの種類の変更）が変わっても、**画面側のコードは守られます**。
> 第5部の Eloquent も、この「モデルにデータ操作を集約する」考え方の延長です。

---

## STEP 0 ─ 準備（MySQL / SQLite どちらでも）

このレッスンは **MySQL を本編**とします。ただし、MySQL を用意できない環境でも学べるよう、
完成コードは **SQLite でも動く**ようにしてあります（`.env` の `DB_DRIVER` で切り替え）。

```
code/04-10/
├─ public/index.php        ← 画面（第3部とほぼ同じ）
├─ config/database.php     ← DB接続（MySQL / SQLite 切替）
├─ app/Models/Post.php     ← 投稿1件（第3部の Post を DB向けに）
├─ app/Repositories/PostRepository.php  ← ★ 今回の主役
├─ database/
│   ├─ schema.mysql.sql    ← MySQL 用スキーマ
│   └─ migrate.php         ← テーブル作成スクリプト
├─ lib/                    ← functions / session / csrf / env
└─ .env.example            ← 接続情報のテンプレート
```

`.env` を用意します（`.env.example` をコピー）。

```env
# MySQL を使う場合（本編）
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=board_app
DB_USER=root
DB_PASS=        # XAMPP は空、MAMP は root（4-2の表）
```

> 🔒 `.env` は **Git に上げません**（`.gitignore` に入れる）。DBのパスワードが書かれているためです。

---

## STEP 1 ─ テーブルを設計する（4-5 の実践）

第4部で学んだ設計原則で、3つのテーブルを作ります。`database/schema.mysql.sql`：

```sql
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(50)  NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NULL DEFAULT NULL,          -- 匿名投稿を許すので NULL 可
    name       VARCHAR(30)  NOT NULL,
    body       TEXT         NOT NULL,
    edit_token CHAR(32)     NOT NULL,                   -- 自分の投稿判定に使う
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_posts_created_at (created_at),            -- 新着順の並び替え用
    INDEX idx_posts_user_id (user_id),
    CONSTRAINT fk_posts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE            -- 退会しても投稿は残す
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT          NOT NULL,
    name       VARCHAR(30)  NOT NULL,
    body       TEXT         NOT NULL,
    edit_token CHAR(32)     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_comments_post_id (post_id),
    CONSTRAINT fk_comments_post
        FOREIGN KEY (post_id) REFERENCES posts(id)
        ON DELETE CASCADE ON UPDATE CASCADE             -- 投稿を消すと返信も消える
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

🔍 **ここで効いている学び（第4部）**

- `utf8mb4`（4-2）… 絵文字を含む日本語を正しく保存
- `INDEX`（4-5）… 「新着順に並べる」「投稿者で絞る」で効く列に付ける
- **外部キーの `ON DELETE` の使い分け**（4-5）
  - `posts.user_id` → `SET NULL`：ユーザーが退会しても、投稿自体は「名無し」として残す
  - `comments.post_id` → `CASCADE`：投稿を削除したら、その返信も一緒に消す

反映します（テーブル作成）。

```bash
# MySQL 版
php database/migrate.php
# → MySQL: テーブルを作成しました
```

> 💡 手書きの `migrate.php` は「仕組みの理解」のためのものです。第5部では、これを
> Laravel のマイグレーション（5-6）が肩代わりします。今その原型に触れておくと、5-6が驚くほど楽になります。

> 🆘 **ここで詰まったら**（`migrate.php` がエラーになる）
> - **`Access denied` / 接続できない**：`.env` のユーザー・パスワードが 4-2 の表と合っているか（XAMPPは空、MAMPは `root`・ポート8889）
> - **`Unknown database 'board_app'`**：MySQL版スキーマは冒頭で `CREATE DATABASE` するので通常は不要ですが、権限が無い環境では phpMyAdmin で先に `board_app` を作る
> - **外部キーで作成に失敗**：`users` → `posts` → `comments` の**順序**で作る（親が先）。スキーマはこの順に並んでいます
> - **MySQLが無い**：`.env` を `DB_DRIVER=sqlite` にして `php database/migrate.php`。SQLite版のテーブルが作られます
> - **直らなければ、AIにこう聞く**（エラー全文とスキーマを貼る／パスワードは伏せる）：
>   「PHPのマイグレーションでこのエラーが出ます。原因と確認手順を教えてください」

---

## STEP 2 ─ 接続を1か所にまとめる（4-7 の実践）

接続は `config/database.php` の `db()` に集約します。**設定は `.env` から読み、エラーの詳細は画面に出しません。**

```php
function db(): PDO
{
    static $pdo = null;               // 1リクエスト中は接続を使い回す
    if ($pdo !== null) return $pdo;

    $env = load_env(__DIR__ . "/../.env");

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // 例外で気づく
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,                  // 本物のプレースホルダ（4-8）
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    try {
        $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], $options);
    } catch (PDOException $e) {
        error_log("DB接続に失敗しました: " . $e->getMessage());  // ログには残す
        http_response_code(503);
        exit("ただいまシステムが混み合っています。しばらくしてからお試しください。"); // 画面には出さない
    }
    return $pdo;
}
```

🔍 **ここで効いている学び**：`ERRMODE_EXCEPTION` と `EMULATE_PREPARES => false`（4-7）、
**接続情報を含むエラーを画面に出さない**（4-7・4-8の多層防御）。第3部で学んだ「エラーで攻撃者に手がかりを与えない」の実践です。

---

## STEP 3 ─ リポジトリの中身を SQL に差し替える（本レッスンの主役）

`app/Repositories/PostRepository.php`。**メソッド名・引数・戻り値は第3部と同じ**にするのがポイントです。

### 保存（INSERT）

```php
public function save(Post $post): int
{
    $stmt = $this->pdo->prepare(
        "INSERT INTO posts (user_id, name, body, edit_token)
         VALUES (:user_id, :name, :body, :edit_token)"       // ★ プレースホルダ（4-8）
    );
    $stmt->execute([
        "user_id"    => $post->userId,
        "name"       => $post->name,
        "body"       => $post->body,
        "edit_token" => $post->editToken,
    ]);

    return (int) $this->pdo->lastInsertId();                 // 採番されたIDを返す
}
```

### 1件取得（find）と削除（delete）

```php
public function find(int $id): ?Post
{
    $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE id = :id");
    $stmt->execute(["id" => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : Post::fromRow($row);
}

public function delete(int $id): bool
{
    // comments は ON DELETE CASCADE なので、返信も一緒に消える（STEP1の設計）
    $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = :id");
    $stmt->execute(["id" => $id]);
    return $stmt->rowCount() > 0;
}
```

### 一覧（ページ送り・検索・投稿者名・返信数）

ここが第4部の総まとめです。**1つのメソッドに、検索・件数・ページ・JOIN・サブクエリ**が詰まっています。

```php
public function paginate(int $page, int $perPage = 10, string $keyword = ""): array
{
    $where  = "";
    $params = [];

    if ($keyword !== "") {
        $where = "WHERE p.name LIKE :kw OR p.body LIKE :kw";
        // ★ LIKE のワイルドカード(% _)をエスケープ（4-8）。しないと "50%" 検索などが壊れる
        $params["kw"] = "%" . addcslashes($keyword, "\\%_") . "%";
    }

    // ① 総件数（ページ数の計算に必要）
    $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM posts p {$where}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $perPage));
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;

    // ② 該当ページの投稿（投稿者名は LEFT JOIN、返信数は相関サブクエリ）
    $sql = "
        SELECT
            p.id, p.user_id, p.name, p.body, p.edit_token, p.created_at,
            u.name AS user_name,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
        FROM posts p
        LEFT JOIN users u ON u.id = p.user_id          -- 匿名投稿も残す（4-6 LEFT JOIN）
        {$where}
        ORDER BY p.created_at DESC, p.id DESC           -- 新着順（idx_posts_created_at が効く）
        LIMIT :limit OFFSET :offset                     -- ページ送り（4-3）
    ";

    $stmt = $this->pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    // ★ LIMIT / OFFSET は整数として束縛する（EMULATE_PREPARES=false のとき必須・4-7）
    $stmt->bindValue("limit",  $perPage, PDO::PARAM_INT);
    $stmt->bindValue("offset", $offset,  PDO::PARAM_INT);
    $stmt->execute();

    $posts = array_map(
        static fn(array $row) => Post::fromRow($row),
        $stmt->fetchAll()
    );

    return compact("posts", "total", "page", "totalPages");
}
```

🔍 **ここで効いている学び（第4部の集大成）**

- **`LIKE` のワイルドカードをエスケープ**（4-8）… `addcslashes(..., "\\%_")`
- **`LEFT JOIN`**（4-6）… 投稿者が匿名（`user_id` が NULL）でも一覧から消えない
- **相関サブクエリで返信数**（4-6/4-7）… 「1対多」を素直に JOIN すると件数が“掛け算”で狂う。
  返信数だけ欲しいので、行ごとに `COUNT` を取るサブクエリにして **N+1 も掛け算も回避**
- **`COUNT(*)` + `LIMIT/OFFSET`**（4-3）… 全件をPHPに読み込まず、DBに数えさせ・切り出させる
- **`LIMIT` の整数束縛**（4-7）… `bindValue(..., PDO::PARAM_INT)`。ここは初学者が必ず1回ハマる所

> 🆘 **ここで詰まったら**（一覧が出ない／`LIMIT` 付近でSQLエラー）
> - **`You have an error in your SQL syntax ... near '?'` / `LIMIT ':limit'`**：`LIMIT` にプレースホルダを使うときは、`EMULATE_PREPARES => false`（STEP2）にしたうえで **`bindValue("limit", $n, PDO::PARAM_INT)`** と**整数で束縛**する。文字列のままだと `LIMIT '10'` になり落ちる
> - **`Invalid parameter number`**：`:kw` を1回のSQLで2回使っているが、`execute` に渡すのは1個でOK（名前付きは使い回せる）。`?` と名前付きを混在させていないかも確認
> - **検索がヒットしない**：全角・半角、前後の空白（`trim_ja` 済みか）を確認。`%` を付け忘れて完全一致になっていないか
> - **直らなければ、AIにこう聞く**（`paginate` のSQLと `bindValue` 周辺を貼る）：
>   「PDOで LIMIT/OFFSET 付きの検索SQLがエラーになります。原因と正しい束縛方法を教えてください」

---

## STEP 4 ─ 画面（index.php）は「ほぼ変えない」ことを確認する

第3部の `index.php` と見比べてください。**リポジトリの呼び出し方は同じ**です。

```php
$repository = new PostRepository(db());          // ← 渡すのが JSONパス → PDO に変わっただけ

// 一覧
$result = $repository->paginate($page, PER_PAGE, $keyword);

// 投稿
$post   = Post::create($_POST["name"] ?? "", $_POST["body"] ?? "");
$new_id = $repository->save($post);
$_SESSION["my_posts"][$new_id] = $post->editToken;   // 自分の投稿として記録

// 削除（認可つき）
$post = $repository->find($id);
$my_token = $_SESSION["my_posts"][$id] ?? null;
if (!$post->canDeleteWith($my_token)) {              // ★ 自分の投稿だけ削除（3-11の認可）
    http_response_code(403);
    // ...
}
$repository->delete($id);
```

🔍 **守られたもの**：CSRF対策（`require_post_with_csrf` / `csrf_field`）、XSSエスケープ（`e()`）、
PRGパターン（`redirect`）、`edit_token` による認可——**第3部で作った安全策はそのまま生きています**。
DB化しても、これらを捨ててはいけません。

> 🧠 **「保存先を変えても、セキュリティは据え置き」**。第4部でSQLインジェクション対策（プレースホルダ）が
> **加わった**だけで、XSS・CSRF・認可が不要になったわけではありません。防御は積み上げるものです。

---

## STEP 5 ─ 動かして確認する

```bash
# public/ をドキュメントルートにして開く（PHPビルトインサーバの例）
php -S localhost:8000 -t public
# → http://localhost:8000/ を開く
```

以下を実際に操作して確認します。

- [ ] 投稿できる → 一覧の先頭に出る（新着順）
- [ ] 名前を空にすると「名無しさん」になる
- [ ] 本文を空で送るとエラーメッセージが出て、入力が消えない
- [ ] キーワード検索が効く／`検索を解除`で戻る
- [ ] 11件以上投稿するとページ送りが出る／2ページ目でも検索条件が保たれる
- [ ] 自分の投稿にだけ「削除」ボタンが出る
- [ ] 削除できる

---

## 🔧 やってみよう（演習）── 第4部を自分の手で確かめる

**AIに頼らず**、まず自力でやってください。

### 演習1（必須）─ 攻撃してみる（4-8の検証）

検索欄に次を入れて送信してください。

```
' OR '1'='1
```

- **全件が出てしまったら危険信号**。だが、このアプリは `LIKE :kw` の**プレースホルダ**なので、
  「`' OR '1'='1` を含む投稿」を探すだけで、SQLは壊れません。**0件（または該当のみ）が正解**です。
- なぜ壊れないのかを、4-8の「構造と値の分離」で自分の言葉で説明できますか？

### 演習2（必須）─ 発行SQLとインデックスを見る（4-3/4-5）

`paginate` の一覧SQLを、`EXPLAIN` を頭に付けて phpMyAdmin（または `mysql`）で実行してください。

- 新着順（`ORDER BY created_at DESC`）で **`idx_posts_created_at` が使われているか**
- 検索（`LIKE '%...%'`）が**インデックスを使えていない**こと（前後ワイルドカードの宿命・4-8）を確認

### 演習3（挑戦）─ 返信（comments）機能を足す

`comments` テーブルはすでに設計済みです。次を実装してみましょう。

- 投稿に返信を追加する（`CommentRepository::save`）
- 一覧の返信数（`comment_count`）はすでに出ています。詳細表示で返信一覧を出す

<details>
<summary>ヒント：返信の取得SQL</summary>

```sql
SELECT id, name, body, created_at
FROM comments
WHERE post_id = :post_id
ORDER BY created_at ASC;
```

投稿削除時、`comments` は `ON DELETE CASCADE`（STEP1）なので**自動で消えます**。
自分でDELETEを書く必要はありません。

</details>

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `LIMIT ':limit'` でSQLエラー | LIMITを文字列で束縛した | `bindValue("limit", $n, PDO::PARAM_INT)` |
| 検索で `50%` などが変にヒット | LIKEのワイルドカード未エスケープ | `addcslashes($kw, "\\%_")` |
| 匿名投稿が一覧から消える | `INNER JOIN` を使った | `LEFT JOIN`（4-6） |
| 返信数を出すと件数が狂う | 1対多を素直にJOINして掛け算 | 相関サブクエリ（または `GROUP BY`） |
| 接続エラーの詳細が画面に出る | 例外をそのまま表示 | `error_log` に記録し、画面は汎用文言（4-7） |
| 投稿は消えたが返信が残る | CASCADEを設定していない | `comments.post_id` に `ON DELETE CASCADE` |
| 他人の投稿が消せてしまう | 認可チェック漏れ | `edit_token` を照合（3-11） |

---

## 🤖 AIに聞いてみよう

### 使えるプロンプト例

```
PHPとPDOで掲示板を作っています。次のリポジトリの paginate メソッドを
レビューしてください。

（PostRepository の paginate を貼る）

観点：
1. SQLインジェクションの穴はないか（プレースホルダの使い方）
2. LIKE のワイルドカードは正しくエスケープされているか
3. N+1問題や、JOINによる件数の掛け算は起きていないか
4. インデックスが効く設計になっているか
すぐ直すのではなく、問題点と理由を指摘してください。
```

### 🔍 AIの答えを疑うチェックリスト

- [ ] 「文字列連結でSQLを組め」と言っていないか（**絶対NG**。プレースホルダを守る）
- [ ] `LIMIT` を整数束縛する話に触れているか
- [ ] 返信数の取得で、掛け算にならない方法（サブクエリ/`GROUP BY`）を示しているか
- [ ] 接続エラーを画面に出す実装を勧めていないか
- [ ] 指摘されたSQLを、自分で `EXPLAIN` して**実際に**速さを確認したか

---

## ✅ 章末チェック（第4部の総まとめ）

1. 第3部の掲示板をDB化するとき、**画面側のコードがほとんど変わらなかった**のはなぜか。
2. `LEFT JOIN` と `INNER JOIN` のどちらを使うべきか、この掲示板の「投稿者名」を例に説明せよ。
3. 返信数を出すのに、`comments` を素直に JOIN してはいけない理由は？
4. `' OR '1'='1` を検索しても安全なのはなぜか。第4部の言葉で説明せよ。
5. DB化しても捨ててはいけない、第3部からの安全策を3つ挙げよ。

> 🎉 **第4部、完了です。** あなたは「データを設計し、安全に・速く出し入れする」力を身につけました。
> 次の第5部では、ここで手書きした接続・リポジトリ・マイグレーションを、
> **Laravel が肩代わりする**とどうなるかを見ていきます。手書きした経験が、そのまま理解の土台になります。

---

**前 → [4-9 AIにSQLとテーブル設計を相談する](04-09-ai-sql.md)　｜　次 → [5-1 フレームワークとは / Laravelを入れる](../05-laravel/05-01-what-is-laravel.md)**
