# 4-4 INSERT / UPDATE / DELETE

> ◎ **このレッスンのゴール**
> - データの追加・更新・削除ができる
> - **`WHERE` の書き忘れで全件を壊す事故**を防げる
> - トランザクションで「全部成功か、全部取り消し」を実現できる

所要 120分 / 難度 🟢
完成コード: [`code/04-04/`](../code/04-04/)

---

## ⚠️ 最初に読んでください

このレッスンで扱う3つの命令は、**データを壊せます**。

```sql
DELETE FROM posts;           -- 全投稿が消える
UPDATE posts SET body = '';  -- 全投稿の本文が空になる
```

**どちらも取り消せません。**

### 事故を防ぐ3つのルール

```
① 作業前にバックアップを取る（4-2 のエクスポート）
② UPDATE / DELETE を書く前に、同じ WHERE で SELECT して確認する
③ 本番環境では、いきなり実行せずトランザクションで囲む
```

**②が最重要です。** 以下を習慣にしてください。

```sql
-- ステップ1：まず SELECT で対象を確認する
SELECT * FROM posts WHERE id = 5;
--   → 1件だけ表示された。想定通り

-- ステップ2：同じ WHERE で DELETE する
DELETE FROM posts WHERE id = 5;
```

> 💡 **「SELECT で確認してから UPDATE / DELETE」は、実務でも必ず行う手順です。**
> これをやらずに `WHERE` を書き忘れ、本番のデータを全件更新した——という事故は、
> 毎年どこかで起きています。

---

## ✍️ 手を動かす① ─ INSERT でデータを追加する

```sql
-- 基本形
INSERT INTO posts (name, body, edit_token)
VALUES ('テスト投稿者', 'これはテストです', REPEAT('z', 32));
```

**指定していないカラムはどうなるか**

| カラム | 定義 | 結果 |
| --- | --- | --- |
| `id` | `AUTO_INCREMENT` | **自動で採番される** |
| `created_at` | `DEFAULT CURRENT_TIMESTAMP` | **現在時刻が入る** |
| `user_id` | `NULL DEFAULT NULL` | `NULL` が入る |
| `updated_at` | `NULL DEFAULT NULL` | `NULL` が入る |

> 💡 **`AUTO_INCREMENT` と `DEFAULT` のおかげで、書くカラムが減ります。**
> 第3部では `bin2hex(random_bytes(8))` と `time()` を自分で書いていました。

### 複数行をまとめて追加

```sql
INSERT INTO posts (name, body, edit_token) VALUES
('Aさん', '1つ目', REPEAT('1', 32)),
('Bさん', '2つ目', REPEAT('2', 32)),
('Cさん', '3つ目', REPEAT('3', 32));
```

> 💡 **1行ずつ3回 INSERT するより、まとめて1回のほうが圧倒的に速いです。**
> 1000件のデータを入れるなら、この違いは数十倍になります。

### 追加したデータのIDを取得する

```sql
INSERT INTO posts (name, body, edit_token) VALUES ('X', 'Y', REPEAT('x', 32));
SELECT LAST_INSERT_ID();
```

**PHPからは `$pdo->lastInsertId()` で取得します**（4-7で扱います）。

> ⚠️ **`LAST_INSERT_ID()` は「自分の接続で最後に採番した値」を返します。**
> 他のユーザーが同時に INSERT しても、混ざりません。安心して使えます。

### 値を明示的に指定する

```sql
-- created_at も指定できる
INSERT INTO posts (name, body, edit_token, created_at)
VALUES ('過去の投稿', '昔の投稿です', REPEAT('p', 32), '2026-01-01 00:00:00');

-- id を明示することもできる（通常はしない）
INSERT INTO posts (id, name, body, edit_token)
VALUES (100, 'ID指定', '本文', REPEAT('q', 32));
--   → 次の AUTO_INCREMENT は 101 になる
```

### エラーになるケース

