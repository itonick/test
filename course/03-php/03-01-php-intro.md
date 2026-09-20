# 3-1 サーバーサイドとは何か / PHPを動かす

> ◎ **このレッスンのゴール**
> - フロントエンドとバックエンドの境界を、コードのレベルで理解する
> - PHPを実行し、エラーを画面に出せるようにする
> - エラーログの場所と読み方を覚える

所要 90分 / 難度 🟢
完成コード: [`code/03-01/`](../code/03-01/)

---

## 📖 第2部で作ったアプリの限界

ToDoアプリは動きましたが、こんな限界がありました。

| 限界 | 理由 |
| --- | --- |
| 他の人と共有できない | データがそのブラウザにしかない |
| 別の端末から見られない | 同上 |
| ブラウザのデータを消すと全部消える | 同上 |
| パスワードで守れない | JSは丸見え |

**これらは全部、「サーバーがない」ことが原因です。**

---

## 📖 実行される場所が、決定的に違う

```
┌──────────── ブラウザ（クライアント） ────────────┐
│                                                   │
│  HTML / CSS / JavaScript がここで動く             │
│                                                   │
│  ・ソースコードは誰でも見られる                    │
│  ・ユーザーが自由に書き換えられる                  │
│  ・データはその人の端末にしかない                  │
│                                                   │
└───────────────────────┬───────────────────────────┘
                        │ HTTP
┌───────────────────────┴───────────────────────────┐
│              サーバー                              │
│                                                    │
│  PHP がここで動く                                  │
│                                                    │
│  ・ソースコードは絶対に見られない                   │
│  ・ユーザーは書き換えられない                       │
│  ・データを全員で共有できる                         │
│  ・パスワードやAPIキーを安全に置ける                │
│                                                    │
└────────────────────────────────────────────────────┘
```

### 実験してみる

`htdocs/php-lesson/test1.php` を作ってください。

```php
<?php
$secret = "これは秘密の値";
echo "<p>公開してよい値</p>";
```

ブラウザで `http://localhost/php-lesson/test1.php` を開き、
**右クリック →「ページのソースを表示」**してください。

```html
<p>公開してよい値</p>
```

**これだけです。** `$secret` も `<?php` も出てきません。

**PHPは「HTMLを生成する装置」であって、PHPコード自体は絶対にブラウザに届きません。**

> 💡 これが、PHPにデータベースのパスワードを書ける理由です。
> JavaScript に書いたら、開発者ツールを開いた瞬間に全員に見えます。

### JavaScript との対比

| | JavaScript | PHP |
| --- | --- | --- |
| 実行場所 | ブラウザ | サーバー |
| コードは見える？ | **見える** | 見えない |
| 実行タイミング | ページ表示後、いつでも | **ページを返す前に1回だけ** |
| DOMを触れる？ | 触れる | **触れない**（HTMLを文字列として作るだけ） |
| ページ更新なしで動く？ | 動く | **動かない**（リクエストが必要） |
| データの保存先 | localStorage（その人だけ） | ファイル・DB（全員で共有） |

> ⚠️ **「PHPでボタンをクリックしたときの処理を書きたい」は、考え方が間違っています。**
> PHPが動くのは**ページを返す瞬間だけ**です。クリックへの反応はJavaScriptの仕事、
> クリック後にサーバーへ送られたデータを処理するのがPHPの仕事です。

---

## ✍️ 手を動かす① ─ 実行環境の確認

第0部（0-4）で XAMPP / MAMP をセットアップしました。起動していることを確認してください。

| 環境 | 起動するもの | URL |
| --- | --- | --- |
| XAMPP | Apache（+ MySQL） | `http://localhost/` |
| MAMP | Apache（+ MySQL） | `http://localhost:8888/` |

> ⚠️ **PHPファイルは必ず `htdocs` の中に置き、`localhost` で開いてください。**
> - デスクトップのファイルをダブルクリック → **動きません**
> - VS Code の Live Server（`127.0.0.1:5500`）→ **動きません**
>
> これは第0部で扱った内容ですが、第3部で必ず1回は踏みます。

