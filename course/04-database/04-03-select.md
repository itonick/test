# 4-3 SELECT で取り出す

> 🎯 **このレッスンのゴール**
> - `SELECT` で狙ったデータを取り出せる
> - `WHERE` で条件を指定できる
> - 並び替え・件数制限・集計ができる
> - NULL の扱いで事故らない

所要 120分 / 難度 🟢
完成コード: [`code/04-03/`](../code/04-03/)

---

## 📖 このレッスンの進め方

**実行結果を載せています。自分の環境でも同じ結果になることを確認してください。**

使うデータは、前回投入した `posts` テーブル（12件）です。
まだ入れていなければ、[`code/04-02/schema.sql`](../code/04-02/schema.sql) と
[`code/04-02/seed.sql`](../code/04-02/seed.sql) を実行してください。

> 💡 **MySQL が動かない場合**
> [`code/04-02/sqlite-playground.php`](../code/04-02/sqlite-playground.php) を使うと、
> MySQL なしでSQLを試せます。
>
> ```bash
> php sqlite-playground.php "SELECT COUNT(*) FROM posts"
> ```
>
> ただし本編は MySQL 前提です。環境が整ったら MySQL でやり直してください。

---

## ✍️ 手を動かす① ─ SELECT の基本

```sql
-- 全カラム・全行
SELECT * FROM posts;

-- カラムを指定する
SELECT id, name, created_at FROM posts;

-- 別名を付ける（AS）
SELECT
    id,
    name AS 投稿者,
    created_at AS 投稿日時
FROM posts;

-- 計算した結果を返す
SELECT id, name, LENGTH(body) AS body_length FROM posts;
```

> ⚠️ **実務では `SELECT *` を避けてください。**
>
> | 理由 | 説明 |
> | --- | --- |
> | 通信量が増える | 使わない `body`（長文）まで取ってくる |
> | カラム追加で壊れる | 後から追加したカラムが勝手に含まれる |
> | 意図が読めない | どのカラムを使っているかコードから分からない |
>
> **学習中・確認中は `*` で構いません。** アプリケーションのコードでは列挙します。

### 結果

```
SELECT id, name, created_at FROM posts LIMIT 3;

+----+------------+---------------------+
| id | name       | created_at          |
+----+------------+---------------------+
| 1  | 山田太郎   | 2026-04-10 09:15:00 |
| 2  | 名無しさん | 2026-04-10 14:30:00 |
| 3  | 佐藤花子   | 2026-04-11 08:00:00 |
+----+------------+---------------------+
```

---

## ✍️ 手を動かす② ─ WHERE で絞り込む

```sql
-- 一致
SELECT * FROM posts WHERE name = '山田太郎';

-- 不一致
SELECT * FROM posts WHERE name != '名無しさん';
SELECT * FROM posts WHERE name <> '名無しさん';   -- <> も同じ意味

-- 比較
SELECT * FROM posts WHERE id > 5;
SELECT * FROM posts WHERE id >= 5;
SELECT * FROM posts WHERE created_at >= '2026-04-13';

-- 範囲（BETWEEN は両端を含む）
SELECT * FROM posts WHERE id BETWEEN 3 AND 6;
SELECT * FROM posts WHERE created_at BETWEEN '2026-04-12' AND '2026-04-13 23:59:59';

-- 複数の候補
SELECT * FROM posts WHERE id IN (1, 3, 5);
SELECT * FROM posts WHERE name IN ('山田太郎', '佐藤花子');
SELECT * FROM posts WHERE name NOT IN ('名無しさん');

-- 複数条件
SELECT * FROM posts WHERE name = '山田太郎' AND created_at >= '2026-04-12';
SELECT * FROM posts WHERE name = '山田太郎' OR name = '佐藤花子';

-- NULL
SELECT * FROM posts WHERE user_id IS NULL;       -- ゲスト投稿
SELECT * FROM posts WHERE user_id IS NOT NULL;   -- 会員投稿
```

### ⚠️ AND と OR の優先順位

```sql
-- ❌ 意図と違う結果になる
SELECT * FROM posts
WHERE name = '山田太郎' OR name = '佐藤花子' AND created_at >= '2026-04-12';

-- AND が先に評価されるため、実際はこう解釈される：
--   name = '山田太郎'
--   OR (name = '佐藤花子' AND created_at >= '2026-04-12')

-- ✅ 括弧で明示する
SELECT * FROM posts
WHERE (name = '山田太郎' OR name = '佐藤花子')
  AND created_at >= '2026-04-12';
```

