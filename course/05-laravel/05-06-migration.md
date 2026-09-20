# 5-6 マイグレーションでテーブルを作る

> ◎ **このレッスンのゴール**
> - マイグレーションで、テーブルの設計を「コード」として管理できる
> - `migrate` / `rollback` / `refresh` の違いを説明できる
> - 第4部で手書きした `CREATE TABLE` が、Laravelでどう進化するかを理解する

所要 150分 / 難度 🟡

---

## 📖 マイグレーションは「テーブル設計の履歴」

第4部で、あなたは `schema.sql` にこう書きました。

```sql
CREATE TABLE posts (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    title      TEXT NOT NULL,
    body       TEXT NOT NULL,
    created_at TEXT NOT NULL
);
```

これでもテーブルは作れます。でも、チーム開発になると困ることが出てきます。

- 「あとから `user_id` 列を足したい」→ どの環境に反映済み？
- 「新しく参加した人のDBに、同じテーブルを作りたい」→ SQLを手で流す？
- 「間違えた。1つ前の状態に戻したい」→ どう戻す？

**マイグレーション**は、この「テーブルの変更履歴」をPHPファイルとして残し、
コマンド1つで**誰の環境でも同じDB構造を再現**できるようにする仕組みです。

> 💡 **一言でいうと**
> マイグレーション = 「DB構造のGit」。いつ・何を・どう変えたかをコードで残す。

---

## ✍️ 手を動かす① ─ マイグレーションファイルを作る

`make:migration` コマンドでひな形を作ります。

```bash
php artisan make:migration create_posts_table
```

`database/migrations/` に、こんな名前のファイルができます。

```
2026_09_13_120000_create_posts_table.php
```

先頭の日時は**実行順**を決めるためのものです（早い順に実行される）。
ファイル名は自分で書かず、**必ずコマンドで生成**してください。順番がずれると事故ります。

中身のひな形はこうなっています。

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

- `up()` … マイグレーションを**進める**ときの処理（テーブルを作る）
- `down()` … **戻す**ときの処理（テーブルを消す）

「進む」と「戻る」を必ずペアで書くのがポイントです。

---

## ✍️ 手を動かす② ─ カラムを定義する

`up()` の中を、第4部の `posts` テーブルに合わせて書きます。

```php
public function up(): void
{
    Schema::create('posts', function (Blueprint $table) {
        $table->id();                          // BIGINT UNSIGNED / 主キー / AUTO_INCREMENT
        $table->string('title', 100);          // VARCHAR(100) NOT NULL
        $table->text('body');                  // TEXT NOT NULL
        $table->timestamps();                  // created_at / updated_at
    });
}
```

第4部で手書きした `CREATE TABLE` と、1対1で対応しています。

| 手書きSQL | マイグレーション |
| --- | --- |
| `id INTEGER PRIMARY KEY AUTO_INCREMENT` | `$table->id();` |
| `title VARCHAR(100) NOT NULL` | `$table->string('title', 100);` |
| `body TEXT NOT NULL` | `$table->text('body');` |
| `created_at ... , updated_at ...` | `$table->timestamps();` |

よく使うカラム定義：

```php
$table->id();                      // 主キー（bigint, auto increment）
$table->string('name');            // VARCHAR(255)（長さ省略時）
$table->string('email')->unique(); // UNIQUE制約つき
$table->text('body');              // TEXT
$table->integer('price');          // INT
$table->boolean('is_public')->default(false); // 真偽値、初期値あり
$table->timestamp('published_at')->nullable(); // NULL を許可
$table->timestamps();              // created_at と updated_at をまとめて
```

> ⚠️ **`->nullable()` を付けない列は NOT NULL**
> Laravelは「デフォルトでNOT NULL」です。空を許したい列にだけ `->nullable()` を付けます。
> 第4部で学んだ「NULLは慎重に」の考え方はそのまま生きています。

---

## ✍️ 手を動かす③ ─ 実行する

書いたら、DBに反映します。

```bash
php artisan migrate
```

実行結果：

```
   INFO  Running migrations.

  2026_09_13_120000_create_posts_table  56ms DONE
```

これで `posts` テーブルができました。
Laravelは「どのマイグレーションを実行済みか」を **`migrations` という管理用テーブル**に記録しています。
だから2回 `migrate` しても、**同じファイルは二度実行されません**（安全）。

