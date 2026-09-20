# 4-5 テーブル設計と正規化の基本

> ◎ **このレッスンのゴール**
> - 「同じ情報を2か所に持たない」設計ができる
> - 1対多・多対多の関係をテーブルで表現できる
> - インデックスを張る場所を自分で判断できる

所要 150分 / 難度 🔴
完成コード: [`code/04-05/`](../code/04-05/)

---

## 📖 悪い設計を見る

**設計の良さは、悪い設計と比べないと分かりません。** まず、ダメな例を見ます。

### 悪い例：1つのテーブルに全部入れる

```
テーブル: orders（注文）
┌────┬──────────┬──────────────────┬──────────────┬────────────────────────┬───────┐
│ id │ 顧客名   │ 顧客メール       │ 顧客電話     │ 商品名                 │ 合計  │
├────┼──────────┼──────────────────┼──────────────┼────────────────────────┼───────┤
│ 1  │ 山田太郎 │ taro@example.com │ 090-1111-1111│ ドリップ,ケーキ        │ 1750  │
│ 2  │ 佐藤花子 │ hana@example.com │ 090-2222-2222│ ラテ                   │ 600   │
│ 3  │ 山田太郎 │ taro@example.com │ 090-1111-1111│ ドリップ,ラテ,ラテ     │ 1800  │
└────┴──────────┴──────────────────┴──────────────┴────────────────────────┴───────┘
```

**動きます。** でも、次の問題が起きます。

### 問題1：更新の手間と不整合（更新異常）

山田太郎さんが電話番号を変えました。

```sql
UPDATE orders SET 顧客電話 = '090-9999-9999' WHERE 顧客名 = '山田太郎';
```

**注文が100件あれば、100行を更新します。**
1行でも漏れると、**同じ人の電話番号が2種類存在する**ことになります。

> ⚠️ **「どちらが正しいのか誰にもわからない」状態が、最悪の事態です。**

### 問題2：同じ人か判別できない

```
│ 山田太郎 │ taro@example.com │
│ 山田 太郎│ taro@example.com │   ← スペースが入った。別人?
│ ヤマダタロウ │ taro@exa..    │   ← カタカナで入力された
```

**名前は同じ人を識別する手段になりません。** 同姓同名もいます。

### 問題3：商品をカンマ区切りで持っている

```
│ ドリップ,ラテ,ラテ │
```

**これでは以下ができません。**

- 「ラテを注文した件数」を数える → `LIKE '%ラテ%'` では「カフェラテ」も引っかかる
- 「商品ごとの売上」を集計する → カンマで分割する処理が必要
- 商品名が変わったとき → 全行の文字列を置換する
- 各商品の個数・単価を持つ → 不可能

### 問題4：削除できない（削除異常）

注文3を削除すると、**「山田太郎さんの電話番号」という情報も一緒に消えます**。
注文が全部消えたら、顧客情報が消滅します。

### 問題5：注文がない顧客を登録できない（挿入異常）

会員登録だけした人を、このテーブルには入れられません。**注文がないからです。**

---

## 📖 解決策：情報の種類ごとにテーブルを分ける

```
customers（顧客）                    products（商品）
┌────┬──────────┬──────────────────┐ ┌────┬──────────┬───────┐
│ id │ name     │ email            │ │ id │ name     │ price │
├────┼──────────┼──────────────────┤ ├────┼──────────┼───────┤
│ 1  │ 山田太郎 │ taro@example.com │ │ 1  │ ドリップ │ 600   │
│ 2  │ 佐藤花子 │ hana@example.com │ │ 2  │ ラテ     │ 600   │
└────┴──────────┴──────────────────┘ │ 3  │ ケーキ   │ 550   │
                                      └────┴──────────┴───────┘
orders（注文）                       order_items（注文明細）
┌────┬─────────────┬─────────────┐  ┌────┬──────────┬────────────┬─────┬───────┐
│ id │ customer_id │ ordered_at  │  │ id │ order_id │ product_id │ qty │ price │
├────┼─────────────┼─────────────┤  ├────┼──────────┼────────────┼─────┼───────┤
│ 1  │ 1           │ 2026-04-10  │  │ 1  │ 1        │ 1          │ 1   │ 600   │
│ 2  │ 2           │ 2026-04-11  │  │ 2  │ 1        │ 3          │ 1   │ 550   │
│ 3  │ 1           │ 2026-04-12  │  │ 3  │ 2        │ 2          │ 1   │ 600   │
└────┴─────────────┴─────────────┘  │ 4  │ 3        │ 1          │ 1   │ 600   │
                                     │ 5  │ 3        │ 2          │ 2   │ 600   │
                                     └────┴──────────┴────────────┴─────┴───────┘
```