> ⚠️ **`AND` は `OR` より優先されます**（PHPの `&&` と `||` と同じ）。
> **OR を使ったら、必ず括弧を付ける**習慣にしてください。
> これは実務でバグを生む定番パターンです。

### 日付の比較の落とし穴

```sql
-- ❌ 4月13日のデータが取れない
SELECT * FROM posts WHERE created_at <= '2026-04-13';
--   '2026-04-13' は '2026-04-13 00:00:00' と解釈されるため、
--   13日の 09:15 のデータは範囲外になる

-- ✅ 方法1：翌日の 00:00 より小さい
SELECT * FROM posts WHERE created_at < '2026-04-14';

-- ✅ 方法2：日付部分だけを取り出して比較
SELECT * FROM posts WHERE DATE(created_at) = '2026-04-13';
```

> ⚠️ **方法2はインデックスが効きません。**
> カラムを関数で加工すると、インデックスが使えなくなります（4-8で扱います）。
> **データ量が増える可能性があるなら、方法1を使ってください。**

---

## ✍️ 手を動かす③ ─ LIKE で部分一致

```sql
-- 部分一致（前後どこでも）
SELECT id, name, body FROM posts WHERE body LIKE '%テスト%';

-- 前方一致（「山田」で始まる）
SELECT * FROM posts WHERE name LIKE '山田%';

-- 後方一致（「さん」で終わる）
SELECT * FROM posts WHERE name LIKE '%さん';

-- 1文字ワイルドカード（_ は任意の1文字）
SELECT * FROM posts WHERE name LIKE '山_太郎';
```

### 結果

```
SELECT id, name, body FROM posts WHERE body LIKE '%テスト%';

+----+------------+------------------+
| id | name       | body             |
+----+------------+------------------+
| 2  | 名無しさん | テスト投稿です。 |
| 9  | 名無しさん | テスト           |
+----+------------+------------------+
(2 rows)
```

| ワイルドカード | 意味 |
| --- | --- |
| `%` | **0文字以上**の任意の文字列 |
| `_` | **1文字**の任意の文字 |

> ⚠️ **`LIKE '%キーワード%'` は遅いです。**
> 前方に `%` があると、**インデックスが使えません**（全行を走査します）。
>
> | パターン | インデックス |
> | --- | --- |
> | `LIKE '山田%'` | **使える** |
> | `LIKE '%太郎'` | 使えない |
> | `LIKE '%田%'` | 使えない |
>
> 本格的な全文検索が必要なら、`MATCH ... AGAINST`（全文検索インデックス）や
> Elasticsearch などを使います。**掲示板の規模なら `LIKE` で十分**です。

### 検索で `%` や `_` そのものを探したいとき

```sql
-- 「50%」を含む投稿を探す
SELECT * FROM posts WHERE body LIKE '%50\%%';
```

`\` でエスケープします。**ユーザーの入力をそのまま `LIKE` に渡すと、
`%` を入力されて全件マッチします**。PHPから使うときの対処は 4-8 で扱います。

---

## ✍️ 手を動かす④ ─ 並び替えと件数制限

```sql
-- 昇順（小さい順。ASC は省略可）
SELECT * FROM posts ORDER BY created_at ASC;

-- 降順（新しい順）
SELECT * FROM posts ORDER BY created_at DESC;

-- 複数キー（第1キーが同じなら第2キーで並べる）
SELECT * FROM posts ORDER BY name ASC, created_at DESC;

-- 件数を制限
SELECT * FROM posts ORDER BY created_at DESC LIMIT 3;

-- ページ送り（OFFSET で何件目から）
SELECT * FROM posts ORDER BY created_at DESC LIMIT 10 OFFSET 0;    -- 1ページ目
SELECT * FROM posts ORDER BY created_at DESC LIMIT 10 OFFSET 10;   -- 2ページ目
SELECT * FROM posts ORDER BY created_at DESC LIMIT 10 OFFSET 20;   -- 3ページ目
```

### 結果

```
SELECT id, name, created_at FROM posts ORDER BY created_at DESC LIMIT 3;

