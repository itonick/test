# 5-4 コントローラ

> ◎ **このレッスンのゴール**
> - コントローラでリクエストを受け、ビューを返せる
> - リクエストデータを受け取れる
> - リダイレクトとフラッシュメッセージを扱える
> - コントローラを「薄く」保つ考え方を身につける

所要 120分 / 難度 🟡

---

## 📖 コントローラは「司令塔」

コントローラの役割は、**受けて・呼んで・渡す**だけです。

```
リクエストを受ける
   ↓
モデルを呼んでデータを取得・保存する
   ↓
ビューにデータを渡す（またはリダイレクトする）
```

**コントローラ自身は、複雑な処理をしません。** それはモデルの仕事です。
第3部の `index.php` で、あなたは検証を `Post::create()` に、
保存を `PostRepository` に任せました。**あれが正しい形です。**

---

## ✍️ 手を動かす① ─ リソースコントローラの全体像

```bash
php artisan make:controller ItemController --resource
```

生成される7つのメソッド：

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()    { }   // 一覧を表示
    public function create()   { }   // 作成フォームを表示
    public function store(Request $request) { }   // 登録処理
    public function show(string $id) { }   // 詳細を表示
    public function edit(string $id) { }   // 編集フォームを表示
    public function update(Request $request, string $id) { }   // 更新処理
    public function destroy(string $id) { }   // 削除処理
}
```

> 🆘 **ここで詰まったら**（`Target class [ItemController] does not exist`）
> - **チェック順**：① コントローラ名のつづり ② `routes/web.php` で `use App\Http\Controllers\ItemController;` を書いたか（または `[\App\Http\Controllers\ItemController::class, 'index']` とフル指定）③ ファイルが `app/Http/Controllers/` にあり、クラス名＝ファイル名か ④ 直らなければ `composer dump-autoload`
> - **直らなければ、AIにこう聞く**（web.php の該当行とコントローラの先頭を貼る）：
>   「Laravelで Target class does not exist が出ます。ルートとコントローラの対応が正しいか教えてください」

**7つの役割**

| メソッド | HTTPメソッド | 役割 | 返すもの |
| --- | --- | --- | --- |
| `index` | GET | 一覧 | ビュー |
| `create` | GET | 作成**フォーム** | ビュー |
| `store` | POST | 作成**処理** | リダイレクト |
| `show` | GET | 詳細 | ビュー |
| `edit` | GET | 編集**フォーム** | ビュー |
| `update` | PUT | 更新**処理** | リダイレクト |
| `destroy` | DELETE | 削除**処理** | リダイレクト |

> 💡 **「フォームを表示する」と「処理する」は別のメソッド**です。
> `create`（フォーム表示）と `store`（登録処理）はペア。
> 第3部で1つのファイルに混ざっていたものが、明確に分かれます。

---

## ✍️ 手を動かす② ─ ビューを返す

```php
public function index()
{
    // 本来はDBから取るが、今は仮データ
    $items = [
        ['id' => 1, 'name' => 'ノートPC', 'price' => 45000],
        ['id' => 2, 'name' => 'マグカップ', 'price' => 1200],
    ];

    // ビューにデータを渡す
    return view('items.index', ['items' => $items]);
}
```

### データの渡し方（3通り）

```php
// ① 連想配列
return view('items.index', ['items' => $items, 'title' => '商品一覧']);

// ② compact()（変数名がキーになる）
$items = ...;
$title = '商品一覧';
return view('items.index', compact('items', 'title'));

// ③ with() メソッドチェーン
return view('items.index')
    ->with('items', $items)
    ->with('title', '商品一覧');
```

> 💡 **`compact('items')` は `['items' => $items]` と同じ**です。
> 変数名とキー名が同じときに使うと、短く書けます。第2部のJSのショートハンドと似ています。

### ビューのファイル名の対応

```php
return view('items.index');
//          ↓
// resources/views/items/index.blade.php
```

**ドット `.` が、ディレクトリの区切り**になります。

---

## ✍️ 手を動かす③ ─ リクエストを受け取る

第3部では `$_POST["name"] ?? ""` と書いていました。Laravel では `Request` を使います。

```php
public function store(Request $request)
{
    // 個別に取得
    $name  = $request->input('name');
    $price = $request->input('price');

    // デフォルト値つき
    $category = $request->input('category', 'other');

    // 全部まとめて
    $all = $request->all();

    // 一部だけ
    $data = $request->only(['name', 'price']);
    $data = $request->except(['_token']);

    // 存在確認
    if ($request->has('name')) { }
    if ($request->filled('name')) { }   // 存在し、かつ空でない

    // プロパティのように取れる
    $name = $request->name;

    // ...処理...
}
```

| メソッド | 第3部でいうと |
| --- | --- |
| `$request->input('name')` | `$_POST['name'] ?? null` |
| `$request->input('name', 'default')` | `$_POST['name'] ?? 'default'` |
| `$request->filled('name')` | `!is_blank($_POST['name'] ?? '')` に近い |
| `$request->all()` | `$_POST`（+ GET も含む） |

> 💡 **`$request` は GET も POST も統一して扱えます。**
> 第3部で `$_GET` と `$_POST` を使い分けたのが、`$request->input()` に一本化されます。

### クエリパラメータ（GET）

```php
public function index(Request $request)
{
    $keyword = $request->query('q', '');       // ?q=... （GET専用）
    $page    = $request->query('page', 1);
}
```

### ルートパラメータ

```php
// Route::get('/items/{id}', ...)
public function show(string $id)
{
    // $id にURLの値が入る
}
```

---

## ✍️ 手を動かす④ ─ リダイレクトとフラッシュメッセージ

第3部で自作した PRG パターン（3-6）とフラッシュメッセージ（3-12）が、Laravel では標準です。

```php
public function store(Request $request)
{
    // ...保存処理...

    // ① 名前付きルートへリダイレクト + フラッシュメッセージ
    return redirect()
        ->route('items.index')
        ->with('success', '出品しました。');

    // ② 直前のページへ戻る（バリデーションエラー時など）
    return back()->with('error', 'エラーが発生しました。');

    // ③ URLを直接指定
    return redirect('/items');

    // ④ 作成したリソースの詳細へ
    return redirect()->route('items.show', $item->id)
        ->with('success', '出品しました。');
}
```

### フラッシュメッセージをビューで表示する

```blade
{{-- resources/views/... --}}
@if (session('success'))
    <p class="alert alert-success">{{ session('success') }}</p>
