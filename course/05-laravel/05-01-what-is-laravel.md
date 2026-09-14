# 5-1 フレームワークとは / Laravelを入れる

> 🎯 **このレッスンのゴール**
> - フレームワークが何を解決するのかを、第3・4部の経験から理解する
> - Laravel をインストールし、開発サーバーを起動できる
> - 「なぜ Laravel なのか」を説明できる

所要 120分 / 難度 🟡
完成コード: [`code/05-01/`](../code/05-01/)

---

## 📖 第3・4部で、あなたは「小さなフレームワーク」を作っていた

第3部・第4部で掲示板を作る中で、こんなものを自作しました。

| 自作したもの | Laravel での名前 |
| --- | --- |
| `lib/functions.php`（`e()` など） | ヘルパ関数 |
| `lib/csrf.php`（トークン生成・検証） | **`@csrf` ディレクティブ**（1行で済む） |
| `lib/session.php`（フラッシュメッセージ） | **`session()->flash()`** |
| `config/database.php`（PDO接続） | **設定 + Eloquent**（接続を書かない） |
| `PostRepository`（データアクセス） | **Eloquent モデル** |
| `partials/header.php`（共通レイアウト） | **Blade レイアウト** |
| バリデーション関数群 | **フォームリクエスト** |
| ルーティング（`if ($action === ...)`） | **ルーティング** |

**あなたが手作業で組み立てたものを、Laravel は最初から用意しています。**

> 💡 **第3・4部を「遠回り」と思わないでください。**
> 自分で作ったからこそ、Laravel が「何を、なぜ」やってくれているかが分かります。
> フレームワークから入った人は、`@csrf` が何をしているか説明できません。
> **あなたは説明できます。** それが、この教材の順序の狙いです。

---

## 📖 フレームワークが解決すること

### ① 車輪の再発明をなくす

CSRF対策、認証、バリデーション、ルーティング——**どのWebアプリでも必要なもの**を、
毎回自作するのは無駄です。しかも、自作するとバグやセキュリティホールが入ります。

**Laravel の CSRF 対策は、世界中の開発者にレビューされ、実績があります。**
自作より安全です。

### ② 書き方が統一される

第3部では、あなたが「どこにファイルを置くか」「どう命名するか」を自分で決めました。
チームで開発すると、これがバラバラになります。

**Laravel は「ここに置く」「こう書く」を決めています（規約）。**
規約に従えば、他の Laravel プロジェクトを見た人が、すぐに理解できます。

### ③ 保守しやすくなる

MVC（後述）でコードが整理され、「どこを直せばいいか」が明確になります。

---

## 📖 なぜ Laravel なのか

| | Laravel | 他の選択肢 |
| --- | --- | --- |
| 日本の求人数 | **多い** | CakePHP（減少中）、Symfony（大規模向け） |
| 学習しやすさ | **ドキュメントが充実** | — |
| 機能の網羅性 | **認証・メール・キューまで込み** | — |
| コミュニティ | **世界最大級** | — |
| Laravel の求人 | **PHP案件の多数派** | — |

**PHPフレームワークを1つ学ぶなら、Laravel が最も就職・案件に繋がりやすい**です。

> 💡 **フレームワークは「乗り換え可能」です。**
> Laravel を理解すれば、Symfony も CakePHP も、考え方は同じなので学べます。
> 「MVC」「ルーティング」「ORM」という概念は共通だからです。

---

## ✍️ 手を動かす① ─ 必要なものを確認する

Laravel には**バージョンごとに必要なPHPのバージョン**があります。

| Laravel | 必要なPHP |
| --- | --- |
| Laravel 11 | PHP 8.2 以上 |
| Laravel 10 | PHP 8.1 以上 |

**この教材は Laravel 11 を前提にします。**

### 確認する

```bash
php -v
# → PHP 8.2 以上であることを確認

composer -V
# → Composer がインストールされていることを確認
```

**Composer が無い場合**は https://getcomposer.org/ からインストールしてください。
第3部（3-10）でも触れました。

> ⚠️ **XAMPP / MAMP に付属の PHP はバージョンが古いことがあります。**
> `php -v` が 8.1 以下なら、XAMPP / MAMP を新しいバージョンに更新してください。