```sql
-- ① NOT NULL のカラムを省略した
INSERT INTO posts (name) VALUES ('太郎');
--   Error: Field 'body' doesn't have a default value

-- ② UNIQUE 制約に違反した
INSERT INTO users (name, email, password_hash)
VALUES ('別人', 'taro@example.com', 'hash');
--   Error: Duplicate entry 'taro@example.com' for key 'users.email'

-- ③ 外部キー制約に違反した（存在しない user_id を指定）
INSERT INTO posts (user_id, name, body, edit_token)
VALUES (999, '太郎', '本文', REPEAT('r', 32));
--   Error: Cannot add or update a child row: a foreign key constraint fails

-- ④ 文字数が上限を超えた
INSERT INTO posts (name, body, edit_token)
VALUES (REPEAT('あ', 50), '本文', REPEAT('s', 32));
--   Error: Data too long for column 'name' at row 1
```

> 💡 **これらのエラーは「データベースが守ってくれた」証拠です。**
> PHPのコードにバグがあっても、不正なデータが入りません。
>
> **PHPからこのエラーを捕まえて、ユーザーに適切なメッセージを出す**のが 4-7 の内容です。

### 重複を無視・上書きする

```sql
-- 重複したら何もしない（エラーにしない）
INSERT IGNORE INTO tags (name) VALUES ('質問');

-- 重複したら更新する（UPSERT）
INSERT INTO tags (name) VALUES ('質問')
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

> ⚠️ **`INSERT IGNORE` は、すべてのエラーを無視します。**
> 文字数超過なども黙って切り詰められるため、**バグを隠します**。
> 「重複だけを無視したい」なら、`ON DUPLICATE KEY UPDATE` を使うほうが安全です。

---

## ✍️ 手を動かす② ─ UPDATE でデータを更新する

```sql
-- 必ず WHERE を付ける
UPDATE posts SET body = '修正しました' WHERE id = 1;

-- 複数カラムを同時に
UPDATE posts
SET name = '山田太郎（修正）',
    body = '内容を更新しました'
WHERE id = 1;

-- 現在の値を使った計算
UPDATE products SET stock = stock - 1 WHERE id = 5;
UPDATE posts SET view_count = view_count + 1 WHERE id = 1;
```

### ⚠️ `WHERE` を書き忘れると全件が更新される

```sql
-- ❌ 全投稿の本文が「あ」になる
UPDATE posts SET body = 'あ';
```

**MySQL の設定で防ぐ方法があります。**

```sql
-- safe update モードを有効にする
SET SQL_SAFE_UPDATES = 1;

UPDATE posts SET body = 'あ';
--   Error: You are using safe update mode and you tried to update
--          a table without a WHERE that uses a KEY column
```

> 💡 **MySQL Workbench では、この設定がデフォルトで有効です。**
> phpMyAdmin では無効なので、**自分で気をつける必要があります**。
>
> 学習中は、毎回のセッションで `SET SQL_SAFE_UPDATES = 1;` を実行しておくと安全です。

### 更新前に必ず SELECT する

```sql
-- ① 対象を確認
SELECT id, name, body FROM posts WHERE name = '名無しさん';
--   → 4件。想定通り

-- ② 同じ WHERE で UPDATE
UPDATE posts SET name = 'ゲスト' WHERE name = '名無しさん';
--   → Query OK, 4 rows affected
```

**`4 rows affected` を確認してください。** 想定と違う件数なら、`WHERE` が間違っています。

### `updated_at` の自動更新

```sql
UPDATE posts SET body = '更新' WHERE id = 1;
SELECT id, created_at, updated_at FROM posts WHERE id = 1;
```

`ON UPDATE CURRENT_TIMESTAMP` を定義していれば、**`updated_at` が自動で更新されます**。

> ⚠️ **値が変わらない UPDATE では、`updated_at` も更新されません。**
>
> ```sql
> UPDATE posts SET body = body WHERE id = 1;   -- 0 rows affected
> ```
>
> MySQL は「実際に値が変わった行」だけを更新済みとみなします。

---

## ✍️ 手を動かす③ ─ DELETE でデータを削除する

```sql
-- 1件削除
DELETE FROM posts WHERE id = 13;

-- 条件で複数削除
DELETE FROM posts WHERE created_at < '2026-01-01';