@endif

@if (session('error'))
    <p class="alert alert-error">{{ session('error') }}</p>
@endif
```

> 💡 **`->with('success', ...)` が、第3部の `set_flash('success', ...)` に相当します。**
> `session('success')` で取り出すと、**表示後に自動で消えます**（1回だけ表示）。
> 自作した `take_flash()` の仕組みが、標準で入っています。

### PRG パターンは自動

```php
public function store(Request $request)
{
    Item::create($request->validated());   // 保存
    return redirect()->route('items.index')->with('success', '登録しました');
    // ↑ リダイレクトするので、リロードしても二重登録されない
}
```

**第3部で `header("Location: ...", true, 303); exit;` と書いた PRG が、
`redirect()->route()` の1行になります。**

---

## ✍️ 手を動かす⑤ ─ フォームで PUT / DELETE を送る

HTMLフォームは GET と POST しか送れません（第3部で学んだ通り）。
Laravel では `@method` でこれを解決します。

```blade
{{-- 更新（PUT） --}}
<form method="POST" action="{{ route('items.update', $item->id) }}">
    @csrf
    @method('PUT')
    <input type="text" name="name" value="{{ old('name', $item->name) }}">
    <button type="submit">更新</button>
</form>

{{-- 削除（DELETE） --}}
<form method="POST" action="{{ route('items.destroy', $item->id) }}"
      onsubmit="return confirm('削除しますか？');">
    @csrf
    @method('DELETE')
    <button type="submit">削除</button>
</form>
```

| Blade | 役割 | 第3部でいうと |
| --- | --- | --- |
| `@csrf` | CSRFトークンを埋め込む | `csrf_field()` |
| `@method('PUT')` | 疑似的に PUT として送る | `<input name="action" value="...">` |

> 💡 **`@csrf` が、第3部で自作した `lib/csrf.php` の全機能を1行で置き換えます。**
> トークンの生成、検証、419エラーの処理——すべて Laravel が自動でやります。
> **自作したから、`@csrf` が何をしているかを説明できます。**

> ⚠️ **`@csrf` を忘れると `419 | Page Expired` になります。**
> フォームには必ず `@csrf` を書いてください（巻末エラー図鑑にも記載）。

---

## ✍️ 手を動かす⑥ ─ コントローラを薄く保つ

**最も重要な設計原則です。**

```php
// ❌ 太ったコントローラ（Fat Controller）
public function store(Request $request)
{
    // バリデーション
    if (empty($request->name)) { return back()->with('error', '名前が必要'); }
    if (strlen($request->name) > 50) { return back()->with('error', '長すぎ'); }
    // 価格計算
    $price = $request->price;
    $tax = floor($price * 0.1);
    $total = $price + $tax;
    // 画像処理
    $image = $request->file('image');
    $path = $image->store('items');
    // DB保存
    $item = new Item();
    $item->name = $request->name;
    $item->price = $total;
    $item->image = $path;
    $item->save();
    // 通知メール
    Mail::to($user)->send(new ItemListed($item));
    // ...
}
```

これは「MVCのControllerに全部書いた」状態で、第3部の `index.php` と同じ問題を抱えます。

```php
// ✅ 薄いコントローラ（Thin Controller）
public function store(StoreItemRequest $request)   // バリデーションは別クラス（5-9）
{
    $item = Item::create($request->validated());   // 保存はモデル
    return redirect()->route('items.show', $item)
        ->with('success', '出品しました。');
}
```

**コントローラの理想は「数行」です。**

| 処理 | どこに書くか |
| --- | --- |
| バリデーション | **フォームリクエスト**（5-9） |
| データの取得・保存 | **モデル**（5-7） |
| 業務ロジック（税計算など） | **モデル、またはサービスクラス** |
| 画像処理 | **モデル、またはサービスクラス** |
| 表示 | **ビュー**（5-5） |
| 「受けて・呼んで・渡す」 | **コントローラ** |

> 💡 **「コントローラに10行以上のロジックを書きたくなったら、切り出すサイン」**です。
> 第2部（2-3）で学んだ「1つの関数は1つの仕事」と同じ考え方です。

---

## ✍️ 手を動かす⑦ ─ 単一アクションコントローラ

**1つのことしかしないコントローラ**は、`__invoke` を使います。

```bash
php artisan make:controller PurchaseController --invokable
```

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    // メソッド名は __invoke（1つだけ）
    public function __invoke(Request $request, Item $item)
    {
        // 購入処理
        return redirect()->route('items.show', $item)
            ->with('success', '購入しました。');
    }
}
```

