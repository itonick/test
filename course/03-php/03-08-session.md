# 3-8 セッションとクッキー

> ◎ **このレッスンのゴール**
> - セッションの仕組みを説明できる
> - ログイン機能を実装できる
> - パスワードを正しく保存できる
> - セッション固定攻撃を防げる

所要 150分 / 難度 🟡
完成コード: [`code/03-08/`](../code/03-08/)

---

## 📖 HTTP は「毎回、初対面」

HTTP には**状態がありません**（ステートレス）。

```
リクエスト1: ログインする       → サーバー「OK」
リクエスト2: マイページを見る   → サーバー「あなた誰？」
```

サーバーは、前のリクエストのことを覚えていません。
**これを解決するのが、Cookie とセッションです。**

---

## 📖 Cookie とセッションの関係

```
【1回目：ログイン】
ブラウザ                                     サーバー
   │  POST /login (email, password)             │
   │───────────────────────────────────────────>│
   │                                            │ ① 認証OK
   │                                            │ ② セッションIDを発行（abc123）
   │                                            │ ③ サーバー内に保存
   │                                            │    abc123 → { user_id: 5 }
   │  Set-Cookie: PHPSESSID=abc123              │
   │<───────────────────────────────────────────│
   │ ④ ブラウザがCookieを保存                    │

【2回目以降】
   │  GET /mypage                               │
   │  Cookie: PHPSESSID=abc123                  │
   │───────────────────────────────────────────>│
   │                                            │ ⑤ abc123 で照合 → user_id: 5
   │  「山田さん、こんにちは」                   │
   │<───────────────────────────────────────────│
```

| | Cookie | セッション |
| --- | --- | --- |
| 保存場所 | **ブラウザ** | **サーバー** |
| 中身 | 小さな文字列（ID など） | 任意のデータ |
| ユーザーに見える？ | **見える・書き換えられる** | 見えない |
| 容量 | 約4KB | 制限なし |
| 用途 | セッションIDの受け渡し、設定の記憶 | ログイン状態、カート |

> ⚠️ **Cookie の中身は、ユーザーが自由に書き換えられます。**
> 開発者ツール → Application → Cookies で編集できます。
>
> **`user_id=5` のような値を Cookie に直接入れてはいけません。** `user_id=1` に書き換えられます。
> **Cookie にはセッションIDだけ**を入れ、実データはサーバー側（セッション）に置きます。

---

## ✍️ 手を動かす① ─ セッションの基本

```php
<?php
declare(strict_types=1);

session_start();          // ← 必ず最初に。出力より前に

// 書き込み
$_SESSION["user_id"] = 5;
$_SESSION["user_name"] = "山田太郎";
$_SESSION["cart"] = ["drip" => 2, "latte" => 1];

// 読み込み
$user_id = $_SESSION["user_id"] ?? null;

// 存在確認
if (isset($_SESSION["user_id"])) { }

// 削除
unset($_SESSION["cart"]);

// 全消去（ログアウト時）
$_SESSION = [];
session_destroy();
```

> ⚠️ **`session_start()` は、出力より前に呼ぶ必要があります。**
> `<?php` の前に空白があるだけで、`headers already sent` エラーになります（3-5参照）。

> 🆘 **ここで詰まったら**（`headers already sent` が出た）
> - **まず確認**：① `<?php` の**前**に空白・改行・文字がないか（1文字目が `<`）② 別ファイルを `require` している場合、そのファイルの末尾に `?>` の後の改行がないか（末尾の `?>` は書かないのが定石）③ ファイルが**BOMなしUTF-8**で保存されているか
> - **エラー文の読み方**：`output started at ファイル名:行番号` の部分が「余計な出力をした場所」。そこを見に行く
> - **直らなければ、AIにこう聞く**（エラー全文と、該当ファイルの先頭・末尾を貼る）：
>   「PHPで `headers already sent` が出ます。エラーは○○（全文）です。どこで余計な出力が起きているか、確認手順を教えてください」

### セッションの設定を安全にする

