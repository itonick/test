# 3-2 変数・型・演算子

> 🎯 **このレッスンのゴール**
> - PHPの変数と型を、JavaScriptとの違いを意識して使える
> - 文字列の扱い（連結・展開・マルチバイト）を身につける
> - 型のゆるさによる事故を避けられる

所要 90分 / 難度 🟢
完成コード: [`code/03-02/`](../code/03-02/)

---

## 📖 JavaScript との対比で覚える

| 項目 | JavaScript | PHP |
| --- | --- | --- |
| 変数 | `const x = 1;` | `$x = 1;` （**`$` が必須**） |
| 定数 | `const MAX = 10;` | `define("MAX", 10);` / `const MAX = 10;` |
| 文字列連結 | `"a" + "b"` | `"a" . "b"` （**ドット**） |
| 変数展開 | `` `Hi ${name}` `` | `"Hi $name"` （**ダブルクォート内**） |
| 厳密比較 | `===` | `===` |
| 配列 | `[1, 2, 3]` | `[1, 2, 3]` |
| 連想配列 | `{a: 1}` | `["a" => 1]` |
| 出力 | `console.log()` | `echo` / `var_dump()` |
| null合体 | `??` | `??` |
| 行末 | セミコロン推奨 | **セミコロン必須** |

> ⚠️ **最初の1週間は、`$` を忘れて Parse error を出します。** これは全員が通る道です。

---

## ✍️ 手を動かす① ─ 変数

```php
<?php
$name = "山田太郎";
$age = 20;
$price = 1980.5;
$isMember = true;
$nothing = null;

// 変数名のルール
$userName    = "OK";   // 英数字とアンダースコア
$user_name   = "OK";   // PHPでは snake_case が慣習
$_private    = "OK";   // アンダースコア開始もOK
// $2user    = "NG";   // 数字始まりは不可
// $user-name = "NG";  // ハイフンは不可
```

### 命名の慣習（JavaScriptと違う）

| 対象 | PHP の慣習 | 例 |
| --- | --- | --- |
| 変数 | **snake_case** | `$user_name` `$total_price` |
| 関数 | **snake_case** | `function get_user_name()` |
| クラス | PascalCase | `class UserAccount` |
| メソッド | **camelCase** | `public function getUserName()` |
| 定数 | UPPER_SNAKE | `const TAX_RATE = 0.1;` |

> 💡 **PHPの世界では、変数と関数は snake_case、クラスのメソッドは camelCase** という、
> やや変則的な慣習があります（PSR-12 という規約で定められています）。
>
> **この教材では、変数を snake_case、関数を snake_case、クラスのメソッドを camelCase で統一します。**
> ただし、チームによって違うので、**参加したプロジェクトのルールに従ってください。**

### 変数は「宣言」せずに使える

```php
<?php
$x = 1;      // いきなり代入できる（const / let のような宣言がない）
```

**これは便利ですが、危険でもあります。**

```php
<?php
$user_name = "太郎";
echo $username;   // ❌ Warning: Undefined variable（タイポに気づきにくい）
```

> ⚠️ **JavaScript の `ReferenceError` と違い、PHP は Warning で済ませて処理を続けます。**
> `display_errors` を On にしていないと、**気づかずに空文字が出力されます**。
> 3-1 の設定が、ここで効いてきます。

### 定数

```php
<?php
const TAX_RATE = 0.1;              // ファイルのトップレベルで使う
define("SITE_NAME", "KOMOREBI");   // 条件分岐の中でも使える

echo TAX_RATE;    // ← $ は付けない
echo SITE_NAME;
```

> ⚠️ **定数には `$` を付けません。** ここも混乱しやすいポイントです。

---

## ✍️ 手を動かす② ─ 型

```php
<?php
$s = "文字列";      // string
$i = 42;            // int
$f = 3.14;          // float
$b = true;          // bool
$a = [1, 2, 3];     // array
$n = null;          // null

var_dump($s, $i, $f, $b, $a, $n);

// 型の確認
echo gettype($i);        // integer
var_dump(is_int($i));    // true
var_dump(is_string($s)); // true
var_dump(is_array($a));  // true
var_dump(is_null($n));   // true
var_dump(is_numeric("123"));  // true（数値として解釈できる文字列）
```