+----+------------+---------------------+
| id | name       | created_at          |
+----+------------+---------------------+
| 12 | 名無しさん | 2026-04-15 08:30:00 |
| 11 | 伊藤大輔   | 2026-04-14 16:55:00 |
| 10 | 佐藤花子   | 2026-04-14 11:30:00 |
+----+------------+---------------------+
(3 rows)
```

### OFFSET の計算

```
OFFSET = (ページ番号 - 1) × 1ページの件数
```

第3部の `paginate()` で `array_slice($all, ($page - 1) * $perPage, $perPage)` と
書いていたのと同じ計算です。**違いは、データベースは必要な10件だけを返す**ことです。

> ⚠️ **`ORDER BY` のないクエリの順序は保証されません。**
>
> ```sql
> SELECT * FROM posts LIMIT 10;   -- ❌ 毎回同じ順序とは限らない
> ```
>
> たまたま id 順に見えても、データが増えたりインデックスが変わると順序が変わります。
> **`LIMIT` を使うときは、必ず `ORDER BY` を書いてください。**

> ⚠️ **`OFFSET` が大きいと遅くなります。**
> `OFFSET 100000` は「10万件読み飛ばす」という処理です。
> 大規模サイトでは「前回の最後のIDより大きいものを取る」方式（カーソルページネーション）を使います。
> **掲示板の規模では `OFFSET` で問題ありません。**

---

## ✍️ 手を動かす⑤ ─ 集計関数

```sql
-- 件数
SELECT COUNT(*) AS total FROM posts;

-- 条件つきの件数
SELECT COUNT(*) AS guest_posts FROM posts WHERE user_id IS NULL;

-- 合計・平均・最大・最小
SELECT
    COUNT(*)          AS 件数,
    MIN(created_at)   AS 最初の投稿,
    MAX(created_at)   AS 最新の投稿,
    AVG(LENGTH(body)) AS 平均文字数
FROM posts;

-- 重複を除いた件数
SELECT COUNT(DISTINCT name) AS 投稿者数 FROM posts;

-- 重複を除いた値の一覧
SELECT DISTINCT name FROM posts ORDER BY name;
```

### 結果

```
SELECT COUNT(*) AS total FROM posts;

+-------+
| total |
+-------+
| 12    |
+-------+
```

### ⚠️ `COUNT(*)` と `COUNT(カラム)` の違い

```sql
SELECT
    COUNT(*)       AS all_rows,      -- 12（全行）
    COUNT(user_id) AS with_user      -- 6（NULL を除いた行）
FROM posts;
```

```
+----------+-----------+----------+
| all_rows | with_user | 投稿者数 |
+----------+-----------+----------+
| 12       | 6         | 7        |
+----------+-----------+----------+
```

**`COUNT(カラム)` は NULL を数えません。** これは意図的に使えば便利ですが、
知らないと「件数が合わない」というバグになります。

> 💡 **「全件数を数えたいなら `COUNT(*)`」** と覚えてください。

### 集計関数と NULL

```sql
-- AVG / SUM も NULL を無視する
SELECT AVG(score) FROM tests;
--   score が NULL の行は「0」ではなく「計算対象外」になる
--   → 平均を出したいとき、意図と違う結果になることがある