`lib/session.php`（📋 コピペして使ってください）

```php
<?php
declare(strict_types=1);

/**
 * 安全な設定でセッションを開始する
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        "lifetime" => 0,          // ブラウザを閉じるまで
        "path"     => "/",
        "domain"   => "",
        "secure"   => !empty($_SERVER["HTTPS"]),   // HTTPS のときのみ送信
        "httponly" => true,       // ★ JavaScript から読めなくする（XSS対策）
        "samesite" => "Lax",      // ★ CSRF 対策
    ]);

    session_start();
}
```

| 設定 | 効果 |
| --- | --- |
| **`httponly => true`** | **JavaScript から Cookie を読めなくする**。XSS でセッションIDを盗まれない |
| **`samesite => "Lax"`** | 他サイトからの POST で Cookie を送らない。**CSRF 対策** |
| `secure => true` | HTTPS でのみ送信（本番では必須） |

> 💡 **`httponly` は、第2部（2-11）で「認証トークンは httpOnly Cookie に」と書いた、まさにその設定です。**
> PHP のセッションは、デフォルトでこれが使えます。**必ず設定してください。**

---

## ✍️ 手を動かす② ─ パスワードの正しい扱い

**これは、絶対に間違えてはいけない部分です。**

### やってはいけないこと

```php
<?php
// ❌ 平文で保存 → 漏洩したら全員のパスワードが判明
$password = $_POST["password"];

// ❌ MD5 / SHA1 → 高速すぎて総当たりで破られる。レインボーテーブルもある
$hash = md5($password);
$hash = sha1($password);

// ❌ 自作の暗号化 → 必ず穴がある
```

### 正しい方法

```php
<?php
// 保存するとき
$hash = password_hash($password, PASSWORD_DEFAULT);
// → "$2y$10$abcdefg..." のような60文字程度の文字列

// 照合するとき
if (password_verify($input_password, $hash)) {
    // 一致
}
```

**これだけです。** `password_hash` が、ソルト（ランダムな値）の付加も、
適切な計算回数も、すべて自動でやってくれます。

| 関数 | 役割 |
| --- | --- |
| `password_hash($pw, PASSWORD_DEFAULT)` | ハッシュ化（**毎回結果が違う**。ソルトが含まれる） |
| `password_verify($pw, $hash)` | 照合（true / false） |
| `password_needs_rehash($hash, PASSWORD_DEFAULT)` | 古いアルゴリズムか判定 |

> ⚠️ **ハッシュ化された値は「元に戻せません」。**
> だから「パスワードを忘れた」→「再設定リンクを送る」という設計になっています。
> **「パスワードを教えてくれるサービス」は、平文で保存している証拠**です。

> ⚠️ **ハッシュを保存するカラムは、最低 255 文字にしてください。**
> アルゴリズムが変わると長さが変わります。`VARCHAR(60)` にすると将来詰みます。

### パスワードの検証（ユーザー登録時）

```php
<?php
function v_password(?string $value): ?string
{
    $v = $value ?? "";

    if ($v === "") return "パスワードを入力してください";
    if (mb_strlen($v) < 8) return "パスワードは8文字以上にしてください";
    if (mb_strlen($v) > 72) return "パスワードは72文字以内にしてください";
    if (!preg_match('/[a-zA-Z]/', $v)) return "パスワードには英字を含めてください";
    if (!preg_match('/[0-9]/', $v)) return "パスワードには数字を含めてください";

    $common = ["password", "12345678", "qwerty123", "abc12345"];
    if (in_array(strtolower($v), $common, true)) {
        return "推測されやすいパスワードは使用できません";
    }

    return null;
}
```

> ⚠️ **72文字の上限は、bcrypt の仕様上の制限です。** これを超えた部分は無視されます。

> 💡 **「記号を必須にする」ルールは、現在は推奨されていません。**
> NIST（米国標準技術研究所）のガイドラインでは、**長さを重視し、複雑さの強制は避ける**とされています。
> ユーザーが `Password1!` のような弱いパスワードを作るだけだからです。

