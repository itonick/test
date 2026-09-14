# 5-8 リレーション（1対多・多対多）

> 🎯 **このレッスンのゴール**
> - Eloquent で「テーブル同士のつながり」を定義して使える
> - 第4部で学んだ `JOIN` と `N+1問題` が、Eloquent でどう表れるか理解する
> - `with()` による eager load で N+1 を実際につぶせる

所要 180分 / 難度 🔴

---

## 📖 リレーション＝「テーブルのつながり」をコードにする

第4部で、こういう関係を設計しました。

- 1人の **user** は、複数の **post** を持つ（1対多）
- 1件の **post** は、1人の **user** に属する

SQLで投稿と投稿者名を一緒に取るには `JOIN` を書きました。

```sql
SELECT posts.*, users.name
FROM posts
JOIN users ON users.id = posts.user_id;
```

Eloquent では、この関係を**モデルにメソッドとして定義**しておけば、
`$post->user->name` のように**オブジェクトを辿るだけ**で取れます。

---

## ✍️ 手を動かす① ─ 1対多を定義する

### 「属する側」＝ Post（多） → User（1）

```php
// app/Models/Post.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = ['title', 'body', 'user_id'];

    public function user(): BelongsTo
    {
        // posts.user_id が users.id を指す
        return $this->belongsTo(User::class);
    }
}
```

### 「持つ側」＝ User（1） → Post（多）

```php
// app/Models/User.php
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    public function posts(): HasMany
    {
        // users.id を持つ posts をすべて
        return $this->hasMany(Post::class);
    }
}
```

覚え方：

| メソッド | 意味 | どっちに書く |
| --- | --- | --- |
| `belongsTo` | 〜に属する（外部キーを**持つ**側） | Post（`user_id` を持つ） |
| `hasMany` | 〜をたくさん持つ | User |

> 💡 **外部キーの命名規約**
> `belongsTo(User::class)` は、自動で `user_id` 列を探します。
> モデル名 `User` → 外部キー `user_id`。第4部の命名規約がここでも効いています。

---

## ✍️ 手を動かす② ─ リレーションを使う

```php
// 投稿から投稿者を辿る
$post = Post::findOrFail(1);
echo $post->user->name;        // ← JOINを書かずに投稿者名

// 投稿者からその投稿を辿る
$user = User::findOrFail(1);
foreach ($user->posts as $post) {
    echo $post->title;
}

// 件数だけ欲しい
echo $user->posts()->count();
```

ポイント：**`()` の有無**で意味が変わります。

```php
$user->posts     // プロパティ：結果（コレクション）を取得
$user->posts()   // メソッド：クエリビルダ（さらに where などを足せる）
```

```php
// 「この人の公開投稿を新しい順で3件」
$user->posts()->where('is_public', true)->latest()->limit(3)->get();
```

> 🆘 **ここで詰まったら**（`Call to a member function ... on null` / リレーションが取れない）
> - **`Call to a member function name() on null`**：`$post->user` が `null`（投稿者が削除済み・未設定）なのに辿った。`$post->user?->name`（ヌル安全演算子）や `optional($post->user)->name` で守る
> - **`()` の付け忘れ/付けすぎ**：結果が欲しいのに `$user->posts()`（クエリのまま）を回そうとしている等。**結果＝`$user->posts`、条件を足す＝`$user->posts()->where(...)->get()`**
> - **リレーションが `null` や空**：外部キー名が規約どおりか（`belongsTo(User::class)` は `user_id` を探す）。違うなら第2引数で明示
> - **直らなければ、AIにこう聞く**（両モデルのリレーション定義と該当コードを貼る）：
>   「Eloquentのリレーションで null エラーが出ます。定義の向きと、null の扱いのどこが原因か教えてください」

Blade では：

```blade
@foreach ($posts as $post)
    <li>{{ $post->title }} — by {{ $post->user->name }}</li>
@endforeach
```

---

## ✍️ 手を動かす③ ─ 【最重要】N+1問題をEloquentで体験する

第4部で名前だけ出た **N+1問題** が、Eloquent では**とても起きやすい**。ここが本レッスンの山場です。

### 悪い例（N+1が発生）

```php
$posts = Post::latest()->get();       // ① 投稿を全部取る（SQL 1回）

foreach ($posts as $post) {
    echo $post->user->name;           // ② 1件ごとに users を取りに行く！
}
```

投稿が20件あると、発行されるSQLは…

```
1回   : select * from posts ...
+ 20回: select * from users where id = ?   ← 投稿の数だけ実行される
------
合計21回
```

これが **N+1問題**（1 + N回のクエリ）。投稿が増えるほど爆発的に遅くなります。

### 実際に見る

```php
DB::enableQueryLog();
$posts = Post::latest()->get();
foreach ($posts as $post) { $post->user->name; }
count(DB::getQueryLog());   // → 投稿数+1 になっているはず
```

---

## ✍️ 手を動かす④ ─ eager load（`with`）で N+1 をつぶす

先に「関連するuserもまとめて取っておいて」と伝えます。これが **eager load**。

```php
$posts = Post::with('user')->latest()->get();   // ← with('user') を足すだけ

foreach ($posts as $post) {
    echo $post->user->name;   // もう追加クエリは飛ばない
}
```

発行されるSQL：

```
1回: select * from posts ...
1回: select * from users where id in (1, 2, 3, ...)   ← まとめて1回！
----
合計2回（投稿が何件でも2回）
```

| | 発行クエリ数（投稿20件） |
| --- | --- |
| `with` なし（N+1） | 21回 |
| `with('user')` あり | **2回** |