**これで、すべての問題が解決します。**

| 問題 | 解決 |
| --- | --- |
| 更新の手間 | 顧客情報は `customers` の1行だけ。**1回の UPDATE で済む** |
| 不整合 | 情報が1か所しかないので、矛盾が起きえない |
| 同一人物の判別 | `customer_id` で識別する |
| 商品の集計 | `order_items` を `GROUP BY product_id` で集計できる |
| 削除異常 | 注文を削除しても、顧客情報は残る |
| 挿入異常 | 注文がなくても顧客を登録できる |

### `order_items` に `price` を持つ理由

**「同じ情報を2か所に持たない」原則に反しているように見えます。** なぜでしょうか。

```sql
-- 商品の価格が変わった
UPDATE products SET price = 700 WHERE id = 1;
```

**過去の注文の金額も変わってしまいます。** 「600円で買ったのに700円の請求になる」——これは事故です。

> 💡 **「その時点の値」を残す必要があるものは、コピーして持ちます。**
> これは重複ではなく、**「注文時の価格」という別の意味を持つデータ**です。
>
> 同様に、注文時の商品名・住所も保存することがあります（商品名が変わっても領収書は変わらない）。
>
> **「正規化すべきか」の判断は、意味で決まります。** 形式的なルールではありません。

---

## 📖 正規化を3段階で理解する

「正規化」とは、この分割を体系的に行う手法です。**第3正規形まで**で実用上十分です。

### 第1正規形：1つのマスに1つの値だけ

```
❌ 非正規形
│ 商品名                 │
│ ドリップ,ラテ,ラテ     │   ← 1つのマスに複数の値

✅ 第1正規形
別テーブル（order_items）に、1行1商品で分ける
```

**判定基準**：「カンマ区切り」「繰り返しのカラム（`商品1`, `商品2`, `商品3`）」がないか。

> ⚠️ **`商品1` `商品2` `商品3` というカラムも第1正規形違反です。**
> 4つ目が必要になったらテーブル定義を変えなければならず、
> 「商品2に入っている商品」を探すには全カラムを調べることになります。

### 第2正規形：主キーの一部だけで決まる項目を分ける

```
❌ 第1正規形のまま
order_items（主キー: order_id + product_id）
┌──────────┬────────────┬──────────────┬─────┐
│ order_id │ product_id │ product_name │ qty │
├──────────┼────────────┼──────────────┼─────┤
│ 1        │ 1          │ ドリップ     │ 1   │
│ 3        │ 1          │ ドリップ     │ 1   │   ← 重複している
└──────────┴────────────┴──────────────┴─────┘
```

`product_name` は **`product_id` だけで決まります**（`order_id` は関係ない）。
これを「**部分関数従属**」と呼び、別テーブルに分けます。

```
✅ 第2正規形
order_items: order_id, product_id, qty
products:    id, name, price
```

### 第3正規形：主キー以外の項目で決まる項目を分ける

```
❌ 第2正規形のまま
customers
┌────┬──────────┬───────────┬──────────────┐
│ id │ name     │ zip       │ prefecture   │
├────┼──────────┼───────────┼──────────────┤
│ 1  │ 山田太郎 │ 100-0001  │ 東京都       │
│ 2  │ 佐藤花子 │ 100-0001  │ 東京都       │   ← 郵便番号が同じなら都道府県も同じ
└────┴──────────┴───────────┴──────────────┘
```

`prefecture` は **`zip` で決まります**（`id` ではなく）。
これを「**推移的関数従属**」と呼びます。

```
✅ 第3正規形
customers: id, name, zip
zip_codes: zip, prefecture, city
```

> 💡 **ただし、住所は正規化しないことが多いです。**
> 郵便番号のマスタを持つとメンテナンスが必要になり、引っ越しや市町村合併に対応できません。
> **「顧客が入力した住所」は、その時点の値として持つほうが実用的です。**
>
> **正規化は目的ではなく手段です。** 「教科書通りにする」ことが目的ではありません。

### 覚え方

```
第1正規形：1マス1値       → 繰り返しをなくす
第2正規形：キーの一部で決まるものを分ける
第3正規形：キー以外で決まるものを分ける
```

> 💡 **実務では、こう考えるだけで十分です。**
>
> **「同じ情報が2か所以上に書かれていないか？」**
> **「1か所を直せば全部直る状態になっているか？」**
>
> これが満たされていれば、だいたい第3正規形になっています。

