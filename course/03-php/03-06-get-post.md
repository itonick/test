# 3-6 GET / POST でデータを受け取る

> 🎯 **このレッスンのゴール**
> - フォームから送られたデータを受け取れる
> - GET と POST を正しく使い分けられる
> - PRG パターンで二重送信を防げる
> - 検索フォームを作る

所要 150分 / 難度 🟡
完成コード: [`code/03-06/`](../code/03-06/)

---

## 📖 ついに、フロントとバックが繋がる

第1部でフォームを作り、第2部でJavaScriptで検証しました。
**そのデータを、いよいよサーバーで受け取ります。**

```
ブラウザ                                    サーバー（PHP）
   │                                            │
   │ ①フォームに入力して送信                    │
   │  name="email" の値 = "a@b.com"             │
   │───────────────────────────────────────────>│
   │                                            │ ② $_POST["email"] で受け取る
   │                                            │ ③ 検証・保存
   │<───────────────────────────────────────────│
   │ ④結果のHTMLが返る                          │
```

---

## ✍️ 手を動かす① ─ スーパーグローバル

PHPには、**どこからでも使える特別な変数**があります。

| 変数 | 中身 |
| --- | --- |
| `$_GET` | URLのクエリ文字列（`?a=1&b=2`） |
| `$_POST` | POST で送られたデータ |
| `$_REQUEST` | GET + POST + Cookie（**使わない**） |
| `$_SERVER` | リクエストの情報（メソッド、IPなど） |
| `$_SESSION` | セッション（3-8） |
| `$_COOKIE` | クッキー（3-8） |
| `$_FILES` | アップロードされたファイル（3-9） |

> ⚠️ **`$_REQUEST` は使わないでください。**
> GET と POST のどちらから来たかがわからず、意図しない上書きが起きます。**必ず `$_GET` か `$_POST` を明示**します。

### すべて「文字列」で届く

```php
<?php
var_dump($_POST);
// array(3) {
//   ["name"]   => string(9) "山田太郎"
//   ["age"]    => string(2) "20"     ← 数値ではなく文字列
//   ["agree"]  => string(1) "1"
// }
```

> ⚠️ **数値も文字列として届きます。** 計算に使うときは `(int)` か `intval()` で変換してください。

### 送られてこないケース

| 状況 | 結果 |
| --- | --- |
| チェックされていないチェックボックス | **キー自体が存在しない** |
| `disabled` な入力欄 | **送られない** |
| `name` 属性がない入力欄 | **送られない** |
| 空の入力欄 | 空文字 `""` として送られる |

```php
<?php
// ❌ Warning: Undefined array key
$agree = $_POST["agree"];

// ✅ null合体演算子でデフォルト値を用意する
$agree = $_POST["agree"] ?? "";
$name  = $_POST["name"] ?? "";
```

**すべての受け取りに `?? ""` を書いてください。** これは例外なく守るルールです。

> 🆘 **ここで詰まったら**（`Warning: Undefined array key "..."`）
> - **まず確認**：① そのキーの受け取りに `?? ""` を付けたか ② フォーム側の `name="..."` と、PHP側の `$_POST["..."]` の**つづりが一致**しているか（ここのズレが本当に多い）③ フォームの `method` は `post` か（GETで送ってPOSTで受けていないか）
> - **直らなければ、AIにこう聞く**（フォームのHTMLと受け取りPHPを両方貼る）：
>   「`Undefined array key` が出ます。フォームの name と受け取りのキーが合っているか、私のコードを見て確認してください」

---

## ✍️ 手を動かす② ─ GET を受け取る

### URLから受け取る

```
http://localhost/php-lesson/get-test.php?keyword=コーヒー&page=2
```

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/functions.php";

$keyword = $_GET["keyword"] ?? "";
$page    = (int) ($_GET["page"] ?? 1);
?>
<p>キーワード: <?= e($keyword) ?></p>
<p>ページ: <?= $page ?></p>
```

> ⚠️ **`(int) ($_GET["page"] ?? 1)` の括弧の位置に注意してください。**
> `(int) $_GET["page"] ?? 1` と書くと、`??` より `(int)` が先に評価され、
> キーが無いとき Warning が出ます。**括弧で囲んでください。**

### GET フォーム

```php
<form action="search.php" method="get">
  <label for="q">キーワード</label>
  <input type="text" id="q" name="q" value="<?= e($_GET["q"] ?? "") ?>">
  <button type="submit">検索</button>
