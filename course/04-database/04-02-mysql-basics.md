# 4-2 MySQL に触れる / phpMyAdmin

> 🎯 **このレッスンのゴール**
> - phpMyAdmin でデータベースとテーブルを作れる
> - `CREATE TABLE` を自分で書ける
> - 文字コードの設定を正しく行える
> - SQLを実行する3つの手段を使い分けられる

所要 90分 / 難度 🟢
完成コード: [`code/04-02/`](../code/04-02/)

---

## ✍️ 手を動かす① ─ MySQL に接続する

### MySQL を起動する

| 環境 | 操作 |
| --- | --- |
| XAMPP | Control Panel → MySQL の「Start」→ 緑になる |
| MAMP | 「Start Servers」→ ランプが緑になる |

**起動しない場合**は第0部（0-4）の「MySQL が起動しない場合」を参照してください。
`Port 3306 in use` なら、以前入れた MySQL サービスが残っています。

### phpMyAdmin を開く

| 環境 | URL |
| --- | --- |
| XAMPP | `http://localhost/phpmyadmin/` |
| MAMP | `http://localhost:8888/phpMyAdmin/` |

### 接続情報を確認する

**この情報は、第4部の間ずっと必要になります。メモしてください。**

| 環境 | ホスト | ポート | ユーザー | パスワード |
| --- | --- | --- | --- | --- |
| **XAMPP** | `localhost` | 3306 | `root` | **空文字**（何も入れない） |
| **MAMP** | `localhost` | **8889** | `root` | **`root`** |

> ⚠️ **MAMP のパスワードは `root`、ポートは 8889** です。
> ここで詰まる人が非常に多いので、いま書き留めてください。
>
> 正確な値は、MAMP のスタートページ（`http://localhost:8888/MAMP/`）の
> 「MySQL」欄に表示されています。

> 🆘 **ここで詰まったら**（`Access denied for user 'root'@'localhost'`）
> - **まず確認**：上の表のとおり、**XAMPPはパスワード空**、**MAMPはパスワード `root`**。この取り違えが原因の大半です
> - **それでもダメ**：MySQL 自体が起動しているか（XAMPP/MAMPのランプが緑か）を確認
> - **直らなければ、AIにこう聞く**（使っているのが XAMPP か MAMP かを必ず添える）：
>   「MySQL に接続すると Access denied for user 'root'@'localhost' が出ます。○○（XAMPP/MAMP）を使っています。原因と確認手順を教えてください」

---

## ✍️ 手を動かす② ─ データベースを作る

### phpMyAdmin での操作

1. 左メニューの「**新規作成**」をクリック
2. データベース名に `board_app` と入力
3. **照合順序**を **`utf8mb4_unicode_ci`** に設定 ← **最重要**
4. 「作成」をクリック

```
┌─ 新しいデータベースを作成 ──────────────────────┐
│  データベース名: [board_app            ]         │
│  照合順序:       [utf8mb4_unicode_ci ▼]  ← ★     │
│                              [ 作成 ]            │
└──────────────────────────────────────────────────┘
```

> ⚠️ **照合順序を `utf8mb4_unicode_ci` にしてください。**
>
> デフォルトの `latin1_swedish_ci` のままだと、**日本語が文字化けします**。
> 後から変更することもできますが、**既存データが壊れる**ことがあります。
> **作るときに正しく設定する**のが唯一の正解です。

### 照合順序（COLLATE）とは

| 用語 | 意味 |
| --- | --- |
| **文字セット（CHARSET）** | どの文字を保存できるか（`utf8mb4` = 絵文字も可） |
| **照合順序（COLLATE）** | **比較・並び替えのルール** |

```sql
-- utf8mb4_unicode_ci の "ci" は case insensitive（大文字小文字を区別しない）
SELECT * FROM users WHERE email = 'TARO@example.com';
-- → 'taro@example.com' も一致する
```

| 照合順序 | 特徴 |
| --- | --- |
| `utf8mb4_unicode_ci` | **標準的。大文字小文字を区別しない**。この教材で使う |
| `utf8mb4_general_ci` | 古い。速いが、一部の言語で比較が不正確 |
| `utf8mb4_bin` | 完全一致のみ。**大文字小文字を区別する** |
| `utf8mb4_0900_ai_ci` | MySQL 8.0 の新しい既定値。より正確 |

> 💡 **迷ったら `utf8mb4_unicode_ci`** を選んでください。
> MySQL 8.0 なら `utf8mb4_0900_ai_ci` でも構いません。