-- NULL を 0 として扱いたい場合
SELECT AVG(COALESCE(score, 0)) FROM tests;
```

| 関数 | 意味 |
| --- | --- |
| `COALESCE(a, b)` | a が NULL なら b を返す（PHPの `??` と同じ） |
| `IFNULL(a, b)` | 同じ（MySQL 専用） |

---

## ✍️ 手を動かす⑥ ─ GROUP BY で集計する

**「〜ごとの件数」を出すときに使います。**

```sql
-- 投稿者ごとの投稿数
SELECT name, COUNT(*) AS cnt
FROM posts
GROUP BY name
ORDER BY cnt DESC, name ASC;
```

### 結果

```
+------------+-----+
| name       | cnt |
+------------+-----+
| 名無しさん | 4   |
| 佐藤花子   | 2   |
| 山田太郎   | 2   |
| 伊藤大輔   | 1   |
| 田中美咲   | 1   |
| 鈴木一郎   | 1   |
| 高橋健     | 1   |
+------------+-----+
(7 rows)
```

### GROUP BY のイメージ

```
【元のデータ】                    【グループ化】              【集計】
name                              名無しさん: 4行      →      名無しさん, 4
山田太郎        ─┐                山田太郎:   2行      →      山田太郎,   2
名無しさん      ─┼─→ 同じ name   佐藤花子:   2行      →      佐藤花子,   2
佐藤花子        ─┤   でまとめる   鈴木一郎:   1行      →      鈴木一郎,   1
鈴木一郎        ─┤                ...
名無しさん      ─┘
...
```

### 日付ごとの集計

```sql
-- 日別の投稿数
SELECT DATE(created_at) AS 日付, COUNT(*) AS 投稿数
FROM posts
GROUP BY DATE(created_at)
ORDER BY 日付;
```

```
+------------+--------+
| 日付       | 投稿数 |
+------------+--------+
| 2026-04-10 | 2      |
| 2026-04-11 | 2      |
| 2026-04-12 | 3      |
| 2026-04-13 | 2      |
| 2026-04-14 | 2      |
| 2026-04-15 | 1      |
+------------+--------+
(6 rows)
```

> ⚠️ **`GROUP BY DATE(created_at)` はインデックスが効きません。**
> カラムを関数で包むと、インデックスが使えなくなります（4-8で扱います）。
> 日別集計は件数が多いテーブルでは重くなるので、注意してください。

### HAVING で集計結果を絞る

```sql
-- 2件以上投稿している人だけ
SELECT name, COUNT(*) AS cnt
FROM posts
GROUP BY name
HAVING COUNT(*) >= 2
ORDER BY cnt DESC;
```

```
+------------+-----+
| name       | cnt |
+------------+-----+
| 名無しさん | 4   |
| 佐藤花子   | 2   |
| 山田太郎   | 2   |
+------------+-----+
(3 rows)
```

### ⚠️ WHERE と HAVING の違い

**これは必ず理解してください。**

| | いつ評価されるか | 使えるもの |
| --- | --- | --- |
| **`WHERE`** | **グループ化の前**（各行に対して） | カラムの値 |
| **`HAVING`** | **グループ化の後**（集計結果に対して） | 集計関数の結果 |

```sql
-- ❌ WHERE では集計関数が使えない
SELECT name, COUNT(*) FROM posts WHERE COUNT(*) >= 2 GROUP BY name;
--   Error: Invalid use of group function

-- ✅ HAVING を使う
SELECT name, COUNT(*) FROM posts GROUP BY name HAVING COUNT(*) >= 2;

-- ✅ 両方使える（WHERE で行を絞り、HAVING で集計結果を絞る）
SELECT name, COUNT(*) AS cnt
FROM posts
WHERE created_at >= '2026-04-12'      -- ① まず行を絞る
GROUP BY name                          -- ② グループ化
HAVING COUNT(*) >= 2                   -- ③ 集計結果を絞る
ORDER BY cnt DESC;                     -- ④ 並べる
```

### SQL の実行順序（重要）

**書く順番と、実行される順番が違います。**

```
書く順番                実行される順番
SELECT      ⑤          ① FROM      どのテーブルから
FROM        ①          ② WHERE     行を絞る
WHERE       ②          ③ GROUP BY  グループ化
GROUP BY    ③          ④ HAVING    グループを絞る
HAVING      ④          ⑤ SELECT    カラムを選ぶ
ORDER BY    ⑥          ⑥ ORDER BY  並べる
LIMIT       ⑦          ⑦ LIMIT     件数を切る
```

> 💡 **この順序を知っていると、多くの疑問が解けます。**
>
> - なぜ `WHERE` で集計関数が使えないのか → `WHERE` は `GROUP BY` より前だから
> - なぜ `WHERE` で `SELECT` の別名（AS）が使えないのか → `SELECT` はもっと後だから
> - なぜ `ORDER BY` では別名が使えるのか → `SELECT` の後だから
>
> ```sql
> -- ❌ WHERE では別名が使えない
> SELECT LENGTH(body) AS len FROM posts WHERE len > 10;
>
> -- ✅ ORDER BY では使える
> SELECT LENGTH(body) AS len FROM posts ORDER BY len DESC;
> ```

### GROUP BY のもう1つの落とし穴

```sql
-- ❌ MySQL 5.7以降ではエラーになる
SELECT name, body, COUNT(*) FROM posts GROUP BY name;
--   Error: Expression #2 of SELECT list is not in GROUP BY clause
--          and contains nonaggregated column 'body'
```

**グループ化したら、`SELECT` に書けるのは「グループ化したカラム」と「集計関数」だけ**です。

`body` は、グループ内に複数の値があるため、**どれを返すべきか決まりません**。

```sql
-- ✅ 集計関数を使う
SELECT name, COUNT(*), MAX(created_at) FROM posts GROUP BY name;

