# 3-5 HTMLとPHPを混ぜて書く

> ◎ **このレッスンのゴール**
> - テンプレート構文（`<?php ... ?>`）を正しく使える
> - 出力時に必ずエスケープする習慣をつける
> - 共通部品（ヘッダー・フッター）を切り出せる

所要 120分 / 難度 🟡
完成コード: [`code/03-05/`](../code/03-05/)

---

## 📖 PHPは「HTMLを生成する」もの

第2部では、JavaScript で `innerHTML` に文字列を入れて画面を作りました。
PHP では、**HTMLの中に処理を埋め込む**のが基本です。

```php
<h1>商品一覧</h1>
<ul>
<?php foreach ($menu as $item): ?>
  <li><?= e($item["name"]) ?> — <?= yen($item["price"]) ?></li>
<?php endforeach; ?>
</ul>
```

**HTMLが主役、PHPが脇役。** この形が読みやすく、実務でも標準です。

---

## ✍️ 手を動かす① ─ 出力の書き方

```php
<?php $name = "太郎"; ?>

<!-- 長い書き方 -->
<p><?php echo $name; ?></p>

<!-- 短縮タグ（推奨） -->
<p><?= $name ?></p>
```

`<?= ` は `<?php echo ` の省略形です。**PHP 5.4 以降、設定に関係なく常に使えます。**

> 💡 **テンプレート内では `<?= ?>` を使ってください。** 短く、読みやすくなります。
> 末尾のセミコロンも省略できます（`?>` が終わりを示すため）。

### ⚠️ 出力には必ず `e()` を通す

```php
<!-- ❌ XSS 脆弱性 -->
<p><?= $comment ?></p>

<!-- ✅ エスケープする -->
<p><?= e($comment) ?></p>
```

3-4 で作った `e()` 関数の中身です。

```php
function e(?string $value): string
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}
```

| 引数 | 意味 |
| --- | --- |
| `ENT_QUOTES` | シングルクォートもエスケープする（**必須**） |
| `"UTF-8"` | 文字コードを明示（**必須**） |

> ⚠️ **`ENT_QUOTES` を省略すると、シングルクォートがエスケープされません。**
> `<input value='<?= $v ?>'>` のような書き方をしていると、属性から抜け出されます。
>
> **`htmlspecialchars($v)` だけでは不十分。** 必ず3つの引数を書くか、`e()` を使ってください。

### 何をエスケープするのか

`htmlspecialchars` は、以下の5文字を実体参照に変換します。

| 文字 | 変換後 |
| --- | --- |
| `&` | `&amp;` |
| `<` | `&lt;` |
| `>` | `&gt;` |
| `"` | `&quot;` |
| `'` | `&#039;`（`ENT_QUOTES` 時のみ） |

これにより、`<script>` が**タグとして解釈されず、文字として表示**されます。

**詳しくは 3-7 で扱います。いまは「出力には必ず `e()`」だけ覚えてください。**

---

## ✍️ 手を動かす② ─ 制御構文の代替構文

HTMLと混ぜるときは、**波括弧ではなくコロン記法**を使います。

```php
<!-- ❌ 波括弧（HTMLと混ぜると、どこで閉じたかわからない） -->
<?php if ($is_member) { ?>
  <p>会員価格でご案内します</p>
<?php } ?>

<!-- ✅ 代替構文（endif で明示的） -->
<?php if ($is_member): ?>
  <p>会員価格でご案内します</p>
<?php endif; ?>
```

> 🆘 **ここで詰まったら**（HTMLに混ぜたら `Parse error: syntax error` が出た）
> - **チェック順**：① 代替構文は **`if (...):` のコロンと、`endif;` / `endforeach;` の対応**が要る。開いたら必ず閉じる ② `<?php ... ?>` の閉じ忘れ・開き忘れ ③ 出力の短縮形は `<?=` （`<?php echo` と等価）。`<?` だけだと環境により動かない ④ 全角スペースの混入
> - **エラーの読み方**：`on line N` の**1〜3行前**も見る（閉じ忘れは、次の行で発覚することが多い）
> - **直らなければ、AIにこう聞く**（該当テンプレート全体を貼る）：
>   「PHPをHTMLに混ぜたら Parse error が出ます。代替構文の対応が取れているか、私のコードを見て指摘してください」

### 全パターン