### 型変換

```php
<?php
// 明示的に変換する
$n = (int)   "123";      // 123
$f = (float) "12.5";     // 12.5
$s = (string) 123;       // "123"
$b = (bool)  "";         // false
$a = (array) "abc";      // ["abc"]

// 関数でも変換できる
$n = intval("123px");    // 123（数字部分だけ）
$f = floatval("12.5em"); // 12.5
```

### 偽（falsy）になる値

```php
false
0
0.0
""       // 空文字
"0"      // ← ★ 文字列の "0" も偽！
[]       // 空配列
null
```

> ⚠️ **`"0"` が偽になるのは PHP 特有です。** JavaScript では `"0"` は真です。
>
> ```php
> if ("0") { echo "実行されない"; }   // PHP
> ```
> ```javascript
> if ("0") { console.log("実行される"); }   // JavaScript
> ```
>
> **フォームで「0」が入力されたときに、未入力として扱ってしまう**バグの原因になります。

### 正しい「未入力」の判定

```php
<?php
$value = $_POST["quantity"] ?? "";

// ❌ "0" が入力されたときも「未入力」になってしまう
if (!$value) { echo "未入力です"; }

// ❌ empty() も同じ問題（"0" が empty になる）
if (empty($value)) { echo "未入力です"; }

// ✅ 明示的に比較する
if ($value === "") { echo "未入力です"; }

// ✅ 空白だけも弾きたい場合
if (trim($value) === "") { echo "未入力です"; }
```

> 💡 **`empty()` は便利に見えて、事故のもとです。**
> 「未入力かどうか」を判定したいなら、`=== ""` と書いてください。**意図が明確になります。**

### `isset` / `empty` / `is_null` の違い

```php
<?php
$a = "値";
$b = "";
$c = "0";
$d = null;
// $e は未定義

//              isset()  empty()  is_null()   === ""
// $a = "値"     true     false    false       false
// $b = ""       true     true     false       true
// $c = "0"      true     true     false       false  ← 注意
// $d = null     false    true     true        false
// $e 未定義      false    true     （Warning）  （Warning）
```

**使い分け**

| やりたいこと | 使うもの |
| --- | --- |
| キーが存在するか（配列・`$_POST`） | `isset()` または `array_key_exists()` |
| 未入力かどうか | `=== ""` または `trim(...) === ""` |
| null かどうか | `=== null` |
| デフォルト値を使う | `?? "default"` |

> ⚠️ **`isset($arr["key"])` は、値が `null` のとき `false` を返します。**
> 「キーは存在するが値が null」を区別したいときは `array_key_exists("key", $arr)` を使ってください。

---

## ✍️ 手を動かす③ ─ 文字列

### 連結は `.`（ドット）

```php
<?php
$name = "太郎";

echo "こんにちは、" . $name . "さん";
echo "<br>";

// .= で追記できる
$msg = "こんにちは、";
$msg .= $name;
$msg .= "さん";
echo $msg;
```

> ⚠️ **`+` で連結しようとすると、数値として計算されます。**
>
> ```php
> echo "a" + "b";   // ❌ TypeError（PHP 8以降）
> echo 1 + "2";     // 3（数値として計算される）
> ```
>
> JavaScript から来ると必ず間違えます。**PHP の連結はドット。**

### クォートの違い（重要）

```php
<?php
$name = "太郎";

echo "こんにちは、$name さん";       // こんにちは、太郎 さん  ← 変数が展開される
echo 'こんにちは、$name さん';       // こんにちは、$name さん ← そのまま出る

echo "改行\nタブ\t";                 // エスケープシーケンスが効く
echo '改行\nタブ\t';                 // そのまま \n \t と出る
```

| クォート | 変数展開 | エスケープシーケンス | 速度 |
| --- | --- | --- | --- |
| `"..."` ダブル | **する** | **する**（`\n` `\t` `\\` `\$`） | わずかに遅い |
| `'...'` シングル | しない | `\\` と `\'` のみ | わずかに速い |

### 変数展開の落とし穴

