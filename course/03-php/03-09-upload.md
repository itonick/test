# 3-9 ファイルアップロードを扱う

> ◎ **このレッスンのゴール**
> - 画像アップロードを安全に実装できる
> - 拡張子偽装などの攻撃を防げる
> - ファイル操作の基本を身につける

所要 120分 / 難度 🟡
完成コード: [`code/03-09/`](../code/03-09/)

---

## 📖 アップロードは「最も危険な機能」

ユーザーがサーバーにファイルを置ける、ということは、
**対策を怠ると、サーバー上で任意のプログラムを実行される**ということです。

```
攻撃者が evil.php をアップロード
   ↓
http://example.com/uploads/evil.php にアクセス
   ↓
サーバー上で PHP が実行される → サーバー乗っ取り
```

**このレッスンの対策を、1つも省略しないでください。**

---

## ✍️ 手を動かす① ─ HTML側の準備

```html
<form method="post" enctype="multipart/form-data">
  <label for="avatar">プロフィール画像</label>
  <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
  <button type="submit">アップロード</button>
</form>
```

| 属性 | 意味 |
| --- | --- |
| **`enctype="multipart/form-data"`** | **これがないとファイルが届きません**（最頻出のミス） |
| `accept` | ファイル選択ダイアログで絞り込む（**検証にはならない**） |
| `multiple` | 複数選択を許可 |

> ⚠️ **`enctype` を書き忘れると、`$_FILES` が空になります。** 最初に確認してください。

> ⚠️ **`accept` は「親切機能」です。** ダイアログで絞り込むだけで、
> 開発者ツールで消せますし、curl なら無関係です。**サーバー側の検証が必須です。**

---

## ✍️ 手を動かす② ─ `$_FILES` の中身

```php
<?php
var_dump($_FILES);
// array(1) {
//   ["avatar"] => array(6) {
//     ["name"]      => string(9) "photo.jpg"    ← ★ ユーザーが決めた名前。信用しない
//     ["full_path"] => string(9) "photo.jpg"
//     ["type"]      => string(10) "image/jpeg"  ← ★ ブラウザの申告。信用しない
//     ["tmp_name"]  => string(24) "/tmp/php8A2E.tmp"  ← 一時ファイルの場所
//     ["error"]     => int(0)                   ← 0 なら成功
//     ["size"]      => int(102400)              ← バイト数
//   }
// }
```

> ⚠️ **`name` と `type` は、クライアントが自由に指定できます。**
> `evil.php` を `photo.jpg` という名前で送ることも、
> `type` を `image/jpeg` と偽ることもできます。**どちらも検証に使ってはいけません。**

> 🆘 **ここで詰まったら**（`$_FILES` が空／`Undefined array key` になる）
> - **原因No.1**：フォームに **`enctype="multipart/form-data"` が無い**（これが無いとファイルは届きません ─ 最頻出）
> - **原因No.2**：`<input type="file">` の `name` と、`$_FILES["○○"]` の `○○` が一致していない／`method="post"` になっていない
> - **`error` が 0 以外**：上のエラーコード表で意味を確認（サイズ超過は `php.ini` の `upload_max_filesize`・`post_max_size` も見る）
> - **直らなければ、AIにこう聞く**（フォームのHTMLと受信PHP、`var_dump($_FILES)` の結果を貼る）：
>   「ファイルアップロードで $_FILES が空になります。原因を確認手順つきで教えてください」

### エラーコード

```php
<?php
$errors_map = [
    UPLOAD_ERR_OK         => null,
    UPLOAD_ERR_INI_SIZE   => "ファイルサイズが大きすぎます（サーバー設定の上限超過）",
    UPLOAD_ERR_FORM_SIZE  => "ファイルサイズが大きすぎます",
    UPLOAD_ERR_PARTIAL    => "ファイルの送信が中断されました",
    UPLOAD_ERR_NO_FILE    => "ファイルが選択されていません",
    UPLOAD_ERR_NO_TMP_DIR => "サーバーの設定に問題があります",
    UPLOAD_ERR_CANT_WRITE => "ファイルの保存に失敗しました",
    UPLOAD_ERR_EXTENSION  => "ファイルのアップロードが拒否されました",
];
```