> 🆘 **ここで詰まったら**（`migrate` が失敗する）
> - **`SQLSTATE[HY000] [1049] Unknown database` / `[2002]`**：`.env` のDB設定ミス、またはDBを作っていない。`.env` の `DB_DATABASE` の名前でデータベースを作成し、`php artisan config:clear` してから再実行
> - **`.env` を直したのに変わらない**：設定がキャッシュされている。`php artisan config:clear` を実行（5-1で学んだ通り）
> - **`Syntax error` 等でテーブルが中途半端**：`php artisan migrate:fresh` でまっさらに作り直す（**開発中のみ**。データは消えます）
> - **直らなければ、AIにこう聞く**（エラー全文と `.env` のDB項目を、パスワードは伏せて貼る）：
>   「Laravelの migrate が失敗します。エラーは○○です。原因と確認手順を教えてください」

---

## ✍️ 手を動かす④ ─ 戻す・やり直す

間違えたときのコマンドを覚えましょう。ここが手書きSQLに対する最大の利点です。

```bash
# 直前のマイグレーションを1つ戻す（down() が実行される）
php artisan migrate:rollback

# まとめて実行した「バッチ」単位で戻す
php artisan migrate:rollback --step=1

# 全部戻してから、もう一度全部実行し直す（開発中の作り直しに便利）
php artisan migrate:refresh

# テーブルを全部消してゼロから作り直す（migrations管理表も含めて一掃）
php artisan migrate:fresh
```

| コマンド | 何をする | いつ使う |
| --- | --- | --- |
| `migrate` | 未実行のものを実行 | 通常の反映 |
| `migrate:rollback` | 直前のバッチを `down()` で戻す | ミスした直後 |
| `migrate:refresh` | 全部戻して全部やり直す | 開発中に作り直したい |
| `migrate:fresh` | テーブルを全削除して作り直す | データごとリセット |

> ⚠️ **`fresh` / `refresh` はデータが全部消えます**
> 本番環境では絶対に使いません。開発中の「まっさらに戻したい」ときだけ。
> 第4部で「DELETEはWHEREとセット」と学んだのと同じ緊張感を持ってください。

---

## ✍️ 手を動かす⑤ ─ あとから列を追加する（現場でいちばん多い）

「もう `migrate` 済みのテーブルに、`user_id` を足したい」。
**すでに実行したファイルは編集しません。** 新しいマイグレーションを作ります。

```bash
php artisan make:migration add_user_id_to_posts_table --table=posts
```

```php
public function up(): void
{
    Schema::table('posts', function (Blueprint $table) {
        // 既存の posts テーブルに列を追加
        $table->foreignId('user_id')->nullable()->constrained();
    });
}

public function down(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropConstrainedForeignKey('user_id');
    });
}
```

- `Schema::create` … **新しいテーブル**を作る
- `Schema::table` … **既存テーブル**を変更する（列の追加など）
- `foreignId('user_id')->constrained()` … `users.id` を参照する外部キー（第4部で手書きした `FOREIGN KEY` と同じ）

反映：

```bash
php artisan migrate
```

すでに済んだファイルは飛ばされ、**追加した1件だけ**が実行されます。

> 💡 **なぜ既存ファイルを編集してはいけない？**
> あなたのPCでは編集後の内容で作り直せても、**すでに `migrate` 済みの本番や仲間のDB**は
> 古い内容のまま。「新しいファイルを積み上げる」から、全員が同じ順序で同じ状態にたどり着けます。
> これがGitの「コミットを積む」感覚とそっくりなところ。

---

## ✍️ 手を動かす⑥ ─ シーダーで初期データを入れる

第4部の `seed.sql`（テスト用データ）に相当するのが**シーダー**です。

```bash
php artisan make:seeder PostSeeder
```