-- ✅ グループ化するカラムに加える
SELECT name, body, COUNT(*) FROM posts GROUP BY name, body;
```

---

## ✍️ 手を動かす⑦ ─ よく使う関数

### 文字列

```sql
SELECT
    CONCAT(name, 'さん')           AS 敬称つき,
    CHAR_LENGTH(body)              AS 文字数,      -- ★ 文字数
    LENGTH(body)                   AS バイト数,    -- バイト数
    UPPER('abc'), LOWER('ABC'),
    TRIM('  a  '),
    SUBSTRING(body, 1, 10)         AS 先頭10文字,
    REPLACE(body, 'テスト', 'test') AS 置換後
FROM posts LIMIT 3;
```

> ⚠️ **`LENGTH()` はバイト数、`CHAR_LENGTH()` は文字数**です。
> 日本語では3倍の差が出ます。PHPの `strlen` / `mb_strlen` と同じ関係です（3-2参照）。
> **文字数を数えたいときは `CHAR_LENGTH()`。**

### 日付

```sql
SELECT
    NOW()                                       AS 現在時刻,
    CURDATE()                                   AS 今日,
    DATE(created_at)                            AS 日付だけ,
    DATE_FORMAT(created_at, '%Y年%m月%d日')      AS 和式,
    DATE_FORMAT(created_at, '%H:%i')            AS 時刻,
    YEAR(created_at), MONTH(created_at), DAY(created_at),
    DATEDIFF(NOW(), created_at)                 AS 経過日数,
    DATE_ADD(created_at, INTERVAL 7 DAY)        AS 一週間後
FROM posts LIMIT 3;
```

**`DATE_FORMAT` の書式**

| 記号 | 意味 |
| --- | --- |
| `%Y` | 4桁の年 |
| `%m` | 2桁の月（01〜12） |
| `%d` | 2桁の日 |
| `%H` | 24時間制の時（00〜23） |
| `%i` | 分 |
| `%s` | 秒 |

> 💡 **表示用の整形は、PHP側でやるほうが柔軟です。**
> データベースからは `DATETIME` のまま取得し、PHPの `date()` で整形するのが一般的です。
> SQLでの整形は、集計やグループ化で必要なときに使います。

### 条件分岐

```sql
-- CASE 式
SELECT
    name,
    CASE
        WHEN user_id IS NULL THEN 'ゲスト'
        ELSE '会員'
    END AS 種別,
    CASE
        WHEN CHAR_LENGTH(body) >= 20 THEN '長文'
        WHEN CHAR_LENGTH(body) >= 10 THEN '普通'
        ELSE '短文'
    END AS 分量
FROM posts
ORDER BY id
LIMIT 5;
```

```
+------------+--------+------+
| name       | 種別   | 分量 |
+------------+--------+------+
| 山田太郎   | 会員   | 普通 |
| 名無しさん | ゲスト | 短文 |
| 佐藤花子   | 会員   | 普通 |
| 鈴木一郎   | 会員   | 長文 |
| 名無しさん | ゲスト | 普通 |
+------------+--------+------+
(5 rows)
```

> 💡 「はじめまして。よろしくお願いします。」は**18文字**なので「普通」になります。
> `CHAR_LENGTH()` が文字数を返していることの確認にもなります
> （`LENGTH()` なら54バイトで「長文」になってしまいます）。

> 💡 **`CASE` は集計と組み合わせると強力です。**
>
> ```sql
> -- 1回のクエリで、会員とゲストの件数を両方出す
> SELECT
>     COUNT(*)                                      AS 全件,
>     SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) AS ゲスト,
>     SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) AS 会員
> FROM posts;
> ```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `WHERE x = NULL` で何も返らない | NULL は `=` で比較できない | `IS NULL` |
| OR の結果がおかしい | AND が優先される | **括弧を付ける** |
| その日のデータが取れない | `<= '2026-04-13'` は 00:00 まで | `< '2026-04-14'` |
| `COUNT` の数が合わない | `COUNT(カラム)` は NULL を除く | `COUNT(*)` |
| `WHERE` で集計関数が使えない | 実行順序が `WHERE` → `GROUP BY` | `HAVING` を使う |
| `WHERE` で別名が使えない | `SELECT` は `WHERE` より後 | 式をそのまま書く |
| `GROUP BY` でエラー | 集計していないカラムを SELECT した | 集計関数か GROUP BY に加える |
| `LIMIT` の順序が安定しない | `ORDER BY` がない | 必ず `ORDER BY` を書く |
| 文字数が3倍になる | `LENGTH()` はバイト数 | `CHAR_LENGTH()` |
| 検索が遅い | `LIKE '%x%'` はインデックスが効かない | 前方一致にするか全文検索 |

---

## 🤖 AIに聞いてみよう

### ① SQLの実行順序を使って疑問を解く

```text
SQL の論理的な実行順序について、理解を確認したいです。