### サイズ上限の設定

`php.ini`

```ini
upload_max_filesize = 5M    ; 1ファイルの上限
post_max_size = 8M          ; POST全体の上限（upload_max_filesize より大きく）
max_file_uploads = 5        ; 同時アップロード数
```

> ⚠️ **`post_max_size` を超えると、`$_POST` も `$_FILES` も空になります。**
> エラーすら取れないので、`$_SERVER["CONTENT_LENGTH"]` で事前に検知します。
>
> ```php
> if (($_SERVER["CONTENT_LENGTH"] ?? 0) > 8 * 1024 * 1024) {
>     exit("ファイルサイズが大きすぎます");
> }
> ```

---

## ✍️ 手を動かす③ ─ 安全なアップロード処理

`lib/upload.php`（📋 完成形は `code/03-09/lib/upload.php`）

```php
<?php
declare(strict_types=1);

const UPLOAD_MAX_SIZE = 2 * 1024 * 1024;   // 2MB

/** 許可する MIMEタイプ → 拡張子 */
const ALLOWED_IMAGE_TYPES = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/gif"  => "gif",
    "image/webp" => "webp",
];

/**
 * アップロードされた画像を検証して保存する
 *
 * @return array{ok: bool, filename?: string, error?: string}
 */
function save_uploaded_image(array $file, string $dest_dir): array
{
    // ---------- ① エラーコードの確認 ----------
    $error = $file["error"] ?? UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_NO_FILE) {
        return ["ok" => false, "error" => "ファイルが選択されていません"];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ["ok" => false, "error" => "アップロードに失敗しました（コード: {$error}）"];
    }

    // ---------- ② 本当にアップロードされたファイルか ----------
    // ★ これがないと、任意のファイルパスを指定される危険がある
    if (!is_uploaded_file($file["tmp_name"])) {
        return ["ok" => false, "error" => "不正なリクエストです"];
    }

    // ---------- ③ サイズ ----------
    if ($file["size"] > UPLOAD_MAX_SIZE) {
        $mb = UPLOAD_MAX_SIZE / 1024 / 1024;
        return ["ok" => false, "error" => "ファイルサイズは{$mb}MB以下にしてください"];
    }
    if ($file["size"] === 0) {
        return ["ok" => false, "error" => "空のファイルはアップロードできません"];
    }

    // ---------- ④ 実際の中身から MIMEタイプを判定 ----------
    // ★ $file["type"] は信用しない。ファイルの中身を見る
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file["tmp_name"]);

    if (!array_key_exists($mime, ALLOWED_IMAGE_TYPES)) {
        return ["ok" => false, "error" => "JPEG / PNG / GIF / WebP の画像を選択してください"];
    }

    // ---------- ⑤ 画像として読めるか ----------
    $info = @getimagesize($file["tmp_name"]);
    if ($info === false) {
        return ["ok" => false, "error" => "画像ファイルとして読み込めませんでした"];
    }

    // 極端に大きい画像を弾く（メモリ枯渇対策）
    if ($info[0] > 8000 || $info[1] > 8000) {
        return ["ok" => false, "error" => "画像の解像度が大きすぎます"];
    }

    // ---------- ⑥ ファイル名を自分で作る ----------
    // ★ 元のファイル名は絶対に使わない
    $ext      = ALLOWED_IMAGE_TYPES[$mime];
    $filename = bin2hex(random_bytes(16)) . "." . $ext;
    $dest     = rtrim($dest_dir, "/") . "/" . $filename;

    // ---------- ⑦ 保存 ----------
    if (!is_dir($dest_dir)) {
        if (!mkdir($dest_dir, 0755, true) && !is_dir($dest_dir)) {
            return ["ok" => false, "error" => "保存先の準備に失敗しました"];
        }
    }

    // ★ move_uploaded_file を使う（copy や rename ではなく）
    if (!move_uploaded_file($file["tmp_name"], $dest)) {
        return ["ok" => false, "error" => "ファイルの保存に失敗しました"];
    }

    chmod($dest, 0644);   // 実行権限を与えない

    return ["ok" => true, "filename" => $filename];
}
```

### 7つの対策と、その理由