```php
<!-- if -->
<?php if ($a): ?>
  A
<?php elseif ($b): ?>
  B
<?php else: ?>
  C
<?php endif; ?>

<!-- foreach -->
<?php foreach ($items as $item): ?>
  <li><?= e($item["name"]) ?></li>
<?php endforeach; ?>

<!-- for -->
<?php for ($i = 0; $i < 5; $i++): ?>
  <span><?= $i ?></span>
<?php endfor; ?>

<!-- while -->
<?php while ($row = next_row()): ?>
  <tr>...</tr>
<?php endwhile; ?>

<!-- switch（HTMLと混ぜるときは書きにくいので、事前に変数を作るほうがよい） -->
```

> ⚠️ **代替構文では `else if`（2語）が使えません。** 必ず `elseif`（1語）です。

### 空のときの表示

```php
<?php if (count($items) === 0): ?>
  <p class="empty">該当する商品がありません</p>
<?php else: ?>
  <ul>
  <?php foreach ($items as $item): ?>
    <li><?= e($item["name"]) ?></li>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>
```

> 💡 **「0件のとき」を必ず書いてください。** 第2部でも強調した通り、これが抜けると
> 「壊れている」と思われます。

---

## ✍️ 手を動かす③ ─ 属性の中で使う

```php
<!-- テキスト -->
<p><?= e($name) ?></p>

<!-- 属性値（必ずダブルクォートで囲み、e() を通す） -->
<input type="text" name="name" value="<?= e($name) ?>">
<img src="<?= e($path) ?>" alt="<?= e($alt) ?>">
<a href="<?= e($url) ?>">リンク</a>

<!-- クラスの出し分け -->
<li class="item <?= $item["sold_out"] ? "is-sold-out" : "" ?>">

<!-- チェック状態 -->
<input type="checkbox" <?= $is_checked ? "checked" : "" ?>>
<option value="a" <?= $selected === "a" ? "selected" : "" ?>>A</option>
```

> ⚠️ **属性値は必ずクォートで囲んでください。**
>
> ```php
> <div class=<?= e($cls) ?>>      <!-- ❌ 値にスペースが入ると壊れる -->
> <div class="<?= e($cls) ?>">    <!-- ✅ -->
> ```

### URLをエスケープする

```php
<!-- クエリ文字列に入れる値は urlencode する -->
<a href="/search.php?q=<?= urlencode($keyword) ?>">検索</a>

<!-- 両方必要な場合 -->
<a href="/search.php?q=<?= e(urlencode($keyword)) ?>">検索</a>
```

| 用途 | 使う関数 |
| --- | --- |
| HTMLの本文・属性値 | `htmlspecialchars()`（= `e()`） |
| URLのクエリ文字列 | `urlencode()` または `rawurlencode()` |
| JavaScript の中に埋める | `json_encode()`（後述） |

> ⚠️ **「エスケープ」は文脈によって方法が違います。**
> HTML用のエスケープをURLに使っても意味がなく、その逆も同じです。**どこに出すかで使い分けてください。**

### JavaScript に値を渡す

```php
<script>
  // ❌ 危険
  const name = "<?= $name ?>";

  // ✅ json_encode を使う
  const name = <?= json_encode($name, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  // ✅ データが多いなら data属性経由が安全
</script>

<div id="app" data-config="<?= e(json_encode($config)) ?>"></div>
<script>
  const config = JSON.parse(document.querySelector("#app").dataset.config);
</script>
```

> 💡 **`data-` 属性を経由するのが最も安全で、実務でもよく使われます。**
> PHPとJavaScriptを直接混ぜると、エスケープの考慮が複雑になります。

---

## ✍️ 手を動かす④ ─ ロジックと表示を分ける

**これが、このレッスンで最も重要な設計原則です。**

```php
<!-- ❌ 悪い例：ロジックと表示が混在している -->
<ul>
<?php
$pdo = new PDO(...);
$stmt = $pdo->query("SELECT * FROM items");
foreach ($stmt as $row) {
    if ($row["sold_out"]) continue;
    $price = floor($row["price"] * 1.1);
    echo "<li>" . htmlspecialchars($row["name"]) . " " . $price . "</li>";
}
?>
</ul>
```

```php
<!-- ✅ 良い例：上でロジック、下で表示 -->
<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/functions.php";

// ---------- ここでロジック ----------
$menu = load_menu();
$available = array_values(array_filter($menu, fn($m) => !$m["sold_out"]));
$total = array_sum(array_column($available, "price"));
?>
<!-- ---------- ここから表示 ---------- -->
<!DOCTYPE html>
<html lang="ja">
<head>...</head>
<body>
  <ul>
  <?php foreach ($available as $item): ?>
    <li><?= e($item["name"]) ?> — <?= yen($item["price"]) ?></li>
  <?php endforeach; ?>
  </ul>
</body>
</html>
```

