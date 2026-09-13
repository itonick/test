# 5-7 Eloquent ORM の基本

> 🎯 **このレッスンのゴール**
> - モデルを使って、SQLを書かずにDBを読み書きできる
> - 第3部で書いた `PDO` + `prepare` が、どれだけ短くなるか実感する
> - 便利さの裏で「何のSQLが発行されているか」を意識できる

所要 180分 / 難度 🔴

---

## 📖 Eloquent は「テーブルをPHPオブジェクトとして扱う」仕組み

第3部で、1件取得するのにこう書きました。

```php
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();
```

Eloquent なら、これだけです。

```php
$post = Post::find($id);
```

**ORM（Object-Relational Mapping）** とは、「DBのテーブル（行）」と「PHPのオブジェクト」を
自動でひも付ける仕組みのこと。Eloquent は Laravel の ORM です。

| やりたいこと | 第3部（PDO） | Eloquent |
| --- | --- | --- |
| 1件取得 | `prepare` + `execute` + `fetch` | `Post::find($id)` |
| 全件取得 | `query('SELECT * ...')` + `fetchAll` | `Post::all()` |
| 条件で取得 | `WHERE ... :param` を組む | `Post::where('title', $t)->get()` |
| 追加 | `INSERT INTO ...` | `Post::create([...])` |
| 更新 | `UPDATE ... WHERE id` | `$post->update([...])` |
| 削除 | `DELETE FROM ... WHERE id` | `$post->delete()` |

> ⚠️ **「SQLを書かない」＝「SQLを知らなくていい」ではない**
> Eloquentは裏で必ずSQLを発行します。第4部でSQLとインデックス・N+1を学んだのは、
> **この裏側を見抜けるようになるため**でした。あとで実際にSQLを覗きます。

---

## ✍️ 手を動かす① ─ モデルを作る

```bash
php artisan make:model Post
```

`app/Models/Post.php` ができます。

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    // これだけで posts テーブルと結びつく
}
```

**規約（convention）が効いています。**

- モデル名 `Post`（単数・パスカルケース）→ テーブル `posts`（複数・スネークケース）に自動対応
- 主キーは `id`
- `created_at` / `updated_at` を自動で管理

だから設定を一切書かなくても、`Post` は `posts` テーブルを操作できます。
（第3部で「命名は大事」と言い続けたのは、この規約に乗るためでもあります）

> 💡 モデルとマイグレーションを同時に作るなら：`php artisan make:model Post -m`

---

## ✍️ 手を動かす② ─ 取得する（Read）

`php artisan tinker` を使うと、対話的にEloquentを試せます。

```bash
php artisan tinker
```

```php
// 全件
Post::all();

// 主キーで1件（見つからなければ null）
Post::find(1);

// 主キーで1件（見つからなければ 404 例外）
Post::findOrFail(1);

// 条件つき
Post::where('title', 'ラテを飲みたい')->get();   // 複数件（コレクション）
Post::where('title', 'ラテを飲みたい')->first();  // 最初の1件

// 並び替え・件数制限
Post::orderBy('created_at', 'desc')->limit(5)->get();

// 件数
Post::count();
```

ここで大事な区別：

| メソッド | 返り値 | いつ使う |
| --- | --- | --- |
| `get()` | コレクション（複数件） | 一覧を出す |
| `first()` | モデル1件 または null | 1件でいい |
| `find($id)` | モデル1件 または null | 主キーで探す |
| `findOrFail($id)` | モデル1件（なければ404） | 詳細ページ |

> 💡 **`findOrFail` が神機能**
> 第3部では「該当なしなら手動で404を返す」コードを自分で書きました。
> `findOrFail` は、見つからなければ自動で 404 ページを出してくれます。

---

## ✍️ 手を動かす③ ─ 追加する（Create）と mass assignment

```php
// 方法A: new してプロパティを埋めて save
$post = new Post();
$post->title = 'テスト投稿';
$post->body  = '本文です';
$post->save();

// 方法B: create で一括（こちらが主流）
Post::create([
    'title' => 'テスト投稿',
    'body'  => '本文です',
]);
```

方法Bを最初に試すと、**エラーになります**。

```
Add [title] to fillable property to allow mass assignment on [App\Models\Post].
```

これは**わざとの安全装置**です。`create([...])` に配列をまるごと渡せると、
悪意あるユーザーが `is_admin => true` のような**想定外のキー**を紛れ込ませられてしまう。
これを **mass assignment 脆弱性** と呼びます。

対策：モデルに「一括代入を許す列」を明示します。

```php
class Post extends Model
{
    protected $fillable = ['title', 'body'];
}
```

> ⚠️ **`$fillable` に何を入れるかはセキュリティ判断**
> `user_id` や `is_admin` のような「ユーザーに勝手に決めさせたくない列」は、
> `$fillable` に**入れない**のが基本。第4部で学んだ「入力は信用しない」がここにも生きています。

---

## ✍️ 手を動かす④ ─ 更新する（Update）・削除する（Delete）

```php
// 更新
$post = Post::findOrFail(1);
$post->title = '書き直したタイトル';
$post->save();

// または一括で
$post->update(['title' => '書き直したタイトル']);