| # | 対策 | 防ぐもの |
| --- | --- | --- |
| ② | `is_uploaded_file()` | **`/etc/passwd` などの任意ファイルを指定される攻撃** |
| ③ | サイズ制限 | ディスク枯渇、DoS |
| ④ | `finfo` で中身を判定 | **拡張子・MIME偽装**（`evil.php` を `photo.jpg` として送る） |
| ⑤ | `getimagesize()` | 画像に見せかけた不正ファイル |
| ⑥ | **ファイル名を自分で作る** | **ディレクトリトラバーサル**（`../../index.php`）、上書き、日本語名の文字化け |
| ⑦ | `move_uploaded_file()` | 一時ファイル以外の移動を防ぐ |
| ⑦ | `chmod 0644` | 実行権限を与えない |

> ⚠️ **⑥ が最重要です。** ユーザーが決めた名前を使うと、
> `../../../index.php` のような名前で、**サイトのファイルを上書きされます**。

### `finfo` による判定の仕組み

ファイルの先頭数バイト（**マジックナンバー**）を見て、実際の種類を判定します。

```
JPEG: FF D8 FF
PNG:  89 50 4E 47
GIF:  47 49 46 38
```

**拡張子を `.jpg` に変えても、中身がPHPなら `text/x-php` と判定されます。**

---

## ✍️ 手を動かす④ ─ アップロード先を守る

**さらに重要な対策があります。**

### ① アップロード先で PHP を実行させない

`uploads/.htaccess`（Apache の場合）

```apache
# PHPの実行を禁止する
php_flag engine off

<FilesMatch "\.(php|phtml|php3|php4|php5|php7|php8|pht|phar)$">
    Require all denied
</FilesMatch>

# 画像として配信されるようにする
<IfModule mod_headers.c>
    Header set X-Content-Type-Options nosniff
</IfModule>
```

> 💡 **これがあれば、万一 PHP ファイルが置かれても実行されません。**
> 「多層防御」といって、1つの対策が破られても次の層で止める考え方です。

### ② できれば、公開ディレクトリの外に置く

```
htdocs/                  ← 公開される
├─ index.php
└─ uploads/              ← 直接アクセスできてしまう

storage/                 ← 公開されない（htdocs の外）
└─ uploads/              ← ✅ こちらが安全
```

公開ディレクトリの外に置き、**PHPを通して配信**します。

```php
<?php
// image.php?id=xxx
declare(strict_types=1);

$id = $_GET["id"] ?? "";

// ★ ファイル名を厳密に検証（英数字とドットのみ）
if (!preg_match('/\A[0-9a-f]{32}\.(jpg|png|gif|webp)\z/', $id)) {
    http_response_code(404);
    exit;
}

$path = __DIR__ . "/../storage/uploads/" . $id;

if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
header("Content-Type: " . $mime);
header("Content-Length: " . filesize($path));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=86400");
readfile($path);
```

> ⚠️ **`$_GET["id"]` をそのままパスに繋いではいけません。**
> `?id=../../config.php` のような値で、**任意のファイルを読まれます**（ディレクトリトラバーサル）。
> 上の例では、正規表現で**形式を厳密に限定**しています。

---

## ✍️ 手を動かす⑤ ─ ファイル操作の基本

```php
<?php
// 読み込み
$content = file_get_contents($path);       // 全体を文字列で（失敗時 false）
$lines   = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);  // 行の配列

// 書き込み
file_put_contents($path, $content);                    // 上書き
file_put_contents($path, $content, FILE_APPEND);       // 追記
file_put_contents($path, $content, LOCK_EX);           // ★ 排他ロック

// 存在確認
is_file($path);      // ファイルか
is_dir($path);       // ディレクトリか
file_exists($path);  // どちらか
is_readable($path);
is_writable($path);

// 情報
filesize($path);
filemtime($path);    // 最終更新日時（タイムスタンプ）
pathinfo($path);     // ["dirname", "basename", "extension", "filename"]

// 削除・作成
unlink($path);
mkdir($dir, 0755, true);   // true = 親ディレクトリも作る
rmdir($dir);               // 空でないと失敗

// 一覧
$files = glob(__DIR__ . "/uploads/*.jpg");
$files = scandir($dir);    // "." と ".." も含まれる
```