以下のそれぞれについて、「なぜそうなるのか」を
実行順序（FROM → WHERE → GROUP BY → HAVING → SELECT → ORDER BY → LIMIT）
から説明してください。

1. WHERE で集計関数（COUNT など）が使えないのはなぜか
2. WHERE で SELECT の別名（AS）が使えないのはなぜか
3. ORDER BY では別名が使えるのはなぜか
4. HAVING で集計関数が使えるのはなぜか
5. GROUP BY したとき、SELECT に書けるカラムが制限されるのはなぜか

そのうえで、この順序を覚えるための語呂合わせや図を提案してください。
```

### ② 自分のSQLをレビューさせる

```text
以下は、私が書いた MySQL のクエリです。

（SQLを貼る）

次の観点でレビューしてください。

1. 意図した結果が得られるか（論理的な誤り）
2. NULL の扱いで問題が起きる箇所
3. AND / OR の優先順位の問題
4. 日付範囲の指定漏れ（境界値）
5. インデックスが効かない書き方になっている箇所
6. パフォーマンス上の懸念（SELECT *、OFFSET、LIKE の前方 % など）
7. 可読性（別名、改行、インデント）

修正後のSQLは書かず、指摘だけをお願いします。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

`posts` テーブルに対して、以下のSQLを書いてください。

- [ ] ① 全件の件数を出す
- [ ] ② `name` が「名無しさん」の投稿を、新しい順に取得する
- [ ] ③ 本文に「参加」を含む投稿の id と body を取得する
- [ ] ④ 4月12日に投稿されたものだけを取得する（**境界値に注意**）
- [ ] ⑤ ゲスト投稿（`user_id` が NULL）の件数を出す
- [ ] ⑥ 本文が最も長い投稿を1件取得する
- [ ] ⑦ 投稿者ごとの「最新の投稿日時」を、新しい順に並べて取得する
- [ ] ⑧ 3件以上投稿している投稿者を取得する
- [ ] ⑨ 2ページ目（1ページ5件）を取得する
- [ ] ⑩ 「会員／ゲスト」の別と件数を、1回のクエリで出す

<details>
<summary>答えを見る</summary>

```sql
-- ①
SELECT COUNT(*) AS total FROM posts;

-- ②
SELECT * FROM posts WHERE name = '名無しさん' ORDER BY created_at DESC;

-- ③
SELECT id, body FROM posts WHERE body LIKE '%参加%';

-- ④ 境界値：'2026-04-12' は 00:00:00 と解釈されるため、翌日未満で切る
SELECT * FROM posts
WHERE created_at >= '2026-04-12' AND created_at < '2026-04-13'
ORDER BY created_at;

-- ⑤
SELECT COUNT(*) AS guest_count FROM posts WHERE user_id IS NULL;

-- ⑥
SELECT id, name, CHAR_LENGTH(body) AS len, body
FROM posts
ORDER BY len DESC
LIMIT 1;

-- ⑦
SELECT name, MAX(created_at) AS latest
FROM posts
GROUP BY name
ORDER BY latest DESC;

-- ⑧
SELECT name, COUNT(*) AS cnt
FROM posts
GROUP BY name
HAVING COUNT(*) >= 3
ORDER BY cnt DESC;

-- ⑨ OFFSET = (2 - 1) × 5 = 5
SELECT * FROM posts ORDER BY created_at DESC LIMIT 5 OFFSET 5;

-- ⑩
SELECT
    CASE WHEN user_id IS NULL THEN 'ゲスト' ELSE '会員' END AS 種別,
    COUNT(*) AS 件数
FROM posts
GROUP BY CASE WHEN user_id IS NULL THEN 'ゲスト' ELSE '会員' END;
```

**⑩の別解**（こちらのほうが読みやすい）

