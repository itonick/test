# 5-11 認可（ポリシー）とミドルウェア

> 🎯 **このレッスンのゴール**
> - 「認証」と「認可」の違いを、コードのレベルで区別できる
> - ポリシーで「自分の投稿だけ編集・削除できる」を宣言的に実装できる
> - 第3部で自作した `403` 制御が、Laravelでどう仕組み化されるか理解する

所要 150分 / 難度 🔴

---

## 📖 認証と認可はちがう

前回（5-10）で「**あなたが誰か**」＝認証（authentication）を扱いました。
今回は「**あなたはこの操作をしてよいか**」＝認可（authorization）です。

第3部の掲示板で、あなたはこれを自作しました。

```php
// 第3部：他人の投稿を消そうとしたら 403
if ($post['user_id'] !== $_SESSION['user_id']) {
    http_response_code(403);
    exit('あなたはこの投稿を削除できません');
}
```

「ログインはしている（認証OK）」けれど「他人の投稿は消せない（認可NG）」。
この判断を、Laravel では **ポリシー（Policy）** という専用の場所にまとめます。

> 💡 **なぜ分けるのか**
> 認可のルール（誰が何をしてよいか）は、あちこちのコントローラに散らばりがち。
> ポリシーに集約すると、「投稿に関する権限ルールはここを見ればいい」となり、抜け漏れが減ります。

---

## ✍️ 手を動かす① ─ ポリシーを作る

```bash
php artisan make:policy PostPolicy --model=Post
```

`app/Policies/PostPolicy.php` ができます。ここに「Postに対して誰が何をしてよいか」を書きます。

```php
<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    // この投稿を更新してよいか？
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;   // 自分の投稿だけ
    }

    // この投稿を削除してよいか？
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
```

`$user->id === $post->user_id` ── これは第3部の
`$post['user_id'] !== $_SESSION['user_id']` と**まったく同じ判断**です。
書く場所が「コントローラの中」から「ポリシー」に移っただけ。

---

## ✍️ 手を動かす② ─ コントローラで呼び出す

コントローラでは `authorize()` を呼ぶだけ。NGなら**自動で403**になります。

```php
public function edit(Post $post)
{
    $this->authorize('update', $post);   // ポリシーの update() が呼ばれる
    return view('posts.edit', ['post' => $post]);
}

public function update(UpdatePostRequest $request, Post $post)
{
    $this->authorize('update', $post);
    $post->update($request->validated());
    return redirect()->route('posts.show', $post)->with('status', '更新しました');
}

public function destroy(Post $post)
{
    $this->authorize('delete', $post);   // ポリシーの delete() が呼ばれる
    $post->delete();
    return redirect()->route('posts.index')->with('status', '削除しました');
}
```

第3部では `if (...) { http_response_code(403); exit; }` を手書きしていた部分が、
`$this->authorize('delete', $post);` の**一行**になりました。
NGなら Laravel が 403 ページを返して、以降のコードは実行されません。

> 💡 メソッド名の対応：`authorize('update', $post)` → `PostPolicy::update()` が呼ばれる。
> 第1引数の文字列が、ポリシーのメソッド名になります。

---

## ✍️ 手を動かす③ ─ Blade でボタンを出し分ける

「消せない投稿に削除ボタンを見せる」のは不親切。ビューでも認可を使えます。

```blade
@can('delete', $post)
    <form method="POST" action="{{ route('posts.destroy', $post) }}">
        @csrf
        @method('DELETE')
        <button>削除</button>
    </form>
@endcan

@can('update', $post)
    <a href="{{ route('posts.edit', $post) }}">編集</a>
@endcan
```

`@can('delete', $post) ... @endcan` … ポリシーが `true` を返す人にだけ表示。

> ⚠️ **ボタンを隠すのは「親切」であって「防御」ではない**
> ビューでボタンを隠しても、URLを直打ちされたら？ だから**コントローラ側の
> `authorize()` が本命の防御**です。第3部で「画面制御とサーバ制御は別物」と学んだ通り。
> ビューの `@can` は UX、コントローラの `authorize()` はセキュリティ。両方やる。

---

## ✍️ 手を動かす④ ─ ミドルウェアで守る（もう一つの入口）

ルート単位で認可を掛けることもできます。

```php
Route::delete('/posts/{post}', [PostController::class, 'destroy'])
    ->middleware('can:delete,post');   // ポリシーの delete を通らないと403
```

`auth`（ログイン必須・5-10）と `can`（権限チェック・今回）は、こう役割が違います。