### SQLで作る場合

phpMyAdmin の「SQL」タブで実行することもできます。

```sql
CREATE DATABASE board_app
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;
```

---

## ✍️ 手を動かす③ ─ テーブルを作る

### `CREATE TABLE` の基本形

phpMyAdmin で `board_app` を選択 → 「SQL」タブ → 以下を貼って実行してください（📋 コピペ可）。

```sql
CREATE TABLE posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(30)  NOT NULL,
    body       TEXT         NOT NULL,
    edit_token CHAR(32)     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
```

### 1行ずつ読む

```sql
id INT AUTO_INCREMENT PRIMARY KEY
```

| 要素 | 意味 |
| --- | --- |
| `INT` | 整数型 |
| `AUTO_INCREMENT` | **自動で連番を振る**（1, 2, 3, ...） |
| `PRIMARY KEY` | **主キー**。重複せず、NULLにならない。自動でインデックスが張られる |

> 💡 **第3部では `bin2hex(random_bytes(8))` で自分でIDを作っていました。**
> データベースなら `AUTO_INCREMENT` に任せられ、**重複の心配がありません**。
>
> 3-11 の欠陥コードで見た「`count($posts) + 1` でID重複」という問題が、これで解決します。

```sql
name VARCHAR(30) NOT NULL
```

| 要素 | 意味 |
| --- | --- |
| `VARCHAR(30)` | 最大30文字の文字列 |
| `NOT NULL` | **NULLを許さない**（必須項目） |

> ⚠️ **`VARCHAR(30)` の「30」は文字数です**（MySQL 5.0以降）。バイト数ではありません。
> 日本語30文字も入ります。

```sql
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
```

`DEFAULT CURRENT_TIMESTAMP` により、**INSERT時に何も指定しなければ現在時刻が入ります**。
PHPで `date()` を書く必要がありません。

```sql
updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
```

`ON UPDATE CURRENT_TIMESTAMP` により、**UPDATE時に自動で現在時刻に更新されます**。

```sql
ENGINE=InnoDB
```

| エンジン | 特徴 |
| --- | --- |
| **InnoDB** | **トランザクション、外部キーが使える**。現在の標準 |
| MyISAM | 古い。トランザクションが使えない。**使わない** |

> ⚠️ **必ず `InnoDB`** にしてください。MySQL 5.5 以降はデフォルトですが、明示するのが安全です。

### NULL と空文字の違い

| | 意味 |
| --- | --- |
| `NULL` | **値が存在しない**（未入力、不明） |
| `''`（空文字） | **空という値が存在する** |

```sql
-- NULL の比較には = を使えない
SELECT * FROM posts WHERE updated_at = NULL;     -- ❌ 何も返らない
SELECT * FROM posts WHERE updated_at IS NULL;    -- ✅
SELECT * FROM posts WHERE updated_at IS NOT NULL; -- ✅
```

> ⚠️ **`= NULL` は必ず何も返しません。** `IS NULL` を使ってください。
> NULL は「不明な値」なので、「不明 = 不明」は判定できない、という考え方です。

> 💡 **設計の指針**：「値がないことに意味がある」場合だけ NULL を許可します。
> 迷ったら `NOT NULL DEFAULT ''` にするほうが、後の処理が単純になります。

---

## ✍️ 手を動かす④ ─ 作ったテーブルを確認する

### phpMyAdmin で

左メニューの `board_app` → `posts` をクリック → 「構造」タブ

```
┌──┬────────────┬──────────────┬──────────┬──────┬─────────────────────┐
│# │ 名前       │ 型           │ 照合順序 │ NULL │ デフォルト値        │
├──┼────────────┼──────────────┼──────────┼──────┼─────────────────────┤
│1 │ id 🔑      │ int          │          │ いいえ│ なし                │
│2 │ name       │ varchar(30)  │ utf8mb4… │ いいえ│ なし                │
│3 │ body       │ text         │ utf8mb4… │ いいえ│ なし                │
│4 │ edit_token │ char(32)     │ utf8mb4… │ いいえ│ なし                │
│5 │ created_at │ datetime     │          │ いいえ│ CURRENT_TIMESTAMP   │
│6 │ updated_at │ datetime     │          │ はい  │ NULL                │
└──┴────────────┴──────────────┴──────────┴──────┴─────────────────────┘
```

### SQLで確認する