### 動作確認

`php-lesson/hello.php`

```php
<?php
echo "PHPが動いています";
echo "<br>";
echo "PHPのバージョン: " . PHP_VERSION;
echo "<br>";
echo "現在時刻: " . date("Y-m-d H:i:s");
```

`http://localhost/php-lesson/hello.php` で確認してください。

**表示されない場合**

| 症状 | 原因 |
| --- | --- |
| コードがそのまま表示される | `htdocs` の外にある / 拡張子が `.php` でない / Live Server で開いている |
| 404 Not Found | パスの打ち間違い |
| 真っ白 | 文法エラー（次で対処します） |
| アクセスできません | Apache が起動していない |

> 🆘 **ここで詰まったら**（PHPのコードがそのまま文字で表示される ─ 最頻出）
> - **まず確認**：① URL が `http://localhost/...`（`127.0.0.1:5500` の Live Server では**ない**）か ② ファイルが `htdocs` の中にあるか ③ 拡張子が `.php` か（`hello.php.txt` になっていないか）
> - **直らなければ、AIにこう聞く**（OSと使っているソフトを添える）：
>   「XAMPP（またはMAMP）で、PHPが実行されずコードがそのまま表示されます。ブラウザのURLは○○です。原因の候補を、確認手順つきで教えてください」

---

## ✍️ 手を動かす② ─ エラーを画面に出す（最重要）

**これをやらないと、第3部はずっと真っ白な画面と戦うことになります。**

### 設定を変える

1. XAMPP Control Panel → Apache の「Config」→「PHP (php.ini)」
   （MAMP は `/Applications/MAMP/bin/php/php8.x.x/conf/php.ini`）
2. `Ctrl + F` で以下を探し、書き換える

```ini
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
log_errors = On
```

3. **Apache を再起動する**（これを忘れる人が非常に多いです）

### 確認する

`php-lesson/error-test.php`

```php
<?php
echo $undefined_variable;   // 定義していない変数
echo "この行は実行される";
```

**Warning が画面に表示されれば成功です。**

```
Warning: Undefined variable $undefined_variable in C:\xampp\htdocs\php-lesson\error-test.php on line 2
この行は実行される
```

表示されない場合は、設定が反映されていません。**次で確認してください。**

### 設定が効いているか確認する

```php
<?php
echo "display_errors: " . ini_get("display_errors") . "<br>";
echo "error_reporting: " . error_reporting() . "<br>";
echo "読み込まれている php.ini: " . php_ini_loaded_file();
```

**最後の行が重要です。** `php.ini` は複数ある場合があり、**編集したファイルと読み込まれているファイルが違う**ことがあります。

> ⚠️ **本番サーバーでは `display_errors = Off` にしてください。**
> エラーメッセージには、ファイルの絶対パスやデータベースの情報が含まれます。
> 攻撃者にとっては貴重な情報源です。第6部（6-6）で扱います。

### ファイル単位で設定する方法

`php.ini` を触れない環境（レンタルサーバーなど）では、ファイルの先頭に書けます。

```php
<?php
ini_set("display_errors", "1");
error_reporting(E_ALL);
```

> 💡 学習中は `php.ini` を設定するのが楽です。この方法は「本番で一時的に確認したい」ときに使います。

---

## ✍️ 手を動かす③ ─ エラーの種類を知る

PHP のエラーには段階があります。

| 種類 | 処理は止まる？ | 例 |
| --- | --- | --- |
| **Parse error** | **止まる**（1行も実行されない） | 文法ミス。セミコロン忘れなど |
| **Fatal error** | **止まる**（そこまでは実行される） | 存在しない関数の呼び出し |
| **Warning** | 止まらない | 未定義変数、ファイルが開けない |
| **Notice / Deprecated** | 止まらない | 非推奨の書き方 |