### 必要なPHP拡張を確認する

```bash
php -m | grep -iE "pdo_mysql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|fileinfo"
```

Laravel には、これらの拡張が必要です。XAMPP / MAMP なら通常は揃っています。
不足していれば `php.ini` で有効にしてください（`;extension=...` の `;` を外す）。

---

## ✍️ 手を動かす② ─ Laravel をインストールする

### プロジェクトを作る

```bash
# htdocs や、好きな作業フォルダに移動してから
composer create-project laravel/laravel flea-market

cd flea-market
```

**`flea-market` が、これから作る「フリマアプリ」のフォルダ名です。**

> ⏳ **初回は数分かかります。** Composer が Laravel 本体と、依存する数十のライブラリを
> ダウンロードするためです。**これは正常です。**

> 🆘 **ここで詰まったら**（`composer create-project` が失敗する）
> - **`command not found: composer`**：Composer 未インストール／PATH未通し（インストール後はターミナルを開き直す）
> - **`requires ext-xxx` / PHPバージョンエラー**：PHP 8.2系が有効か、必要な拡張（mbstring, openssl 等）が入っているかを確認（このレッスンの「必要なPHP拡張を確認する」参照）
> - **途中で止まる／メモリ不足**：もう一度実行するか、`COMPOSER_MEMORY_LIMIT=-1 composer create-project ...`
> - **直らなければ、AIにこう聞く**（`php -v` と `composer -V` の結果、エラー全文を添える）：
>   「Laravelの `composer create-project` が失敗します。PHPは○○、エラーは△△です。原因と確認手順を教えてください」

### 何が作られたか確認する

```bash
ls
```

```
app/            ← ★ アプリのコード（Model, Controller など）を書く場所
bootstrap/      ← 起動処理（触らない）
config/         ← 設定ファイル
database/       ← マイグレーション、シーダー
public/         ← ★ 公開ディレクトリ（index.php はここ）
resources/      ← ★ Blade テンプレート、CSS/JS の元
routes/         ← ★ ルーティング（web.php）
storage/        ← ログ、キャッシュ、アップロードファイル
tests/          ← テスト
vendor/         ← ライブラリ本体（Git管理しない）
.env            ← ★ 環境設定（Git管理しない）
artisan         ← ★ コマンドラインツール
composer.json   ← 依存の定義
```

> 💡 **`public/` が公開ディレクトリ**になっています。第3部（3-12）で自分で作った
> 「`public/` だけを公開する」構成と、まったく同じです。
> `app/`、`config/`、`.env` などは公開されません。**安全な構成が最初からできています。**

---

## ✍️ 手を動かす③ ─ 開発サーバーを起動する

```bash
php artisan serve
```

```
INFO  Server running on [http://127.0.0.1:8000].
Press Ctrl+C to stop the server.
```

**`http://127.0.0.1:8000` を開くと、Laravel のウェルカムページが表示されます。**

> 💡 **`php artisan serve` は、PHPの組み込みサーバー（第3部で使った `php -S`）の
> Laravel 版です。** XAMPP / MAMP を起動しなくても、これだけで動きます。
>
> ただし、**MySQL は別途起動が必要**です（XAMPP / MAMP の MySQL）。

### `artisan` とは

**`artisan`（アルチザン）は、Laravel のコマンドラインツールです。**
これから何度も使います。

```bash
php artisan list          # 使えるコマンドの一覧
php artisan --version     # Laravel のバージョン
php artisan serve         # 開発サーバー起動
php artisan make:model    # モデルを作る（後のレッスンで）
php artisan migrate       # マイグレーション実行（後のレッスンで）
```

> 💡 **`artisan` は「職人」という意味です。** 面倒な作業を自動化してくれる相棒です。
> ファイルの雛形生成、DB操作、キャッシュ削除など、ほとんどの作業が `artisan` で完結します。

---

## ✍️ 手を動かす④ ─ 環境設定（.env）

Laravel の設定は `.env` ファイルにあります。**第4部（4-7）で自作した `.env` と同じ考え方です。**