-- ❌ 全件削除（WHERE なし）
DELETE FROM posts;
```

### `DELETE` / `TRUNCATE` / `DROP` の違い

| 命令 | 消えるもの | AUTO_INCREMENT | 取り消し |
| --- | --- | --- | --- |
| `DELETE FROM t WHERE ...` | 該当行 | リセットされない | **トランザクション内なら可** |
| `DELETE FROM t` | 全行 | リセットされない | トランザクション内なら可 |
| `TRUNCATE TABLE t` | 全行（高速） | **1に戻る** | **不可** |
| `DROP TABLE t` | **テーブルごと** | — | **不可** |

> ⚠️ **`TRUNCATE` と `DROP` はトランザクションで取り消せません。**
> 実行した瞬間に確定します。**本番環境では絶対に使わないでください。**

> 🆘 **ここで詰まったら**（`DELETE`/`UPDATE` が効かない・消えすぎた・エラーになる）
> - **事故防止のクセ**：`DELETE`/`UPDATE` を書くときは、**先に同じ `WHERE` で `SELECT`** して対象件数を確認してから実行する。これで「全件消し」をほぼ防げます
> - **`Cannot delete or update a parent row: a foreign key constraint fails`**：他テーブルから参照されている行を消そうとした。先に子の行を消すか、外部キーの `ON DELETE`（`SET NULL`/`CASCADE`）の設計を確認（次の4-5）
> - **phpMyAdminで「セーフモード」的に消せない**：`WHERE` 無しの危険な操作をブロックしている場合がある。条件を付ける
> - **直らなければ、AIにこう聞く**（実行したSQLとテーブルの関係を貼る）：
>   「この DELETE（UPDATE）が意図どおりに動きません（またはエラーになります）。原因と、安全な実行手順を教えてください」

### 外部キー制約と削除

`schema.sql` では、以下のように定義しました。

```sql
-- posts テーブル
CONSTRAINT fk_posts_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL      -- ★ ユーザーが消えたら user_id を NULL にする

-- comments テーブル
CONSTRAINT fk_comments_post
    FOREIGN KEY (post_id) REFERENCES posts(id)
    ON DELETE CASCADE       -- ★ 投稿が消えたら返信も消す
```

**実際に試してみてください。**

```sql
-- 投稿1には返信が3件ある
SELECT COUNT(*) FROM comments WHERE post_id = 1;   -- 3

-- 投稿1を削除する
DELETE FROM posts WHERE id = 1;

-- 返信も自動で消えている
SELECT COUNT(*) FROM comments WHERE post_id = 1;   -- 0
```

### `ON DELETE` の選択肢

| 設定 | 挙動 | 使いどころ |
| --- | --- | --- |
| `CASCADE` | **一緒に削除** | 返信、中間テーブル（親がないと意味がないもの） |
| `SET NULL` | NULL にする | 投稿者が退会しても投稿は残したい |
| `RESTRICT`（既定） | **削除を拒否** | 注文がある商品は消させない |
| `NO ACTION` | RESTRICT と同じ | — |

> ⚠️ **`CASCADE` は便利ですが、危険です。**
> 「ユーザーを1人削除したら、投稿も返信もいいねも全部消えた」という事態が起きます。
> **どこまで連鎖するかを、設計時に必ず確認してください。**

### 論理削除という選択

**実務では、物理的に削除しないことが多いです。**

```sql
-- deleted_at カラムを追加する
ALTER TABLE posts ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL;

-- 「削除」＝日時を入れるだけ
UPDATE posts SET deleted_at = NOW() WHERE id = 5;