### 実際に見てみる

```php
<?php
// ① Warning（止まらない）
echo $nothing;
echo "①のあと<br>";

// ② Fatal error（ここで止まる）
undefined_function();
echo "②のあと（表示されない）";
```

**出力**

```
Warning: Undefined variable $nothing in ... on line 3
①のあと
Fatal error: Uncaught Error: Call to undefined function undefined_function() in ...
```

「②のあと」は表示されません。**Fatal error で処理が止まった**からです。

### Parse error は特別

```php
<?php
echo "最初の行";
echo "セミコロンがない"
echo "次の行";
```

**「最初の行」すら表示されません。**

Parse error は「そもそもプログラムとして読めない」状態なので、**1行も実行されずに終わります**。

> 💡 **「何も表示されない」ときは Parse error を疑ってください。**
> `echo` を先頭に足しても出ないなら、確実に文法エラーです。

---

## ✍️ 手を動かす④ ─ エラーログを読む

画面に出せない状況（本番、AJAX通信の裏側）では、**ログを見ます**。

### ログの場所

| 環境 | パス |
| --- | --- |
| XAMPP | `C:\xampp\apache\logs\error.log` |
| MAMP | `/Applications/MAMP/logs/php_error.log` |
| Laravel（第5部） | `storage/logs/laravel.log` |

### 読み方

**一番下（最新）から読んでください。** 上から読むと過去のエラーを見てしまいます。

```
[13-Apr-2026 10:23:45 Asia/Tokyo] PHP Warning:  Undefined variable $name in C:\xampp\htdocs\test.php on line 5
[13-Apr-2026 10:24:02 Asia/Tokyo] PHP Fatal error:  Uncaught Error: Call to undefined function foo() in C:\xampp\htdocs\test.php:8
```

### 自分でログに書く

```php
<?php
error_log("ここまで来た");
error_log("変数の中身: " . print_r($data, true));
```

> 💡 **`print_r($data, true)` の第2引数 `true` が重要です。**
> `true` にすると「表示せず、文字列として返す」になります。ログに書くときはこれが必要です。

### VS Code でログを開いておく

`error.log` を VS Code で開いておくと、**保存のたびに自動で更新されます**。
画面とログを並べて見られるので、デバッグが速くなります。

---

## ✍️ 手を動かす⑤ ─ デバッグ関数

JavaScript の `console.log()` にあたるものです。

```php
<?php
$user = ["name" => "太郎", "age" => 20, "tags" => ["会員", "常連"]];

// ① var_dump — 型も出る（最も情報が多い）
var_dump($user);

// ② print_r — 構造が読みやすい
print_r($user);

// ③ 見やすく整形する（おすすめ）
echo "<pre>";
print_r($user);
echo "</pre>";
```

### `var_dump` と `print_r` の違い

```php
$x = "5";

var_dump($x);   // string(1) "5"    ← 型と長さがわかる
print_r($x);    // 5                ← 値だけ
```

| 関数 | 型 | 長さ | 読みやすさ |
| --- | --- | --- | --- |
| `var_dump()` | **出る** | **出る** | 情報が多い |
| `print_r()` | 出ない | 出ない | **読みやすい** |

> 💡 **「文字列の "5" なのか、数値の 5 なのか」で悩んだら `var_dump`。**
> 構造を確認したいだけなら `print_r`。使い分けてください。

### 便利なデバッグ関数を作っておく

`php-lesson/debug.php`（📋 コピペして使い回してください）

```php
<?php
/**
 * 変数を見やすく出力する
 * @param mixed $value 出力したい値
 * @param bool $stop true なら、ここで処理を止める
 */
function dd($value, bool $stop = true): void
{
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:12px;'
       . 'border-radius:4px;overflow:auto;font-size:13px;line-height:1.6">';
    var_dump($value);
    echo '</pre>';

    if ($stop) {
        exit;
    }
}
```

使い方：