---

## ✍️ 手を動かす① ─ 1対多の関係

**最もよく使う関係です。** 「1人のユーザーが、複数の投稿を持つ」。

```
users（1）                posts（多）
┌────┬──────────┐        ┌────┬─────────┬──────────┐
│ id │ name     │        │ id │ user_id │ body     │
├────┼──────────┤        ├────┼─────────┼──────────┤
│ 1  │ 山田太郎 │◄───────│ 1  │ 1       │ 投稿A    │
│ 2  │ 佐藤花子 │◄──┐    │ 2  │ 1       │ 投稿B    │
└────┴──────────┘   └────│ 3  │ 2       │ 投稿C    │
                          └────┴─────────┴──────────┘
```

**「多」の側に、「1」の側のIDを持たせます。**

```sql
CREATE TABLE posts (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,                          -- ★ ここに持つ
    body    TEXT NOT NULL,

    INDEX idx_posts_user_id (user_id),          -- ★ 検索用

    CONSTRAINT fk_posts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
);
```

> 🆘 **ここで詰まったら**（外部キーで `Cannot add or update a child row` / `foreign key constraint fails`）
> - **原因**：`posts.user_id` に、**`users` に存在しないID**を入れようとしている。参照先（親）に無いIDは入れられません
> - **チェック順**：① `users` を**先に**作ってから `posts` を作ったか（順序が逆だとテーブル作成自体が失敗）② 参照する列の**型が完全一致**しているか（両方 `INT`、符号や `UNSIGNED` まで一致）③ 「投稿者なし」を許すなら `user_id` を `NULL` 可にする
> - **直らなければ、AIにこう聞く**（両テーブルの `CREATE TABLE` と、実行したSQLを貼る）：
>   「外部キー制約でエラーが出ます。親テーブルとの対応・型・作成順のどれが原因か教えてください」

> ⚠️ **逆にしてはいけません。**
>
> ```sql
> -- ❌ users テーブルに post_id を持つ
> CREATE TABLE users (id INT, name VARCHAR(50), post_id INT);
> ```
>
> これでは**1人が1投稿しか持てません**。
> 「複数持てるようにする」ために `post_id1`, `post_id2` …とするのは第1正規形違反です。
>
> **「1対多なら、多の側が相手のIDを持つ」。** これは例外なく成り立ちます。

### 外部キー制約を張る理由

```sql
CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id)
```

| 効果 | 説明 |
| --- | --- |
| **存在しないIDを拒否** | `user_id = 999` を INSERT できない |
| **削除の連鎖を定義** | 親が消えたときの挙動を決められる |
| **設計が伝わる** | テーブル定義を見れば関係が分かる |

> 💡 **外部キーがないと、「孤児データ」が生まれます。**
> 存在しないユーザーを参照する投稿ができ、JOINしたときに消えます。
>
> ⚠️ **ただし、大規模サイトでは外部キーを張らないこともあります**（パフォーマンスと運用の都合）。
> その場合、整合性はアプリケーション側で保証します。**学習中は必ず張ってください。**

---

## ✍️ 手を動かす② ─ 多対多の関係

**「1つの投稿に複数のタグ、1つのタグが複数の投稿に」** という関係です。

```
posts                post_tag（中間テーブル）        tags
┌────┬────────┐     ┌─────────┬────────┐          ┌────┬──────────┐
│ id │ body   │     │ post_id │ tag_id │          │ id │ name     │
├────┼────────┤     ├─────────┼────────┤          ├────┼──────────┤
│ 1  │ 投稿A  │◄────│ 1       │ 2      │─────────►│ 1  │ 質問     │
│ 4  │ 投稿B  │◄────│ 4       │ 1      │─────────►│ 2  │ 雑談     │
│ 6  │ 投稿C  │◄─┬──│ 6       │ 3      │──┬──────►│ 3  │ お知らせ │
└────┴────────┘  └──│ 6       │ 2      │  │       │ 4  │ 要望     │
                     └─────────┴────────┘  └──────►└────┴──────────┘
```

**両方のIDを持つ「中間テーブル」を作ります。**

```sql
CREATE TABLE post_tag (
    post_id INT NOT NULL,
    tag_id  INT NOT NULL,

    -- ★ 2カラムの組み合わせを主キーにする
    --    → 同じ投稿に同じタグを2回付けられない
    PRIMARY KEY (post_id, tag_id),

    -- ★ tag_id 側からも引けるようにインデックスを張る
    INDEX idx_post_tag_tag_id (tag_id),

    CONSTRAINT fk_post_tag_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_tag_tag  FOREIGN KEY (tag_id)  REFERENCES tags(id)  ON DELETE CASCADE
);
```