```sql
-- テーブルの構造
DESCRIBE posts;
-- または
SHOW COLUMNS FROM posts;

-- CREATE TABLE 文を見る（★ 便利）
SHOW CREATE TABLE posts;

-- テーブル一覧
SHOW TABLES;

-- データベース一覧
SHOW DATABASES;
```

> 💡 **`SHOW CREATE TABLE` は非常に便利です。**
> phpMyAdmin のGUIで作ったテーブルも、**SQLとして書き出せます**。
> これをファイルに保存しておけば、他の環境で同じテーブルを再現できます。

---

## ✍️ 手を動かす⑤ ─ テーブルを変更する

**運用中に「カラムを足したい」は必ず発生します。**

```sql
-- カラムを追加
ALTER TABLE posts ADD COLUMN is_deleted BOOLEAN NOT NULL DEFAULT FALSE;

-- 位置を指定して追加
ALTER TABLE posts ADD COLUMN title VARCHAR(100) NOT NULL DEFAULT '' AFTER id;

-- カラムの型を変更
ALTER TABLE posts MODIFY COLUMN name VARCHAR(50) NOT NULL;

-- カラム名を変更
ALTER TABLE posts CHANGE COLUMN body content TEXT NOT NULL;

-- カラムを削除（★ データが消える。取り消せない）
ALTER TABLE posts DROP COLUMN is_deleted;

-- インデックスを追加
ALTER TABLE posts ADD INDEX idx_created_at (created_at);

-- テーブル名を変更
RENAME TABLE posts TO articles;

-- テーブルを削除（★ 全データが消える）
DROP TABLE posts;
```

> ⚠️ **`DROP TABLE` と `DROP COLUMN` は取り消せません。**
> 実行前に必ずバックアップを取ってください（後述）。

### カラムを追加するときの注意

```sql
-- ❌ 既にデータがある場合、NOT NULL なのに値がない行ができてエラー
ALTER TABLE posts ADD COLUMN title VARCHAR(100) NOT NULL;

-- ✅ DEFAULT を付ける
ALTER TABLE posts ADD COLUMN title VARCHAR(100) NOT NULL DEFAULT '';

-- ✅ または NULL を許可する
ALTER TABLE posts ADD COLUMN title VARCHAR(100) NULL;
```

---

## ✍️ 手を動かす⑥ ─ SQLを実行する3つの手段

| 手段 | 使いどころ |
| --- | --- |
| **phpMyAdmin** | 学習中、構造の確認、データの目視 |
| **コマンドライン** | バックアップ、一括実行、本番サーバー |
| **PHPから（PDO）** | **アプリケーション本体**（4-7で学ぶ） |

### コマンドラインで使う

```bash
# XAMPP（Windows）
C:\xampp\mysql\bin\mysql -u root

# MAMP（Mac）
/Applications/MAMP/Library/bin/mysql -u root -proot -P 8889
```

> 💡 **PATH を通しておくと `mysql` だけで起動できます。**
> XAMPP なら `C:\xampp\mysql\bin`、MAMP なら `/Applications/MAMP/Library/bin` を
> 環境変数 PATH に追加してください（第0部 0-4 と同じ手順）。

```sql
-- 接続後の操作
SHOW DATABASES;
USE board_app;
SHOW TABLES;
SELECT * FROM posts;
exit
```

> ⚠️ **コマンドラインでは、SQLの末尾に `;` が必須**です。
> `;` を忘れると、次の行の入力待ち（`->`）になります。**`;` を打てば実行されます。**

### 文字化けする場合

```sql
-- 接続の文字コードを確認
SHOW VARIABLES LIKE 'character_set%';

-- 明示的に設定
SET NAMES utf8mb4;
```

Windows のコマンドプロンプトは文字コードの相性が悪いことがあります。
**phpMyAdmin を使うのが確実です。**

---

## ✍️ 手を動かす⑦ ─ バックアップと復元

**これは必ず身につけてください。** `DROP TABLE` を間違えて実行したときの命綱です。

### phpMyAdmin でエクスポート

1. `board_app` を選択
2. 「**エクスポート**」タブ
3. 方法：「簡易」、フォーマット：「SQL」
4. 「エクスポート」→ `board_app.sql` がダウンロードされる

**このファイルには、`CREATE TABLE` と `INSERT` が全部入っています。**
テキストエディタで開いて中を見てください。

### phpMyAdmin でインポート

1. データベースを選択
2. 「**インポート**」タブ
3. ファイルを選択 → 「実行」