-- 表示するときは、削除済みを除く
SELECT * FROM posts WHERE deleted_at IS NULL ORDER BY created_at DESC;
```

| | 物理削除（`DELETE`） | 論理削除（`deleted_at`） |
| --- | --- | --- |
| 復元 | **できない** | **できる** |
| 集計への影響 | データが消える | 過去の集計が保てる |
| 容量 | 減る | 増え続ける |
| クエリ | 単純 | **毎回 `WHERE deleted_at IS NULL` が必要** |
| 誤削除 | 致命的 | 復元できる |

> 💡 **ユーザーが「消した」と思うものは、論理削除にするのが安全です。**
> ただし、**`WHERE deleted_at IS NULL` を書き忘れると、削除済みが表示されます**。
> Laravel（第5部）には、これを自動でやってくれる仕組み（SoftDeletes）があります。

---

## ✍️ 手を動かす④ ─ トランザクション

**「全部成功か、全部取り消し」を実現する仕組みです。**

### なぜ必要か

「投稿を削除し、同時に投稿数カウンタを減らす」処理を考えます。

```sql
DELETE FROM posts WHERE id = 5;              -- ① 成功
UPDATE users SET post_count = post_count - 1 WHERE id = 1;   -- ② ここで停電
```

**①だけが実行され、カウンタがずれたまま残ります。** これを防ぐのがトランザクションです。

### 基本の使い方

```sql
START TRANSACTION;

DELETE FROM posts WHERE id = 5;
UPDATE users SET post_count = post_count - 1 WHERE id = 1;

COMMIT;      -- ここで確定する
```

途中で問題が起きたら、

```sql
ROLLBACK;    -- 開始時点まで巻き戻す
```

### 動作を確認する

phpMyAdmin では接続が切れるため、**コマンドラインで試してください**。

```sql
-- 現在の件数
SELECT COUNT(*) FROM posts;      -- 12

START TRANSACTION;

DELETE FROM posts;               -- 全件削除
SELECT COUNT(*) FROM posts;      -- 0（自分にはそう見える）

ROLLBACK;                        -- 取り消す

SELECT COUNT(*) FROM posts;      -- 12（戻った！）
```

> 💡 **これを1回やっておくと、安心感が全く違います。**
> 「`ROLLBACK` すれば戻せる」を体験しておいてください。

### 危険な作業の手順

```sql
START TRANSACTION;

-- 影響範囲を確認
SELECT COUNT(*) FROM posts WHERE created_at < '2026-04-12';   -- 4件

DELETE FROM posts WHERE created_at < '2026-04-12';
--   → Query OK, 4 rows affected

-- 想定通りなら
COMMIT;

-- 想定と違ったら
-- ROLLBACK;
```

**本番環境での作業は、必ずこの形にしてください。**

### 自動コミットについて

MySQL はデフォルトで **autocommit が有効**です。

```sql
-- 1つ1つの文が、自動的に確定される
DELETE FROM posts WHERE id = 1;   -- この瞬間に確定
```

`START TRANSACTION` を書くと、`COMMIT` するまで確定されません。

```sql
-- autocommit を切ることもできる
SET autocommit = 0;
-- 以降、明示的に COMMIT するまで確定しない
```

> ⚠️ **`TRUNCATE` / `DROP` / `ALTER TABLE` は、トランザクション内でも即座に確定します**
> （暗黙のコミットが発生します）。**`ROLLBACK` で戻せません。**

### ACID という性質

トランザクションが保証する4つの性質です。

| 頭文字 | 意味 | 具体的に |
| --- | --- | --- |
| **A** Atomicity（原子性） | **全部か、なしか** | 途中で止まっても中途半端にならない |
| **C** Consistency（一貫性） | 制約が保たれる | 外部キーなどが常に整合している |
| **I** Isolation（独立性） | 他の処理から見えない | `COMMIT` 前の変更は他人に見えない |
| **D** Durability（永続性） | 確定したら消えない | `COMMIT` 後は停電でも残る |

> 💡 **第3部のJSONファイル保存では、A（原子性）とD（永続性）が保証できませんでした。**
> 書き込み途中で停電すれば、ファイルが壊れます。
> **これがデータベースを使う最大の理由の1つです。**

---

## ✍️ 手を動かす⑤ ─ 実践：安全な一括処理

**「名無しさん」を「ゲスト」に変える**作業を、安全な手順で行ってください。

```sql
-- ==========================================================
-- ステップ1：バックアップ（phpMyAdmin でエクスポート）
-- ==========================================================

-- ==========================================================
-- ステップ2：影響範囲を確認
-- ==========================================================
SELECT COUNT(*) AS 対象件数 FROM posts WHERE name = '名無しさん';
--   → 4