### 複合主キーのポイント

```sql
PRIMARY KEY (post_id, tag_id)
```

**2つのカラムの組み合わせが一意**になります。

| post_id | tag_id | 結果 |
| --- | --- | --- |
| 1 | 2 | OK |
| 1 | 3 | OK（同じ post_id でもタグが違う） |
| 4 | 2 | OK（同じ tag_id でも投稿が違う） |
| 1 | 2 | **エラー**（既にある組み合わせ） |

> 💡 **これで「二重にタグを付ける」バグが、データベースレベルで防げます。**
> PHPのコードでチェックする必要がありません。

### なぜ `tag_id` に別途インデックスが必要か

複合主キー `(post_id, tag_id)` のインデックスは、**左から使われます**。

| クエリ | インデックス |
| --- | --- |
| `WHERE post_id = 1` | **使える**（左端） |
| `WHERE post_id = 1 AND tag_id = 2` | **使える** |
| `WHERE tag_id = 2` | **使えない**（左端が指定されていない） |

**「このタグが付いた投稿を探す」ときのために、`tag_id` 単体のインデックスを張ります。**

> 💡 これは**電話帳の比喩**で理解できます。
> 「姓 → 名」の順に並んだ電話帳では、「姓が田中」は引けますが、
> 「名が太郎」を引くには全部見るしかありません。

### 中間テーブルに情報を持たせる

```sql
-- 「いつ付けたか」「誰が付けたか」を持たせることもできる
CREATE TABLE post_tag (
    post_id    INT NOT NULL,
    tag_id     INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    PRIMARY KEY (post_id, tag_id),
    ...
);
```

**中間テーブルが独自の情報を持つなら、独立したテーブルとして扱います。**

```sql
-- 「受講登録」は多対多だが、成績や登録日を持つ
CREATE TABLE enrollments (
    id          INT AUTO_INCREMENT PRIMARY KEY,   -- ★ 独自のID
    student_id  INT NOT NULL,
    course_id   INT NOT NULL,
    enrolled_at DATETIME NOT NULL,
    grade       CHAR(1) NULL,
    UNIQUE KEY uk_student_course (student_id, course_id),
    ...
);
```

---

## ✍️ 手を動かす③ ─ 1対1の関係

**あまり使いませんが、知っておくと便利です。**

```sql
-- ユーザーの詳細情報を分ける
CREATE TABLE user_profiles (
    user_id   INT PRIMARY KEY,              -- ★ 主キーが外部キーでもある
    bio       TEXT NULL,
    birthday  DATE NULL,
    website   VARCHAR(255) NULL,
    CONSTRAINT fk_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**使いどころ**

| 場面 | 理由 |
| --- | --- |
| カラムが多すぎる | 頻繁に使うものと、そうでないものを分ける |
| 巨大なデータがある | `TEXT` や `BLOB` を分けて、一覧取得を速くする |
| アクセス権限が違う | 機密情報だけ別テーブルにする |

> 💡 **基本は1つのテーブルにまとめます。** 分けるとJOINが必要になり、複雑になります。
> 「明確な理由があるときだけ分ける」が正解です。

---

## ✍️ 手を動かす④ ─ 命名規則

**チームで揃えることが目的です。以下はよく使われる慣習です。**

| 対象 | 規則 | 例 |
| --- | --- | --- |
| テーブル名 | **複数形・snake_case** | `users` `posts` `order_items` |
| 中間テーブル | **単数形を辞書順で繋ぐ** | `post_tag` `role_user` |
| カラム名 | **snake_case** | `user_id` `created_at` |
| 主キー | **`id`** | `id` |
| 外部キー | **`相手テーブルの単数形_id`** | `user_id` `post_id` |
| 真偽値 | **`is_` / `has_`** | `is_admin` `has_avatar` |
| 日時 | **`_at`** | `created_at` `deleted_at` |
| 日付 | **`_on` / `_date`** | `published_on` `birth_date` |
| 件数 | **`_count`** | `view_count` `comment_count` |

> 💡 **Laravel（第5部）はこの規則を前提に動きます。**
> `Post` モデルは自動で `posts` テーブルを見に行き、`user_id` を外部キーとして扱います。
> **規則に従うだけで、設定を書かずに動きます。**

### 予約語を避ける

```sql
-- ❌ MySQL の予約語
CREATE TABLE order (...);           -- order は ORDER BY の order
CREATE TABLE group (...);
SELECT key, value FROM settings;    -- key は予約語

