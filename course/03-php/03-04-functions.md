# 3-4 関数と組み込み関数の調べ方

> 🎯 **このレッスンのゴール**
> - 型宣言つきの関数を書ける
> - PHPの公式ドキュメントを読めるようになる
> - 存在しない関数（AIのハルシネーション）を自力で見抜ける

所要 120分 / 難度 🟡
完成コード: [`code/03-04/`](../code/03-04/)

---

## ✍️ 手を動かす① ─ 関数の定義

```php
<?php
declare(strict_types=1);

function greet(string $name): string
{
    return "こんにちは、{$name}さん";
}

echo greet("太郎");
```

**JavaScript との違い**

| 項目 | JavaScript | PHP |
| --- | --- | --- |
| 定義 | `const f = () => {}` | `function f() {}` |
| 引数の型 | 書けない（TSなら書ける） | **書ける** |
| 戻り値の型 | 同上 | **書ける** |
| 巻き上げ | `function` 宣言のみ | **`function` 宣言はされる** |
| デフォルト引数 | あり | あり |
| 引数の数が違う | エラーにならない | **足りないとエラー** |

```php
<?php
function add(int $a, int $b): int { return $a + $b; }

add(1);          // ❌ ArgumentCountError（JavaScriptと違い、エラーになる）
add(1, 2, 3);    // ❌ 余分な引数もエラー（PHP 8以降）
```

> 💡 **PHPのほうが厳格です。** これは良いことで、バグが早期に見つかります。

### デフォルト引数と名前付き引数

```php
<?php
function make_button(
    string $label,
    string $type = "button",
    bool $disabled = false,
    string $class = "btn",
): string {
    $attr = $disabled ? " disabled" : "";
    return "<button type=\"{$type}\" class=\"{$class}\"{$attr}>{$label}</button>";
}

// 位置で渡す
echo make_button("送信", "submit");

// 名前付き引数（PHP 8以降）— 順番を気にしなくてよい
echo make_button(label: "送信", disabled: true);
echo make_button("送信", class: "btn btn-primary");
```

> 💡 **名前付き引数は、引数が多い関数で威力を発揮します。**
> `make_button("送信", "button", false, "btn")` より、
> `make_button(label: "送信", class: "btn")` のほうが、何を指定しているか明確です。

### 型宣言の一覧

```php
<?php
declare(strict_types=1);

function example(
    int $a,
    float $b,
    string $c,
    bool $d,
    array $e,
    callable $f,          // 関数
    ?string $g = null,    // null を許容
    int|string $h = 0,    // 複数の型（PHP 8以降）
    mixed $i = null,      // 何でも
): void {                 // void = 何も返さない
    // ...
}

// 戻り値の型
function f1(): int { return 1; }
function f2(): ?string { return null; }        // null を返す可能性あり
function f3(): array { return []; }
function f4(): void { }                        // return しない
function f5(): never { throw new Exception(); } // 必ず例外か exit（PHP 8.1以降）
```

> ⚠️ **`?string` と `string|null` は同じ意味**です。`?` のほうが短いので、こちらが好まれます。

---

## ✍️ 手を動かす② ─ 引数の渡し方

```php
<?php
// 値渡し（デフォルト）— 元は変わらない
function add_one(int $n): int {
    $n++;
    return $n;
}
$x = 5;
add_one($x);
echo $x;   // 5（変わっていない）

// 参照渡し（&）— 元が変わる
function add_one_ref(int &$n): void {
    $n++;
}
$y = 5;
add_one_ref($y);
echo $y;   // 6
```

> ⚠️ **配列も「値渡し」です。** JavaScript と違い、関数に渡した配列を中で変更しても、呼び出し元には影響しません。
>
> ```php
> function f(array $a) { $a[] = 4; }
> $arr = [1,2,3];
> f($arr);
> print_r($arr);   // [1,2,3]  ← 変わらない
> ```
>
> **これは JavaScript から来ると驚くポイントです。** PHPのほうが安全な設計です。
>
> ただし、**オブジェクトは参照で渡されます**（3-10で扱います）。

### 可変長引数

```php
<?php
function sum(int ...$numbers): int
{
    return array_sum($numbers);
}

echo sum(1, 2, 3);          // 6
echo sum(...[1, 2, 3, 4]);  // 10（配列を展開して渡す）
```

---

## ✍️ 手を動かす③ ─ 無名関数とアロー関数

```php
<?php
// 無名関数（クロージャ）
$double = function (int $n): int {
    return $n * 2;
};
echo $double(5);

// アロー関数（PHP 7.4以降）— 1式のみ、return 省略
$double = fn(int $n): int => $n * 2;

// 配列関数で使う
$prices = [100, 200, 300];
$with_tax = array_map(fn($p) => (int) floor($p * 1.1), $prices);
```