**ルール**

1. **ファイルの上部で、データを準備する**（計算、絞り込み、整形）
2. **HTML部分では、`foreach` と `if` と出力しかしない**
3. HTML部分で計算やDB接続をしない

> 💡 **これが MVC（第5部の Laravel）の考え方の原型**です。
> 「データを用意する係」と「表示する係」を分ける。いま身につけておくと、第5部が楽になります。

---

## ✍️ 手を動かす⑤ ─ 共通部品を切り出す

第1部で「同じHTMLを何度も書くのは無駄」と感じたはずです。PHP で解決します。

### ファイル構成

```
htdocs/komorebi-php/
├─ index.php
├─ menu.php
├─ about.php
├─ lib/
│   └─ functions.php
├─ partials/
│   ├─ header.php
│   └─ footer.php
└─ css/
    └─ style.css
```

### `partials/header.php`（📋 コピペ可）

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/../lib/functions.php";

// 各ページで $page_title を定義してから include する
$page_title = $page_title ?? "KOMOREBI COFFEE";
$current    = $current ?? "";

$nav_items = [
    "index" => ["label" => "HOME",  "href" => "index.php"],
    "menu"  => ["label" => "MENU",  "href" => "menu.php"],
    "about" => ["label" => "ABOUT", "href" => "about.php"],
];
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

<header class="site-header">
  <div class="header-inner">
    <p class="logo"><a href="index.php">KOMOREBI COFFEE</a></p>
    <nav class="global-nav">
      <ul>
        <?php foreach ($nav_items as $key => $item): ?>
          <li>
            <a href="<?= e($item["href"]) ?>"
               class="<?= $current === $key ? "is-current" : "" ?>"
               <?= $current === $key ? 'aria-current="page"' : "" ?>>
              <?= e($item["label"]) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>

<main>
```

### `partials/footer.php`

```php
</main>

<footer class="site-footer">
  <p>&copy; <?= date("Y") ?> KOMOREBI COFFEE</p>
</footer>

</body>
</html>
```

### 使う側（`menu.php`）

```php
<?php
declare(strict_types=1);

// ---------- ロジック ----------
$menu = [
    ["name" => "ハンドドリップ", "price" => 600, "category" => "coffee", "sold_out" => false],
    ["name" => "カフェラテ",     "price" => 600, "category" => "coffee", "sold_out" => false],
    ["name" => "本日のケーキ",   "price" => 550, "category" => "food",   "sold_out" => true],
];

$page_title = "MENU｜KOMOREBI COFFEE";
$current    = "menu";

require_once __DIR__ . "/partials/header.php";
?>

<!-- ---------- 表示 ---------- -->
<section class="menu">
  <h1>MENU</h1>

  <?php if (count($menu) === 0): ?>
    <p class="empty">現在ご用意しているメニューはありません。</p>
  <?php else: ?>
    <ul class="menu-list">
      <?php foreach ($menu as $item): ?>
        <li class="menu-item <?= $item["sold_out"] ? "is-sold-out" : "" ?>">
          <h2><?= e($item["name"]) ?></h2>
          <p class="price"><?= yen($item["price"]) ?></p>
          <?php if ($item["sold_out"]): ?>
            <span class="badge">売り切れ</span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . "/partials/footer.php"; ?>
```

**これで、ヘッダーを直せば全ページに反映されます。**

> 💡 **第1部で「ヘッダーをコピペするのは無駄だ」と感じた問題が、ここで解決しました。**
> この体験が、フレームワーク（第5部）の価値を理解する土台になります。

---

## ✍️ 手を動かす⑥ ─ 部品を関数にする

繰り返し使う小さなHTMLは、関数にすると便利です。

```php
<?php
/**
 * メニューカード1枚のHTMLを返す
 */