### コマンドラインで

```bash
# バックアップ（ダンプ）
mysqldump -u root board_app > backup.sql

# パスワードがある場合（MAMP）
mysqldump -u root -proot -P 8889 board_app > backup.sql

# 復元
mysql -u root board_app < backup.sql
```

> 💡 **`mysqldump` は実務で毎日使うコマンドです。**
> 本番サーバーでは、これを自動実行（cron）してバックアップを取ります。

> ⚠️ **作業前にバックアップを取る習慣をつけてください。**
> 「`DELETE` の `WHERE` を書き忘れて全件消した」は、実際に起きる事故です。

---

## ✍️ 手を動かす⑧ ─ テストデータを入れる

次のレッスン（SELECT）のために、データを入れておきます。

phpMyAdmin の「SQL」タブで実行してください（📋 コピペ可）。

```sql
-- 掲示板のテストデータ
INSERT INTO posts (name, body, edit_token, created_at) VALUES
('山田太郎',   'はじめまして。よろしくお願いします。',        REPEAT('a', 32), '2026-04-10 09:15:00'),
('名無しさん', 'テスト投稿です。',                            REPEAT('b', 32), '2026-04-10 14:30:00'),
('佐藤花子',   'このサイト、使いやすいですね。',              REPEAT('c', 32), '2026-04-11 08:00:00'),
('鈴木一郎',   '質問があります。\nどこに書けばいいですか？',  REPEAT('d', 32), '2026-04-11 19:45:00'),
('名無しさん', '↑ここでいいと思います',                       REPEAT('e', 32), '2026-04-12 07:20:00'),
('田中美咲',   '週末のイベント、参加します！',                REPEAT('f', 32), '2026-04-12 12:10:00'),
('山田太郎',   '私も参加します。',                            REPEAT('g', 32), '2026-04-12 21:00:00'),
('高橋健',     'よろしくお願いします 😊',                     REPEAT('h', 32), '2026-04-13 10:05:00'),
('名無しさん', 'テスト',                                      REPEAT('i', 32), '2026-04-13 15:40:00'),
('渡辺さくら', '写真を投稿できる機能が欲しいです。',          REPEAT('j', 32), '2026-04-14 11:30:00'),
('伊藤大輔',   'スマホからでも見やすくて助かります。',        REPEAT('k', 32), '2026-04-14 16:55:00'),
('名無しさん', 'こんにちは',                                  REPEAT('l', 32), '2026-04-15 08:30:00');
```

### 確認する

```sql
SELECT * FROM posts;
SELECT COUNT(*) FROM posts;   -- 12 が返るはず
```

> 💡 **絵文字（😊）が正しく表示されることを確認してください。**
> `?` や `????` になったら、**文字セットが `utf8mb4` になっていません**。
> データベースを作り直してください。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| phpMyAdmin が開けない | MySQL が起動していない | Control Panel で起動 |
| `Access denied for user 'root'` | パスワードが違う | **XAMPP は空、MAMP は `root`** |
| 日本語が `???` になる | 文字セットが `utf8mb4` でない | データベースを作り直す |
| 絵文字が保存できない | `utf8`（3バイト）になっている | `utf8mb4` にする |
| `ALTER TABLE` でエラー | 既存データが `NOT NULL` を満たせない | `DEFAULT` を付ける |
| `= NULL` で何も返らない | NULL の比較は `=` では不可 | `IS NULL` を使う |
| コマンドラインで実行されない | `;` を忘れた | `;` を打つ |
| `Unknown database` | データベース名の打ち間違い | `SHOW DATABASES;` で確認 |
| `Table already exists` | 同名のテーブルがある | `DROP TABLE` するか名前を変える |

### `CREATE TABLE IF NOT EXISTS`

```sql
CREATE TABLE IF NOT EXISTS posts ( ... );
```

**既に存在する場合はエラーにならず、何もしません。** セットアップ用のSQLでは便利です。

---

## 🤖 AIに聞いてみよう

### ① CREATE TABLE をレビューさせる

```text
以下は、私が書いた MySQL の CREATE TABLE です。

（SQLを貼る）

次の観点でレビューしてください。

1. 型の選択が適切か（長すぎる / 短すぎる / 不適切な型）
2. NOT NULL / NULL の設計が適切か
3. 文字セット・照合順序の指定
4. デフォルト値の設定
5. インデックスを張るべきカラム
6. 命名規則（テーブル名は複数形か、カラム名は snake_case か）
7. 将来の拡張で問題になりそうな点

MySQL 8.0 / InnoDB を想定しています。
修正後のSQLは書かず、指摘だけをお願いします。
```