```php
// ルートでは、メソッド名を書かない
Route::post('/items/{item}/purchase', PurchaseController::class);
```

> 💡 **「購入する」「フォローする」のような、CRUDに当てはまらない単一の操作**に使います。
> 無理に7メソッドのコントローラに詰め込むより、分けるほうが読みやすくなります。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `419 Page Expired` | `@csrf` がない | フォームに `@csrf` |
| PUT/DELETE が動かない | `@method` がない | `@method('PUT')` |
| `View not found` | ビュー名・場所が違う | `items.index` → `views/items/index.blade.php` |
| リクエストの値が `null` | `name` 属性の不一致 | `$request->input('正しいname')` |
| フラッシュが表示されない | ビューで `session()` を読んでいない | `@if (session('success'))` |
| リロードで二重登録 | リダイレクトしていない | `return redirect()` |
| `Missing required parameter` | `route()` にパラメータ不足 | `route('items.show', $item->id)` |

---

## 🤖 AIに聞いてみよう

### ① コントローラの肥大化をレビューさせる

```text
以下は、私が書いた Laravel のコントローラです。

（コントローラを貼る）

次の観点でレビューしてください。

1. コントローラに書くべきでない処理（バリデーション、業務ロジック、
   画像処理など）が混ざっていないか
2. それぞれ、どこに切り出すべきか（フォームリクエスト / モデル /
   サービスクラス）
3. PRG パターン（リダイレクト）が守られているか
4. 1メソッドが長すぎないか

修正後のコードは書かず、「どこを・どこへ切り出すべきか」の
方針だけを教えてください。私が自分でリファクタリングします。
```

### ② Request の使い方を確認する

```text
Laravel の Request オブジェクトの使い方を整理したいです。

素の PHP では以下のように書いていました。それぞれ Laravel では
どう書くか教えてください。

1. $_POST['name'] ?? ''
2. $_GET['q'] ?? ''
3. isset($_POST['agree'])       // チェックボックス
4. trim($_POST['name'] ?? '')
5. (int) ($_POST['price'] ?? 0)
6. $_FILES['image']             // アップロードファイル

また、input() / query() / all() / only() / filled() の
使い分けも教えてください。
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）

- [ ] `ItemController` を `--resource` で作る
- [ ] `index()` で仮の商品配列を作り、`items.index` ビューに渡す
- [ ] `resources/views/items/index.blade.php` で一覧を表示する
- [ ] `show($id)` で、IDを受け取って表示する
- [ ] `store(Request $request)` で、送信された `name` を受け取り、
      リダイレクト + フラッシュメッセージを返す

### 演習2（必須）

- [ ] 商品の削除フォームを作る（`@csrf` + `@method('DELETE')`）
- [ ] `destroy($id)` で、フラッシュメッセージ付きでリダイレクトする
- [ ] 削除ボタンに確認ダイアログ（`onsubmit="return confirm(...)"`）を付ける
- [ ] フラッシュメッセージがビューで表示されることを確認する
- [ ] `@method('DELETE')` を消すと、どうなるか確認する（405エラー）

### 演習3（挑戦）

- [ ] 「購入」を単一アクションコントローラ（`--invokable`）で作る
- [ ] `POST /items/{item}/purchase` のルートを定義する
- [ ] 商品詳細ページに購入ボタン（フォーム + `@csrf`）を置く
- [ ] 購入したら、詳細ページにリダイレクトして「購入しました」と表示する
- [ ] コントローラが「数行」に収まっていることを確認する

---

## ✅ 章末チェック

- [ ] コントローラの役割「受けて・呼んで・渡す」を説明できる
- [ ] リソースコントローラの7メソッドと役割を言える
- [ ] `create`（フォーム）と `store`（処理）が別である理由を言える
- [ ] `view()` へのデータの渡し方を3通り言える
- [ ] `$request->input()` などでデータを受け取れる
- [ ] `redirect()->with()` でフラッシュメッセージを送れる
- [ ] `@csrf` が第3部の自作CSRF対策に相当することを理解した
- [ ] `@method('PUT')` の必要性を説明できる
- [ ] コントローラを薄く保つ理由を説明できる

---

**前 → [5-3 ルーティング](05-03-routing.md)　｜　次 → [5-5 Bladeテンプレート](05-05-blade.md)**