function render_menu_item(array $item): string
{
    $sold_out_class = $item["sold_out"] ? " is-sold-out" : "";
    $badge = $item["sold_out"] ? '<span class="badge">売り切れ</span>' : "";

    return <<<HTML
      <li class="menu-item{$sold_out_class}">
        <h2>{$item["name"]}</h2>
        <p class="price">{$item["price"]}円</p>
        {$badge}
      </li>
    HTML;
}
```

> ⚠️ **この書き方には落とし穴があります。**
> `{$item["name"]}` を**エスケープしていません**。ヒアドキュメント内では `e()` を呼べないため、
> **事前にエスケープした変数を用意する**必要があります。

```php
<?php
function render_menu_item(array $item): string
{
    $name  = e($item["name"]);           // ← 先にエスケープ
    $price = yen((int) $item["price"]);
    $cls   = $item["sold_out"] ? " is-sold-out" : "";
    $badge = $item["sold_out"] ? '<span class="badge">売り切れ</span>' : "";

    return <<<HTML
      <li class="menu-item{$cls}">
        <h2>{$name}</h2>
        <p class="price">{$price}</p>
        {$badge}
      </li>
    HTML;
}
```

### より安全な方法：出力バッファリング

```php
<?php
function render_menu_item(array $item): string
{
    ob_start();          // ここから出力をためる
    ?>
    <li class="menu-item <?= $item["sold_out"] ? "is-sold-out" : "" ?>">
      <h2><?= e($item["name"]) ?></h2>
      <p class="price"><?= yen($item["price"]) ?></p>
      <?php if ($item["sold_out"]): ?>
        <span class="badge">売り切れ</span>
      <?php endif; ?>
    </li>
    <?php
    return ob_get_clean();   // ためた出力を文字列として返す
}
```

**`<?= e(...) ?>` が使えるので、エスケープ漏れが起きにくくなります。**

> 💡 `ob_start()` / `ob_get_clean()` は「出力バッファリング」という仕組みです。
> **テンプレートを関数化するときの定番テクニック**なので、覚えておいてください。

### さらに簡単な方法：include で部品化

```php
<!-- partials/menu-item.php -->
<li class="menu-item <?= $item["sold_out"] ? "is-sold-out" : "" ?>">
  <h2><?= e($item["name"]) ?></h2>
  <p class="price"><?= yen($item["price"]) ?></p>
</li>
```

```php
<!-- 使う側 -->
<?php foreach ($menu as $item): ?>
  <?php include __DIR__ . "/partials/menu-item.php"; ?>
<?php endforeach; ?>
```

**`include` されたファイルからは、呼び出し元の変数がそのまま見えます。**

> ⚠️ **ただし、どの変数に依存しているかがコードから読めません。**
> ファイルの先頭にコメントで書いておくのが親切です。
>
> ```php
> <?php
> /** @var array $item メニュー1件のデータ */
> ?>
> ```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| HTMLがそのまま表示される | `<?php` のつづりミス / ファイルが `.php` でない | 確認する |
| Parse error: unexpected end of file | `endforeach;` などの閉じ忘れ | 代替構文の対応を確認 |
| `else if` が動かない | 代替構文では使えない | `elseif`（1語） |
| 属性が壊れる | クォートで囲んでいない | `"<?= e($v) ?>"` |
| XSSが発生する | `e()` を通していない | すべての出力に `e()` |
| `Warning: Undefined variable` | include先で変数が未定義 | `??` でデフォルト値 |
| ヘッダーが二重に出る | `include` を2回している | `require_once` を使う |
| `headers already sent` | `<?php` の前に空白や改行がある | ファイルの1行目から `<?php` |

### `headers already sent` の対処

```php
（ここに空行や BOM があると発生）
<?php
header("Location: /");   // ❌ Warning: Cannot modify header information
```

**対処**

1. ファイルの**1行目**から `<?php` を書く
2. **PHPだけのファイルでは、末尾の `?>` を書かない**（後ろの改行が出力されるため）
3. 「UTF-8（BOMなし）」で保存する（VS Code の右下で確認）

> ⚠️ **`?>` を書かない**のは、PHPの世界での標準的な作法です。
> `lib/functions.php` や `config.php` のような、PHPコードだけのファイルには `?>` を書かないでください。

---

## 🤖 AIに聞いてみよう

### ① テンプレートをレビューさせる

```text
以下は、私が書いた PHP のテンプレートです。

（コードを貼る）

次の観点でレビューしてください。

1. XSS の危険がある箇所（エスケープ漏れ）
2. エスケープの方法が文脈に合っていない箇所
   （HTML用なのに URL に使っている、など）
3. ロジックと表示が混ざっている箇所
4. 0件のときの表示が抜けている箇所
5. アクセシビリティの問題

修正後のコードは書かず、指摘だけをお願いします。
特に 1 と 2 は、見落としがないよう網羅的にお願いします。
```

> 💡 **エスケープ漏れは、目視では見落とします。** AIレビューが最も効く領域の1つです。

### ② テンプレートの設計を相談する

```text
PHP で、フレームワークを使わずに複数ページのサイトを作っています。