```
APP_NAME=Laravel
APP_ENV=local              # local / production
APP_KEY=base64:...         # 暗号化キー（自動生成される）
APP_DEBUG=true             # ★ エラー詳細を表示（本番では false）
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flea_market    # ★ データベース名
DB_USERNAME=root
DB_PASSWORD=               # ★ XAMPP は空、MAMP は root
```

### データベースを準備する

1. phpMyAdmin で `flea_market` データベースを作る（`utf8mb4_unicode_ci`）
2. `.env` の `DB_*` を、自分の環境に合わせる

| 環境 | DB_PORT | DB_USERNAME | DB_PASSWORD |
| --- | --- | --- | --- |
| XAMPP | 3306 | root | （空） |
| MAMP | **8889** | root | **root** |

### ⚠️ `.env` を変えたらキャッシュをクリアする

```bash
php artisan config:clear
```

> ⚠️ **Laravel は設定をキャッシュします。** `.env` を変えたのに反映されないときは、
> このコマンドを実行してください。**第4部の掲示板では起きなかった、Laravel 特有の注意点です。**

### `APP_KEY` について

```bash
# 通常は create-project 時に自動生成されるが、無ければ
php artisan key:generate
```

**`APP_KEY` は、Cookie やセッションの暗号化に使われます。**
これが無いと `No application encryption key has been specified` エラーになります（巻末エラー図鑑にも記載）。

---

## ✍️ 手を動かす⑤ ─ 接続を確認する

```bash
# データベースに接続できるか確認する
php artisan migrate
```

Laravel には**最初から用意されたマイグレーション**（users テーブルなど）があります。
これが成功すれば、接続できています。

```
INFO  Preparing database.
INFO  Running migrations.
  0001_01_01_000000_create_users_table ............... DONE
  0001_01_01_000001_create_cache_table ............... DONE
  0001_01_01_000002_create_jobs_table ................ DONE
```

**エラーが出た場合**

| エラー | 原因 | 対処 |
| --- | --- | --- |
| `Access denied for user 'root'` | パスワードが違う | `.env` の `DB_PASSWORD` |
| `Unknown database 'flea_market'` | DBを作っていない | phpMyAdmin で作る |
| `Connection refused` | MySQL が起動していない | XAMPP / MAMP で起動 |
| `No such file or directory`（Mac） | ソケット / ポート | `DB_HOST=127.0.0.1`、MAMP は `DB_PORT=8889` |

> 💡 phpMyAdmin で `flea_market` を見てください。**`users` などのテーブルができています。**
> Laravel が、あなたの代わりにテーブルを作りました。

---

## ✍️ 手を動かす⑥ ─ エディタの準備

VS Code に、Laravel 用の拡張機能を入れておくと快適です。

| 拡張機能 | 効果 |
| --- | --- |
| **PHP Intelephense** | PHPの補完・型チェック |
| **Laravel Blade Snippets** | Blade の色分けと補完 |
| **Laravel Extra Intellisense** | ルート名・ビュー名の補完 |
| **DotENV** | `.env` の色分け |

> 💡 **`APP_DEBUG=true` のとき、エラー画面が非常に親切です。**
> Laravel は、エラーが起きた箇所のコード、スタックトレース、解決のヒントまで表示します。
> 第3部で `dd()` を自作しましたが、**Laravel には最初から `dd()` があります。**

---

## 📖 この先の全体像

第5部で、以下を1つずつ学び、**フリマアプリ**を作ります。

```
5-2  MVC とディレクトリ構成
5-3  ルーティング          ← URLと処理の対応
5-4  コントローラ          ← 処理の受け皿
5-5  Blade                 ← テンプレート
5-6  マイグレーション       ← テーブルをコードで作る
5-7  Eloquent              ← DBを触る（SQLを書かない）
5-8  リレーション          ← テーブル間の関係
5-9  フォームリクエスト     ← バリデーション
5-10 認証（Breeze）        ← ログイン機能が一瞬でできる
5-11 認可（ポリシー）      ← 「自分のものだけ操作できる」
5-12 ファイルアップロード
5-13 ページネーション・検索
5-14 テスト
5-15 AIwith Laravel
5-16 総合演習：フリマアプリ
```