```php
<?php
$user = ["name" => "太郎"];
$count = 3;

echo "名前は $user[name] です";      // ✅ 動く（クォートなしなら）
echo "名前は {$user['name']} です";  // ✅ 推奨（波括弧で囲む）
echo "名前は $user['name'] です";    // ❌ Parse error

echo "$count個";                     // ❌ $count個 という変数を探してしまう
echo "{$count}個";                   // ✅
```

> 💡 **迷ったら `{}` で囲む。** これを習慣にすれば事故が起きません。

### ヒアドキュメント（複数行）

```php
<?php
$name = "太郎";

// 変数展開する（ダブルクォート相当）
$html = <<<HTML
<div class="card">
  <h3>{$name}さん</h3>
  <p>ようこそ</p>
</div>
HTML;

echo $html;

// 変数展開しない（シングルクォート相当）
$text = <<<'TEXT'
これは $name のまま出力されます
TEXT;
```

> ⚠️ **終端の `HTML;` は、行頭から書くか、開始行と同じインデントに揃えます。**
> PHP 7.3 以降はインデントできますが、揃っていないとエラーになります。

---

## ✍️ 手を動かす④ ─ 日本語（マルチバイト）の扱い

**これは日本語サイトを作るうえで必須の知識です。**

```php
<?php
$text = "こんにちは";

echo strlen($text);       // 15  ← バイト数（UTF-8では1文字3バイト）
echo mb_strlen($text);    // 5   ← 文字数 ✅

echo substr($text, 0, 3);      // こ    ← 3バイト = 1文字
echo mb_substr($text, 0, 3);   // こんに ← 3文字 ✅
```

### `mb_` を付けるべき関数

| ❌ 使わない | ✅ 使う | 用途 |
| --- | --- | --- |
| `strlen()` | `mb_strlen()` | 文字数 |
| `substr()` | `mb_substr()` | 切り出し |
| `strpos()` | `mb_strpos()` | 位置検索 |
| `strtoupper()` | `mb_strtoupper()` | 大文字化 |
| `str_split()` | `mb_str_split()` | 1文字ずつ分割 |

> ⚠️ **日本語を扱うなら、文字数系の関数は必ず `mb_` を付けてください。**
> 付けないと、「50文字以内」のチェックが「50バイト以内」（＝日本語16文字）になります。
>
> ただし、`str_replace()` や `trim($s)`（第2引数なし）は `mb_` 不要です
> （UTF-8のバイト列をそのまま扱っても、境界を壊さないため）。
> **例外は `trim($s, "マスク")` の第2引数**で、ここに全角文字を入れると壊れます（後述）。

> 🆘 **ここで詰まったら**（文字数チェックがおかしい／日本語が途中で切れる・文字化けする）
> - **文字数が合わない**：`strlen`（バイト数）を使っている。文字数は `mb_strlen`。切り出しは `mb_substr`
> - **日本語が「ラ」などで欠ける／文字化けする**：`trim($s, " 　")` のように**第2引数に全角スペースを入れた**のが原因（バイト単位で処理され、日本語が壊れます）。前後の全角空白も消したいときは `preg_replace('/\A[\s　]+|[\s　]+\z/u', "", $s)` を使う
> - **画面全体が文字化け**：`<meta charset="UTF-8">`、ファイルの保存文字コード（UTF-8）、DB接続の `charset=utf8mb4` を確認
> - **直らなければ、AIにこう聞く**（該当コードと「入力→期待→実際」を貼る）：
>   「PHPで日本語の文字数（または切り出し）が意図どおりになりません。マルチバイトの観点で原因を教えてください」

### よく使う文字列関数

```php
<?php
$s = "  Hello, World  ";

echo trim($s);                    // "Hello, World"（前後の空白除去）
echo str_replace("World", "PHP", $s);
echo strtolower("ABC");           // abc
echo str_repeat("-", 20);         // --------------------
echo str_pad("5", 3, "0", STR_PAD_LEFT);  // 005
echo number_format(1234567);      // 1,234,567
echo number_format(1234.567, 2);  // 1,234.57

// 分割と結合
$parts = explode(",", "a,b,c");   // ["a", "b", "c"]
echo implode("・", $parts);        // a・b・c

// 含むか
var_dump(str_contains("hello", "ell"));    // true（PHP 8以降）
var_dump(str_starts_with("hello", "he"));  // true
var_dump(str_ends_with("hello", "lo"));    // true
```