| ミドルウェア | 何を確認 | 対応する概念 |
| --- | --- | --- |
| `auth` | ログインしているか | 認証（誰か） |
| `can:delete,post` | その操作をしてよいか | 認可（してよいか） |

---

## ✍️ 手を動かす⑤ ─ 「管理者は何でもできる」を足す（発展）

`before()` を使うと、全ポリシーの前に走る特別ルールを書けます。

```php
public function before(User $user, string $ability): ?bool
{
    if ($user->is_admin) {
        return true;   // 管理者は全部許可（以降のメソッドは見に行かない）
    }

    return null;       // null＝「判断を各メソッドに任せる」
}
```

> ⚠️ `before()` で `false` を返すと**全部禁止**になってしまうので、
> 「判断しない」ときは必ず `null` を返します（`false` と混同しないこと）。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| 誰でも他人の投稿を消せてしまう | コントローラで `authorize()` を呼んでいない | 各アクションで `$this->authorize(...)` |
| いつも403になる | ポリシーの比較が逆／`before` が `false` を返している | 条件と `before` の戻り値を確認 |
| `@can` が常にfalse | 未ログイン、または引数ミス | `@auth` の中で使う／引数を確認 |
| ポリシーが呼ばれない | モデルと結びついていない | `make:policy --model=Post` で作る／命名規約を確認 |
| ボタンは隠れるが直打ちで消せる | ビューだけで守っている | コントローラの `authorize()` が必須 |

---

## 🤖 AIに聞いてみよう

認可はセキュリティの要。AIは「動くコード」を出しても「穴」を残しがちです。

### 使えるプロンプト例

```
Laravel のポリシーを設計してください。
- 投稿(Post)は、作成者本人だけが編集・削除できる
- 管理者(users.is_admin=true)は全投稿を編集・削除できる
- 一覧・詳細は誰でも見れる
PostPolicy と、コントローラでの呼び出し、Bladeの @can の使い方を示してください。
```

### 🔍 AIの答えを疑うチェックリスト

- [ ] **コントローラ側**に `authorize()` があるか？（ビューの `@can` だけで満足していないか）
- [ ] 所有者判定は `$user->id === $post->user_id` の**正しい向き**か（`!==` にして逆になっていないか）
- [ ] `before()` で `null` を返すべき所を `false` にして全禁止になっていないか
- [ ] 「認証(auth)」で足りる所に過剰な認可を、逆に必要な認可を**飛ばして**いないか
- [ ] 直打ちURL（画面にボタンが無くても叩けるルート）まで守られているか

> 🧠 **「ボタンが無いから安全」は初学者が必ずハマる罠**。
> AIのコードをレビューするとき、「このURLを直接叩いたらどうなる？」を必ず自問してください。

---

## 🔧 やってみよう（演習）

第3部の「自分の投稿だけ削除できる」を、Laravelのポリシーで再現します。

1. `PostPolicy` を作り、`update` / `delete` に所有者判定を書く
2. コントローラの `edit` / `update` / `destroy` で `authorize()` を呼ぶ
3. **他人の投稿の削除URLを直打ち**して、403になることを確認（第3部と同じ検証）
4. 自分の投稿は削除できることを確認
5. Blade で `@can('delete', $post)` を使い、自分の投稿にだけ削除ボタンを出す
6. （発展）`is_admin` 列を足し、`before()` で管理者は全投稿を消せるようにする

> ✅ **手順3が最重要**。第3部でやった「cross-session delete → 403」を、
> 今度はポリシーで実現できているか。ここが認可を理解できた証拠です。

---

## ✅ 章末チェック

1. 認証と認可の違いを、`auth` ミドルウェアと `can` ミドルウェアの役割で説明せよ。
2. コントローラの `authorize()` とビューの `@can` は、それぞれ何のためにあるか。片方だけではなぜダメか。
3. 第3部の `if ($post['user_id'] !== $_SESSION['user_id']) { 403 }` は、Laravelのどこに書くか。
4. ポリシーの `before()` で「判断しない」ときに返すべき値は？ `false` を返すと何が起きるか。

> 次回はフォームから**画像ファイル**を扱います。第3部で学んだ
> 「アップロードの危険」を思い出しながら、Laravel流の安全な保存・表示を学びます。

---

[⬅️ 5-10 認証機能（Breeze）](05-10-auth.md) ｜ [➡️ 5-12 ファイルアップロードと画像表示](05-12-file-upload.md)