</form>
```

送信すると、`search.php?q=入力値` に遷移します。

> 💡 **`value="<?= e($_GET["q"] ?? "") ?>"` で、検索後も入力値が残ります。**
> これがないと、検索するたびに入力欄が空になり、ユーザーが困ります。

---

## ✍️ 手を動かす③ ─ POST を受け取る

### リクエストメソッドで分岐する

**1つのファイルで「表示」と「処理」の両方を扱う**のが、素のPHPでの定石です。

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/functions.php";

$errors = [];
$input  = ["name" => "", "email" => "", "message" => ""];
$done   = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ---------- POST されたときの処理 ----------
    $input["name"]    = trim_ja($_POST["name"] ?? "");
    $input["email"]   = trim_ja($_POST["email"] ?? "");
    $input["message"] = trim_ja($_POST["message"] ?? "");

    // 検証
    if (is_blank($input["name"]))    $errors["name"]    = "お名前を入力してください";
    if (is_blank($input["email"]))   $errors["email"]   = "メールアドレスを入力してください";
    if (is_blank($input["message"])) $errors["message"] = "お問い合わせ内容を入力してください";

    if (count($errors) === 0) {
        // 保存や送信の処理（いまは何もしない）
        $done = true;
    }
}
?>
<!-- ---------- 表示 ---------- -->
<?php if ($done): ?>
  <p class="success">送信しました。ありがとうございました。</p>
<?php else: ?>
  <form method="post">
    <div class="field">
      <label for="name">お名前</label>
      <input type="text" id="name" name="name" value="<?= e($input["name"]) ?>">
      <?php if (isset($errors["name"])): ?>
        <p class="error"><?= e($errors["name"]) ?></p>
      <?php endif; ?>
    </div>
    ...
    <button type="submit">送信</button>
  </form>
<?php endif; ?>
```

**この形が基本です。** 覚えてください。

| 変数 | 役割 |
| --- | --- |
| `$errors` | エラーメッセージの連想配列（キー = 項目名） |
| `$input` | 入力値。**エラー時に再表示するため**に保持する |
| `$done` | 処理が成功したか |

> 💡 **`$input` に入力値を保持する**のが重要です。
> これがないと、エラーで戻ったときに入力が全部消え、ユーザーが激怒します。

### `action` を省略する

```php
<form method="post">          <!-- 自分自身に送る -->
<form method="post" action=""> <!-- 同上 -->
```

**`action` を省略すると、同じURLに送られます。** 上記のパターンではこれが便利です。

---

## ✍️ 手を動かす④ ─ PRG パターン（二重送信の防止）

### 問題

POST でフォームを送信した後、ブラウザで**リロード**すると…

```
「フォームを再送信しますか？」
```

**「はい」を押すと、同じデータが2回登録されます。** 掲示板なら二重投稿、ECサイトなら二重注文です。

### 解決：Post / Redirect / Get

```
① POST でデータを送る
      ↓
② サーバーが処理する
      ↓
③ サーバーが「別のURLへ行け」と返す（リダイレクト）
      ↓
④ ブラウザが GET でそのURLを開く
      ↓
   → リロードしても、④の GET が繰り返されるだけ。安全
```

### 実装

```php
<?php
declare(strict_types=1);
session_start();                      // ← セッションを使う（3-8で詳しく）
require_once __DIR__ . "/lib/functions.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim_ja($_POST["name"] ?? "");

    $errors = [];
    if (is_blank($name)) $errors["name"] = "お名前を入力してください";

    if (count($errors) === 0) {
        // 保存処理...

        // 完了メッセージをセッションに入れる
        $_SESSION["flash"] = "送信しました。ありがとうございました。";

        // リダイレクト（自分自身の GET へ）
        header("Location: " . $_SERVER["PHP_SELF"], true, 303);
        exit;                          // ← ★ 必ず exit
    }

    // エラーがある場合は、入力値とエラーをセッションに入れて戻す
    $_SESSION["old"]    = $_POST;
    $_SESSION["errors"] = $errors;
    header("Location: " . $_SERVER["PHP_SELF"], true, 303);
    exit;
}

// ---------- GET のとき ----------
$flash  = $_SESSION["flash"]  ?? null;
$old    = $_SESSION["old"]    ?? [];
$errors = $_SESSION["errors"] ?? [];

// 一度表示したら消す（フラッシュメッセージ）
unset($_SESSION["flash"], $_SESSION["old"], $_SESSION["errors"]);
?>
```