> ⚠️ **`trim()` は、全角スペースを除去しません。しかも「第2引数に全角スペースを足す」対処は危険です。**
>
> ```php
> trim("　あ　");                              // "　あ　"（変わらない）
> trim("　あ　", " \t\n\r\0\x0B　");           // "あ"（一見うまくいく）
> ```
>
> **しかし、`trim()` の第2引数はバイト単位で処理されます。**
> 全角スペース `　` は UTF-8 で `E3 80 80` の3バイト。この対処だと、
> **先頭や末尾が「E3」や「80」で始まる文字まで壊れます。**
>
> ```php
> // 「ラ」は E3 83 A9。先頭の E3 が全角スペースのバイトと一致し、削られる
> trim("ラテ", " \t\n\r\0\x0B　");             // "�テ" 😱 文字化け
> ```
>
> **正しい対処は、正規表現の `/u`（UTF-8モード）で「文字単位」に処理することです。**
>
> ```php
> function trim_ja(string $s): string {
>     return preg_replace('/\A[\s　]+|[\s　]+\z/u', "", $s) ?? "";
> }
> trim_ja("　ラテ　");   // "ラテ" ✅
> ```
>
> 💡 これは「マルチバイトの罠」の代表例です。**バイト単位で動く関数に、
> マルチバイト文字を混ぜてはいけません。** `mb_` 系がない `trim()` では、
> 文字マスクに ASCII 以外を入れないでください。

---

## ✍️ 手を動かす⑤ ─ 演算子

```php
<?php
// 算術
echo 7 + 3;    // 10
echo 7 - 3;    // 4
echo 7 * 3;    // 21
echo 7 / 3;    // 2.3333...
echo 7 % 3;    // 1（余り）
echo 7 ** 3;   // 343（べき乗）
echo intdiv(7, 3);  // 2（整数除算）

// 代入
$x = 5;
$x += 3;   // 8
$x -= 2;   // 6
$x *= 2;   // 12
$x /= 4;   // 3
$x .= "!"; // "3!"（文字列連結）
$x = 5;
$x++;      // 6
$x--;      // 5

// 比較
var_dump(1 == "1");    // true  ← ゆるい比較
var_dump(1 === "1");   // false ← 厳密比較 ✅
var_dump(1 <=> 2);     // -1（宇宙船演算子。ソートで使う）

// 論理
var_dump(true && false);   // false
var_dump(true || false);   // true
var_dump(!true);           // false

// null合体
$name = $_GET["name"] ?? "ゲスト";
$config["debug"] ??= false;    // キーが無ければ設定する

// 三項演算子
$label = $score >= 60 ? "合格" : "不合格";
```

### `==` の危険性（PHP 8 で改善されたが、まだ危険）

```php
<?php
// PHP 8 での挙動
var_dump(0 == "");        // false（PHP 7では true だった）
var_dump(0 == "abc");     // false（PHP 7では true だった）
var_dump("1" == "01");    // true  ← まだ危険
var_dump("10" == "1e1");  // true  ← まだ危険
var_dump(null == false);  // true
var_dump([] == false);    // true
```

> ⚠️ **`===` を使ってください。例外はありません。**
> PHP 8 で `==` の挙動は改善されましたが、まだ罠は残っています。

### `<=>`（宇宙船演算子）

```php
<?php
// ソートのコールバックで使う
usort($items, fn($a, $b) => $a["price"] <=> $b["price"]);   // 昇順
usort($items, fn($a, $b) => $b["price"] <=> $a["price"]);   // 降順
```

**JavaScript の `a - b` にあたるもの**ですが、文字列でも使えます。

---

## ✍️ 手を動かす⑥ ─ 型宣言（推奨）

PHP 7 以降、**型を書けます**。書くと事故が激減します。

```php
<?php
declare(strict_types=1);   // ← ファイルの先頭に書く（重要）

function calc_tax(int $price, float $rate = 0.1): int
{
    return (int) floor($price * (1 + $rate));
}

echo calc_tax(1000);        // 1100
echo calc_tax("1000");      // ❌ TypeError（strict_types=1 のおかげ）
```

### `declare(strict_types=1)` を必ず書く