### 外の変数を使う（ここが JavaScript と大きく違う）

```php
<?php
$rate = 0.1;

// ❌ 無名関数は、外の変数を自動では見られない
$f = function (int $p) {
    return $p * (1 + $rate);   // Warning: Undefined variable $rate
};

// ✅ use で明示的に取り込む
$f = function (int $p) use ($rate) {
    return $p * (1 + $rate);
};

// ✅ アロー関数は自動で取り込む（こちらが楽）
$f = fn(int $p) => $p * (1 + $rate);
```

> ⚠️ **これは PHP 特有の仕様です。**
> JavaScript では外側の変数が自然に見えますが、PHP の無名関数は `use` が必要です。
>
> **アロー関数（`fn`）なら自動で取り込む**ので、短い処理はアロー関数を使ってください。

### `use` で参照を取り込む

```php
<?php
$count = 0;

$increment = function () use (&$count) {   // ← & を付けると参照
    $count++;
};

$increment();
$increment();
echo $count;   // 2
```

> 💡 アロー関数（`fn`）は**常に値でコピー**します。外の変数を書き換えたいときは、`use (&$x)` の無名関数を使ってください。

---

## ✍️ 手を動かす④ ─ 例外を投げる

```php
<?php
declare(strict_types=1);

function divide(int $a, int $b): float
{
    if ($b === 0) {
        throw new InvalidArgumentException("0で割ることはできません");
    }
    return $a / $b;
}

try {
    echo divide(10, 0);
} catch (InvalidArgumentException $e) {
    echo "エラー: " . $e->getMessage();
} catch (Throwable $e) {
    echo "予期しないエラー: " . $e->getMessage();
} finally {
    echo "必ず実行される";
}
```

### 主な例外クラス

| クラス | 使う場面 |
| --- | --- |
| `InvalidArgumentException` | 引数が不正 |
| `RuntimeException` | 実行時の想定外（ファイルが開けない等） |
| `LogicException` | プログラムのロジックの誤り |
| `Exception` | 一般的な例外 |
| `Throwable` | **すべての例外・エラーの親**（`catch` で使う） |

> 💡 **`catch (Throwable $e)` が最も広く捕まえられます。**
> PHP 7 以降、`Error`（TypeError など）は `Exception` を継承していないため、
> `catch (Exception $e)` では捕まりません。**`Throwable` を使ってください。**

### 戻り値で返すか、例外を投げるか

| 状況 | 方針 |
| --- | --- |
| 「入力が不正」は想定内 | **戻り値で返す**（`null` やエラーメッセージ） |
| 「そもそも呼び出し方が間違っている」 | **例外を投げる** |
| 「外部要因で処理できない」（DB接続失敗など） | **例外を投げる** |

```php
<?php
// 想定内 → 戻り値
function validate_email(string $email): ?string
{
    if (!str_contains($email, "@")) return "メールアドレスの形式が正しくありません";
    return null;
}

// 想定外 → 例外
function load_config(string $path): array
{
    if (!file_exists($path)) {
        throw new RuntimeException("設定ファイルが見つかりません: {$path}");
    }
    return require $path;
}
```

---

## ✍️ 手を動かす⑤ ─ 公式ドキュメントを読む

**これが、このレッスンで最も重要な部分です。**

### php.net の使い方

**URLの規則を覚えてください。**

```
https://www.php.net/manual/ja/function.関数名.php
```

`_`（アンダースコア）は `-`（ハイフン）に変換します。

| 関数 | URL |
| --- | --- |
| `strlen` | `https://www.php.net/manual/ja/function.strlen.php` |
| `array_map` | `https://www.php.net/manual/ja/function.array-map.php` |
| `mb_substr` | `https://www.php.net/manual/ja/function.mb-substr.php` |

> 💡 **ページが存在しなければ、その関数は存在しません。**
> これが、AIのハルシネーションを見抜く最速の方法です（第0部 0-7）。

### ドキュメントの読み方

```
array_slice(array $array, int $offset, ?int $length = null, bool $preserve_keys = false): array
└──①──┘ └──────────────────②────────────────────────────────────────┘  └─③─┘
```

| 部分 | 意味 |
| --- | --- |
| ① 関数名 | — |
| ② 引数リスト | 型・名前・デフォルト値 |
| ③ 戻り値の型 | — |
| `?int` | null を渡せる |
| `= null` | 省略できる（デフォルト値） |

**必ず確認すべき3か所**

1. **引数の順番と型** — AIは名前は合っていても順番を間違えます
2. **戻り値** — 失敗時に `false` を返すのか、`null` を返すのか、例外を投げるのか
3. **ページ下部のユーザーコメント** — 実務での落とし穴が書かれていることがあります

### 失敗時の戻り値に注意

