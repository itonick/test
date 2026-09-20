# 5-13 ページネーション・検索・並び替え

> ◎ **このレッスンのゴール**
> - 一覧を「ページ送り・検索・並び替え」できる実用的な画面にできる
> - 検索・並び替えの条件を、ページをまたいでも保持できる
> - 第4部の `LIMIT` / `WHERE ... LIKE` / `ORDER BY` が、Eloquentでどう表れるか理解する

所要 150分 / 難度 🟡

---

## 📖 一覧は「全件表示」では実用にならない

第4部で、大量データに `LIMIT` を付ける・`WHERE` で絞る・`ORDER BY` で並べる、を学びました。
一覧画面には、この3つがほぼ必ず必要になります。

| やりたいこと | 第4部（SQL） | Eloquent |
| --- | --- | --- |
| ページ送り | `LIMIT 20 OFFSET 40` | `->paginate(20)` |
| 絞り込み | `WHERE title LIKE '%〜%'` | `->where('title', 'like', "%{$kw}%")` |
| 並び替え | `ORDER BY created_at DESC` | `->orderBy('created_at', 'desc')` |

Eloquent なら、これらを**メソッドをつなげる**だけで書けます。

---

## ✍️ 手を動かす① ─ ページネーション

コントローラで `get()` の代わりに `paginate()` を使います。

```php
public function index()
{
    $posts = Post::with('user')            // N+1対策（5-8）は忘れずに！
        ->latest()                          // = orderBy('created_at', 'desc')
        ->paginate(20);                     // 1ページ20件

    return view('posts.index', ['posts' => $posts]);
}
```

Blade で一覧と「ページ送りリンク」を出します。

```blade
@foreach ($posts as $post)
    <article>{{ $post->title }} — {{ $post->user->name }}</article>
@endforeach

{{ $posts->links() }}   {{-- ← 前へ / 1 2 3 / 次へ が自動で出る --}}
```

`paginate()` は、裏で「全体件数のCOUNT」と「LIMIT/OFFSET付きのSELECT」を発行します。
第4部の `LIMIT ... OFFSET ...` を、件数計算込みで肩代わりしてくれる仕組みです。

> 🆘 **ここで詰まったら**（ページ送りリンクが出ない／2ページ目で検索が消える）
> - **リンクが出ない**：コントローラが `get()` のまま（`paginate()` に変える）／Bladeに `{{ $posts->links() }}` を書いていない
> - **リンクの見た目が崩れる**：Laravel既定のページネーションは Tailwind 前提。Bootstrap等を使うなら `AppServiceProvider` で `Paginator::useBootstrapFive()` などを設定
> - **2ページ目で検索条件が外れる**：`->paginate(20)->withQueryString()` を付ける（この後の③で詳説）
> - **直らなければ、AIにこう聞く**（コントローラと一覧Bladeを貼る）：
>   「Laravelのページネーションでリンクが出ません（または検索がページ送りで消えます）。原因を教えてください」

> 💡 `->latest()` は `orderBy('created_at', 'desc')` の短縮形。逆順は `->oldest()`。

---

## ✍️ 手を動かす② ─ 検索を足す

検索キーワードを受け取り、**入力があるときだけ** `where` を足します。

```php
public function index(Request $request)
{
    $keyword = $request->input('keyword');   // ?keyword=ラテ

    $posts = Post::with('user')
        ->when($keyword, function ($query, $keyword) {
            // keyword が空でないときだけ、この where が適用される
            $query->where('title', 'like', "%{$keyword}%")
                  ->orWhere('body', 'like', "%{$keyword}%");
        })
        ->latest()
        ->paginate(20)
        ->withQueryString();     // ← 重要（後述）

    return view('posts.index', [
        'posts'   => $posts,
        'keyword' => $keyword,
    ]);
}
```

`when($条件, $処理)` は「条件が真のときだけ処理を足す」便利メソッド。
`if ($keyword) { $query->where(...); }` と書くのと同じですが、メソッドチェーンを切らずに書けます。

検索フォーム（Blade）：

```blade
<form method="GET" action="{{ route('posts.index') }}">
    <input type="text" name="keyword" value="{{ $keyword }}" placeholder="検索">
    <button>検索</button>
</form>
```

> ⚠️ **検索は `method="GET"`**
> 検索条件はURL（`?keyword=ラテ`）に載せます。そうすると「その検索結果のURLを共有・
> ブックマーク」でき、ページ送りとも噛み合います。フォーム送信＝POST、ではありません。

---

## ✍️ 手を動かす③ ─ 【重要】検索条件をページ送りで保持する

「ラテ」で検索して2ページ目を押したら、検索が外れて全件に戻ってしまう──よくある事故です。

原因：ページ送りリンクが `?page=2` だけを付け、`?keyword=ラテ` を落とすから。

対策は2つ、**両方**やります。

```php
// コントローラ：ページャに現在のクエリ文字列を引き継がせる
->paginate(20)->withQueryString();
```

```blade
{{-- Blade：links にも明示的に引き継ぐと確実 --}}
{{ $posts->withQueryString()->links() }}
```