```php
<?php
require __DIR__ . "/debug.php";

$data = ["a" => 1, "b" => 2];
dd($data);              // 表示してここで止まる
dd($data, false);       // 表示して続行
```

> 💡 **`dd` は "dump and die" の略**です。Laravel（第5部）にも同名の関数があり、実務で最もよく使うデバッグ手段です。
> **いま自分で作っておくと、第5部で「あれか」とつながります。**

### `exit` で止める意味

```php
<?php
$a = calc1();
dd($a);          // ← ここで止めて中身を確認
$b = calc2($a);  // ここから先は実行されない
```

**途中で止めて、そこまでの状態を確認する。** これが PHP のデバッグの基本形です。

> ⚠️ **`dd()` を消し忘れて本番にアップすると、ページが途中で止まります。** 必ず消してください。

---

## ✍️ 手を動かす⑥ ─ ファイルの分割

PHP は、他のファイルを読み込めます。

```php
<?php
require __DIR__ . "/debug.php";        // 失敗したら Fatal error で停止
require_once __DIR__ . "/config.php";  // 一度だけ読み込む
include __DIR__ . "/header.php";       // 失敗しても Warning で続行
include_once __DIR__ . "/footer.php";
```

| 関数 | ファイルが無いとき | 二重読み込み |
| --- | --- | --- |
| `require` | **Fatal error**（停止） | する |
| `require_once` | **Fatal error**（停止） | **しない** |
| `include` | Warning（続行） | する |
| `include_once` | Warning（続行） | **しない** |

> 💡 **基本は `require_once` を使ってください。**
> - 無いと困るファイル → `require`（設定ファイル、関数定義）
> - 無くても動くファイル → `include`（オプショナルな部品）
>
> 実務では、ほぼ `require_once` です。

### `__DIR__` を必ず使う

```php
require "debug.php";              // ❌ 実行時のディレクトリ次第で失敗する
require __DIR__ . "/debug.php";   // ✅ このファイルの場所を基準にする
```

`__DIR__` は「**このPHPファイルがあるディレクトリの絶対パス**」です。

> ⚠️ **相対パスで書くと、別のファイルから読み込まれたときに壊れます。**
> `pages/list.php` が `require "config.php"` を書いていて、それを `index.php` から読み込むと、
> 基準が `index.php` の場所になり、ファイルが見つかりません。
>
> **`__DIR__` を付ける。これは例外なく守ってください。**

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| PHPコードがそのまま表示される | htdocs の外 / 拡張子が違う / Live Server | `htdocs` に置き、`localhost` で開く |
| 画面が真っ白 | Parse error、または display_errors が Off | php.ini を設定して Apache 再起動 |
| エラー設定が反映されない | Apache を再起動していない / 別の php.ini を編集した | `php_ini_loaded_file()` で確認 |
| 404 | パスの打ち間違い | フォルダ名・ファイル名を確認（大文字小文字も） |
| `require` が失敗する | 相対パスで書いている | `__DIR__ . "/..."` にする |
| 途中までしか表示されない | Fatal error で停止した | エラーメッセージを読む |
| 日本語が文字化けする | ファイルの文字コード / meta charset | UTF-8（BOMなし）で保存 |

---

## 🤖 AIに聞いてみよう

### ① 環境の問題を切り分けさせる

```text
PHPの学習を始めたところですが、環境で問題が起きています。

【環境】
- OS: （Windows 11 / macOS 14）
- 実行環境: （XAMPP 8.2 / MAMP）
- ブラウザ: Chrome

【やろうとしたこと】
【実際に起きたこと】
【画面に出ているもの】
（丸ごとコピペ）

【自分で確認したこと】
- ファイルの場所: （実際のパス）
- アクセスしたURL: （実際のURL）
- Apache の状態: （起動している / いない）

原因として考えられるものを、可能性の高い順に3つ挙げ、
それぞれの確認手順を教えてください。
すぐに答えを出すのではなく、私が自分で確認できる手順を示してください。
```