---

## ✍️ 手を動かす③ ─ ログイン機能を実装する

### ファイル構成

```
htdocs/php-lesson/auth/
├─ lib/
│   ├─ functions.php
│   ├─ session.php
│   ├─ csrf.php
│   └─ auth.php
├─ data/
│   └─ users.php        ← 本来はDB。第4部で置き換えます
├─ login.php
├─ logout.php
├─ mypage.php
└─ register.php
```

### `data/users.php`（仮のユーザーデータ）

```php
<?php
declare(strict_types=1);

// 本来はデータベースに保存します（第4部）
// パスワードはすべて "password123"
return [
    [
        "id"       => 1,
        "email"    => "taro@example.com",
        "name"     => "山田太郎",
        "password" => '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfgqwAgWvJTZS8pQ5nkNQ9nDVXKvJDPO',
    ],
];
```

> ⚠️ **上のハッシュは例です。** 自分で生成してください。
>
> ```php
> <?php echo password_hash("password123", PASSWORD_DEFAULT);
> ```
>
> 実行するたびに違う値が出ますが、**どれでも `password_verify` で照合できます**（ソルトが含まれるため）。

### `lib/auth.php`

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/session.php";

/**
 * ユーザーをメールアドレスで探す
 */
function find_user_by_email(string $email): ?array
{
    $users = require __DIR__ . "/../data/users.php";
    foreach ($users as $user) {
        if ($user["email"] === $email) {
            return $user;
        }
    }
    return null;
}

/**
 * ログイン処理
 */
function login(array $user): void
{
    // ★ セッション固定攻撃対策：IDを作り直す
    session_regenerate_id(true);

    $_SESSION["user_id"]    = $user["id"];
    $_SESSION["user_name"]  = $user["name"];
    $_SESSION["login_at"]   = time();
}

/**
 * ログアウト処理
 */
function logout(): void
{
    $_SESSION = [];

    // Cookie も削除する
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), "", time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * ログイン中か
 */
function is_logged_in(): bool
{
    return isset($_SESSION["user_id"]);
}

/**
 * ログイン中のユーザー情報
 */
function current_user(): ?array
{
    if (!is_logged_in()) return null;
    return [
        "id"   => $_SESSION["user_id"],
        "name" => $_SESSION["user_name"] ?? "",
    ];
}

/**
 * ログインしていなければログイン画面へ飛ばす
 */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION["redirect_to"] = $_SERVER["REQUEST_URI"] ?? "/";
        header("Location: login.php", true, 303);
        exit;
    }
}
```

### ⚠️ `session_regenerate_id(true)` が必須な理由

**セッション固定攻撃（Session Fixation）**

```
① 攻撃者が、あるセッションID（例: abc123）でサイトにアクセスする
② そのIDを含むURLやCookieを、被害者に踏ませる
③ 被害者が、そのIDのままログインする
④ サーバー内で abc123 → user_id: 5 が紐づく
⑤ 攻撃者は abc123 を知っているので、被害者としてアクセスできる
```

**対策**：ログイン成功の瞬間に、**セッションIDを新しく作り直す**。
これで、攻撃者が知っている古いIDは無効になります。

> ⚠️ **`session_regenerate_id(true)` の `true` は「古いセッションを削除する」意味です。** 必ず付けてください。

### `login.php`

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/session.php";
start_secure_session();
require_once __DIR__ . "/lib/functions.php";
require_once __DIR__ . "/lib/csrf.php";
require_once __DIR__ . "/lib/auth.php";

// すでにログイン済みならマイページへ
if (is_logged_in()) {
    header("Location: mypage.php", true, 303);
    exit;
}

$error = null;
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["_csrf_token"] ?? null)) {
        http_response_code(419);
        exit("セッションの有効期限が切れました。もう一度お試しください。");
    }

    $email    = trim_ja($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // 簡易的な総当たり対策
    $key = "login_attempts";
    $_SESSION[$key] = ($_SESSION[$key] ?? 0);

    if ($_SESSION[$key] >= 5) {
        $error = "ログインの試行回数が上限に達しました。しばらく時間をおいてお試しください。";
    } elseif ($email === "" || $password === "") {
        $error = "メールアドレスとパスワードを入力してください";
    } else {
        $user = find_user_by_email($email);

        if ($user !== null && password_verify($password, $user["password"])) {
            unset($_SESSION[$key]);
            login($user);

            $to = $_SESSION["redirect_to"] ?? "mypage.php";
            unset($_SESSION["redirect_to"]);
            header("Location: " . $to, true, 303);
            exit;
        }

        $_SESSION[$key]++;
        // ★ 「メールが違う」「パスワードが違う」を区別しない
        $error = "メールアドレスまたはパスワードが正しくありません";
    }
}

$page_title = "ログイン";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="container narrow">
  <h1>ログイン</h1>

  <?php if ($error !== null): ?>
    <p class="alert alert-error" role="alert"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="post" class="form">
    <?= csrf_field() ?>

    <div class="field">
      <label for="email">メールアドレス</label>
      <input type="email" id="email" name="email"
             value="<?= e($email) ?>" autocomplete="email" required>
    </div>

    <div class="field">
      <label for="password">パスワード</label>
      <input type="password" id="password" name="password"
             autocomplete="current-password" required>
    </div>

    <button type="submit" class="btn">ログイン</button>
  </form>
</main>
</body>
</html>
```