> ⚠️ **`file_put_contents` には `LOCK_EX` を付けてください。**
> 同時に2つのリクエストが書き込むと、データが壊れます。

### JSON でデータを保存する

第4部でデータベースを使うまでの、簡易的な保存方法です。

```php
<?php
declare(strict_types=1);

function load_json(string $path, array $default = []): array
{
    if (!is_file($path)) return $default;

    $raw = file_get_contents($path);
    if ($raw === false || $raw === "") return $default;

    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function save_json(string $path, array $data): bool
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($json === false) return false;

    return file_put_contents($path, $json, LOCK_EX) !== false;
}
```

| オプション | 効果 |
| --- | --- |
| `JSON_PRETTY_PRINT` | 人間が読める整形 |
| `JSON_UNESCAPED_UNICODE` | **日本語をそのまま出す**（`あ` にしない） |
| `JSON_UNESCAPED_SLASHES` | `/` を `\/` にしない |

> ⚠️ **JSONファイルは、公開ディレクトリの外に置いてください。**
> `htdocs/data/users.json` に置くと、**URLで直接ダウンロードされます**。

---

## ✍️ 手を動かす⑥ ─ 実践：プロフィール画像

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/session.php";
start_secure_session();
require_once __DIR__ . "/lib/functions.php";
require_once __DIR__ . "/lib/csrf.php";
require_once __DIR__ . "/lib/upload.php";

const UPLOAD_DIR = __DIR__ . "/uploads";

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["_csrf_token"] ?? null)) {
        http_response_code(419);
        exit("セッションの有効期限が切れました");
    }

    $result = save_uploaded_image($_FILES["avatar"] ?? [], UPLOAD_DIR);

    if ($result["ok"]) {
        // 古い画像を削除
        $old = $_SESSION["avatar"] ?? null;
        if ($old !== null && is_file(UPLOAD_DIR . "/" . $old)) {
            unlink(UPLOAD_DIR . "/" . $old);
        }

        $_SESSION["avatar"] = $result["filename"];
        $_SESSION["flash"]  = "プロフィール画像を更新しました";
        header("Location: " . $_SERVER["PHP_SELF"], true, 303);
        exit;
    }

    $error = $result["error"];
}

$flash  = $_SESSION["flash"] ?? null;
$avatar = $_SESSION["avatar"] ?? null;
unset($_SESSION["flash"]);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>プロフィール画像</title>
</head>
<body>
  <h1>プロフィール画像</h1>

  <?php if ($flash !== null): ?>
    <p class="alert alert-success" role="status"><?= e($flash) ?></p>
  <?php endif; ?>

  <?php if ($error !== null): ?>
    <p class="alert alert-error" role="alert"><?= e($error) ?></p>
  <?php endif; ?>

  <?php if ($avatar !== null): ?>
    <img src="uploads/<?= e($avatar) ?>" alt="現在のプロフィール画像"
         width="200" height="200" style="object-fit:cover;border-radius:50%">
  <?php else: ?>
    <p>画像が設定されていません</p>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="field">
      <label for="avatar">画像を選択（2MB以下 / JPEG・PNG・GIF・WebP）</label>
      <input type="file" id="avatar" name="avatar"
             accept="image/jpeg,image/png,image/gif,image/webp" required>
    </div>
    <button type="submit" class="btn">アップロード</button>
  </form>
</body>
</html>
```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `$_FILES` が空 | `enctype` を書き忘れた | `enctype="multipart/form-data"` |
| `$_POST` も `$_FILES` も空 | `post_max_size` 超過 | `CONTENT_LENGTH` で事前チェック |
| 大きいファイルでエラー | `upload_max_filesize` | php.ini を変更して Apache 再起動 |
| 保存に失敗する | ディレクトリの権限 | `chmod 755`（Mac/Linux） |
| 画像が表示されない | パスの間違い | ブラウザで画像URLを直接開いて確認 |
| 日本語のファイル名が化ける | 文字コード | **ファイル名は自分で生成する**（対策済み） |
| 同名ファイルが上書きされる | 元の名前を使っている | ランダムな名前にする（対策済み） |

---

## 🤖 AIに聞いてみよう

### ① アップロード処理のセキュリティレビュー

```text
以下は、私が実装した PHP のファイルアップロード処理です。