-- ✅ 避ける
CREATE TABLE orders (...);
SELECT setting_key, setting_value FROM settings;

-- どうしても使うならバッククォートで囲む（推奨しない）
SELECT `key`, `value` FROM settings;
```

---

## ✍️ 手を動かす⑤ ─ インデックスを設計する

### インデックスを張るべきカラム

```
□ 主キー                        → 自動で張られる
□ 外部キー（user_id など）      → JOIN と絞り込みで使う
□ WHERE でよく使うカラム        → 検索条件
□ ORDER BY でよく使うカラム     → 並び替え
□ UNIQUE にしたいカラム         → 制約とインデックスを兼ねる
```

### インデックスを張るべきでないカラム

```
□ 値の種類が少ないカラム（真偽値、性別など）
   → 半分が該当するなら、全件読むのと変わらない
□ 頻繁に更新されるカラム
   → 更新のたびにインデックスも作り直される
□ 使われないカラム
   → 容量と書き込み速度の無駄
□ TEXT 型（全文検索インデックスは別）
```

### デメリットを理解する

| メリット | デメリット |
| --- | --- |
| **検索が速くなる**（O(n) → O(log n)） | **INSERT / UPDATE / DELETE が遅くなる** |
| 並び替えが速くなる | **ディスク容量が増える** |

> 💡 **インデックスは「読み取りを速くし、書き込みを遅くする」トレードオフです。**
> 「とりあえず全カラムに張る」は間違いです。

### 実際に張る

```sql
-- 単一カラム
CREATE INDEX idx_posts_created_at ON posts(created_at);

-- 複合インデックス（複数カラム）
CREATE INDEX idx_posts_user_created ON posts(user_id, created_at);

-- ユニークインデックス
CREATE UNIQUE INDEX idx_users_email ON users(email);

-- 確認
SHOW INDEX FROM posts;

-- 削除
DROP INDEX idx_posts_created_at ON posts;
```

### 複合インデックスの順序が重要

```sql
CREATE INDEX idx_posts_user_created ON posts(user_id, created_at);
```

| クエリ | インデックス |
| --- | --- |
| `WHERE user_id = 1` | **使える** |
| `WHERE user_id = 1 AND created_at > '2026-04-01'` | **使える（最も効率的）** |
| `WHERE user_id = 1 ORDER BY created_at DESC` | **使える（並び替えも省略できる）** |
| `WHERE created_at > '2026-04-01'` | **使えない** |

**「左から順に使われる」**（左端接頭辞の原則）。

> 💡 **順序の決め方**
> 1. **等価比較（`=`）で使うカラムを左に**
> 2. 範囲比較（`>`, `<`, `BETWEEN`）は右に
> 3. `ORDER BY` のカラムは、その後ろに

### `EXPLAIN` で確認する

**インデックスが実際に使われているかを確認できます。**

```sql
EXPLAIN SELECT * FROM posts WHERE user_id = 1 ORDER BY created_at DESC;
```

```
+----+-------------+-------+---------------------------+---------+------+----------+
| id | select_type | table | key                       | rows    | type | Extra    |
+----+-------------+-------+---------------------------+---------+------+----------+
| 1  | SIMPLE      | posts | idx_posts_user_created    | 2       | ref  |          |
+----+-------------+-------+---------------------------+---------+------+----------+
```

**見るべき3か所**

| 列 | 意味 | 良い / 悪い |
| --- | --- | --- |
| **`key`** | 使われたインデックス | `NULL` なら**使われていない** |
| **`rows`** | 調べた行数の見積もり | 少ないほど良い |
| **`type`** | アクセス方法 | `const` > `eq_ref` > `ref` > `range` > `index` > **`ALL`（最悪）** |
| `Extra` | 補足 | `Using filesort` / `Using temporary` は遅い兆候 |

> ⚠️ **`type: ALL` は「全行を読んでいる」という意味です。**
> 件数が増えると必ず遅くなります。インデックスを見直してください。

> 💡 **`EXPLAIN` は実務で毎日使います。**
> 「このクエリが遅い」と言われたら、まず `EXPLAIN` を付けて実行します。

### インデックスが効かない書き方

```sql
-- ❌ カラムを関数で包む
SELECT * FROM posts WHERE DATE(created_at) = '2026-04-13';
-- ✅
SELECT * FROM posts WHERE created_at >= '2026-04-13' AND created_at < '2026-04-14';

