# エラー図鑑（症状から逆引き）

エラーが出たら、まずここを引いてください。
**エラーメッセージの一部をコピーして、このページ内を検索（`Ctrl + F`）**するのが最速です。

---

## エラーとの向き合い方（最初に読む）

### エラーは敵ではありません

初学者はエラー画面を見ると「壊した」と感じますが、**エラーは「どこが問題か」を教えてくれる親切な通知**です。

**最悪なのは、エラーも出ずに黙って間違った結果が出ることです。** エラーが出るのは、まだマシな状態です。

### エラーメッセージの読み方（3点だけ）

英語でも、次の3つを拾えれば十分です。

```
Parse error: syntax error, unexpected ';' in C:\xampp\htdocs\lesson\test.php on line 12
└──① エラーの種類───┘  └──② 何が起きたか──┘   └────③ 場所（ファイルと行）────┘
```

| 拾うもの | 例 | 意味 |
| --- | --- | --- |
| ① 種類 | `Parse error` | 文法エラー |
| ② 内容 | `unexpected ';'` | 予期しない `;` がある |
| ③ 場所 | `test.php on line 12` | test.php の12行目 |

> ⚠️ **③の行番号は「エラーが発覚した行」であって、「間違えた行」とは限りません。**
> 特に括弧の閉じ忘れは、**数行〜数十行あとで発覚**します。指摘された行の**手前**も見てください。

### 困ったときの手順

```
1. エラーメッセージ全文をコピー
2. このエラー図鑑内で検索
3. 見つからなければ、メッセージをそのままGoogle検索
   （ファイルパスや変数名など、自分固有の部分は消して検索する）
4. それでもダメなら AIに聞く（プロンプト集 B-1）
```

---

## 目次