（コードを貼る）

セキュリティの観点で、厳しくレビューしてください。

1. 拡張子・MIMEタイプの偽装を防げているか
2. ディレクトリトラバーサルの危険はないか
3. アップロード先で PHP が実行される危険はないか
4. サイズ制限・DoS への対策
5. ファイル名の扱い
6. 権限設定
7. その他、実装が漏れている一般的な対策

各指摘に優先度を付けてください。修正コードは書かないでください。
```

### ② 多層防御の考え方を学ぶ

```text
Webアプリのファイルアップロード機能について、
「多層防御」の考え方を教えてください。

1. アプリケーション層でできる対策
2. Webサーバー（Apache/Nginx）の設定でできる対策
3. ファイルシステムの権限でできる対策
4. インフラ構成でできる対策（保存先の分離、CDN など）

それぞれの層が「どの攻撃を、どの段階で止めるのか」を明示してください。
初学者が最低限やるべきものと、実務で追加すべきものを分けてください。
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）

プロフィール画像のアップロードを実装し、以下を確認してください。

- [ ] JPEG 画像がアップロードできる
- [ ] 3MB の画像を選ぶと、サイズエラーになる
- [ ] `.txt` ファイルを選ぶと、形式エラーになる
- [ ] 保存されたファイル名が、ランダムな英数字になっている
- [ ] `uploads/` に `.htaccess` を置く
- [ ] `enctype` を消してみて、`$_FILES` が空になることを確認する

### 演習2（必須）─ 偽装を試す

**自分のローカル環境でのみ**行ってください。

- [ ] テキストエディタで `<?php echo "実行された"; ?>` と書いたファイルを作る
- [ ] 拡張子を `.jpg` に変えて、アップロードを試す
- [ ] **`finfo` の検証で弾かれる**ことを確認する
- [ ] 検証（④のブロック）を一時的にコメントアウトして、アップロードできてしまうことを確認
- [ ] アップロードされたファイルに、ブラウザで直接アクセスしてみる
      → `.htaccess` があれば実行されない
- [ ] **確認したら、検証を元に戻し、テストファイルを削除する**

> ⚠️ **この演習は、必ずローカル環境で行ってください。** 公開サーバーでは絶対にやらないこと。

### 演習3（挑戦）

以下の機能を追加してください。

- [ ] アップロード時に、画像を最大幅600pxにリサイズする
      （`imagecreatefromjpeg` / `imagescale` / `imagejpeg` を調べる）
- [ ] 複数ファイルの同時アップロード（`multiple` 属性 + `$_FILES` の配列構造）
- [ ] アップロード先を `htdocs` の外にし、`image.php` 経由で配信する
- [ ] アップロード前に、JavaScript でプレビューを表示する（`FileReader`）

> 💡 **`$_FILES` の複数ファイル時の構造は、直感に反します。**
>
> ```php
> $_FILES["photos"]["name"][0]      // 1つ目の名前
> $_FILES["photos"]["tmp_name"][0]  // 1つ目の一時パス
> ```
>
> **「ファイルごとの配列」ではなく「項目ごとの配列」**になります。
> 扱いやすい形に変換する関数を自作するのが定石です。

---

## ✅ 章末チェック

- [ ] `enctype="multipart/form-data"` が必要な理由を言える
- [ ] `$_FILES["x"]["name"]` と `["type"]` を信用してはいけない理由を説明できる
- [ ] `is_uploaded_file()` と `move_uploaded_file()` を使う理由を言える
- [ ] `finfo` で中身から判定する理由を説明できる
- [ ] ファイル名を自分で生成する理由を3つ言える
- [ ] `.htaccess` で PHP の実行を止める意味を理解した
- [ ] ディレクトリトラバーサルがどういう攻撃か説明できる
- [ ] `file_put_contents` に `LOCK_EX` を付ける理由を言える

---

**前 → [3-8 セッションとクッキー](03-08-session.md)　｜　次 → [3-10 クラスとオブジェクト指向の入口](03-10-oop.md)**