```php
<?php
// strpos は「見つからない」とき false を返す
$pos = strpos("hello", "z");
var_dump($pos);            // false

if ($pos == false) { }     // ❌ 位置0で見つかった場合も true になる
if ($pos === false) { }    // ✅ 厳密比較
```

> ⚠️ **PHP の関数は「失敗時に `false`」を返すものが多い**です。
> `0` や `""` と `false` を区別するため、**必ず `===` で比較**してください。
>
> これは PHP で最も有名な罠の1つです。

### 覚えるべきではない、調べるべき

**組み込み関数は 1000 個以上あります。暗記は不可能です。**

```
「文字列を〇〇したい」
   ↓
① php.net の検索窓で「string」→ 文字列関数の一覧
② Google で「PHP 文字列 〇〇」
③ AIに聞く → 必ず php.net で実在確認
```

**よく使う関数のカテゴリ**

| カテゴリ | URL |
| --- | --- |
| 文字列 | `https://www.php.net/manual/ja/ref.strings.php` |
| 配列 | `https://www.php.net/manual/ja/ref.array.php` |
| 日付 | `https://www.php.net/manual/ja/ref.datetime.php` |
| ファイル | `https://www.php.net/manual/ja/ref.filesystem.php` |
| マルチバイト | `https://www.php.net/manual/ja/ref.mbstring.php` |

---

## ✍️ 手を動かす⑥ ─ 実践：ユーティリティ関数を作る

`php-lesson/lib/functions.php`（📋 完成形は `code/03-04/lib/functions.php`）

```php
<?php
declare(strict_types=1);

/**
 * HTMLエスケープ（XSS対策）
 * PHPで最も重要な関数。すべての出力に必ず通す。
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

/**
 * 全角スペースも含めて前後の空白を除去する
 */
function trim_ja(?string $value): string
{
    return trim($value ?? "", " \t\n\r\0\x0B　");
}

/**
 * 未入力かどうか（"0" は未入力ではない）
 */
function is_blank(?string $value): bool
{
    return trim_ja($value) === "";
}

/**
 * 文字列を指定文字数で切り詰める
 */
function truncate(string $text, int $length, string $suffix = "…"): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * 金額を「1,234円」の形式にする
 */
function yen(int $amount): string
{
    return number_format($amount) . "円";
}

/**
 * 配列から最初にマッチする要素を返す（PHPには array_find がない）
 */
function array_find(array $array, callable $callback): mixed
{
    foreach ($array as $key => $value) {
        if ($callback($value, $key)) {
            return $value;
        }
    }
    return null;
}

/**
 * 配列を指定キーでグループ化する
 * @return array<string, array>
 */
function array_group_by(array $array, string $key): array
{
    $result = [];
    foreach ($array as $item) {
        $result[$item[$key]][] = $item;
    }
    return $result;
}

/**
 * 相対的な日時表現にする（「3分前」など）
 */
function time_ago(int $timestamp): string
{
    $diff = time() - $timestamp;

    return match (true) {
        $diff < 60        => "たった今",
        $diff < 3600      => floor($diff / 60) . "分前",
        $diff < 86400     => floor($diff / 3600) . "時間前",
        $diff < 86400 * 7 => floor($diff / 86400) . "日前",
        default           => date("Y年n月j日", $timestamp),
    };
}
```

### `match (true)` のテクニック

```php
match (true) {
    $diff < 60   => "たった今",
    $diff < 3600 => "○分前",
    default      => "...",
}
```

**`match` は本来「値の一致」を見ますが、`match (true)` にすると「最初に true になった条件」を選べます。**
`if / elseif` の連鎖より読みやすく書けます。

> 💡 **この書き方は実務でよく使われます。** 覚えておいてください。

### 使う側

```php
<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/functions.php";

$user_input = '<script>alert("XSS")</script>';

echo e($user_input);              // &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
echo truncate("こんにちは世界", 5); // こんにちは…
echo yen(1234);                   // 1,234円
echo time_ago(time() - 300);      // 5分前
```

> ⚠️ **`e()` 関数は、次のレッスンから毎回使います。**
> **PHPで「出力するときは必ず `e()` を通す」** を体に染み込ませてください。
> これを怠ると、XSS 脆弱性が生まれます（3-7で詳しく扱います）。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `ArgumentCountError` | 引数が足りない | デフォルト値を付けるか、渡す |
| `TypeError` | 型が違う | `strict_types` のおかげ。型を合わせる |
| 無名関数で外の変数が使えない | PHPは自動で取り込まない | `use ($var)` か `fn` を使う |
| 配列を関数で変更しても反映されない | PHPは値渡し | 戻り値で返すか `&` を使う |
| `strpos` の判定がおかしい | 失敗時 `false`、位置0も falsy | `=== false` で比較 |
| 存在しない関数を呼んでいる | AIのハルシネーション / タイポ | php.net で確認 |
| `catch (Exception)` で捕まらない | TypeError などは Error 系 | `catch (Throwable)` |