// 削除
$post = Post::findOrFail(1);
$post->delete();
```

第3部で `UPDATE posts SET title = :title WHERE id = :id` と書いていたのが、
`$post->update([...])` の一行になりました。**WHERE id の付け忘れ事故が起きない**のも利点です。

---

## ✍️ 手を動かす⑤ ─ コントローラで使う（第3部の掲示板をEloquent化）

第3部・第4部で作った掲示板のコントローラを、Eloquentで書き直すとこうなります。

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // 一覧
    public function index()
    {
        $posts = Post::orderBy('created_at', 'desc')->get();
        return view('posts.index', ['posts' => $posts]);
    }

    // 詳細
    public function show(Post $post)   // ← ルートモデルバインディング（後述）
    {
        return view('posts.show', ['post' => $post]);
    }

    // 保存
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'max:100'],
            'body'  => ['required'],
        ]);

        Post::create($validated);

        return redirect()->route('posts.index')->with('status', '投稿しました');
    }

    // 削除
    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->route('posts.index')->with('status', '削除しました');
    }
}
```

第3部で数十行あった保存処理が、**バリデーション込みで数行**になりました。

### ✨ ルートモデルバインディング

`show(Post $post)` の `Post $post` に注目。ルートをこう書いておくと：

```php
Route::get('/posts/{post}', [PostController::class, 'show']);
```

`{post}` のID → 自動で `Post::findOrFail()` されて `$post` に入ります。
`find` も `findOrFail` も自分で呼ばなくていい。見つからなければ自動で404。

---

## ✍️ 手を動かす⑥ ─ 【超重要】発行されるSQLを覗く

「便利すぎて何が起きているか分からない」を防ぎます。第4部の知識の出番です。

```php
// tinker で
DB::enableQueryLog();

Post::where('title', 'like', '%ラテ%')->orderBy('created_at', 'desc')->get();

DB::getQueryLog();
```

出力（整形）：

```sql
select * from `posts`
where `title` like ?          -- ? に '%ラテ%' がバインドされる
order by `created_at` desc
```

ここで気づいてほしいこと：

- `?` を使った **プレースホルダ**になっている＝Eloquentは自動でSQLインジェクション対策済み
  （第4部で手書きした `prepare` を、Eloquentが肩代わりしている）
- でも `like '%ラテ%'` は**前方一致じゃないのでインデックスが効きにくい**
  → 第4部の「LIKEの罠」はEloquentでもそのまま当てはまる

> 🧠 **これが第4部を先にやった理由**
> Eloquentは「楽をさせてくれる」道具ですが、**遅いSQLや危ないSQLを自動で直してはくれません**。
> 裏のSQLを読める人だけが、Eloquentを安全に速く使えます。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `Add [title] to fillable...` | mass assignment 未許可 | `$fillable` に列を追加 |
| `Class "App\Models\Post" not found` | 名前空間 or use 忘れ | `use App\Models\Post;` |
| `find()` が null なのに `->title` を呼んで落ちる | 存在チェック忘れ | `findOrFail` を使う |
| `all()` が重い | 全件取ってメモリを食う | `where` で絞る / ページネーション（5-13） |
| 一覧で大量のSQLが出る | N+1問題（次回で対処） | `with()` で eager load |

---

## 🤖 AIに聞いてみよう

### 使えるプロンプト例

```
Laravel の Eloquent で、次を書いてください。
- Post モデルの中で「公開済み(is_published=true)かつ新しい順の10件」を取るスコープ
- コントローラからの呼び出し方も
発行されるSQLも教えてください。
```

### 🔍 AIの答えを疑うチェックリスト

- [ ] `$fillable`（または `$guarded`）の話に触れているか？ セキュリティを飛ばしていないか
- [ ] `get()` と `first()` を取り違えていないか？（1件のつもりでコレクションが返る等）
- [ ] 提案されたコードで **N+1** が起きないか？（`with()` があるか）
- [ ] `all()` を安易に勧めていないか？（大量データで危険）
- [ ] 生成された「発行SQL」を、自分で `DB::enableQueryLog()` で**実際に確かめたか**

> 🧠 AIは「動くEloquent」を書くのは得意ですが、「速いEloquent」かは別問題。
> **発行SQLを自分の目で確認する**習慣が、あなたを初学者から一段引き上げます。

---

## 🔧 やってみよう（演習）

tinker で、第4部の掲示板データに対して次を実行し、**発行SQLも確認**してください。

1. `posts` を新しい順で全件取得
2. タイトルに「ラテ」を含む投稿を取得（`where(... 'like' ...)`）
3. 新しい投稿を1件 `create` で追加（先に `$fillable` を設定）
4. 追加した投稿を `findOrFail` で取得し、`title` を更新
5. その投稿を削除
6. `DB::enableQueryLog()` → 2 を実行 → `DB::getQueryLog()` でSQLを見る

<details>
<summary>ヒント：2 と 6</summary>

```php
DB::enableQueryLog();
$posts = Post::where('body', 'like', '%ラテ%')->orderBy('id', 'desc')->get();
dump(DB::getQueryLog());   // where ... like ? が出るはず
```

`like` のSQLに `?` プレースホルダが使われていること（＝インジェクション対策済み）を確認しましょう。

</details>

---

## ✅ 章末チェック

1. `Post::find(1)` と `Post::findOrFail(1)` の違いは？ 詳細ページではどちらを使うべき？
2. `create([...])` を使う前に、モデルに何を設定する必要があるか。それは何を防ぐためか？
3. `get()` と `first()` はそれぞれ何を返すか？
4. Eloquentを使っても、第4部で学んだ「N+1」「LIKEの罠」「SQLインジェクション対策」のうち、
   **自動で解決されるもの・されないもの**をそれぞれ挙げよ。

> 次回は、投稿と「投稿者(user)」のように**テーブル同士のつながり（リレーション）**を扱います。
> 第4部の `JOIN` と N+1 問題が、Eloquentでどう表現されるかを見ていきます。

---

[⬅️ 5-6 マイグレーション](05-06-migration.md) ｜ [➡️ 5-8 リレーション](05-08-relations.md)
