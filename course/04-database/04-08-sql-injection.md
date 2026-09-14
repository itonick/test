# 4-8 SQLインジェクションを防ぐ

> 🎯 **このレッスンのゴール**
> - SQLインジェクションを実際に体験する
> - プレースホルダで確実に防げるようになる
> - プレースホルダで守れない箇所（テーブル名・ORDER BY・LIKE）を知る

所要 120分 / 難度 🔴
完成コード: [`code/04-08/`](../code/04-08/)

---

## ⚠️ 最初に読んでください

このレッスンでは、**攻撃を実際に体験します**。

- **必ず自分のローカル環境でのみ**行ってください
- 他人のサイトに対して試すのは、**犯罪です**（不正アクセス禁止法）
- 目的は「攻撃方法を学ぶこと」ではなく、「防ぎ方を体で理解すること」です

第2部でXSS、第3部でCSRFを扱いました。**SQLインジェクションは、
それらと並ぶ「Webの三大脆弱性」の1つ**です。データベースを扱う以上、
避けて通れません。

---

## 📖 SQLインジェクションとは

**ユーザーの入力が、SQL文の一部として実行されてしまう**攻撃です。

### 脆弱なコード

```php
<?php
// ❌ 絶対にやってはいけない
$name = $_GET["name"];
$sql = "SELECT * FROM posts WHERE name = '{$name}'";
$result = $pdo->query($sql);
```

**通常の入力**（`name=山田太郎`）なら、こうなります。

```sql
SELECT * FROM posts WHERE name = '山田太郎'
```

**しかし、攻撃者はこう入力します。**

```
name=' OR '1'='1
```

すると、組み立てられるSQLは：

```sql
SELECT * FROM posts WHERE name = '' OR '1'='1'
```

**`'1'='1'` は常に真なので、全件が返ります。** ユーザー名を知らなくても、全データが見えます。

### さらに危険な例

```
name='; DROP TABLE posts; --
```

```sql
SELECT * FROM posts WHERE name = ''; DROP TABLE posts; --'
```

**`posts` テーブルが丸ごと消えます。**（`--` 以降はコメントとして無視される）

> 💡 有名なジョーク「Little Bobby Tables」（xkcd）は、
> 息子の名前を `Robert'); DROP TABLE Students;--` にした親の話です。
> 学校のデータベースが消えます。**これがSQLインジェクションです。**

---

## 📖 何が奪われ、何が起きるのか

| 攻撃 | 結果 |
| --- | --- |
| データの窃取 | **全ユーザーの個人情報・パスワードハッシュが漏洩** |
| 認証の回避 | パスワードを知らなくてもログインできる |
| データの改ざん | 他人の投稿・注文・残高を書き換える |
| データの破壊 | テーブルを削除する |
| サーバーの乗っ取り | 設定次第で、OSコマンドを実行される |

### 認証回避の例

```php
// ❌ 脆弱なログイン
$sql = "SELECT * FROM users WHERE email = '{$email}' AND password = '{$password}'";
```

```
email=admin@example.com' --
```

```sql
SELECT * FROM users WHERE email = 'admin@example.com' -- ' AND password = '...'
```

**パスワードのチェックがコメントアウトされ、パスワードなしでadminとしてログインできます。**

> ⚠️ **SQLインジェクションは、情報漏洩事件の主要な原因の1つです。**
> 実在の大企業が、これで数百万件の個人情報を流出させています。

---

## ✍️ 手を動かす① ─ 実際に攻撃してみる

**自分のローカル環境で、脆弱なページを作って試します。**

`php-lesson/sqli-demo.php`（**確認後は必ず削除してください**）

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/config/database.php";

$pdo = db();
$keyword = $_GET["keyword"] ?? "";

// ❌❌❌ 絶対にやってはいけない書き方（学習用） ❌❌❌
$sql = "SELECT id, name, body FROM posts WHERE name = '{$keyword}'";

echo "<p>実行されたSQL: <code>" . htmlspecialchars($sql) . "</code></p>";