```php
// strict_types なし（デフォルト）
function f(int $n) { return $n; }
f("123");   // 123 に自動変換される（バグに気づけない）

// strict_types=1
f("123");   // TypeError: Argument #1 must be of type int, string given
```

> 💡 **`declare(strict_types=1);` は、PHPファイルの1行目（`<?php` の直後）に書きます。**
> これだけで、型の取り違えバグが実行時に検出されるようになります。
>
> **この教材では、以降すべてのPHPファイルに書きます。**

### 使える型

```php
<?php
declare(strict_types=1);

function example(
    int $a,
    float $b,
    string $c,
    bool $d,
    array $e,
    ?string $f = null,        // null を許容（nullable）
    int|string $g = 0,        // 複数の型（PHP 8以降）
): void {
    // void = 何も返さない
}
```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| Parse error（変数の行） | `$` を書き忘れた | すべての変数に `$` |
| 連結できない / 数値になる | `+` で連結しようとした | `.`（ドット）を使う |
| 変数が展開されない | シングルクォートを使っている | ダブルクォートにする |
| `"$count個"` が動かない | 変数名の区切りが不明確 | `{$count}個` と囲む |
| 文字数が3倍になる | `strlen()` を使っている | `mb_strlen()` |
| 「0」が未入力扱いになる | `empty()` / `!$value` を使った | `=== ""` で判定 |
| 全角スペースが消えない | `trim()` のデフォルト | `preg_replace('/\A[\s　]+\|[\s　]+\z/u', ...)`（`trim` の第2引数に全角を入れると文字が壊れる） |
| 型のバグに気づけない | `strict_types` を書いていない | 1行目に `declare(strict_types=1);` |
| 未定義変数に気づけない | display_errors が Off | 3-1 の設定を確認 |

---

## 🤖 AIに聞いてみよう

### ① 型の落とし穴を体系的に理解する

```text
PHP の型の扱いについて、JavaScript 経験者として理解したいです。

以下について、それぞれ「PHPでの結果」と「なぜそうなるか」を教えてください。
また、JavaScript との違いも明記してください。

1. "0" を if で判定するとどうなるか
2. empty("0") の結果
3. 1 == "1" と 1 === "1"
4. "10" == "1e1"
5. null == false
6. [] == false
7. "abc" + 1

そのうえで、「初学者が守るべき3つのルール」にまとめてください。
```

### ② 自分のコードをレビューさせる

```text
以下は、私が書いた PHP のコードです。PHP学習1週目です。

（コードを貼る）

次の観点でレビューしてください。

1. 型に関する潜在的なバグ
2. 日本語（マルチバイト）の扱いで問題になる箇所
3. empty() / isset() の誤用
4. 命名規則（PSR-12）に沿っていない箇所
5. declare(strict_types=1) を書くべきか

修正後のコードは書かず、指摘だけをお願いします。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

以下の出力を予想してから、実行して確認してください。

```php
<?php
var_dump("0" == false);
var_dump("0" === false);
var_dump(empty("0"));
var_dump(isset($undefined));
var_dump("abc" . 1);
var_dump(1 + "2");
var_dump(strlen("あいう"));
var_dump(mb_strlen("あいう"));
var_dump(trim("　あ　"));
var_dump("10" == "1e1");
```

<details>
<summary>答えを見る</summary>

```
bool(true)     ← "0" は falsy
bool(false)    ← 型が違う
bool(true)     ← ★ "0" は empty 判定される
bool(false)    ← 未定義なので false（Warningは出ない）
string(4) "abc1"  ← 数値が文字列化されて連結
int(3)         ← "2" が数値に変換される
int(9)         ← UTF-8 の日本語は1文字3バイト
int(3)         ← 文字数
string(9) "　あ　"  ← 全角スペースは trim されない
bool(true)     ← 両方 10 として解釈される
```

</details>

### 演習2（必須）

以下の関数を書いてください。**`declare(strict_types=1);` を付けてください。**

```php
<?php
declare(strict_types=1);

/**
 * 税込み価格を計算する（1円未満切り捨て）
 */
function calc_tax_included(int $price, float $rate = 0.1): int
{
    // 実装する
}