SELECT id, name, LEFT(body, 20) AS 本文 FROM posts WHERE name = '名無しさん';
--   → 内容を目で確認

-- ==========================================================
-- ステップ3：トランザクションで実行
-- ==========================================================
START TRANSACTION;

UPDATE posts SET name = 'ゲスト' WHERE name = '名無しさん';
--   → Query OK, 4 rows affected   ★ 件数が一致することを確認

-- 結果を確認
SELECT id, name FROM posts WHERE name IN ('名無しさん', 'ゲスト');

-- ==========================================================
-- ステップ4：確定または取り消し
-- ==========================================================
COMMIT;
-- ROLLBACK;   ← 想定と違ったらこちら
```

> ⚠️ **`4 rows affected` の「4」が、ステップ2で確認した件数と一致すること**を必ず確認してください。
> 一致しなければ `WHERE` が間違っています。`ROLLBACK` してください。

### 元に戻す

```sql
START TRANSACTION;
UPDATE posts SET name = '名無しさん' WHERE name = 'ゲスト';
COMMIT;
```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| 全件が更新された | `WHERE` を書き忘れた | **SELECT で確認してから実行** |
| `Field 'x' doesn't have a default value` | `NOT NULL` のカラムを省略した | 値を指定するか `DEFAULT` を設定 |
| `Duplicate entry` | `UNIQUE` 制約に違反 | 既存データを確認 |
| `foreign key constraint fails` | 参照先が存在しない | 親を先に作る |
| `Data too long for column` | 文字数超過 | 入力を制限するか、カラムを広げる |
| 削除したら関連データも消えた | `ON DELETE CASCADE` | 設計を確認 |
| 削除できない | `ON DELETE RESTRICT` | 子を先に削除する |
| `ROLLBACK` で戻らない | `TRUNCATE` / `DROP` / `ALTER` を実行した | これらは暗黙コミットされる |
| `0 rows affected` | 値が変わっていない / `WHERE` が該当しない | `SELECT` で確認 |
| `updated_at` が更新されない | 値が変わっていない | MySQL の仕様 |

---

## 🤖 AIに聞いてみよう

### ① 物理削除と論理削除を相談する

```text
掲示板アプリで、投稿の削除をどう実装すべきか相談です。

1. 物理削除（DELETE）と論理削除（deleted_at）の使い分けの基準
2. 論理削除にした場合、注意すべき点
   （クエリの書き忘れ、UNIQUE制約との相性、容量、法令対応）
3. 個人情報を含むデータの場合、どう考えるべきか
   （削除依頼を受けたときに論理削除で足りるか）
4. 中間的な方法（一定期間後に物理削除する、アーカイブテーブルに移す）の是非

掲示板のような小規模アプリでの推奨と、
実務の大規模アプリでの一般的な選択を、それぞれ教えてください。
```

> 💡 **4番目の観点が実務的に重要です。** 「削除しました」とユーザーに伝えて
> 論理削除しかしていない場合、個人情報保護の観点で問題になることがあります。

### ② 外部キーの ON DELETE を設計させる

```text
以下のテーブル構成で、外部キー制約の ON DELETE を
どう設定すべきか教えてください。

【テーブル】
- users（利用者）
- posts（投稿）… user_id
- comments（返信）… post_id
- likes（いいね）… user_id, post_id
- orders（注文）… user_id
- order_items（注文明細）… order_id, product_id
- products（商品）

それぞれの外部キーについて、
CASCADE / SET NULL / RESTRICT のどれを選ぶべきか、
理由つきで教えてください。

特に「ユーザーが退会したとき、何が消えて何が残るべきか」を
業務的な観点から説明してください。
```

---

## 🔧 やってみよう（演習）

**演習の前に、必ずバックアップを取ってください。**

### 演習1（必須）

以下を実行してください。**UPDATE / DELETE の前には必ず SELECT で確認**します。