try {
    $result = $pdo->query($sql);
    $posts = $result->fetchAll();
    echo "<p>" . count($posts) . "件ヒット</p>";
    echo "<pre>";
    foreach ($posts as $post) {
        echo htmlspecialchars($post["name"] . ": " . $post["body"]) . "\n";
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<form method="get">
  <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" size="50">
  <button type="submit">検索</button>
</form>
```

**以下を順に入力してください。**

### 攻撃1：全件を取り出す

```
' OR '1'='1
```

**実行されるSQL**
```sql
SELECT id, name, body FROM posts WHERE name = '' OR '1'='1'
```

**全12件が表示されます。** 名前を1つも知らないのに。

### 攻撃2：常に真の条件

```
' OR 1=1 -- 
```

**（末尾に半角スペースが必要です）** これも全件が返ります。

### 攻撃3：他のテーブルの情報を取る（UNION攻撃）

```
' UNION SELECT id, email, password_hash FROM users -- 
```

**実行されるSQL**
```sql
SELECT id, name, body FROM posts WHERE name = ''
UNION SELECT id, email, password_hash FROM users -- '
```

**`users` テーブルのメールアドレスとパスワードハッシュが、投稿一覧に混ざって表示されます。**

> ⚠️ **これを自分の目で見てください。**
> 「パスワードハッシュが画面に出る」瞬間を体験すると、この脆弱性の恐ろしさが分かります。
> 読むだけでは、実感が湧きません。

### 攻撃4：テーブル構造を探る

```
' AND 1=2 UNION SELECT table_name, column_name, 1 FROM information_schema.columns WHERE table_schema = 'board_app' -- 
```

**データベースの全テーブル・全カラムの名前が見えます。** 攻撃者はここから狙いを定めます。

---

## ✍️ 手を動かす② ─ プレースホルダで防ぐ

**上の脆弱なコードを、プレースホルダで書き直します。**

```php
<?php
// ✅ 安全な書き方
$stmt = $pdo->prepare("SELECT id, name, body FROM posts WHERE name = :keyword");
$stmt->execute(["keyword" => $keyword]);
$posts = $stmt->fetchAll();
```

**同じ攻撃を試してください。**

```
' OR '1'='1
```

**結果：0件。** `' OR '1'='1` という**名前の投稿**を探すため、該当しません。

> 🆘 **ここで詰まったら**（プレースホルダにしたらエラーが出る）
> - **`Invalid parameter number` / `number of bound variables does not match`**：SQL中の `:keyword` の**数と、`execute([...])` で渡すキーの数・名前が一致していない**。名前付き（`:name`）と `?` を混在させていないかも確認
> - **`:keyword` を `'` で囲んでいる**：`WHERE name = ':keyword'` は文字列リテラルになり、プレースホルダとして働かない。**囲まない**（`WHERE name = :keyword`）
> - **値が入らない**：`execute` に渡す配列のキーは `":keyword"` でも `"keyword"` でも可だが、SQL側の名前と揃える
> - **直らなければ、AIにこう聞く**（SQLと execute の行を貼る）：
>   「プレースホルダを使ったらエラーになります。プレースホルダの数と渡す値の対応が合っているか、私のコードを見て教えてください」

### なぜ防げるのか

**プレースホルダは、「SQLの構造」と「値」を完全に分離します。**

```
【脆弱なコード】
  PHPが文字列を組み立てる
    "SELECT ... WHERE name = '" + 入力 + "'"
    → 入力がSQLの一部になる（構造が変わる）

【プレースホルダ】
  ① SQLの構造だけを先にデータベースへ送る
    "SELECT ... WHERE name = ?"
    → データベースが「これはSQLの骨組み」と解釈する
  ② 値を別々に送る
    "' OR '1'='1"
    → データベースは「これは name カラムと比較する値」として扱う
    → SQLとして解釈されない
```

**値が何であっても、それは「値」として扱われ、SQLの構造には影響しません。**
`'` も `OR` も `DROP TABLE` も、ただの文字列になります。

> 💡 **`ATTR_EMULATE_PREPARES => false`（4-7）にしていると、
> この分離が「PHP内」ではなく「データベース側」で行われます。**
> より確実です。

### エスケープ（`quote`）は使わない

```php
// △ 一応動くが、推奨されない
$safe = $pdo->quote($keyword);
$sql = "SELECT * FROM posts WHERE name = {$safe}";
```

**なぜ推奨されないか**

- エスケープ漏れのリスクがある（1か所でも忘れると穴になる）
- 文字コードによっては回避される攻撃がある
- そもそもプレースホルダのほうが簡単

> ⚠️ **「エスケープすれば安全」は古い考えです。**
> 現代のPHPでは、**プレースホルダ一択**です。`mysqli_real_escape_string` も使いません。

---

## ✍️ 手を動かす③ ─ プレースホルダで守れない箇所

**ここが、このレッスンで最も重要です。**

**プレースホルダは「値」だけを埋め込めます。** 以下は埋め込めません。

| 埋め込めるもの（値） | 埋め込めないもの（SQLの構造） |
| --- | --- |
| `WHERE name = ?` | テーブル名 |
| `WHERE age > ?` | カラム名 |
| `VALUES (?, ?)` | `ORDER BY` のカラム |
| `LIMIT ?`（EMULATE=false時） | `ASC` / `DESC` |
| — | `AND` / `OR` |

### ❌ 動かない例

```php
// テーブル名は埋め込めない
$stmt = $pdo->prepare("SELECT * FROM ?");
$stmt->execute(["posts"]);
// → 'posts' というテーブルを探す（クォート付き）ためエラー

// ORDER BY のカラムも埋め込めない
$stmt = $pdo->prepare("SELECT * FROM posts ORDER BY ?");
$stmt->execute(["created_at"]);
// → ORDER BY 'created_at' となり、文字列で並べようとする（意図と違う）
```

### 危険な例

```php
// ❌ 並び替えのカラムをユーザーが指定できる
$sort = $_GET["sort"];   // "created_at" のつもり
$sql = "SELECT * FROM posts ORDER BY {$sort}";
```

```
sort=(SELECT password_hash FROM users LIMIT 1)
```

**並び替えを悪用して、情報を抜き出せます**（ブラインドSQLインジェクション）。

### ✅ 対処：ホワイトリスト方式

**「許可する値のリスト」を作り、それ以外は拒否します。**

```php
<?php
// ---------- 並び替え ----------
$allowed_sorts = [
    "new"        => "created_at DESC",
    "old"        => "created_at ASC",
    "name"       => "name ASC",
];

$sort_key = $_GET["sort"] ?? "new";
// ★ リストにあるものだけを使い、無ければデフォルト
$order_by = $allowed_sorts[$sort_key] ?? $allowed_sorts["new"];

$sql = "SELECT * FROM posts ORDER BY {$order_by}";
```

**ユーザーが渡すのは `new` `old` `name` というキーだけ。**
実際のSQLは、あらかじめ用意した安全な文字列です。

```php
<?php
// ---------- テーブル名・カラム名 ----------
$allowed_columns = ["name", "created_at", "body"];

$column = $_GET["column"] ?? "created_at";
if (!in_array($column, $allowed_columns, true)) {
    $column = "created_at";   // 不正な値はデフォルトに
}

$sql = "SELECT * FROM posts ORDER BY {$column} DESC";
```

> ⚠️ **「値をチェックする」のではなく「許可リストと照合する」**のが鉄則です。
>
> ```php
> // ❌ ダメ：危険な文字を除去しようとする（抜け道がある）
> $column = str_replace([";", "'", " "], "", $_GET["column"]);
>
> // ✅ 正解：許可リストにあるものだけを使う
> $column = in_array($_GET["column"], $allowed, true) ? $_GET["column"] : "id";
> ```
>
> **「危険なものを弾く（ブラックリスト）」は、必ず抜け道があります。**
> **「安全なものだけ許す（ホワイトリスト）」なら、抜け道がありません。**

これは3-6でも扱った考え方です。**入力の検証は、常にホワイトリスト。**

---

## ✍️ 手を動かす④ ─ LIKE のワイルドカードをエスケープする

**プレースホルダを使っていても、LIKEには別の注意が必要です。**

```php
<?php
// プレースホルダを使っている（SQLインジェクションは防げている）
$stmt = $pdo->prepare("SELECT * FROM posts WHERE body LIKE :kw");
$stmt->execute(["kw" => "%" . $keyword . "%"]);
```

**しかし、ユーザーが `%` や `_` を入力すると、意図しない検索になります。**

```
keyword = %
→ LIKE '%%%' となり、全件マッチ
```

これは**SQLインジェクションではありません**が、
「1文字入力しただけで全件返る」という問題を起こします。DoS の一種にもなります。

### 対処：ワイルドカードをエスケープする

```php
<?php
/**
 * LIKE で使う文字列の特殊文字をエスケープする
 */
function escape_like(string $value): string
{
    // \ % _ をエスケープする（\ を最初に処理すること）
    return addcslashes($value, "\\%_");
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE body LIKE :kw");
$stmt->execute(["kw" => "%" . escape_like($keyword) . "%"]);
```

> ⚠️ **`\` を最初にエスケープする**必要があります。
> 順番を間違えると、エスケープ用の `\` 自体が二重にエスケープされます。
> `addcslashes($value, "\\%_")` は正しく処理してくれます。

> 💡 4-7 の `PostRepository::paginate()` で `addcslashes($keyword, "\\_%")` と
> 書いていたのは、このためです。

---

## ✍️ 手を動かす⑤ ─ IN 句のプレースホルダ

**配列を IN 句に渡すのは、初学者がよく詰まる箇所です。**

```php
<?php
// ❌ これは動かない（1つのプレースホルダに配列は渡せない）
$ids = [1, 3, 5];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id IN (?)");
$stmt->execute([$ids]);
```

### 対処：個数分のプレースホルダを動的に作る

```php
<?php
$ids = [1, 3, 5];

// ① 個数分の ? をカンマで繋ぐ： "?,?,?"
$placeholders = implode(",", array_fill(0, count($ids), "?"));

// ② SQL に埋め込む
$sql = "SELECT * FROM posts WHERE id IN ({$placeholders})";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);   // 配列をそのまま渡す
```

> ⚠️ **`$placeholders` を SQL に埋め込んでいますが、これは安全です。**
> 埋め込んでいるのは `?,?,?` という**プレースホルダの記号だけ**で、
> **ユーザーの値ではない**からです。値は `execute($ids)` で別途渡しています。
>
> **「個数」はユーザーが決められますが、「値」は必ずプレースホルダを通ります。**

### 空配列に注意

```php
<?php
$ids = [];

if ($ids === []) {
    // ★ IN () は構文エラーになる。空なら別処理にする
    $posts = [];
} else {
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id IN ({$placeholders})");
    $stmt->execute($ids);
    $posts = $stmt->fetchAll();
}
```

> ⚠️ **`WHERE id IN ()` は構文エラー**です。配列が空のときを必ず処理してください。

---

## ✍️ 手を動かす⑥ ─ 安全なクエリビルダーを作る

**動的に条件を組み立てるとき、安全に書く方法です。**

```php
<?php
declare(strict_types=1);

/**
 * 検索条件を安全に組み立てる
 */
function build_search(PDO $pdo, array $filters): array
{
    $conditions = [];
    $params     = [];

    // キーワード（LIKE）
    if (!empty($filters["keyword"])) {
        $conditions[] = "(name LIKE :kw OR body LIKE :kw)";
        $params["kw"] = "%" . addcslashes($filters["keyword"], "\\%_") . "%";
    }

    // 会員/ゲスト
    if (isset($filters["member_only"]) && $filters["member_only"]) {
        $conditions[] = "user_id IS NOT NULL";
    }

    // 期間
    if (!empty($filters["since"])) {
        $conditions[] = "created_at >= :since";
        $params["since"] = $filters["since"];
    }

    // ★ 並び替えはホワイトリスト
    $sorts = [
        "new"  => "created_at DESC",
        "old"  => "created_at ASC",
        "name" => "name ASC, created_at DESC",
    ];
    $order = $sorts[$filters["sort"] ?? "new"] ?? $sorts["new"];

    // 条件を組み立てる
    $where = $conditions === [] ? "" : "WHERE " . implode(" AND ", $conditions);
    $sql   = "SELECT * FROM posts {$where} ORDER BY {$order}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
```

**使う側**

```php
$posts = build_search($pdo, [
    "keyword"     => $_GET["q"] ?? "",
    "member_only" => isset($_GET["member"]),
    "since"       => $_GET["since"] ?? "",
    "sort"        => $_GET["sort"] ?? "new",
]);
```

**ポイント**

| 部分 | 安全性 |
| --- | --- |
| 条件（`name LIKE :kw`） | プレースホルダの記号だけ埋め込む → 安全 |
| 値（`$params["kw"]`） | `execute()` で別途渡す → 安全 |
| 並び替え（`$order`） | ホワイトリストから選ぶ → 安全 |
| LIKE のキーワード | `addcslashes` でエスケープ → 意図通り |

**ユーザーの入力が、SQLの構造として使われる箇所が1つもありません。**

---

## ✍️ 手を動かす⑦ ─ 多層防御

**プレースホルダだけに頼らず、複数の対策を重ねます。**

```
① プレースホルダを使う           ← 第一の防御（最重要）
② 入力を検証する（型・範囲・長さ） ← SQLに届く前に弾く
③ DBユーザーの権限を絞る          ← 万一のときの被害を限定
④ エラーメッセージを出さない       ← 攻撃の手がかりを与えない
```

### ② 入力の検証

```php
<?php
// 型を保証する
$id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false) {
    http_response_code(400);
    exit("不正なリクエストです");
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
$stmt->execute(["id" => $id]);
```

**IDが整数であることを保証すれば、そもそも攻撃文字列が入りません。**

### ③ DBユーザーの権限を絞る

```sql
-- ❌ アプリが root で接続している（何でもできる）

-- ✅ アプリ専用のユーザーを作り、必要な権限だけ与える
CREATE USER 'board_app'@'localhost' IDENTIFIED BY '強いパスワード';
GRANT SELECT, INSERT, UPDATE, DELETE ON board_app.* TO 'board_app'@'localhost';
-- DROP TABLE や、他のデータベースへのアクセスは許可しない
```

**万一SQLインジェクションが成立しても、`DROP TABLE` は権限エラーで失敗します。**

> 💡 **学習中は root で構いませんが、本番では必ず専用ユーザーを作ってください。**
> 「アプリが必要な操作」だけに権限を絞るのが、被害を最小化する鍵です。

### ④ エラーメッセージを出さない

```php
// ❌ エラーの詳細が、攻撃のヒントになる
catch (PDOException $e) {
    echo $e->getMessage();   // テーブル名・カラム名が見える
}

// ✅
catch (PDOException $e) {
    error_log($e->getMessage());   // ログには詳細
    exit("エラーが発生しました");   // 画面には一般的なメッセージ
}
```

**攻撃者は、エラーメッセージからテーブル構造を推測します。**
「そのカラムは存在しません」というエラーは、カラム名の当たりを教えることになります。

---

## ⚠️ つまずきポイントまとめ

| 誤り | 正しい対処 |
| --- | --- |
| 文字列連結でSQLを組み立てる | **プレースホルダを使う** |
| エスケープ関数で対処する | プレースホルダを使う |
| テーブル名・カラム名をプレースホルダに | **ホワイトリストで選ぶ** |
| `ORDER BY ?` | ホワイトリストで文字列を選ぶ |
| 危険な文字を除去する（ブラックリスト） | **許可リストと照合する（ホワイトリスト）** |
| LIKE でユーザー入力をそのまま使う | `addcslashes` でワイルドカードをエスケープ |
| `IN (?)` に配列を渡す | 個数分の `?` を動的に作る |
| `IN ()`（空配列） | 空のときを別処理にする |
| アプリが root で接続 | 専用ユーザーで権限を絞る |
| エラーの詳細を画面に出す | ログに記録し、一般的なメッセージ |

---

## 🤖 AIに聞いてみよう

### ① SQLインジェクションのセキュリティレビュー（新しい会話で）

```text
以下のPHPコードを、SQLインジェクションの観点でレビューしてください。
一般ユーザーからの入力を扱うコードです。

（コードを貼る）

次の観点で、問題があれば具体的に指摘してください。

1. 文字列連結でSQLを組み立てている箇所
2. プレースホルダを使うべきなのに使っていない箇所
3. テーブル名・カラム名・ORDER BY にユーザー入力を使っている箇所
   （プレースホルダで守れない箇所）
4. LIKE のワイルドカードエスケープの漏れ
5. ブラックリスト方式で対処しようとしている箇所
6. エラーメッセージからの情報漏洩

それぞれ、どんな入力で攻撃が成立するかも説明してください。
（実際に動く完全な攻撃コードは不要です。原理の説明で十分です）
修正後のコードは書かず、指摘だけをお願いします。
```

### ② 動的な検索の安全な組み立て方を学ぶ

```text
検索フォームで、ユーザーが以下を自由に指定できるようにしたいです。

- キーワード（本文の部分一致）
- カテゴリ（複数選択可）
- 並び替え（新着順 / 古い順 / 人気順）
- 表示件数（10 / 20 / 50）

これらを安全にSQLに反映する方法を教えてください。

1. プレースホルダで扱えるもの / 扱えないものの区別
2. 並び替え・件数のようにプレースホルダで扱えないものの安全な扱い方
3. 複数選択（IN句）の安全な組み立て方
4. 条件が「指定されたときだけ」追加される動的なWHEREの書き方

コードは考え方を示す最小限で構いません。
「なぜ安全なのか」を各所で説明してください。
```

---

## 🔧 やってみよう（演習）

**すべて自分のローカル環境でのみ行ってください。**

### 演習1（必須）─ 攻撃を体験する

- [ ] `sqli-demo.php`（脆弱版）を作る
- [ ] `' OR '1'='1` で全件が返ることを確認する
- [ ] `' UNION SELECT id, email, password_hash FROM users -- ` で、
      **パスワードハッシュが表示されることを確認する**
- [ ] プレースホルダ版に書き換える
- [ ] 同じ攻撃が効かなくなることを確認する（0件になる）
- [ ] **確認したら、`sqli-demo.php` を削除する**

> ⚠️ **脆弱なファイルを公開サーバーに置かないでください。** 学習後は必ず削除します。

### 演習2（必須）─ 問題を見つける

以下のコードの問題点を、それぞれ指摘して直してください。

```php
<?php
// ①
$id = $_GET["id"];
$pdo->query("SELECT * FROM posts WHERE id = {$id}");

// ②
$name = $_POST["name"];
$stmt = $pdo->prepare("SELECT * FROM users WHERE name = '{$name}'");
$stmt->execute();

// ③
$sort = $_GET["sort"];
$pdo->query("SELECT * FROM posts ORDER BY {$sort}");

// ④
$keyword = $_GET["q"];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE body LIKE :kw");
$stmt->execute(["kw" => "%{$keyword}%"]);

// ⑤
$ids = $_POST["ids"];  // [1, 2, 3]
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id IN (?)");
$stmt->execute([implode(",", $ids)]);

// ⑥
$table = $_GET["table"];
$pdo->query("SELECT * FROM {$table}");
```

<details>
<summary>答えを見る</summary>

① **値を直接埋め込んでいる（整数でも危険）**
```php
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
$stmt->execute(["id" => (int) $_GET["id"]]);
```

② **`prepare` を使っているが、値を文字列に埋め込んでいる（プレースホルダになっていない）**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE name = :name");
$stmt->execute(["name" => $_POST["name"]]);
```

③ **`ORDER BY` にユーザー入力を直接使っている**
```php
$sorts = ["new" => "created_at DESC", "old" => "created_at ASC"];
$order = $sorts[$_GET["sort"] ?? "new"] ?? $sorts["new"];
$pdo->query("SELECT * FROM posts ORDER BY {$order}");
```

④ **LIKE のワイルドカードをエスケープしていない**（SQLインジェクションは防げているが、`%` で全件マッチ）
```php
$stmt->execute(["kw" => "%" . addcslashes($keyword, "\\%_") . "%"]);
```

⑤ **`IN (?)` に配列を渡せない。カンマ連結は「'1,2,3'」という1つの文字列になり、意図通り動かない**
```php
$ids = array_map("intval", $_POST["ids"]);   // 型も保証する
if ($ids === []) { $posts = []; }
else {
    $ph = implode(",", array_fill(0, count($ids), "?"));
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id IN ({$ph})");
    $stmt->execute($ids);
}
```

⑥ **テーブル名にユーザー入力を使っている（最も危険）**
```php
$allowed = ["posts", "comments", "tags"];
$table = in_array($_GET["table"] ?? "", $allowed, true) ? $_GET["table"] : "posts";
$pdo->query("SELECT * FROM {$table}");
```

</details>

### 演習3（必須）

4-7 で作った `PostRepository` を見直してください。

- [ ] すべてのクエリでプレースホルダが使われているか確認する
- [ ] 検索の LIKE でワイルドカードがエスケープされているか確認する
- [ ] ORDER BY にユーザー入力が使われていないか確認する
- [ ] エラーメッセージが画面に出ていないか確認する
- [ ] AIにセキュリティレビューさせる（「手を動かす」のプロンプト①）

### 演習4（挑戦）─ 安全な検索機能を作る

「手を動かす⑥」の `build_search()` を参考に、掲示板の検索機能を実装してください。

- [ ] キーワード検索（LIKE、ワイルドカードエスケープ済み）
- [ ] タグでの絞り込み（複数選択、IN句を安全に組み立てる）
- [ ] 会員/ゲストの絞り込み
- [ ] 並び替え（ホワイトリスト）
- [ ] ページ送り（LIMIT / OFFSET を整数で保証）
- [ ] 上記を組み合わせても、SQLインジェクションが成立しないことを確認する

> 💡 **完成したら、演習1の攻撃文字列をすべての入力欄に試してください。**
> 1つも成立しないことを確認できたら、このレッスンは完璧です。

---

## ✅ 章末チェック

- [ ] SQLインジェクションがどういう攻撃か説明できる
- [ ] `' OR '1'='1` で全件が返る仕組みを説明できる
- [ ] **実際に攻撃を試し、パスワードハッシュが漏れるのを見た**
- [ ] プレースホルダが「構造と値を分離する」ことを説明できる
- [ ] エスケープではなくプレースホルダを使う理由を言える
- [ ] プレースホルダで守れない箇所（テーブル名・ORDER BY）を言える
- [ ] ホワイトリスト方式でそれらを守れる
- [ ] ブラックリストが危険な理由を説明できる
- [ ] LIKE のワイルドカードをエスケープする理由を言える
- [ ] `IN` 句に配列を安全に渡せる
- [ ] 多層防御の4つの層を言える
- [ ] DBユーザーの権限を絞る意味を説明できる

---

**前 → [4-7 PHPからDBを操作する（PDO）](04-07-pdo.md)　｜　次 → [4-9 AIにSQLとテーブル設計を相談する](04-09-ai-sql.md)**