【現状】
- 各ページで header.php と footer.php を require している
- ページごとに $page_title を定義している

【困っていること】
- ページ固有の CSS / JS を読み込みたいが、header.php をどう変えるべきか
- ナビの「現在のページ」判定を、もっとよい方法にしたい
- 同じ構造のカードを複数ページで使いたい

それぞれについて、フレームワークを使わない範囲での
設計方針を教えてください。コードは最小限で構いません。
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）

第1部で作ったカフェLPを、**PHP化**してください。

- [ ] `komorebi-php/` フォルダを作り、`index.html` を `index.php` にコピー
- [ ] `partials/header.php` と `partials/footer.php` を切り出す
- [ ] `lib/functions.php` に `e()` と `yen()` を置く
- [ ] メニューのデータを `$menu` 配列にし、`foreach` で出力する
- [ ] `menu.php` `about.php` を作り、ヘッダー・フッターを共有する
- [ ] ナビの現在ページに `is-current` クラスと `aria-current="page"` を付ける
- [ ] **すべての出力に `e()` を通す**

### 演習2（必須）─ エスケープ漏れを探す

以下のコードには、**5か所のエスケープ漏れ・誤用**があります。すべて指摘してください。

```php
<div class="profile">
  <h2><?= $user["name"] ?></h2>
  <img src="<?= $user["avatar"] ?>" alt="<?= e($user["name"]) ?>">
  <p><?= nl2br($user["bio"]) ?></p>
  <a href="/search?q=<?= e($user["name"]) ?>">この人の投稿を検索</a>
  <script>
    const userName = "<?= e($user["name"]) ?>";
  </script>
</div>
```

<details>
<summary>答えを見る</summary>

1. **`<?= $user["name"] ?>`** — エスケープしていない。`e()` を通す
2. **`src="<?= $user["avatar"] ?>"`** — エスケープしていない。加えて、`javascript:` スキームのURLを弾く検証も必要
3. **`nl2br($user["bio"])`** — `nl2br` はエスケープしない。**先にエスケープしてから** `nl2br(e($user["bio"]))` の順にする
4. **`?q=<?= e($user["name"]) ?>`** — URLのクエリには `urlencode()` が必要。`e()` だけでは `&` や `=` が正しく処理されない
5. **`const userName = "<?= e($user["name"]) ?>"`** — JavaScript の文字列リテラルの中では、HTMLエスケープでは不十分。
   `<?= json_encode($user["name"]) ?>` を使う（クォートも含めて出力される）

**修正版**

```php
<div class="profile">
  <h2><?= e($user["name"]) ?></h2>
  <img src="<?= e($user["avatar"]) ?>" alt="<?= e($user["name"]) ?>">
  <p><?= nl2br(e($user["bio"])) ?></p>
  <a href="/search?q=<?= e(urlencode($user["name"])) ?>">この人の投稿を検索</a>
  <script>
    const userName = <?= json_encode($user["name"], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
</div>
```

**ポイント**：`nl2br(e($x))` の**順序**が重要です。逆にすると `<br>` までエスケープされます。

</details>

### 演習3（挑戦）

`partials/` に、再利用できる部品を作ってください。

- [ ] `partials/menu-card.php` — メニュー1件のカード（`$item` を受け取る）
- [ ] `partials/alert.php` — 通知メッセージ（`$type`（success/error）と `$message` を受け取る）
- [ ] `partials/pagination.php` — ページ送り（`$current_page` と `$total_pages` を受け取る）

**それぞれのファイル先頭に、必要な変数を `@var` コメントで書いてください。**

---

## ✅ 章末チェック

- [ ] `<?= ?>` が `<?php echo ?>` の省略形だと知っている
- [ ] すべての出力に `e()` を通す習慣がついた
- [ ] `htmlspecialchars` に3つの引数が必要な理由を言える
- [ ] 代替構文（`endif` / `endforeach`）を使える
- [ ] HTML / URL / JavaScript でエスケープ方法が違うことを知っている
- [ ] ロジックと表示を上下に分けている
- [ ] `require_once` でヘッダー・フッターを共有できた
- [ ] PHPだけのファイルで `?>` を書かない理由を言える
- [ ] `nl2br(e($x))` の順序の意味を説明できる

---

**前 → [3-4 関数と組み込み関数の調べ方](03-04-functions.md)　｜　次 → [3-6 GET / POST でデータを受け取る](03-06-get-post.md)**