### ② 文字セットの問題を理解する

```text
MySQL の文字セットについて、初学者として正しく理解したいです。

1. utf8 と utf8mb4 の違いは何ですか。なぜ2つあるのですか
2. utf8 を使うと、具体的にどんな不具合が起きますか
3. 照合順序（collation）とは何で、どう選べばよいですか
4. すでに utf8 で作ってしまったデータベースを utf8mb4 に
   変更する手順と、そのリスクを教えてください
5. 文字化けが起きたとき、どこを確認すべきかのチェックリストを作ってください

（データベース / テーブル / カラム / 接続 のどの層に問題があるか
 切り分けられるようにしたいです）
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）

- [ ] `board_app` データベースを `utf8mb4_unicode_ci` で作る
- [ ] `posts` テーブルを作る
- [ ] テストデータ12件を投入する
- [ ] `SELECT * FROM posts;` で12件表示されることを確認
- [ ] **絵文字が正しく表示されることを確認**
- [ ] `SHOW CREATE TABLE posts;` の結果をメモ帳に保存する
- [ ] エクスポートして `.sql` ファイルを保存する

### 演習2（必須）

以下のテーブルを作ってください。**型と制約を自分で考えます。**

**`users` テーブル**
- [ ] ID（自動連番、主キー）
- [ ] 名前（必須、50文字以内）
- [ ] メールアドレス（必須、**重複不可**、255文字以内）
- [ ] パスワードハッシュ（必須、255文字）
- [ ] プロフィール画像のファイル名（任意）
- [ ] 管理者フラグ（必須、デフォルトは false）
- [ ] 作成日時（必須、自動）
- [ ] 更新日時（任意、自動更新）

> 💡 「重複不可」は `UNIQUE` 制約で実現します。調べて使ってください。

<details>
<summary>答えを見る</summary>

```sql
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(50)  NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar        VARCHAR(255) NULL DEFAULT NULL,
    is_admin      BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**ポイント**
- `email` に `UNIQUE` を付けると、**同じメールアドレスで二重登録できなくなります**。
  PHPのコードで重複チェックをしても、**同時アクセスでは通り抜けます**（競合状態）。
  データベースの制約が、最後の砦になります。
- `password_hash` は `VARCHAR(255)`（3-8で学んだ通り、アルゴリズム変更に備える）
- `avatar` は「画像を設定していない」状態があるので `NULL` を許可

</details>

### 演習3（挑戦）

- [ ] `users` テーブルにテストデータを3件入れる
      （パスワードは `password_hash("password123", PASSWORD_DEFAULT)` の結果を使う）
- [ ] 同じメールアドレスで2回 INSERT を試み、**エラーになることを確認**する
- [ ] そのエラーメッセージを記録する（第4部の後半で、PHPから捕まえます）
- [ ] `ALTER TABLE` で「自己紹介文」カラム（`TEXT`、任意）を追加する
- [ ] `posts` テーブルに `created_at` のインデックスを追加する
- [ ] テーブルを `DROP` してから、エクスポートした `.sql` で復元できることを確認する

> 💡 **最後の「復元できることを確認」が最も重要です。**
> バックアップは「取ること」より「戻せること」に意味があります。
> 実務では、**復元のテストをしていないバックアップは、バックアップとみなされません。**

---

## ✅ 章末チェック

- [ ] 自分の環境の接続情報（ユーザー・パスワード・ポート）をメモした
- [ ] データベースを `utf8mb4_unicode_ci` で作れる
- [ ] `CREATE TABLE` を自分で書ける
- [ ] `AUTO_INCREMENT` と `PRIMARY KEY` の役割を説明できる
- [ ] `NOT NULL` の意味と、NULL / 空文字の違いを説明できる
- [ ] `= NULL` ではなく `IS NULL` を使う理由を言える
- [ ] `utf8` ではなく `utf8mb4` が必要な理由を言える
- [ ] `ENGINE=InnoDB` を指定する理由を言える
- [ ] `ALTER TABLE` でカラムを追加できる
- [ ] エクスポートと復元ができる

---

**前 → [4-1 データベースが必要な理由](04-01-why-database.md)　｜　次 → [4-3 SELECT で取り出す](04-03-select.md)**