### ② JavaScript との違いを整理する

```text
JavaScript を1ヶ月学んだあと、PHP を学び始めました。

「JavaScript ではこうだったが、PHP ではこう」という対比の形で、
初学者が混乱しやすいポイントを10個挙げてください。

観点:
- 実行される場所とタイミング
- 変数の書き方
- 文字列の連結
- 配列とオブジェクト
- 関数の定義
- 真偽値の扱い
- エラーの出方

表形式で、「JavaScript」「PHP」「注意点」の3列でお願いします。
```

> 💡 **既に知っている言語と対比させると、学習速度が大きく上がります。**
> この回答は保存して、第3部の間ずっと参照してください。

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

- [ ] `php-lesson/` フォルダを作る
- [ ] `hello.php` で PHP のバージョンと現在時刻を表示する
- [ ] `display_errors` を On にして、Apache を再起動する
- [ ] 未定義変数を出力して、Warning が表示されることを確認する
- [ ] `debug.php` を作り、`dd()` 関数を実装する
- [ ] 別のファイルから `require __DIR__ . "/debug.php"` して使う
- [ ] エラーログの場所を開いて、実際のログを読む

### 演習2（必須）─ わざと壊す

以下をそれぞれ試して、**どんなエラーが出るか**をメモしてください。

| 壊し方 | 予想されるエラー | 実際 |
| --- | --- | --- |
| セミコロンを1つ消す | | |
| `echo` を `eco` にする | | |
| `<?php` を `<? php` にする | | |
| 波括弧を1つ消す | | |
| 全角スペースを1つ入れる | | |
| 存在しないファイルを `require` する | | |
| 存在しないファイルを `include` する | | |

<details>
<summary>答えを見る</summary>

| 壊し方 | エラー | 特徴 |
| --- | --- | --- |
| セミコロンを消す | **Parse error** | 何も表示されない |
| `eco` にする | **Fatal error**: Call to undefined function | そこまでは表示される |
| `<? php` | PHPとして認識されず、**そのまま表示される** | 見た目では気づきにくい |
| 波括弧を消す | **Parse error**: unexpected end of file | 指摘行は末尾になりがち |
| 全角スペース | **Parse error**: unexpected character | **見た目ではわからない**（VS Code の zenkaku 拡張で可視化） |
| 存在しないファイルを `require` | **Fatal error** | 停止する |
| 存在しないファイルを `include` | **Warning** | 続行する |

**「何も表示されない = Parse error」「途中で止まる = Fatal error」「続くけど変 = Warning」**
この対応を覚えると、原因の見当が一瞬でつきます。

</details>

### 演習3（挑戦）

- [ ] `php-lesson/info.php` を作り、以下を表示する
      - PHPのバージョン
      - 読み込まれている `php.ini` のパス
      - `display_errors` の設定値
      - エラーログのパス（`ini_get("error_log")`）
      - 有効になっている拡張機能のうち、`pdo_mysql` が含まれているか
- [ ] 結果をメモしておく（第4部でデータベースに繋ぐとき必要になります）

> 💡 ヒント：`extension_loaded("pdo_mysql")` で確認できます。
> `false` だった場合、第4部でデータベースに接続できません。**いま確認しておくと、後で慌てずに済みます。**

---

## ✅ 章末チェック

- [ ] PHPコードがブラウザに届かない理由を説明できる
- [ ] PHPが動くのは「ページを返す瞬間だけ」だと理解した
- [ ] `display_errors` を On にして、Apache を再起動した
- [ ] Parse error / Fatal error / Warning の違いを言える
- [ ] 「何も表示されない = Parse error」と判断できる
- [ ] エラーログの場所を知っている
- [ ] `var_dump` と `print_r` の違いを言える
- [ ] `dd()` 関数を自分で作った
- [ ] `require` で `__DIR__` を使う理由を説明できる

---

**次のレッスン → [3-2 変数・型・演算子](03-02-variables.md)**