-- ❌ カラムを計算する
SELECT * FROM products WHERE price * 1.1 > 1000;
-- ✅
SELECT * FROM products WHERE price > 1000 / 1.1;

-- ❌ 前方に % がある LIKE
SELECT * FROM posts WHERE name LIKE '%太郎';
-- ✅（可能なら）
SELECT * FROM posts WHERE name LIKE '山田%';

-- ❌ 型が違う比較（暗黙の型変換が起きる）
SELECT * FROM users WHERE id = '1';      -- id は INT
-- ✅
SELECT * FROM users WHERE id = 1;

-- ❌ 否定条件
SELECT * FROM posts WHERE user_id != 1;

-- ❌ OR で別のカラムを使う（インデックスが1つしか使えないことがある)
SELECT * FROM posts WHERE user_id = 1 OR name = '山田太郎';
-- ✅ UNION に分ける
SELECT * FROM posts WHERE user_id = 1
UNION
SELECT * FROM posts WHERE name = '山田太郎';
```

> 💡 **原則：「カラムを裸のまま左辺に置く」。** 加工するなら右辺（値の側）で行います。

---

## ✍️ 手を動かす⑥ ─ 設計の手順

**ゼロから設計するときの順番です。**

```
① 何を管理するか、名詞を書き出す
   → ユーザー、投稿、返信、タグ、いいね

② それぞれの属性を書き出す
   → 投稿：本文、投稿者、投稿日時

③ 関係を矢印で結ぶ（1対多か、多対多か）
   → ユーザー 1 ──< 多 投稿
   → 投稿 多 >──< 多 タグ

④ 多対多には中間テーブルを作る
   → post_tag

⑤ 各カラムの型と制約を決める
   → NOT NULL か、UNIQUE か、デフォルト値は何か

⑥ インデックスを決める
   → 外部キー、WHERE・ORDER BY で使うカラム

⑦ 「同じ情報が2か所にないか」を確認する
   → ある場合、「その時点の値」として意図的なものか確認

⑧ 主要なクエリを実際に書いてみる
   → 書きにくいなら、設計に問題がある
```

> 💡 **⑧が最も重要です。**
> 「一覧を取得するSQL」「詳細を取得するSQL」を実際に書いてみると、
> 設計の問題が見えます。**JOINが4つ以上必要なら、設計を疑ってください。**

### ER図を描く

```
┌─────────────┐        ┌─────────────┐        ┌─────────────┐
│   users     │        │   posts     │        │  comments   │
├─────────────┤        ├─────────────┤        ├─────────────┤
│ id (PK)     │───1:N──│ id (PK)     │───1:N──│ id (PK)     │
│ name        │        │ user_id (FK)│        │ post_id (FK)│
│ email (UQ)  │        │ body        │        │ body        │
│ password    │        │ created_at  │        │ created_at  │
└─────────────┘        └──────┬──────┘        └─────────────┘
                              │ N:N
                       ┌──────┴──────┐        ┌─────────────┐
                       │  post_tag   │        │    tags     │
                       ├─────────────┤        ├─────────────┤
                       │ post_id (PK)│───N:1──│ id (PK)     │
                       │ tag_id  (PK)│        │ name (UQ)   │
                       └─────────────┘        └─────────────┘
```

| 記号 | 意味 |
| --- | --- |
| PK | 主キー（Primary Key） |
| FK | 外部キー（Foreign Key） |
| UQ | ユニーク制約 |
| 1:N | 1対多 |
| N:N | 多対多 |

> 💡 **ER図は手書きで十分です。** ツールを探す時間より、紙に描くほうが速いです。
> 清書が必要なら、draw.io や dbdiagram.io が無料で使えます。

---

## ⚠️ つまずきポイントまとめ

| 誤り | 正しい設計 |
| --- | --- |
| カンマ区切りで複数の値を持つ | 別テーブルに1行1値で分ける |
| `商品1` `商品2` `商品3` のカラム | 同上 |
| 1対多で「1」の側にIDを持つ | **「多」の側が持つ** |
| 多対多を1つのテーブルで表す | **中間テーブルを作る** |
| 中間テーブルに主キーがない | **複合主キー**を設定する |
| 外部キーにインデックスがない | 必ず張る |
| 全カラムにインデックスを張る | 必要なカラムだけ |
| 予約語をテーブル名にする | `order` → `orders` |
| 注文明細に価格を持たない | **その時点の価格を持つ**（意図的な重複） |
| 正規化を形式的に徹底する | 意味で判断する |

---

## 🤖 AIに聞いてみよう

### ① テーブル設計をレビューさせる

```text
以下は、私が設計したテーブル構成です。