> 🧠 **これを体で覚えることが、第4部と第5部をつなぐ核心**
> 第4部で「N+1は遅い」と知識で学び、いまEloquentで**実際に発生させて・実際に直す**。
> 一覧を作るときは口ぐせのように「`with` は要らないか？」と自問してください。

コントローラの一覧は、こう書くのが基本形になります。

```php
public function index()
{
    $posts = Post::with('user')->latest()->paginate(20);  // ページネーションは5-13
    return view('posts.index', ['posts' => $posts]);
}
```

---

## ✍️ 手を動かす⑤ ─ 多対多（タグ機能）

「1つの投稿に複数タグ、1つのタグは複数投稿につく」＝ **多対多**。
第4部で学んだ通り、**中間テーブル**が必要です。

### 中間テーブルのマイグレーション

```php
// create_post_tag_table（命名規約：2つのモデル名を単数・アルファベット順で "_" 連結）
Schema::create('post_tag', function (Blueprint $table) {
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
});
```

### モデルに `belongsToMany`

```php
// Post.php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class);
}

// Tag.php
public function posts(): BelongsToMany
{
    return $this->belongsToMany(Post::class);
}
```

### 使う

```php
$post = Post::findOrFail(1);

// タグを付ける（中間テーブルに行を追加）
$post->tags()->attach($tagId);

// 外す
$post->tags()->detach($tagId);

// まとめて置き換える（今あるものを消して、この配列に）
$post->tags()->sync([1, 2, 3]);

// 辿る
foreach ($post->tags as $tag) {
    echo $tag->name;
}
```

> 💡 中間テーブル名 `post_tag` は「単数・アルファベット順・アンダースコア連結」。
> `posts_tags` でも `tag_post` でもないので注意。規約に外れると自動で見つけてくれません
> （どうしても別名にしたいときは `belongsToMany(Tag::class, 'テーブル名')` で明示）。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| 一覧ページが妙に遅い | N+1問題 | `with('user')` で eager load |
| `Call to a member function ... on null` | 関連が無いのに辿った（`$post->user` が null） | `optional($post->user)->name` / `$post->user?->name` |
| `belongsTo` が動かない | 外部キー名が規約外 | `belongsTo(User::class, 'author_id')` で明示 |
| 多対多で `attach` が効かない | 中間テーブル名が規約外 | テーブル名を `post_tag` に、または第2引数で指定 |
| `()` を付けたら結果が来ない | メソッドはクエリを返す | 結果が欲しいなら `->get()` か、プロパティで `$user->posts` |

---

## 🤖 AIに聞いてみよう

リレーションはAIが取り違えやすい領域です（`hasMany` と `belongsTo` の向きなど）。

### 使えるプロンプト例

```
Laravel のEloquentリレーションを設計してください。
- User は複数の Post を持つ
- Post は1人の User に属する
- Post は複数の Tag を持ち、Tag も複数の Post に付く（多対多）
各モデルのメソッドと、必要なマイグレーション（中間テーブル含む）を書いてください。
一覧取得でN+1が起きないコードも示してください。
```

### 🔍 AIの答えを疑うチェックリスト

- [ ] `belongsTo` と `hasMany` の**向き**は正しいか？（外部キーを持つ側が `belongsTo`）
- [ ] 一覧取得のコードに `with()` があるか？（無ければN+1を指摘して直させる）
- [ ] 中間テーブル名は規約（単数・アルファベット順）に合っているか？
- [ ] `$post->user` が null になりうる場面で、`?->` などの安全策があるか？
- [ ] 提案SQLを `DB::getQueryLog()` で**実際に確認したか**（クエリ数が想定通りか）

---

## 🔧 やってみよう（演習）

1. `Post` に `belongsTo(User::class)`、`User` に `hasMany(Post::class)` を定義
2. tinker で `Post::first()->user->name` が取れることを確認
3. **N+1を発生させる**：`Post::latest()->get()` してループ内で `$post->user->name`。
   `DB::getQueryLog()` のクエリ数を数える
4. **N+1を直す**：`Post::with('user')->latest()->get()` に変えて、クエリ数が2になることを確認
5. （発展）`Tag` モデルと `post_tag` 中間テーブルを作り、`attach` / `sync` を試す

<details>
<summary>ヒント：3 と 4 の比較</summary>

```php
DB::flushQueryLog(); DB::enableQueryLog();
foreach (Post::latest()->get() as $p) { $p->user->name; }
echo "N+1: " . count(DB::getQueryLog()) . " queries\n";   // 例: 21

DB::flushQueryLog();
foreach (Post::with('user')->latest()->get() as $p) { $p->user->name; }
echo "eager: " . count(DB::getQueryLog()) . " queries\n"; // 2
```

</details>

---

## ✅ 章末チェック

1. `hasMany` と `belongsTo` は、それぞれどちらのモデルに書くか？ 判断基準は？
2. N+1問題とは何か。なぜ Eloquent では起きやすいのか。どう直すか。
3. `with('user')` を付けると、発行されるクエリ数はどう変わるか（投稿N件のとき）。
4. 多対多に必要な「中間テーブル」の命名規約を、`post` と `tag` の例で答えよ。

> 次回は、フォームから来たデータを安全に検証する **フォームリクエスト** を学びます。
> 第3部で自作したバリデーションが、Laravelでどう仕組み化されるかを見ます。

---

[⬅️ 5-7 Eloquent ORM の基本](05-07-eloquent.md) ｜ [➡️ 5-9 フォームリクエストとバリデーション](05-09-form-request.md)