### ⚠️ エラーメッセージを区別しない理由

```php
// ❌ 「メールアドレスが登録されていません」
//    → そのメールアドレスが未登録だと攻撃者にわかる
//    → 逆に「パスワードが違います」なら、登録済みだとわかる（アカウント列挙）

// ✅ 「メールアドレスまたはパスワードが正しromしくありません」
```

> ⚠️ 上のメッセージに、わざと壊れた文字（`romし`）が混ざっています。
> **コピペせず、自分で打つか、必ず画面で確認してください。** 3-7 の教訓です。

### `logout.php`

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/session.php";
start_secure_session();
require_once __DIR__ . "/lib/csrf.php";
require_once __DIR__ . "/lib/auth.php";

// ★ ログアウトも POST で行う（GET だと CSRF で勝手にログアウトさせられる）
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_verify($_POST["_csrf_token"] ?? null)) {
    http_response_code(405);
    exit("不正なリクエストです");
}

logout();
header("Location: login.php", true, 303);
exit;
```

**ログアウトボタンはフォームにします。**

```php
<form method="post" action="logout.php" class="logout-form">
  <?= csrf_field() ?>
  <button type="submit" class="link-btn">ログアウト</button>
</form>
```

> 💡 **`<a href="logout.php">ログアウト</a>` にしてはいけません。**
> 攻撃者が `<img src="https://example.com/logout.php">` を仕込むと、
> ユーザーが勝手にログアウトさせられます（軽微ですが、DoS の一種）。

### `mypage.php`

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/session.php";
start_secure_session();
require_once __DIR__ . "/lib/functions.php";
require_once __DIR__ . "/lib/csrf.php";
require_once __DIR__ . "/lib/auth.php";

require_login();          // ★ ログインしていなければ弾く

$user = current_user();
$page_title = "マイページ";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title><?= e($page_title) ?></title>
</head>
<body>
  <h1><?= e($user["name"]) ?>さん、こんにちは</h1>
  <p>ログイン日時: <?= date("Y年n月j日 H:i", $_SESSION["login_at"]) ?></p>

  <form method="post" action="logout.php">
    <?= csrf_field() ?>
    <button type="submit">ログアウト</button>
  </form>
</body>
</html>
```

---

## ✍️ 手を動かす④ ─ Cookie を直接使う

セッション以外にも Cookie を使う場面があります。