---

## 🤖 AIに聞いてみよう

### ① 関数を探させ、必ず検証する

```text
PHPで「（やりたいこと）」を実現したいです。

1. 使える組み込み関数を3つ挙げてください
2. それぞれについて、php.net の正確なURLを教えてください
3. 引数の順番と型、戻り値（特に失敗時に何が返るか）を明記してください
4. それぞれの使い分けと、注意点を教えてください
```

> ⚠️ **返ってきた関数名は、必ず php.net で実在確認してください。**
> URLを開いて、引数の順番が回答と一致しているかも確認します。
> **AIは関数名は合っていても、引数の順番を間違えることがあります。**

### ② 自作関数をレビューさせる

```text
以下は、私が作った PHP のユーティリティ関数集です。

（コードを貼る）

次の観点でレビューしてください。

1. 型宣言が不足している / 不適切な箇所
2. エッジケース（null、空文字、極端な値、日本語）で壊れる箇所
3. 車輪の再発明（PHPの標準関数で済むもの）
4. 命名がわかりにくい箇所
5. セキュリティ上の問題

修正後のコードは書かず、指摘だけをお願いします。
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）─ ドキュメントを引く

以下の関数について、**php.net で確認**して表を埋めてください。

| 関数 | 引数の順番 | 戻り値 | 失敗時 |
| --- | --- | --- | --- |
| `str_replace` | | | |
| `array_search` | | | |
| `explode` | | | |
| `date` | | | |
| `file_get_contents` | | | |

<details>
<summary>答えを見る</summary>

| 関数 | 引数の順番 | 戻り値 | 失敗時 |
| --- | --- | --- | --- |
| `str_replace` | `(検索, 置換, 対象)` | 置換後の文字列 | — |
| `array_search` | `(値, 配列, 厳密)` | **キー** | **`false`** |
| `explode` | `(区切り文字, 文字列)` | 配列 | — |
| `date` | `(フォーマット, タイムスタンプ)` | 文字列 | — |
| `file_get_contents` | `(パス)` | 文字列 | **`false`** + Warning |

**`array_search` と `file_get_contents` は失敗時に `false` を返します。**
`=== false` で判定してください。

</details>

### 演習2（必須）─ 存在しない関数を見抜く

以下のうち、**PHPに実在しない関数**はどれですか。php.net で確認してください。

```
1. str_reverse()
2. strrev()
3. array_find()
4. array_search()
5. mb_str_pad()
6. str_pad()
7. array_first()
8. array_key_first()
9. str_contains()
10. string_contains()
```

<details>
<summary>答えを見る</summary>

**実在しない**：1（正: `strrev`）、3（自作が必要）、7（正: `array_key_first`）、10（正: `str_contains`）

**実在する**：2, 4, 6, 8, 9

**`mb_str_pad()` は PHP 8.3 で追加されました。** バージョンによって存在するかが変わる例です。
「AIが出した関数が動かない」原因が、**PHPのバージョン違い**であることもあります。

</details>

### 演習3（挑戦）

`lib/functions.php` に、以下の関数を追加してください。

```php
<?php
/**
 * 郵便番号を「123-4567」の形式に整形する
 * 入力が7桁でなければ null を返す
 */
function format_zip(?string $zip): ?string { }

/**
 * 電話番号から数字以外を除去する
 */
function normalize_tel(?string $tel): string { }

/**
 * 配列を指定キーで並び替える（元の配列は変更しない）
 * @param string $direction "asc" | "desc"
 */
function array_sort_by(array $array, string $key, string $direction = "asc"): array { }

/**
 * 秒数を「1時間23分」の形式にする
 */
function format_duration(int $seconds): string { }

/**
 * 文字列がすべて半角英数字か
 */
function is_alnum(string $value): bool { }
```

**すべてに `declare(strict_types=1)` の型宣言と、テストケースを付けてください。**

---

## ✅ 章末チェック

- [ ] 型宣言つきの関数を書ける
- [ ] 名前付き引数を使える
- [ ] PHPの配列が値渡しであることを知っている
- [ ] 無名関数で `use` が必要な理由を説明できる
- [ ] `fn` なら自動で取り込むことを知っている
- [ ] `catch (Throwable $e)` を使う理由を言える
- [ ] php.net のURL規則を覚えた
- [ ] 「失敗時 `false`」の関数を `===` で判定する理由を言える
- [ ] `e()` 関数を作り、出力に必ず通すと決めた

---

**前 → [3-3 条件分岐・繰り返し・配列](03-03-array.md)　｜　次 → [3-5 HTMLとPHPを混ぜて書く](03-05-html-php.md)**