> ⚠️ **`header()` の後には必ず `exit;` を書いてください。**
> `header()` は「ヘッダーを送るだけ」で、**処理は止まりません**。
> `exit` がないと、リダイレクトしつつ以降の処理も実行され、思わぬ副作用が出ます。

> ⚠️ **`header()` の前に出力があってはいけません。**
> `echo` はもちろん、**`<?php` の前の空白や改行**も出力です（3-5参照）。
> `Cannot modify header information` が出たら、これが原因です。

### ステータスコード 303 について

```php
header("Location: /done.php", true, 303);
```

| コード | 意味 |
| --- | --- |
| 301 | 恒久的な移動（キャッシュされる。**フォームには使わない**） |
| 302 | 一時的な移動（デフォルト） |
| **303** | **See Other**。「POST の結果を GET で見に行け」 |

**PRG パターンでは 303 が意味的に正しい**です。302 でも動きますが、303 を使ってください。

> 💡 **フラッシュメッセージ**（一度だけ表示されるメッセージ）は、
> セッションに入れて、表示後に `unset` するのが定石です。Laravel の `with('success', ...)` も同じ仕組みです。

---

## ✍️ 手を動かす⑤ ─ 実践：メニュー検索フォーム

`php-lesson/search.php`（📋 完成形は `code/03-06/search.php`）

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/functions.php";

// ==========================================================
// データ（本来はDB。第4部で置き換えます）
// ==========================================================

$menu = [
    ["id" => 1, "name" => "ハンドドリップ", "price" => 600, "category" => "coffee", "sold_out" => false],
    ["id" => 2, "name" => "カフェラテ",     "price" => 600, "category" => "coffee", "sold_out" => false],
    ["id" => 3, "name" => "本日のケーキ",   "price" => 550, "category" => "food",   "sold_out" => true],
    ["id" => 4, "name" => "季節のお茶",     "price" => 500, "category" => "tea",    "sold_out" => false],
    ["id" => 5, "name" => "厚切りトースト", "price" => 450, "category" => "food",   "sold_out" => false],
    ["id" => 6, "name" => "アイスコーヒー", "price" => 550, "category" => "coffee", "sold_out" => false],
];

$categories = [
    ""       => "すべて",
    "coffee" => "コーヒー",
    "food"   => "フード",
    "tea"    => "お茶",
];

$sort_options = [
    "default"    => "標準",
    "price_asc"  => "価格が安い順",
    "price_desc" => "価格が高い順",
    "name"       => "名前順",
];

// ==========================================================
// 検索条件を受け取る（GET）
// ==========================================================

$keyword       = trim_ja($_GET["q"] ?? "");
$category      = $_GET["category"] ?? "";
$sort          = $_GET["sort"] ?? "default";
$hide_sold_out = isset($_GET["hide_sold_out"]);

// 不正な値を弾く（ホワイトリスト方式）
if (!array_key_exists($category, $categories)) $category = "";
if (!array_key_exists($sort, $sort_options))   $sort = "default";

// ==========================================================
// 絞り込み
// ==========================================================

$results = $menu;

if ($keyword !== "") {
    $results = array_filter(
        $results,
        fn($m) => mb_strpos($m["name"], $keyword) !== false
    );
}

if ($category !== "") {
    $results = array_filter($results, fn($m) => $m["category"] === $category);
}

if ($hide_sold_out) {
    $results = array_filter($results, fn($m) => !$m["sold_out"]);
}

$results = array_values($results);   // ★ キーを振り直す

// 並び替え
match ($sort) {
    "price_asc"  => usort($results, fn($a, $b) => $a["price"] <=> $b["price"]),
    "price_desc" => usort($results, fn($a, $b) => $b["price"] <=> $a["price"]),
    "name"       => usort($results, fn($a, $b) => strcmp($a["name"], $b["name"])),
    default      => null,
};