```php
<?php
// 設定（出力より前に）
setcookie("theme", "dark", [
    "expires"  => time() + 60 * 60 * 24 * 365,   // 1年
    "path"     => "/",
    "secure"   => !empty($_SERVER["HTTPS"]),
    "httponly" => false,   // JavaScript から読む必要があるなら false
    "samesite" => "Lax",
]);

// 読み取り
$theme = $_COOKIE["theme"] ?? "light";

// 削除（過去の日時を設定する）
setcookie("theme", "", ["expires" => time() - 3600, "path" => "/"]);
```

> ⚠️ **`setcookie()` で設定した Cookie は、次のリクエストから読めます。**
> 同じリクエスト内で `$_COOKIE["theme"]` を読んでも、古い値のままです。

### Cookie に入れてよいもの / いけないもの

| ✅ 入れてよい | ❌ 入れてはいけない |
| --- | --- |
| テーマ設定（dark / light） | ユーザーID |
| 言語設定 | 権限（admin など） |
| 「次回から表示しない」フラグ | 金額 |
| 表示件数の設定 | パスワード |

> ⚠️ **Cookie の値は、ユーザーが自由に書き換えられます。**
> `is_admin=0` を `is_admin=1` に変えられたら終わりです。
> **判断に使う値は、必ずサーバー側（セッション）に置いてください。**

---

## ✍️ 手を動かす⑤ ─ セッションの有効期限

```php
<?php
$timeout = 60 * 30;   // 30分

if (isset($_SESSION["last_activity"])) {
    if (time() - $_SESSION["last_activity"] > $timeout) {
        logout();
        header("Location: login.php?reason=timeout", true, 303);
        exit;
    }
}
$_SESSION["last_activity"] = time();
```

> 💡 **銀行など、機密性の高いサイトでは10〜15分**、
> 一般的なサービスでは**30分〜数時間**が目安です。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `headers already sent` | `session_start()` の前に出力がある | 1行目から `<?php`、末尾の `?>` を書かない |
| セッションが保持されない | `session_start()` を書き忘れたページがある | **全ページの先頭で呼ぶ** |
| ログインしてもすぐ切れる | Cookie が保存されていない | ブラウザの設定、`path` の指定を確認 |
| ログアウトしても戻れてしまう | `session_destroy()` だけでは Cookie が残る | Cookie も削除する |
| 別のブラウザではログインできている | セッションはブラウザごと | 正常な動作 |
| パスワードが照合できない | ハッシュ化せずに保存した / カラムが短い | `password_hash` を使い、255文字にする |
| `password_hash` の結果が毎回違う | ソルトが含まれるため | **正常**。`password_verify` で照合する |

---

## 🤖 AIに聞いてみよう

### ① 認証まわりのセキュリティレビュー

```text
以下は、私が実装した PHP のログイン機能です。

（login.php / auth.php を貼る）

セキュリティの観点で、厳しくレビューしてください。

1. セッション固定攻撃への対策
2. CSRF 対策
3. パスワードの扱い
4. アカウント列挙（ユーザーの存在が推測できてしまう）
5. 総当たり攻撃への対策
6. Cookie の属性設定
7. ログアウト処理の完全性
8. その他、実装が漏れている一般的な対策

各指摘に優先度を付けてください。修正コードは書かないでください。
```

### ② セッションの仕組みを図で理解する