（CREATE TABLE を全部貼る）

【このアプリの主な機能】
-
-

次の観点でレビューしてください。

1. 正規化の問題（同じ情報が2か所にある、第1〜3正規形違反）
2. 1対多・多対多の表現が適切か
3. 型と制約の選択（NOT NULL、UNIQUE、DEFAULT、外部キー）
4. インデックスの過不足
5. 命名規則の一貫性
6. 予約語の使用
7. 将来の拡張で問題になりそうな点

そのうえで、「意図的に正規化していない」ように見える箇所があれば、
それが妥当かどうかも判断してください。

修正後のSQLは書かず、指摘だけをお願いします。
```

### ② 主要なクエリを書かせて設計を検証する

```text
以下のテーブル構成で、次のクエリが書けるか確認したいです。

（CREATE TABLE を貼る）

【書きたいクエリ】
1. 投稿一覧（投稿者名、返信数、タグを含む）を新しい順に10件
2. 特定のタグが付いた投稿の一覧
3. ユーザーごとの投稿数と最終投稿日
4. 返信が1件もない投稿
5. 今月最も返信が多かった投稿トップ5

それぞれについて、
- SQLが書けるか
- JOINが何個必要か
- パフォーマンス上の懸念

を教えてください。
書きにくいクエリがあれば、それは設計の問題かどうか判断してください。
```

> 💡 **これは「設計を検証する」ための最も有効な方法です。**
> クエリが書きにくいなら、設計に問題があります。

---

## 🔧 やってみよう（演習）

### 演習1（必須）─ 悪い設計を直す

以下のテーブルを、正規化して分割してください。

```
テーブル: reservations（予約）
┌────┬──────────┬──────────────────┬────────────┬────────┬──────────┬────────────────┐
│ id │ 顧客名   │ 顧客メール       │ 予約日     │ 人数   │ 席タイプ │ 席料金         │
├────┼──────────┼──────────────────┼────────────┼────────┼──────────┼────────────────┤
│ 1  │ 山田太郎 │ taro@example.com │ 2026-04-20 │ 2      │ カウンター│ 0             │
│ 2  │ 佐藤花子 │ hana@example.com │ 2026-04-20 │ 4      │ 個室      │ 2000          │
│ 3  │ 山田太郎 │ taro@example.com │ 2026-04-25 │ 2      │ カウンター│ 0             │
└────┴──────────┴──────────────────┴────────────┴────────┴──────────┴────────────────┘
```

- [ ] 何と何を分けるべきか書き出す
- [ ] `CREATE TABLE` を書く（型・制約・外部キー・インデックス込み）
- [ ] ER図を手で描く
- [ ] 「予約一覧（顧客名と席タイプを含む）を取得するSQL」を書いてみる

<details>
<summary>答えを見る</summary>

**分けるもと**
- 顧客情報（`顧客名`, `顧客メール`）→ `customers`
- 席の種類と料金（`席タイプ`, `席料金`）→ `seat_types`
- 予約そのもの → `reservations`

```sql
CREATE TABLE customers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(50)  NOT NULL,
    email      VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seat_types (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(30) NOT NULL UNIQUE,
    price INT         NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT      NOT NULL,
    seat_type_id INT      NOT NULL,
    reserved_on  DATE     NOT NULL,
    guests       TINYINT  NOT NULL,

    -- ★ 予約時点の席料金を保存する（料金改定の影響を受けないように）
    seat_price   INT      NOT NULL,

    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_reservations_customer (customer_id),
    INDEX idx_reservations_date (reserved_on),

    CONSTRAINT fk_reservations_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_reservations_seat_type
        FOREIGN KEY (seat_type_id) REFERENCES seat_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**ポイント**
- `seat_price` を予約側にコピーしている → **意図的な非正規化**。
  料金改定後も、過去の予約金額が変わらない
- 顧客の削除は `RESTRICT`（予約があるのに顧客を消せないようにする）
- `reserved_on` は `DATE`（時刻が不要）、`created_at` は `DATETIME`
- 予約日での検索が多いので `reserved_on` にインデックス

**一覧のSQL**（JOINは 4-6 で学びます）

```sql
SELECT
    r.id,
    c.name        AS 顧客名,
    st.name       AS 席タイプ,
    r.reserved_on AS 予約日,
    r.guests      AS 人数,
    r.seat_price  AS 席料金
FROM reservations r
INNER JOIN customers  c  ON c.id  = r.customer_id
INNER JOIN seat_types st ON st.id = r.seat_type_id
ORDER BY r.reserved_on, r.id;
```

</details>

### 演習2（必須）─ インデックスを判断する

以下のクエリに対して、**どのインデックスを張るべきか**答えてください。

```sql
-- ① 投稿一覧（新しい順、10件）
SELECT id, name, body FROM posts ORDER BY created_at DESC LIMIT 10;

-- ② 特定ユーザーの投稿を新しい順に
SELECT * FROM posts WHERE user_id = 5 ORDER BY created_at DESC;

-- ③ メールアドレスでログイン
SELECT * FROM users WHERE email = 'taro@example.com';

-- ④ 削除されていない投稿を新しい順に
SELECT * FROM posts WHERE deleted_at IS NULL ORDER BY created_at DESC;

-- ⑤ 本文にキーワードを含む投稿
SELECT * FROM posts WHERE body LIKE '%キーワード%';
```

<details>
<summary>答えを見る</summary>

① `INDEX (created_at)`
並び替えだけなので、単一カラムで十分。

② `INDEX (user_id, created_at)`
**複合インデックス**。`user_id` で絞り、そのまま `created_at` 順に並んでいるので、
**並び替えの処理が不要になります**（`Using filesort` が消える）。

③ `UNIQUE INDEX (email)`
制約とインデックスを兼ねられる。ログインは最頻出のクエリなので必須。

④ `INDEX (deleted_at, created_at)`
ただし、**`deleted_at` は大半が NULL なので選択性が低い**。
実際には ① の `INDEX (created_at)` だけで十分なことが多い。
`EXPLAIN` で確認して判断する。

⑤ **通常のインデックスは効きません。**
選択肢：
- 全文検索インデックス（`FULLTEXT INDEX (body)` + `MATCH ... AGAINST`）
- 件数が少ないなら、そのまま `LIKE` で許容する
- 大規模なら Elasticsearch などの専用エンジン

</details>

### 演習3（挑戦）─ ゼロから設計する

**「カフェの予約・注文管理システム」**を設計してください。

**要件**
- 会員登録（メール、パスワード、氏名、電話）
- 席の予約（日時、人数、席タイプ）
- 注文（1回の来店で複数商品、数量つき）
- 商品マスタ（名称、価格、カテゴリ、販売中フラグ）
- ポイント（注文金額の1%が貯まる、次回以降使える）
- レビュー（1注文につき1件、5段階評価とコメント）

**手順**
- [ ] ① 名詞を書き出す
- [ ] ② 属性を書き出す
- [ ] ③ 関係（1対多 / 多対多）を矢印で結ぶ
- [ ] ④ ER図を描く
- [ ] ⑤ `CREATE TABLE` を書く
- [ ] ⑥ 以下のクエリを書いてみる
      - 今日の予約一覧（顧客名つき）
      - 会員ごとの累計注文額とポイント残高
      - 売れている商品トップ5
      - 評価が4以上のレビュー一覧
- [ ] ⑦ AIにレビューさせる（「手を動かす」のプロンプト①）

> 💡 **ポイントの設計が最も難しいです。**
> 「残高カラムを持つ」か「加算・減算の履歴テーブルを持つ」か。
> **履歴テーブルを持つのが正解**です。理由を考えてみてください。
> （ヒント：残高だけだと「なぜその残高になったか」を説明できません）

---

## ✅ 章末チェック

- [ ] 更新異常・削除異常・挿入異常を説明できる
- [ ] 「同じ情報を2か所に持たない」原則を説明できる
- [ ] 第1〜第3正規形を、自分の言葉で言える
- [ ] **意図的に非正規化する場面**（その時点の価格）を説明できる
- [ ] 1対多で「多」の側がIDを持つ理由を言える
- [ ] 多対多に中間テーブルが必要な理由を説明できる
- [ ] 複合主キーの効果を言える
- [ ] インデックスのメリットとデメリットを両方言える
- [ ] 複合インデックスが「左から使われる」ことを知っている
- [ ] `EXPLAIN` で `key` と `type` を確認できる
- [ ] インデックスが効かない書き方を5つ言える
- [ ] 命名規則（`_id` `_at` `is_`）を守れる

---

**前 → [4-4 INSERT / UPDATE / DELETE](04-04-insert-update-delete.md)　｜　次 → [4-6 JOIN で複数テーブルをつなぐ](04-06-join.md)**
