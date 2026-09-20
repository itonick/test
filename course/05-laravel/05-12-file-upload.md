# 5-12 ファイルアップロードと画像表示

> ◎ **このレッスンのゴール**
> - フォームから画像を受け取り、安全に保存して表示できる
> - `storage` と `public` の関係、`storage:link` の意味が分かる
> - 第3部で学んだ「アップロードの危険」が、Laravelでどう守られるか理解する

所要 120分 / 難度 🟡

---

## 📖 ファイルアップロードは「一番事故が多い」機能

第3部で、アップロードの危険をいくつも学びました。

- 拡張子を偽装した実行ファイル（`evil.php.jpg` など）を置かれる
- 巨大ファイルでサーバを圧迫される
- 元のファイル名をそのまま使うと、上書き・パス破壊が起きる

Laravel は、これらを**安全に処理する道具**を用意しています。ただし
**「道具があるだけ」では守れません**。正しく使う判断はあなたの仕事です。

---

## ✍️ 手を動かす① ─ フォーム側（`enctype` を忘れない）

ファイルを送るフォームには、必ず `enctype="multipart/form-data"` が要ります。

```blade
<form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="file" name="image" accept="image/*">
    @error('image')
        <p class="error">{{ $message }}</p>
    @enderror
    <button>投稿</button>
</form>
```

> ⚠️ `enctype` を書き忘れると、ファイルが**サーバに届きません**（テキストだけ送られる）。
> 「アップロードできない」の原因No.1がこれ。第3部でも同じ罠を踏みました。

---

## ✍️ 手を動かす② ─ バリデーションで弾く（防御の第一線）

コントローラ／フォームリクエストで、**受け取る前に**ルールで縛ります。

```php
$request->validate([
    'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    //           任意       画像である  許可する形式だけ            2MBまで(KB単位)
]);
```

| ルール | 何を守るか | 第3部の何に対応 |
| --- | --- | --- |
| `image` | 画像ファイルであること | MIMEタイプの確認 |
| `mimes:jpg,png,...` | 許可した形式だけ通す | 拡張子偽装対策 |
| `max:2048` | サイズ上限（KB） | 巨大ファイル対策 |

> 🔒 **`image` / `mimes` は中身も見て判定**します。拡張子だけを信じないので、
> `evil.php` を `photo.jpg` に偽装しても弾かれます（第3部で「拡張子は信用するな」と学んだ通り）。

---

## ✍️ 手を動かす③ ─ 保存する（ファイル名はLaravelに任せる）

```php
public function store(StorePostRequest $request)
{
    $data = $request->validated();

    if ($request->hasFile('image')) {
        // storage/app/public/posts に保存し、保存先パスを受け取る
        // ファイル名はLaravelがランダム生成 → 上書き・パス破壊を防ぐ
        $data['image_path'] = $request->file('image')->store('posts', 'public');
    }

    $request->user()->posts()->create($data);

    return redirect()->route('posts.index')->with('status', '投稿しました');
}
```

`->store('posts', 'public')` がやってくれること：

- `storage/app/public/posts/` に保存
- **ファイル名を安全なランダム文字列に自動変換**（元の名前は使わない）
- 保存パス（例 `posts/a1b2c3.jpg`）を返す → これをDBの `image_path` 列に入れる

第3部で「`uniqid()` でファイル名を作り直す」と手書きした処理を、Laravelが肩代わりします。

> 💡 マイグレーションで `posts` に `image_path`（`string`, `nullable`）列を足しておくこと（5-6の復習）。

> 🆘 **ここで詰まったら**（保存は成功するのに画像が表示されない ─ 最頻出）
> - **原因No.1**：`php artisan storage:link` を実行していない（`storage` と `public` を橋渡しするリンク。次の④で実行）
> - **原因No.2**：表示側が `asset('storage/' . $post->image_path)` になっているか。DBに保存したパス（`posts/xxx.jpg`）と組み合わせる
> - **ファイルが届かない**：フォームに `enctype="multipart/form-data"` があるか（①）
> - **直らなければ、AIにこう聞く**（保存処理・表示Blade・実行したartisanコマンドを貼る）：
>   「Laravelで画像の保存は成功しますが表示されません。storage:link と asset() のどこが問題か教えてください」

---

## ✍️ 手を動かす④ ─ 表示する（`storage:link` の魔法）

ここが最初の関門です。Laravel は保存先を `storage/` に置きますが、
ブラウザから見えるのは `public/` の中だけ。**橋渡しのシンボリックリンク**を1回作ります。

```bash
php artisan storage:link
```

これで `public/storage` → `storage/app/public` のリンクが張られ、
保存した画像がURLで見えるようになります。表示は `asset('storage/...')`：