- [ ] ① 自分の名前で投稿を1件追加する
- [ ] ② `LAST_INSERT_ID()` で、追加した投稿のIDを確認する
- [ ] ③ 3件をまとめて1回の INSERT で追加する
- [ ] ④ ①で追加した投稿の本文を更新する
- [ ] ⑤ `updated_at` が自動で入っていることを確認する
- [ ] ⑥ ③で追加した3件を、1回の DELETE で削除する
- [ ] ⑦ 存在しない `user_id`（999）で INSERT を試み、**エラーになることを確認**する
- [ ] ⑧ 既存のメールアドレスで `users` に INSERT を試み、**エラーになることを確認**する

### 演習2（必須）─ トランザクションを体験する

**コマンドラインで実行してください**（phpMyAdmin では接続が切れます）。

- [ ] `SELECT COUNT(*) FROM posts;` で件数を記録する
- [ ] `START TRANSACTION;`
- [ ] `DELETE FROM posts;` を実行する
- [ ] `SELECT COUNT(*) FROM posts;` が 0 になることを確認する
- [ ] `ROLLBACK;`
- [ ] `SELECT COUNT(*) FROM posts;` が元に戻ることを確認する
- [ ] 同じ手順を `COMMIT;` で試す → **戻らないことを確認**する
- [ ] バックアップから復元する

> ⚠️ **最後の「COMMIT で戻らない」を体験してください。**
> 「ROLLBACK すれば戻る」と「COMMIT したら戻らない」の両方を知ることが重要です。

### 演習3（必須）─ 外部キーの連鎖を確認する

- [ ] 投稿1の返信が3件あることを確認する
- [ ] `START TRANSACTION;` してから投稿1を削除する
- [ ] 返信も消えていることを確認する（`ON DELETE CASCADE`）
- [ ] `ROLLBACK;` で戻す
- [ ] ユーザー1を削除してみる → 投稿はどうなるか（`ON DELETE SET NULL`）
- [ ] `ROLLBACK;` で戻す

### 演習4（挑戦）─ 論理削除に移行する

- [ ] `posts` に `deleted_at DATETIME NULL DEFAULT NULL` を追加する
- [ ] 投稿を1件「論理削除」する（`deleted_at` に現在時刻を入れる）
- [ ] 一覧取得のSQLを、削除済みを除外する形に書き換える
- [ ] 「削除済みの投稿を復元する」SQLを書く
- [ ] 「30日以上前に削除された投稿を物理削除する」SQLを書く

<details>
<summary>答えを見る</summary>

```sql
-- カラム追加
ALTER TABLE posts ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL;

-- 論理削除
UPDATE posts SET deleted_at = NOW() WHERE id = 5;

-- 一覧（削除済みを除く）
SELECT id, name, body, created_at
FROM posts
WHERE deleted_at IS NULL
ORDER BY created_at DESC;

-- 復元
UPDATE posts SET deleted_at = NULL WHERE id = 5;

-- 30日以上前に削除されたものを物理削除
DELETE FROM posts
WHERE deleted_at IS NOT NULL
  AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

**最後のSQLは、実務では cron（定期実行）で自動化します。**
ただし、実行前に必ず `SELECT COUNT(*)` で件数を確認する処理を入れてください。

</details>

---

## ✅ 章末チェック

- [ ] `INSERT` で複数行をまとめて追加できる
- [ ] `AUTO_INCREMENT` と `DEFAULT` により書くカラムが減ることを理解した
- [ ] `LAST_INSERT_ID()` の使い方を知っている
- [ ] **`UPDATE` / `DELETE` の前に `SELECT` で確認する**習慣がついた
- [ ] `n rows affected` の件数を確認する理由を言える
- [ ] `DELETE` / `TRUNCATE` / `DROP` の違いを説明できる
- [ ] `ON DELETE CASCADE` / `SET NULL` / `RESTRICT` を使い分けられる
- [ ] 論理削除のメリットとデメリットを言える
- [ ] トランザクションで `ROLLBACK` できることを体験した
- [ ] `TRUNCATE` が `ROLLBACK` できない理由を知っている
- [ ] ACID の4つの性質を説明できる

---

**前 → [4-3 SELECT で取り出す](04-03-select.md)　｜　次 → [4-5 テーブル設計と正規化の基本](04-05-design.md)**