`withQueryString()` を付けると、ページ送りリンクが `?keyword=ラテ&page=2` になり、
**検索したままページを移動**できます。

> 🧠 第4部で「`LIMIT/OFFSET` と `WHERE` は同時に効く」と学びました。
> `withQueryString()` は、その「同時に効かせる状態」を**画面遷移でも維持する**ための仕掛けです。

---

## ✍️ 手を動かす④ ─ 並び替えを足す

「新しい順／古い順」を選べるようにします。

```php
$sort = $request->input('sort', 'new');   // 既定は new

$posts = Post::with('user')
    ->when($keyword, fn ($q) => $q->where('title', 'like', "%{$keyword}%"))
    ->orderBy('created_at', $sort === 'old' ? 'asc' : 'desc')
    ->paginate(20)
    ->withQueryString();
```

```blade
<a href="{{ route('posts.index', ['sort' => 'new', 'keyword' => $keyword]) }}">新しい順</a>
<a href="{{ route('posts.index', ['sort' => 'old', 'keyword' => $keyword]) }}">古い順</a>
```

> 🔒 **並び替えの列名をユーザー入力からそのまま渡さない**
> `orderBy($request->sort)` のように、リクエストの文字列を直接 `orderBy` に渡すと、
> 想定外の列で並べられたり、実装によっては危険です。上の例のように
> **「新か古か」を受け取って、列名はコード側で決める**のが安全。第4部の
> 「入力を信用しない」がここでも効きます。

---

## ✍️ 手を動かす⑤ ─ 発行SQLとインデックスを意識する

第4部の知識を使って、この一覧が速いか確認します。

```php
DB::enableQueryLog();
Post::with('user')->where('title', 'like', "%ラテ%")->latest()->paginate(20);
dump(DB::getQueryLog());
```

見えてくること：

- `like '%ラテ%'` は**前後にワイルドカード**があるので、第4部で学んだ通り
  **インデックスが効きにくい**（データが増えると遅くなる候補）
- 対策の方向：前方一致 `"{$kw}%"` にできないか、件数が多いなら全文検索の導入を検討
- `with('user')` を外すと、一覧の行ごとにuserを引きに行く**N+1**が復活する

> 🧠 「動く一覧」と「速い一覧」は別物。Eloquentで簡単に書けるからこそ、
> **裏のSQLを読める第4部の力**が、ここで差になります。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| ページ送りリンクが出ない | `get()` のまま／`links()` を書いていない | `paginate()` + `{{ $posts->links() }}` |
| 2ページ目で検索が外れる | クエリ文字列が引き継がれない | `->withQueryString()` |
| 検索してもヒットしない | 全角/半角・`like` のワイルドカード位置 | `"%{$kw}%"` を確認、trim（`trim_ja`）で前後空白を除去 |
| 一覧が急に重い | N+1 or `like '%..%'` | `with()` を付ける／検索方式を見直す |
| 並び替えでエラー | 入力文字列を `orderBy` に直渡し | 列名はコード側で決める |

---

## 🤖 AIに聞いてみよう

### 使えるプロンプト例

```
Laravel の投稿一覧に、次を実装してください。
- 1ページ20件のページネーション
- タイトル・本文のキーワード検索（空なら絞り込まない）
- 新しい順／古い順の切り替え
- 検索条件と並び順は、ページを送っても保持される
- N+1が起きないこと
発行されるSQLの注意点（LIKEとインデックス）も教えてください。
```

### 🔍 AIの答えを疑うチェックリスト

- [ ] `paginate()` に `withQueryString()` が付いているか（検索がページ送りで消えないか）
- [ ] `with('user')` などで**N+1対策**がされているか
- [ ] 並び替えで、リクエストの文字列を `orderBy` に**直渡ししていない**か
- [ ] 検索フォームが `method="GET"` になっているか
- [ ] `like '%..%'` の**性能上の注意**に触れているか（第4部の知識で検算する）

---

## 🔧 やってみよう（演習）

1. 一覧を `paginate(20)` にし、`{{ $posts->links() }}` を表示
2. キーワード検索を `when()` + `where('...','like',...)` で実装
3. 「ラテ」で検索 → 2ページ目に移動しても検索が保持されることを確認（`withQueryString()`）
4. 新しい順／古い順の切り替えを実装（列名はコード側で決める）
5. `DB::getQueryLog()` で、検索時の発行SQLを確認し、`with('user')` の有無でクエリ数が変わることを見る

---

## ✅ 章末チェック

1. `paginate(20)` は、第4部のどのSQL構文に相当するか。裏で何回クエリが飛ぶか（大まかに）。
2. 検索を `method="GET"` にする利点は？
3. `withQueryString()` は何を解決するためのものか。
4. 並び替えで、ユーザー入力を `orderBy` に直渡ししてはいけない理由は？

> ここまでで、実用アプリに必要な機能がひと通りそろいました。
> 次回はコードの品質を守る **テスト（PHPUnit）** に入ります。

---

[⬅️ 5-12 ファイルアップロード](05-12-file-upload.md) ｜ [➡️ 5-14 テストを書く（PHPUnit入門）](05-14-testing.md)