**第3・4部で自作したものが、次々と「1行」に置き換わっていきます。**
その体験を楽しんでください。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `composer create-project` が遅い | 依存のダウンロード | 正常。数分待つ |
| PHP のバージョンが足りない | XAMPP / MAMP が古い | 更新する |
| `No application encryption key` | APP_KEY がない | `php artisan key:generate` |
| `.env` を変えたのに反映されない | 設定がキャッシュされている | `php artisan config:clear` |
| `migrate` で接続エラー | DB_* の設定 | `.env` を環境に合わせる |
| ウェルカムページが出ない | サーバーが起動していない | `php artisan serve` |
| MySQL に繋がらない | MySQL が起動していない | XAMPP / MAMP で起動 |

---

## 🤖 AIに聞いてみよう

### ① 自作コードと Laravel の対応を整理する

```text
私は素の PHP で掲示板アプリを作りました。以下を自作しています。

- CSRF トークンの生成・検証（lib/csrf.php）
- セッションのフラッシュメッセージ（lib/session.php）
- PDO でのDB接続（config/database.php）
- データアクセス用のリポジトリクラス
- 共通レイアウト（header.php / footer.php）
- バリデーション関数群

これらが Laravel ではそれぞれ何に対応するか、対応表を作ってください。
「自作では何行必要だったか」と「Laravel では何を書くか」を
比較する形でお願いします。

私は Laravel を学び始めたところです。
```

### ② インストールのトラブルを切り分ける

```text
Laravel のインストールまたは起動でエラーが出ています。

【環境】
- OS:
- PHP のバージョン（php -v の結果）:
- Composer のバージョン:
- 実行したコマンド:
- エラーメッセージ（全文）:

原因として考えられるものを可能性の高い順に3つ挙げ、
それぞれの確認手順を教えてください。
すぐに解決策を出すのではなく、私が確認できる手順を示してください。
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）

- [ ] PHP 8.2 以上、Composer が入っていることを確認する
- [ ] `composer create-project laravel/laravel flea-market` でプロジェクトを作る
- [ ] `php artisan serve` でウェルカムページを表示する
- [ ] phpMyAdmin で `flea_market` データベースを作る
- [ ] `.env` の `DB_*` を自分の環境に合わせる
- [ ] `php artisan migrate` が成功することを確認する
- [ ] phpMyAdmin で `users` テーブルができていることを確認する

### 演習2（必須）

- [ ] `php artisan list` で、使えるコマンドを眺める
- [ ] `APP_DEBUG=false` に変えてから、わざと存在しないURL（`/hoge`）にアクセスする
- [ ] エラー画面がどう変わるか確認する（`true` に戻す）
- [ ] `config:clear` を実行してから、もう一度確認する

> 💡 **`APP_DEBUG` の `true` と `false` の違いを体験してください。**
> `true` は開発用（詳細を表示）、`false` は本番用（一般的なエラーページ）。
> 第3部で「エラーの詳細を画面に出さない」と学んだことを、Laravel が自動でやってくれます。

### 演習3（挑戦）

- [ ] `routes/web.php` を開く
- [ ] デフォルトのルート（`/` → ウェルカムページ）を見つける
- [ ] 以下を追加して、`http://127.0.0.1:8000/hello` にアクセスする

```php
Route::get('/hello', function () {
    return 'こんにちは、Laravel！';
});
```

- [ ] 表示されることを確認する（これがルーティングの最小形です）

> 💡 **次のレッスン（5-3）で、ルーティングを本格的に学びます。**
> いまは「URLと処理が対応する」ことだけ体験してください。

---

## ✅ 章末チェック

- [ ] フレームワークが解決する3つのこと（再発明の排除、統一、保守性）を言える
- [ ] 第3・4部の自作コードが、Laravel の何に対応するか説明できる
- [ ] Laravel をインストールできた
- [ ] `php artisan serve` でウェルカムページを表示できた
- [ ] `public/` が公開ディレクトリであることを確認した
- [ ] `.env` の役割を説明できる
- [ ] `.env` を変えたら `config:clear` が必要なことを知っている
- [ ] `php artisan migrate` で接続を確認できた
- [ ] `artisan` が何をするツールか説明できる

---

**次のレッスン → [5-2 ディレクトリ構成とMVC](05-02-mvc.md)**