- [共通](#共通)
- [HTML / CSS](#html--css)
- [JavaScript](#javascript)
- [PHP](#php)
- [MySQL / SQL](#mysql--sql)
- [Laravel](#laravel)
- [Git](#git)
- [環境構築](#環境構築)

---

## 共通

### 🔴 画面に `404 Not Found` と出る

**意味**：指定したURLの場所に、ファイルがない。

| 確認すること | 詳細 |
| --- | --- |
| ファイルの置き場所 | `htdocs` の中にあるか？ デスクトップに置いていないか？ |
| URLのつづり | フォルダ名・ファイル名のスペルミス。**大文字小文字も区別されます** |
| 拡張子 | `index.html.txt` になっていないか（Windowsは拡張子を隠す設定があります） |
| ポート番号 | `localhost:8888`（MAMP）や `localhost:8080` を忘れていないか |

> 💡 Windows で拡張子を表示する：エクスプローラー → 表示 → 「ファイル名拡張子」にチェック

---

### 🔴 画面が真っ白（何も表示されない）

| 言語 | 主な原因 | 対処 |
| --- | --- | --- |
| PHP | 文法エラーだが、エラー表示がOFF | `php.ini` で `display_errors = On` にする（下記） |
| HTML | `<body>` の中身が空 | HTMLを確認 |
| JavaScript | JSがエラーで停止し、描画されていない | 開発者ツールの Console を見る |
| CSS | 文字色と背景色が同じ | 開発者ツールで要素を検査 |

**PHPのエラーを画面に出す設定**

1. XAMPP Control Panel → Apache の「Config」→「PHP (php.ini)」
2. 以下を探して書き換える

```ini
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
```

3. **Apache を再起動**（これを忘れる人が非常に多いです）

> ⚠️ この設定は**開発環境だけ**です。本番サーバーでは必ず `Off` にしてください。エラー内容から内部構造が漏れます。

---

### 🔴 `500 Internal Server Error`

**意味**：サーバー側でプログラムが異常終了した。

| 原因 | 対処 |
| --- | --- |
| PHPの文法エラー | 上記の設定でエラー内容を表示させる |
| `.htaccess` の記述ミス | `.htaccess` を一時的にリネームして切り分ける |
| ファイルの権限 | Mac/Linux では `chmod 644`（ファイル）/ `755`（フォルダ） |

**エラーログの場所**

| 環境 | パス |
| --- | --- |
| XAMPP | `C:\xampp\apache\logs\error.log` |
| MAMP | `/Applications/MAMP/logs/apache_error.log` |
| Laravel | `storage/logs/laravel.log` |

**ログは一番下（最新）から読んでください。**

---

### 🔴 修正したのに、画面が変わらない

| 原因 | 対処 |
| --- | --- |
| 保存していない | タブに `●` が付いていないか確認（`Ctrl + S`） |
| ブラウザのキャッシュ | **スーパーリロード**：`Ctrl + Shift + R` / `Cmd + Shift + R` |
| 違うファイルを編集している | 同名ファイルが複数ないか確認 |
| 違うURLを見ている | ブラウザのURLとファイルパスが対応しているか確認 |

> 💡 **迷ったらスーパーリロード。** CSSを直したのに変わらない、の8割はキャッシュです。

---

## HTML / CSS

### 🟡 CSSがまったく効かない

チェックリスト（上から順に確認）

- [ ] `<link rel="stylesheet" href="style.css">` の**パスが合っている**か
  - 開発者ツール → Network タブで `style.css` が **404** になっていないか
- [ ] `rel="stylesheet"` を書いたか（`rel` を忘れると読み込まれません）
- [ ] セレクタが合っているか（`.box` と `#box` の取り違え）
- [ ] `{ }` `;` の書き忘れ
- [ ] **全角スペース**が混ざっていないか
- [ ] キャッシュ（スーパーリロード）

### 🟡 CSSが一部だけ効かない

**優先順位（詳細度）で負けています。**

開発者ツールで要素を選択すると、**打ち消し線**が引かれたプロパティが見えます。それが負けている指定です。

| 優先度 | セレクタ |
| --- | --- |
| 高 | `!important` |
| ↑ | インラインスタイル（`style="..."`） |
| ↑ | ID（`#id`） |
| ↑ | クラス・属性・擬似クラス（`.class`） |
| 低 | 要素（`div`） |

> ⚠️ **`!important` で解決しないでください。** 一度使うと、以降すべてを `!important` で書く羽目になります。セレクタを見直すのが正解です。

### 🟡 要素が横並びにならない

- [ ] 親要素に `display: flex;` を付けたか（**子ではなく親**）
- [ ] 子要素に `width` を付けすぎて折り返していないか
- [ ] `flex-wrap: nowrap;` を確認

### 🟡 余白が思った通りにならない

- [ ] `box-sizing: border-box;` を設定したか（下記を全体に効かせるのが定番）

```css
*, *::before, *::after {
  box-sizing: border-box;
}
```

- [ ] **マージンの相殺**：上下に隣接する要素の margin は、大きいほうだけが適用されます
- [ ] 親のパディングか、子のマージンか、開発者ツールで確認

### 🟡 スマホで表示が崩れる

- [ ] `<head>` に viewport が入っているか

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

- [ ] 固定 `width: 1200px` などを使っていないか（`max-width` にする）
- [ ] 横スクロールが出る → 開発者ツールで、はみ出している要素を特定

---

## JavaScript

> 💡 JSのエラーは**開発者ツールの Console タブ**に出ます。まずそこを開いてください。

### 🔴 `Uncaught SyntaxError: Unexpected token`

**文法ミス。** 括弧・クォート・カンマの書き忘れ／余分。エラーの行番号の**手前**も確認。

### 🔴 `Uncaught ReferenceError: xxx is not defined`

**その名前のものが存在しない。**

| 原因 | 対処 |
| --- | --- |
| スペルミス | 変数名・関数名を確認 |
| 定義前に使っている | 定義を上に移動 |
| スコープの外から呼んでいる | `{ }` の外から中の変数は見えません |
| `<script>` の読み込み順 | 使うより先に読み込む |

### 🔴 `Uncaught TypeError: Cannot read properties of null (reading 'xxx')`

**最頻出エラーです。** 意味は「`null` に対して `.xxx` しようとした」。

**原因はほぼ2つ**

1. **`querySelector` で要素が取れていない**
   - セレクタのつづりミス（`.btn` と `#btn`）
   - HTMLにその要素が存在しない
2. **`<script>` が `<head>` にあり、HTMLより先に実行されている**

```html
<!-- ❌ HTMLがまだ存在しない時点で実行される -->
<head>
  <script src="app.js"></script>
</head>

<!-- ✅ </body> の直前に置く -->
<body>
  ...
  <script src="app.js"></script>
</body>
```

または `defer` を付けます。

```html
<script src="app.js" defer></script>
```

**確認方法**

```javascript
const btn = document.querySelector('.btn');
console.log(btn);   // null なら取れていない
```

### 🔴 `Uncaught TypeError: xxx is not a function`

| 原因 | 例 |
| --- | --- |
| メソッド名のスペルミス | `addEventListner`（正: `addEventListener`） |
| 型が違う | 配列のメソッドを文字列に対して使っている |
| 変数名と関数名が衝突 | 同じ名前で変数を上書きしている |

### 🟡 `console.log` が表示されない

- [ ] Console タブを開いているか
- [ ] フィルタが掛かっていないか（Console左上のレベル選択）
- [ ] そもそもその行まで到達しているか（手前で例外が出て止まっている）

### 🟡 クリックしても反応しない

- [ ] 要素が取れているか（`console.log(要素)` で確認）
- [ ] `addEventListener('click', 関数名)` の第2引数に **`()` を付けていないか**

```javascript
btn.addEventListener('click', myFunc());   // ❌ 即座に実行されてしまう
btn.addEventListener('click', myFunc);     // ✅ 正しい
```

- [ ] 後から追加した要素にイベントを付けようとしていないか（イベント委譲が必要）

### 🟡 フォーム送信でページがリロードされてしまう

```javascript
form.addEventListener('submit', (e) => {
  e.preventDefault();   // ← これが必要
  // 以降の処理
});
```

---

## PHP

### 🔴 `Parse error: syntax error, unexpected ...`

**文法ミス。** 指摘された行と、**その1〜3行前**を確認。

| よくある原因 | 例 |
| --- | --- |
| セミコロンの書き忘れ | `echo "hello"` → `echo "hello";` |
| 括弧の閉じ忘れ | `if ($a == 1) {` の `}` がない |
| クォートの対応ミス | `"hello'` |
| **全角スペース** | 見えないので要注意（レッスン0-5の設定で可視化） |
| 全角の記号 | `；` `（` `”` などが混入 |

### 🔴 `Fatal error: Uncaught Error: Call to undefined function xxx()`

**その関数が存在しない。**

| 原因 | 対処 |
| --- | --- |
| スペルミス | php.net で正しい名前を確認 |
| **AIが作った架空の関数** | php.net で実在確認（レッスン0-7） |
| PHP拡張が有効でない | `php.ini` で該当拡張のコメント（`;`）を外す |
| ファイルを `require` していない | 自作関数の場合 |

### 🔴 `Warning: Undefined array key "xxx"`

**意味**：その名前のキーが配列に存在しない。

```php
// ❌ name が送られてこないとエラー
$name = $_POST['name'];

// ✅ 存在確認をする
$name = $_POST['name'] ?? '';

// ✅ または
if (isset($_POST['name'])) {
    $name = $_POST['name'];
}
```

> 💡 `??`（null合体演算子）は「左がなければ右を使う」。初学者が最初に覚えるべき便利記法です。

### 🔴 `Warning: Undefined variable $xxx`

変数を定義する前に使っています。スペルミス（`$user` と `$users`）も頻出。

### 🔴 `Fatal error: Allowed memory size exhausted`

**無限ループの可能性が高いです。**

- `while` の条件が永久に真になっていないか
- `for` のカウンタを更新し忘れていないか
- 再帰関数の終了条件があるか

### 🟡 PHPのコードがそのままブラウザに表示される

| 原因 | 対処 |
| --- | --- |
| ファイルが `htdocs` の外にある | `htdocs` に移動 |
| 拡張子が `.php` でない | `.html` になっていないか |
| Live Server で開いている | `localhost` で開き直す（レッスン0-5） |
| `<?php` のつづりミス | `<?PHP` `<? php` などになっていないか |

### 🟡 日本語が文字化けする

- [ ] HTMLに `<meta charset="UTF-8">` があるか
- [ ] **ファイル自体がUTF-8で保存されているか**（VS Code右下の表示で確認。`Shift_JIS` なら「UTF-8で保存し直す」）
- [ ] DBの文字コードが `utf8mb4` か
- [ ] DB接続時に文字コードを指定しているか

```php
$pdo = new PDO('mysql:host=localhost;dbname=test;charset=utf8mb4', $user, $pass);
```

### 🟡 `Warning: Cannot modify header information - headers already sent`

`header()` や `session_start()` の**前に何か出力されています。**

| 原因 | 対処 |
| --- | --- |
| `<?php` より前に空白・改行がある | ファイルの1行目から `<?php` を書く |
| ファイル末尾の `?>` の後ろに改行がある | **末尾の `?>` は書かない**のが定石 |
| 途中で `echo` している | `header()` より前に出力しない |
| BOM付きUTF-8で保存されている | 「UTF-8（BOMなし）」で保存し直す |

---

## MySQL / SQL

### 🔴 `SQLSTATE[42S02]: Base table or view not found`

テーブルが存在しない。テーブル名のスペルミス、または接続先DBが違います。

### 🔴 `SQLSTATE[42S22]: Column not found`

カラム名が存在しない。phpMyAdmin で実際のカラム名を確認してください。

### 🔴 `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`

**接続情報が違います。**

| 環境 | ユーザー | パスワード |
| --- | --- | --- |
| XAMPP | `root` | **空文字** `''` |
| MAMP | `root` | `root` |

MAMPは初期パスワードが `root` です。**ここで詰まる人が非常に多いです。**

### 🔴 `SQLSTATE[HY000] [2002] No such file or directory`（Mac / MAMP）

ソケットのパスが違います。接続文字列のホストを `127.0.0.1` にするか、ポートを指定します。

```php
$pdo = new PDO('mysql:host=127.0.0.1;port=8889;dbname=test;charset=utf8mb4', 'root', 'root');
```

> 💡 MAMPのMySQLポートは初期状態で **8889** です。

### 🔴 `Duplicate entry 'xxx' for key 'PRIMARY'`

主キーまたはユニークキーが重複しています。同じIDを2回登録しようとしています。

### 🔴 `Cannot add or update a child row: a foreign key constraint fails`

外部キー制約違反。参照先のレコードが存在しません。**親（参照される側）を先に作成**してください。

### 🟡 日本語が `????` になる

DBとテーブルの照合順序を `utf8mb4_unicode_ci` にし、接続時に `charset=utf8mb4` を指定してください。

---

## Laravel

### 🔴 `No application encryption key has been specified`

```bash
php artisan key:generate
```

### 🔴 `SQLSTATE[HY000] [1049] Unknown database 'xxx'`

`.env` の `DB_DATABASE` に書いたデータベースが存在しません。phpMyAdmin で先に作成してください。

### 🔴 `.env` を変更したのに反映されない

```bash
php artisan config:clear
php artisan cache:clear
```

> ⚠️ Laravelは設定をキャッシュします。**`.env` を触ったら、必ずこれを実行**する癖をつけてください。

### 🔴 `Class "App\Models\Xxx" not found`

| 原因 | 対処 |
| --- | --- |
| `use` の書き忘れ | ファイル冒頭に `use App\Models\Xxx;` |
| クラス名とファイル名が違う | 一致させる（大文字小文字も） |
| オートロードのキャッシュ | `composer dump-autoload` |

### 🔴 `The GET method is not supported for this route`

ルートの HTTPメソッドが違います。`routes/web.php` を確認してください。

```bash
php artisan route:list
```

で、登録されているルート一覧を確認できます。

### 🔴 `419 | Page Expired`

**CSRFトークンがありません。** フォームに次を追加してください。

```blade
<form method="POST" action="/xxx">
    @csrf
    ...
</form>
```

### 🔴 `Permission denied` / `failed to open stream`（storage関連）

```bash
chmod -R 775 storage bootstrap/cache
```

（Windowsでは通常発生しません）

### 🟡 Bladeの変数が表示されない

- [ ] `{{ $変数 }}` の書き方が合っているか（`{ }` 1個ではなく2個）
- [ ] コントローラから `compact()` や `with()` で渡しているか
- [ ] 変数名がコントローラ側と一致しているか

### 🟡 CSSやJSが読み込まれない

`asset()` ヘルパを使ってください。

```blade
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
```

`public/` フォルダからの相対パスになります。

---

## Git

### 🔴 `fatal: not a git repository`

そのフォルダはGit管理下にありません。`git init` するか、正しいフォルダに移動してください。

### 🔴 `error: failed to push some refs to ...`

リモートに、自分が持っていないコミットがあります。

```bash
git pull origin main
# コンフリクトがあれば解消してから
git push origin main
```

### 🔴 `Please tell me who you are`

初回設定が未完了です。

```bash
git config --global user.name "あなたの名前"
git config --global user.email "you@example.com"
```

### 🟡 間違えてコミットしてしまった

```bash
# 直前のコミットを取り消す（変更内容は残す）
git reset --soft HEAD^
```

> ⚠️ `git reset --hard` は**変更が消えます**。意味がわかるまで使わないでください。

---

## 環境構築

### 🔴 XAMPP: Apache が起動しない（`Port 80 in use`）

レッスン [0-4](../00-orientation/00-04-setup.md#-apache-が起動しない場合超頻出) を参照してください。犯人は Skype / IIS / VMware のいずれかであることが多いです。

### 🔴 XAMPP: MySQL が起動しない（`Port 3306 in use`）

既存の MySQL サービスが動いています。`services.msc` から停止してください。

### 🔴 `composer: command not found`

Composer がインストールされていない、またはPATHが通っていません。https://getcomposer.org/ からインストールしてください。インストール後は**ターミナルを開き直す**必要があります。

### 🔴 `php: command not found`

PHPにPATHが通っていません。

| 環境 | PATHに追加するパス |
| --- | --- |
| XAMPP | `C:\xampp\php` |
| MAMP | `/Applications/MAMP/bin/php/php8.x.x/bin` |

追加後は**ターミナルを開き直してください。**

---

## それでも解決しないとき

1. **エラーメッセージを丸ごとGoogle検索**
   - 自分固有の部分（ファイルパス、変数名）は消して検索する
   - 例: `Parse error: syntax error, unexpected ';'` だけで検索
2. **[AIプロンプト集 B-1](ai-prompts.md#b-1-エラーが出た基本テンプレート)** を使ってAIに聞く
3. **切り分ける**
   - コードを半分コメントアウトして、どちらでエラーが出るか確認
   - 動いていた時点まで戻して、1つずつ足していく
4. **一晩寝かせる**
   - 本当に効きます。翌朝5分で見つかることが珍しくありません

> 💡 **最強のデバッグ手法は「切り分け」です。**
> 「どこまでは正しく動いているか」を確定させていけば、必ず原因にたどり着きます。
> `var_dump()` / `console.log()` を恐れずに撒いてください。