$page_title = "メニュー検索";
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
<main class="container">

  <h1>メニュー検索</h1>

  <!-- ========== 検索フォーム（GET） ========== -->
  <form method="get" class="search-form">
    <div class="field">
      <label for="q">キーワード</label>
      <input type="search" id="q" name="q" value="<?= e($keyword) ?>"
             placeholder="商品名で検索">
    </div>

    <div class="field">
      <label for="category">カテゴリ</label>
      <select id="category" name="category">
        <?php foreach ($categories as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $category === $value ? "selected" : "" ?>>
            <?= e($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="sort">並び順</label>
      <select id="sort" name="sort">
        <?php foreach ($sort_options as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $sort === $value ? "selected" : "" ?>>
            <?= e($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field field-check">
      <input type="checkbox" id="hide_sold_out" name="hide_sold_out" value="1"
             <?= $hide_sold_out ? "checked" : "" ?>>
      <label for="hide_sold_out">売り切れを隠す</label>
    </div>

    <div class="field-actions">
      <button type="submit" class="btn">検索</button>
      <a href="search.php" class="link-btn">条件をリセット</a>
    </div>
  </form>

  <!-- ========== 結果 ========== -->
  <p class="result-count" aria-live="polite">
    <?= count($menu) ?>件中 <?= count($results) ?>件を表示
  </p>

  <?php if (count($results) === 0): ?>
    <p class="empty">
      条件に一致する商品が見つかりませんでした。<br>
      キーワードを変えるか、条件を減らしてお試しください。
    </p>
  <?php else: ?>
    <ul class="result-list">
      <?php foreach ($results as $item): ?>
        <li class="result-item <?= $item["sold_out"] ? "is-sold-out" : "" ?>">
          <span class="name"><?= e($item["name"]) ?></span>
          <span class="price"><?= yen($item["price"]) ?></span>
          <span class="category"><?= e($categories[$item["category"]] ?? "") ?></span>
          <?php if ($item["sold_out"]): ?>
            <span class="badge">売り切れ</span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

</main>
</body>
</html>
```

### このコードのポイント

| 実装 | 理由 |
| --- | --- |
| **検索は GET** | URLをブックマーク・共有できる。リロードしても安全 |
| **ホワイトリストで検証** | 不正な `sort` 値が来ても落ちない |
| **入力値をフォームに戻す** | 検索後も条件が残る |
| **`array_values()`** | `array_filter` 後のキーを振り直す |
| **`mb_strpos`** | 日本語の部分一致検索 |
| **0件のメッセージが具体的** | 「見つかりません」だけでなく、次の行動を示す |
| **`aria-live="polite"`** | 件数の変化が読み上げられる |
| **リセットリンク** | 条件をクリアできる |

> ⚠️ **ホワイトリスト方式の検証は重要です。**
>
> ```php
> if (!array_key_exists($sort, $sort_options)) $sort = "default";
> ```
>
> これがないと、`?sort=<script>...` のような値がそのまま使われる可能性があります。
> **「許可する値のリスト」を作り、それ以外は弾く。** これがセキュリティの基本形です。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `Undefined array key` | 送られてこないキーを読んだ | `?? ""` を付ける |
| 値が届かない | `name` 属性がない | すべての入力欄に `name` |
| チェックボックスの値が取れない | チェックなしだとキーが無い | `isset($_POST["x"])` で判定 |
| 数値計算がおかしい | 文字列として届いている | `(int)` で変換 |
| リロードで二重送信される | PRG パターンを使っていない | リダイレクトする |
| `Cannot modify header information` | `header()` の前に出力がある | 1行目から `<?php`、末尾の `?>` を書かない |
| リダイレクト後も処理が続く | `exit` を書いていない | `header()` の直後に `exit;` |
| エラー時に入力が消える | 入力値を保持していない | `$input` に入れて `value` に戻す |
| `(int) $_GET["x"] ?? 1` で Warning | 演算子の優先順位 | `(int) ($_GET["x"] ?? 1)` |

---

## 🤖 AIに聞いてみよう

### ① GET / POST の設計を相談する

```text
PHPでWebアプリを作っています。以下の機能について、
GET と POST のどちらを使うべきか、理由つきで教えてください。

1. 商品の検索・絞り込み
2. 会員登録
3. ログイン
4. カートに商品を追加
5. 記事の削除
6. ページ送り（2ページ目へ）
7. 言語の切り替え
8. お気に入り登録の切り替え

判断基準を明示してください。
また、「GETで実装すると危険なもの」があれば、その理由も教えてください。
```

> 💡 **5番（削除）が特に重要です。**
> GET で削除できると、`<img src="/delete.php?id=1">` のようなタグを踏ませるだけで
> データが消せてしまいます（CSRF）。3-8 で扱います。

### ② 入力の受け取りをレビューさせる

```text
以下は、私が書いた PHP のフォーム処理です。

（コードを貼る）

次の観点でレビューしてください。

1. 未定義キーへのアクセス（Warning が出る箇所）
2. 型変換が必要な箇所
3. 検証が不足している箇所（特に、値のホワイトリスト検証）
4. 二重送信の対策
5. エラー時のユーザー体験（入力値の保持、メッセージの具体性）
6. セキュリティ上の問題

修正後のコードは書かず、指摘だけをお願いします。
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）

`search.php` を実装し、以下を確認してください。

- [ ] キーワードで絞り込める
- [ ] カテゴリで絞り込める
- [ ] 並び替えができる
- [ ] 検索後も、入力値がフォームに残っている
- [ ] URLをコピーして別のタブで開くと、同じ結果が出る
- [ ] `?sort=hoge` のような不正な値を入れても壊れない
- [ ] 0件のとき、具体的なメッセージが出る
- [ ] リセットリンクで条件がクリアされる

### 演習2（必須）─ 問題を見つける

以下のコードには**6つの問題**があります。すべて指摘してください。

```php
<?php
$name = $_REQUEST["name"];
$age = $_POST["age"];
$total = $age * 1000;

if ($_POST) {
    save_data($name, $age);
    echo "保存しました";
}
?>
<form method="post">
  <input type="text" name="name">
  <input type="number" name="age">
  <button>送信</button>
</form>
```

<details>
<summary>答えを見る</summary>

1. **`$_REQUEST` を使っている** — GET/POST/Cookie が混ざる。`$_POST` を明示する
2. **`?? ""` がない** — 初回アクセス（GET）時に `Undefined array key` の Warning
3. **`$_POST` を条件に使っている** — 空配列は falsy なので動くが、意図が不明確。
   `$_SERVER["REQUEST_METHOD"] === "POST"` を使う
4. **型変換していない** — `$age` は文字列。`(int)` で変換する
5. **検証がない** — 空でも保存される。数値以外が入る可能性もある
6. **PRG パターンでない** — リロードで二重送信される
7. **（おまけ）`<label>` がない** — アクセシビリティの問題
8. **（おまけ）エラー時に入力が消える** — 入力値を `value` に戻していない

**修正版**

```php
<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . "/lib/functions.php";

$errors = [];
$input = ["name" => "", "age" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input["name"] = trim_ja($_POST["name"] ?? "");
    $input["age"]  = trim_ja($_POST["age"] ?? "");

    if (is_blank($input["name"])) {
        $errors["name"] = "お名前を入力してください";
    }
    if (!ctype_digit($input["age"])) {
        $errors["age"] = "年齢は数字で入力してください";
    } elseif ((int) $input["age"] < 0 || (int) $input["age"] > 120) {
        $errors["age"] = "年齢は0〜120で入力してください";
    }

    if (count($errors) === 0) {
        save_data($input["name"], (int) $input["age"]);
        $_SESSION["flash"] = "保存しました";
        header("Location: " . $_SERVER["PHP_SELF"], true, 303);
        exit;
    }
}

$flash = $_SESSION["flash"] ?? null;
unset($_SESSION["flash"]);
?>
```

</details>

### 演習3（挑戦）

**お問い合わせフォーム**を、PRG パターンで実装してください。

- [ ] 氏名（必須、50文字以内）
- [ ] メールアドレス（必須、形式チェック）
- [ ] 電話番号（任意、数字とハイフンのみ）
- [ ] ご用件（セレクト、ホワイトリスト検証）
- [ ] 本文（必須、1000文字以内）
- [ ] 同意チェック（必須）
- [ ] エラー時、入力値がすべて保持される
- [ ] 成功時、リダイレクトしてフラッシュメッセージを表示
- [ ] リロードしても二重送信されない
- [ ] 各エラーが、該当項目のすぐ下に表示される

> 💡 第1部（1-4）で作ったHTMLのフォームを、そのまま流用できます。
> **`action="contact.php"` が、ついに意味を持ちます。**

---

## ✅ 章末チェック

- [ ] `$_GET` と `$_POST` の使い分けを説明できる
- [ ] `$_REQUEST` を使わない理由を言える
- [ ] すべての値が文字列で届くことを知っている
- [ ] チェックボックスは `isset()` で判定する
- [ ] `?? ""` を必ず付ける
- [ ] `$_SERVER["REQUEST_METHOD"]` で分岐できる
- [ ] PRG パターンの目的と実装を説明できる
- [ ] `header()` の後に `exit;` が必要な理由を言える
- [ ] ホワイトリスト方式の検証を実装できる
- [ ] エラー時に入力値を保持する重要性を理解した

---

**前 → [3-5 HTMLとPHPを混ぜて書く](03-05-html-php.md)　｜　次 → [3-7 バリデーションとエスケープ（XSS対策）](03-07-validation-xss.md)**