```text
PHP のセッションの仕組みを、初学者向けに説明してください。

1. session_start() を呼ぶと、内部で何が起きるのか
2. セッションIDはどこに保存され、どうやってやり取りされるのか
3. サーバー側では、セッションデータはどこに保存されるのか
4. 複数のサーバーがある場合（ロードバランサ）に何が問題になるのか
5. session_regenerate_id() は何を作り直しているのか

図（テキストアート）を使って、時系列で説明してください。
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）

ログイン機能を実装してください。

- [ ] `password_hash("password123", PASSWORD_DEFAULT)` を実行し、結果を `data/users.php` に入れる
- [ ] ログインできることを確認する
- [ ] 間違ったパスワードで弾かれることを確認する
- [ ] ログインしていない状態で `mypage.php` にアクセスすると、`login.php` に飛ばされる
- [ ] ログアウトできる
- [ ] ログアウト後、ブラウザの「戻る」で `mypage.php` に戻れないことを確認する
- [ ] 開発者ツール → Application → Cookies で、`PHPSESSID` に **HttpOnly のチェック**が入っていることを確認する

### 演習2（必須）─ 攻撃を試す

**自分のローカル環境でのみ**、以下を試してください。

- [ ] Cookie の `PHPSESSID` の値を、開発者ツールで書き換える → ログアウト状態になる
- [ ] Console で `document.cookie` を実行 → **`PHPSESSID` が出てこない**ことを確認（HttpOnly の効果）
- [ ] `session_regenerate_id(true)` をコメントアウトし、ログイン前後で `PHPSESSID` が**変わらない**ことを確認
- [ ] 元に戻して、ログイン後にIDが**変わる**ことを確認

> 💡 **`document.cookie` で出てこないことを自分の目で確認する**のが重要です。
> これが、XSS でセッションを盗まれない理由です。

### 演習3（必須）─ 問題を見つける

以下のコードには**6つの問題**があります。すべて指摘してください。

```php
<?php
session_start();

if ($_POST) {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $user = find_user_by_email($email);

    if (!$user) {
        $error = "そのメールアドレスは登録されていません";
    } elseif ($user["password"] !== md5($password)) {
        $error = "パスワードが違います";
    } else {
        $_SESSION["user_id"] = $user["id"];
        header("Location: mypage.php");
    }
}
?>
<a href="logout.php">ログアウト</a>
```

<details>
<summary>答えを見る</summary>

1. **`md5()` でハッシュ化している** → `password_hash` / `password_verify` を使う
2. **エラーメッセージを区別している** → アカウント列挙が可能。「メールアドレスまたはパスワードが正しくありません」に統一
3. **`session_regenerate_id(true)` がない** → セッション固定攻撃を防げない
4. **CSRF トークンの検証がない**
5. **`header()` の後に `exit;` がない** → 処理が続行される
6. **ログアウトが GET（`<a>` タグ）** → 勝手にログアウトさせられる。POST + CSRF にする
7. **（おまけ）`$_POST` を条件に使っている** → `$_SERVER["REQUEST_METHOD"] === "POST"`
8. **（おまけ）`?? ""` がない** → Warning
9. **（おまけ）総当たり対策がない**

</details>

### 演習4（挑戦）

**ユーザー登録機能**を追加してください。

- [ ] `register.php` を作る
- [ ] 氏名、メールアドレス、パスワード、パスワード確認を入力させる
- [ ] メールアドレスの重複をチェックする
- [ ] パスワードは `v_password()` で検証する
- [ ] パスワードと確認が一致するか検証する
- [ ] `password_hash()` でハッシュ化して保存する
      （ファイルへの保存方法は次のレッスン、または `file_put_contents` + `var_export`）
- [ ] 登録後、自動的にログイン状態にしてマイページへ
- [ ] **CSRF トークンを必ず入れる**

> 💡 ファイルへの保存は、第4部でデータベースに置き換えます。
> いまは `var_export($users, true)` で PHP の配列として書き出す方法が簡単です。

---

## ✅ 章末チェック

- [ ] HTTP がステートレスであることを説明できる
- [ ] Cookie とセッションの違いを図で説明できる
- [ ] Cookie にユーザーIDを入れてはいけない理由を言える
- [ ] `httponly` と `samesite` の効果を説明できる
- [ ] `password_hash` / `password_verify` を使える
- [ ] MD5 / SHA1 を使ってはいけない理由を言える
- [ ] `session_regenerate_id(true)` が必要な理由を説明できる
- [ ] エラーメッセージを区別しない理由を言える
- [ ] ログアウトを POST にする理由を言える
- [ ] `document.cookie` でセッションIDが見えないことを確認した

---

**前 → [3-7 バリデーションとエスケープ](03-07-validation-xss.md)　｜　次 → [3-9 ファイルアップロードを扱う](03-09-upload.md)**