```sql
SELECT
    SUM(CASE WHEN user_id IS NULL     THEN 1 ELSE 0 END) AS ゲスト,
    SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) AS 会員
FROM posts;
```

</details>

### 演習2（必須）─ 間違いを見つける

以下のSQLの問題点を、それぞれ指摘してください。

```sql
-- ①
SELECT * FROM posts WHERE user_id = NULL;

-- ②
SELECT * FROM posts WHERE name = '山田太郎' OR name = '佐藤花子' AND id > 5;

-- ③
SELECT name, COUNT(*) FROM posts WHERE COUNT(*) > 1 GROUP BY name;

-- ④
SELECT CHAR_LENGTH(body) AS len FROM posts WHERE len > 10;

-- ⑤
SELECT * FROM posts LIMIT 5;

-- ⑥
SELECT name, body, COUNT(*) FROM posts GROUP BY name;

-- ⑦
SELECT COUNT(user_id) AS total FROM posts;
```

<details>
<summary>答えを見る</summary>

① **`= NULL` では何も返らない** → `IS NULL`

② **AND が優先されるため、意図と違う**
```sql
SELECT * FROM posts WHERE (name = '山田太郎' OR name = '佐藤花子') AND id > 5;
```

③ **`WHERE` では集計関数が使えない**（実行順序が `WHERE` → `GROUP BY`）
```sql
SELECT name, COUNT(*) FROM posts GROUP BY name HAVING COUNT(*) > 1;
```

④ **`WHERE` では `SELECT` の別名が使えない**（`SELECT` は後で評価される）
```sql
SELECT CHAR_LENGTH(body) AS len FROM posts WHERE CHAR_LENGTH(body) > 10;
```

⑤ **`ORDER BY` がないので順序が保証されない**
```sql
SELECT * FROM posts ORDER BY created_at DESC LIMIT 5;
```
（また、実務では `*` を避けてカラムを列挙する）

⑥ **`body` が集計されていない** → MySQL 5.7 以降はエラー
```sql
SELECT name, COUNT(*), MAX(created_at) FROM posts GROUP BY name;
```

⑦ **`COUNT(user_id)` は NULL を除くため、全件数にならない**（12ではなく5になる）
```sql
SELECT COUNT(*) AS total FROM posts;
```

</details>

### 演習3（挑戦）

以下を1回のクエリで取得してください。

- [ ] ① 日別の「投稿数」と「会員投稿数」と「ゲスト投稿数」を並べた表
- [ ] ② 投稿者ごとの「投稿数」「初回投稿日」「最新投稿日」「平均文字数」
- [ ] ③ 投稿を「短文（10文字未満）／普通（10〜19）／長文（20以上）」に分類した件数
- [ ] ④ 「名無しさん」以外の投稿者について、投稿数の多い順トップ3

<details>
<summary>①の答えを見る</summary>

```sql
SELECT
    DATE(created_at) AS 日付,
    COUNT(*)                                             AS 投稿数,
    SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) AS 会員,
    SUM(CASE WHEN user_id IS NULL     THEN 1 ELSE 0 END) AS ゲスト
FROM posts
GROUP BY DATE(created_at)
ORDER BY 日付;
```

**ポイント**：`SUM(CASE WHEN ... THEN 1 ELSE 0 END)` は「条件を満たす行を数える」定番の書き方です。
MySQL 8.0 なら `COUNT(*) FILTER (WHERE ...)` ではなく、この書き方を使います。

</details>

---

## ✅ 章末チェック

- [ ] `SELECT` でカラムを指定できる
- [ ] `WHERE` で条件を書ける
- [ ] `IS NULL` を使う理由を説明できる
- [ ] `OR` を使うとき括弧を付ける理由を言える
- [ ] 日付範囲の境界値の罠を説明できる
- [ ] `LIKE` の `%` がインデックスを無効にすることを知っている
- [ ] `ORDER BY` なしの `LIMIT` が危険な理由を言える
- [ ] `COUNT(*)` と `COUNT(カラム)` の違いを説明できる
- [ ] `WHERE` と `HAVING` の違いを説明できる
- [ ] **SQLの実行順序を言える**
- [ ] `LENGTH()` と `CHAR_LENGTH()` の違いを知っている

---

**前 → [4-2 MySQL に触れる / phpMyAdmin](04-02-mysql-basics.md)　｜　次 → [4-4 INSERT / UPDATE / DELETE](04-04-insert-update-delete.md)**