`database/seeders/PostSeeder.php`：

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('posts')->insert([
            ['title' => 'はじめまして', 'body' => '最初の投稿です', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'ラテを飲みたい', 'body' => '今日はカフェ日和', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
```

実行：

```bash
php artisan db:seed --class=PostSeeder

# migrate:fresh と同時に流したいとき
php artisan migrate:fresh --seed
```

> 💡 本格的な「大量のダミーデータ」は、次の Eloquent の回で **ファクトリ** を使うともっと楽になります。ここでは「初期データもコードで管理できる」ことだけ押さえればOK。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `Nothing to migrate.` | もう実行済み | 正常。作り直したいなら `migrate:refresh` |
| `Base table already exists` | 同名テーブルを再作成しようとした | `migrate:fresh` でリセット、または新規migrationで `Schema::table` を使う |
| 列を足したのに反映されない | 既存ファイルを編集しただけ | **新しいマイグレーション**を作って `migrate` |
| `Field 'xxx' doesn't have a default value` | NOT NULL列に値を入れていない | `->nullable()` にするか `->default(...)` を付ける |
| ファイル名を手で変えたら順序が壊れた | 先頭の日時が実行順 | 手で作らず必ず `make:migration` |

---

## 🤖 AIに聞いてみよう

マイグレーションは「やりたいこと」を言葉で伝えると、AIが定義を書いてくれる場面が多い領域です。
ただし**そのまま信じない**練習を続けます。

### 使えるプロンプト例

```
Laravel 11 のマイグレーションを書いてください。
- テーブル名: products
- 列: name(必須, 最大100文字), price(整数), description(任意), is_published(真偽, 初期値false)
- created_at / updated_at も
up() と down() の両方を書いてください。
```

### 🔍 AIの答えを疑うチェックリスト

- [ ] `down()` は書かれているか？（省略するAIがいる。ロールバックできなくなる）
- [ ] 「任意」の列に `->nullable()` が付いているか？（付け忘れるとNOT NULLで落ちる）
- [ ] 既存テーブルの変更なのに `Schema::create` を使っていないか？（`Schema::table` が正しい）
- [ ] `->default(false)` の型は合っているか？（`boolean` 列に文字列を入れていないか）
- [ ] 外部キーが `foreignId()->constrained()` になっているか、参照先テーブルは先に作られる順序か？

> 🧠 **考え方**
> AIは「1ファイルの中身」を書くのは得意ですが、**ファイル同士の実行順**（usersを先に作る等）や
> 「本番に既に流れているか」といった**あなたの状況**は知りません。そこは人間の仕事です。

---

## 🔧 やってみよう（演習）

第4部で設計した掲示板を、マイグレーションで作り直します。

1. `users` テーブルのマイグレーションを作る
   - `id` / `name`(必須) / `email`(必須・ユニーク) / `password`(必須) / `timestamps`
2. `posts` テーブルのマイグレーションを作る
   - `id` / `title`(必須,100) / `body`(必須) / `user_id`(usersを参照) / `timestamps`
   - ※実行順に注意：`users` が先、`posts` が後
3. `migrate` して2つのテーブルができることを確認
4. わざと `posts` に `category` 列を**あとから**追加するマイグレーションを作り、`migrate`
5. `migrate:rollback` で 4 を戻し、`category` が消えることを確認

<details>
<summary>ヒント：解答例（posts のマイグレーション）</summary>

```php
public function up(): void
{
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title', 100);
        $table->text('body');
        $table->foreignId('user_id')->constrained();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('posts');
}
```

`category` の追加（別ファイル）：

```php
// make:migration add_category_to_posts_table --table=posts
public function up(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->string('category')->nullable();
    });
}

public function down(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn('category');
    });
}
```

</details>

---

## ✅ 章末チェック

次の問いに、自分の言葉で答えられたら合格です。

1. マイグレーションを使うと、手書きの `CREATE TABLE` に比べて何が嬉しいか、3つ挙げよ。
2. `migrate:rollback` と `migrate:fresh` の違いは？ 本番で使ってはいけないのはどちら？
3. すでに `migrate` 済みのテーブルに列を足したい。やってはいけないことと、正しい手順は？
4. `up()` と `down()` はそれぞれ何のためにあるか？

> 次のレッスンからは、この作ったテーブルに対して**Eloquent**で読み書きします。
> 第3部で書いた `PDO` のSQL文が、どれだけ短くなるか見ものです。

---

[⬅️ 5-5 Bladeテンプレート](05-05-blade.md) ｜ [➡️ 5-7 Eloquent ORM の基本](05-07-eloquent.md)