/**
 * 文字列を指定文字数で切り詰め、超えていたら末尾に "…" を付ける
 * 例: truncate("こんにちは世界", 5) → "こんにちは…"
 */
function truncate(string $text, int $length): string
{
    // 実装する（日本語対応すること）
}

/**
 * 全角スペースを含めて前後の空白を除去する
 */
function trim_ja(string $text): string
{
    // 実装する
}

/**
 * 未入力かどうかを判定する（"0" は未入力ではない）
 */
function is_blank(?string $value): bool
{
    // 実装する
}

// テスト
var_dump(calc_tax_included(1000));           // int(1100)
var_dump(truncate("こんにちは世界", 5));      // string "こんにちは…"
var_dump(truncate("こんにちは", 5));          // string "こんにちは"（…は付かない）
var_dump(trim_ja("　あ　"));                  // string "あ"
var_dump(is_blank(""));                       // bool(true)
var_dump(is_blank("　"));                     // bool(true)
var_dump(is_blank("0"));                      // bool(false)  ★
var_dump(is_blank(null));                     // bool(true)
```

<details>
<summary>答えを見る</summary>

```php
<?php
declare(strict_types=1);

function calc_tax_included(int $price, float $rate = 0.1): int
{
    return (int) floor($price * (1 + $rate));
}

function truncate(string $text, int $length): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . "…";
}

function trim_ja(string $text): string
{
    // trim の第2引数はバイト単位で動くため、全角スペースを入れると
    // "ラテ" のような文字列を壊す。正規表現の /u で文字単位に処理する
    return preg_replace('/\A[\s　]+|[\s　]+\z/u', "", $text) ?? "";
}

function is_blank(?string $value): bool
{
    if ($value === null) {
        return true;
    }
    return trim_ja($value) === "";
}
```

**ポイント**
- `mb_strlen` / `mb_substr` を使う（日本語対応）
- 全角スペースの除去は `preg_replace('/\A[\s　]+|[\s　]+\z/u', ...)` で行う
  （`trim` の第2引数に全角スペースを入れると、バイト単位で処理されて
  「ラテ」のような文字列が壊れる）
- `is_blank` は `empty()` を使わない（`"0"` を弾いてしまうため）
- `?string` で null を許容
- `(int) floor(...)` で整数に（`round` だと切り上げになる場合がある）

</details>

### 演習3（挑戦）

第2部の ToDoアプリのバリデーションを、PHPで書き直してください。

```php
<?php
declare(strict_types=1);

/**
 * タスクのテキストを検証する
 * @return string|null エラーメッセージ、問題なければ null
 */
function validate_todo_text(?string $text): ?string
{
    // 要件:
    // - 未入力（空、空白のみ、全角空白のみ、null）→ "タスクの内容を入力してください"
    // - 120文字を超える → "120文字以内で入力してください（現在○文字）"
    // - 制御文字（改行・タブ以外）を含む → "使用できない文字が含まれています"
    // - 問題なければ null
}

// テストケース
var_dump(validate_todo_text(""));                    // エラー
var_dump(validate_todo_text("　　"));                 // エラー
var_dump(validate_todo_text(null));                  // エラー
var_dump(validate_todo_text("0"));                   // null（有効）
var_dump(validate_todo_text(str_repeat("あ", 121))); // エラー
var_dump(validate_todo_text("牛乳を買う"));           // null
```

> 💡 ヒント：制御文字の判定は `preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $text)` で行えます。

---

## ✅ 章末チェック

- [ ] 変数に `$` が必要なことを体で覚えた
- [ ] 文字列連結が `.` であることを覚えた
- [ ] ダブルクォートとシングルクォートの違いを説明できる
- [ ] `{$var}` で囲む理由を言える
- [ ] `"0"` が falsy であることを知っている
- [ ] `empty()` を未入力判定に使ってはいけない理由を言える
- [ ] `mb_` 系の関数を使うべき場面を言える
- [ ] `trim()` が全角スペースを除去しないことを知っている
- [ ] `declare(strict_types=1);` を書く理由を説明できる

---

**前 → [3-1 サーバーサイドとは何か / PHPを動かす](03-01-php-intro.md)　｜　次 → [3-3 条件分岐・繰り返し・配列](03-03-array.md)**