```blade
@if ($post->image_path)
    <img src="{{ asset('storage/' . $post->image_path) }}" alt="投稿画像">
@endif
```

> ⚠️ **画像が出ない No.1 の原因が `storage:link` 忘れ**。
> 「保存は成功しているのに表示だけ出ない」ときは、まずこれを疑ってください。

### なぜ `storage/` と `public/` を分けるのか？

- `public/` … 誰でもURLで直接アクセスできる（＝公開してよいものだけ置く）
- `storage/` … アプリ経由でしか触れない（＝**非公開ファイルの置き場**）

「ログインユーザーだけが見れる領収書PDF」のような**見せたくないファイル**は、
`storage:link` せず `storage/app/private` に置き、コントローラで認可（5-11）を通してから返す──
という使い分けができます。これも「アップロードは公開範囲を考える」という第3部の学びの延長です。

---

## ✍️ 手を動かす⑤ ─ 差し替え・削除でゴミを残さない

投稿を消したら、画像ファイルも消す。放置すると「孤児ファイル」が溜まります。

```php
use Illuminate\Support\Facades\Storage;

public function destroy(Post $post)
{
    $this->authorize('delete', $post);   // 5-11の認可

    if ($post->image_path) {
        Storage::disk('public')->delete($post->image_path);
    }
    $post->delete();

    return redirect()->route('posts.index')->with('status', '削除しました');
}
```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| ファイルが届かない | フォームに `enctype` が無い | `enctype="multipart/form-data"` |
| 画像が表示されない | `storage:link` 未実行 | `php artisan storage:link` |
| でかい画像でエラー | `max` 未設定 or php.ini の上限 | `max:2048` を付ける／`upload_max_filesize` 確認 |
| 何でもアップロードできてしまう | `mimes`/`image` を付けていない | ルールで形式を制限 |
| ファイルが上書きされる | 元のファイル名を使っている | `->store()` に任せる（ランダム名） |
| 削除後もファイルが残る | 物理ファイルを消していない | `Storage::disk('public')->delete()` |

---

## 🤖 AIに聞いてみよう

アップロードは**セキュリティと運用**の両方で穴が出やすい所です。

### 使えるプロンプト例

```
Laravel で投稿に画像を1枚添付できるようにしたいです。
- jpg/png/webp のみ、2MBまで
- 保存先は storage の public ディスク
- 投稿削除時に画像も消す
バリデーション・保存・表示・削除の全部を示してください。
```

### 🔍 AIの答えを疑うチェックリスト

- [ ] `image` / `mimes` で**形式を制限**しているか（何でも受け付けていないか）
- [ ] `max` で**サイズ上限**を付けているか
- [ ] フォームに `enctype="multipart/form-data"` があるか
- [ ] 元のファイル名をそのまま使っていないか（`->store()` に任せているか）
- [ ] 表示に `storage:link` と `asset('storage/...')` の説明があるか
- [ ] 削除時に**物理ファイルも消す**処理があるか
- [ ] 「公開してよいファイル」と「見せたくないファイル」の置き場を区別しているか

> 🧠 AIは「保存して表示する」までは書けても、**削除時のゴミ・非公開ファイルの扱い・
> サイズ上限**を忘れがちです。運用で効いてくるこの3点を、あなたがチェックしてください。

---

## 🔧 やってみよう（演習）

1. `posts` に `image_path`（nullable）列を足すマイグレーションを作って `migrate`
2. 投稿フォームに `enctype` と `<input type="file">` を追加
3. `image` / `mimes` / `max` のバリデーションを付ける
4. `->store('posts', 'public')` で保存し、`image_path` をDBに記録
5. `php artisan storage:link` を実行し、一覧で画像が表示されることを確認
6. わざと `.php` ファイルをアップロードして、**弾かれる**ことを確認
7. 投稿削除時に物理ファイルも消えることを確認

---

## ✅ 章末チェック

1. `image` / `mimes` / `max` の3ルールは、第3部で学んだどの脅威にそれぞれ対応するか。
2. `->store()` に保存を任せると、ファイル名の何が安全になるか。
3. `storage:link` は何と何をつなぐか。これを忘れると何が起きるか。
4. 「公開してよいファイル」と「見せたくないファイル」で、置き場と出し方はどう変えるべきか。

> 次回は一覧を実用的にする **ページネーション・検索・並び替え**。
> 第4部で学んだ `LIMIT` / `WHERE` / `ORDER BY` が、Eloquentでどう表現されるかを見ます。

---

[⬅️ 5-11 認可（ポリシー）](05-11-authorization.md) ｜ [➡️ 5-13 ページネーション・検索・並び替え](05-13-pagination-search.md)
