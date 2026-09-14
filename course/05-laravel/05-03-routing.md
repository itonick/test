# 5-3 ルーティング

> 🎯 **このロッスンのゴール**
> - HTTPメソッドとURLに応じて処理を振り分けられる
> - ルートパラメータを受け取れる
> - 名前付きルートでURLを一元管理できる
> - リソースルートで CRUD を一括定義できる

所要 120分 / 難度 🟢

---

> ⚠️ ゴールに「ロッスン」の誤字があります。読んで確認する練習です。気づいたら読み進めてください。

---

## 📖 ルーティングは「URLと処理の対応表」

第3部の掲示板では、こう書いていました。

```php
// 第3部：自分で分岐していた
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "create") { /* 投稿処理 */ }
    if ($action === "delete") { /* 削除処理 */ }
}
```

**Laravel では、これを宣言的に書きます。**

```php
// routes/web.php
Route::get('/items', [ItemController::class, 'index']);      // 一覧
Route::get('/items/{id}', [ItemController::class, 'show']);  // 詳細
Route::post('/items', [ItemController::class, 'store']);     // 登録
```

**「このURLに、このメソッドで来たら、この処理」を1行ずつ宣言します。**

---

## ✍️ 手を動かす① ─ HTTPメソッド

```php
<?php
use Illuminate\Support\Facades\Route;

// GET：データを取得する（表示）
Route::get('/items', [ItemController::class, 'index']);

// POST：データを作成する
Route::post('/items', [ItemController::class, 'store']);

// PUT / PATCH：データを更新する
Route::put('/items/{id}', [ItemController::class, 'update']);

// DELETE：データを削除する
Route::delete('/items/{id}', [ItemController::class, 'destroy']);
```

**第3部（3-6）で学んだ GET / POST の使い分けが、そのまま活きます。**

| メソッド | 用途 | 第3部でいうと |
| --- | --- | --- |
| `GET` | 取得・表示 | 検索フォーム、一覧 |
| `POST` | 新規作成 | 投稿 |
| `PUT` / `PATCH` | 更新 | 編集 |
| `DELETE` | 削除 | 削除 |

> 💡 **HTMLフォームは GET と POST しか送れません。**
> `PUT` / `DELETE` を使うには、フォームに `@method('DELETE')` を書きます（5-4で扱います）。
> 第3部で「削除は `action=delete` の POST」にしていたのは、この制約のためでした。
> Laravel では `@method` でこれを解決します。

### 簡単なレスポンス

```php
// 文字列を返す
Route::get('/ping', function () {
    return 'pong';
});

// JSON を返す
Route::get('/api/status', function () {
    return ['status' => 'ok', 'time' => now()];   // 自動でJSONになる
});

// ビューを返す
Route::get('/about', function () {
    return view('about');
});

// リダイレクト
Route::get('/old', function () {
    return redirect('/new');
});
```

> 💡 **配列を `return` すると、自動で JSON になります。**
> 第2部で `fetch` を使って叩いた API を、Laravel では簡単に作れます。

---

## ✍️ 手を動かす② ─ ルートパラメータ

**URLの一部を、値として受け取れます。**

```php
// {id} が変数になる
Route::get('/items/{id}', function ($id) {
    return "商品ID: {$id}";
});
// /items/5 → 「商品ID: 5」
```

### 複数のパラメータ

```php
Route::get('/users/{userId}/posts/{postId}', function ($userId, $postId) {
    return "ユーザー{$userId} の投稿{$postId}";
});
```

### 任意のパラメータ

```php
Route::get('/items/{category?}', function ($category = 'all') {
    return "カテゴリ: {$category}";
});
// /items      → 「カテゴリ: all」
// /items/book → 「カテゴリ: book」
```

### パラメータの制約

```php
// id は数字のみ
Route::get('/items/{id}', function ($id) {
    return "商品ID: {$id}";
})->whereNumber('id');

// /items/5   → OK
// /items/abc → 404（数字でないため）
```

> 💡 **`whereNumber` で制約すると、不正なURLを弾けます。**
> 第4部（4-7）で `(int) $_GET["id"]` と型を保証したのと同じ効果です。
> **URLの段階で弾く**ので、コントローラに不正な値が届きません。

主な制約：

```php
->whereNumber('id')            // 数字
->whereAlpha('name')           // 英字
->whereAlphaNumeric('slug')    // 英数字
->where('id', '[0-9]+')        // 正規表現
->whereIn('category', ['book', 'game'])   // 許可リスト
```

---

## ✍️ 手を動かす③ ─ 名前付きルート

**URLに名前を付けて、コードから参照できます。**

```php
Route::get('/items/{id}', [ItemController::class, 'show'])->name('items.show');
```

### なぜ名前を付けるのか

```php
// ❌ URLをハードコーディング
<a href="/items/{{ $item->id }}">詳細</a>

// もしURLを /products/{id} に変えたら、全部のリンクを直す必要がある

// ✅ 名前で参照
<a href="{{ route('items.show', $item->id) }}">詳細</a>

// URLを変えても、web.php の1行を直すだけで全リンクが変わる
```

> 💡 **これは第1部で学んだ「変数で色を管理する（カスタムプロパティ）」と同じ発想です。**
> 「1か所を直せば全部変わる」——保守しやすいコードの基本原則です。

> 🆘 **ここで詰まったら**（ルート関連のエラー）
> - **`Route [items.show] not defined`**：`->name('items.show')` を付け忘れ、または `route('...')` の名前のつづり違い。`php artisan route:list` で登録名を確認
> - **`The GET method is not supported for this route`（405）**：フォームの `method` とルートの定義がズレている（`Route::post` なのに GET で開いた等）。削除・更新は `@method('DELETE')`/`@method('PUT')` が要る
> - **`404`**：パラメータ付きルート（`/items/{id}`）の順序に注意。`/items/create` は `/items/{id}` より**先**に書く（後だと `create` が `{id}` に一致してしまう）
> - **直らなければ、AIにこう聞く**（web.php と、叩いているURL/フォームを貼る）：
>   「Laravelのルーティングで○○エラーが出ます。route:list の結果も添えます。原因を教えてください」

### 命名の規約

```php
Route::get('/items',           ...)->name('items.index');   // 一覧
Route::get('/items/create',    ...)->name('items.create');  // 作成フォーム
Route::post('/items',          ...)->name('items.store');   // 登録
Route::get('/items/{id}',      ...)->name('items.show');    // 詳細
Route::get('/items/{id}/edit', ...)->name('items.edit');    // 編集フォーム
Route::put('/items/{id}',      ...)->name('items.update');  // 更新
Route::delete('/items/{id}',   ...)->name('items.destroy'); // 削除
```

**`リソース名.アクション名`** という規約です。これは次の「リソースルート」で自動生成されます。

### route() ヘルパの使い方

```php
route('items.index')              // /items
route('items.show', 5)            // /items/5
route('items.show', ['id' => 5])  // /items/5
route('items.show', [5, 'tab' => 'reviews'])  // /items/5?tab=reviews
```

---

## ✍️ 手を動かす④ ─ リソースルート（CRUD を一括定義）

**CRUD（作成・読取・更新・削除）の7つのルートを、1行で定義できます。**

```php
Route::resource('items', ItemController::class);
```

**これだけで、以下の7つが定義されます。**

| メソッド | URL | コントローラのメソッド | 名前 | 用途 |
| --- | --- | --- | --- | --- |
| GET | `/items` | `index` | `items.index` | 一覧 |
| GET | `/items/create` | `create` | `items.create` | 作成フォーム |
| POST | `/items` | `store` | `items.store` | 登録 |
| GET | `/items/{item}` | `show` | `items.show` | 詳細 |
| GET | `/items/{item}/edit` | `edit` | `items.edit` | 編集フォーム |
| PUT/PATCH | `/items/{item}` | `update` | `items.update` | 更新 |
| DELETE | `/items/{item}` | `destroy` | `items.destroy` | 削除 |

```bash
php artisan route:list --name=items
```

で、7つのルートが確認できます。

> 💡 **これが Laravel の生産性の高さの象徴です。**
> 第3部の掲示板で、あなたは投稿・削除・一覧を手で分岐しました。
> Laravel なら、CRUD の骨組みが1行でできます。

### 一部だけ使う

```php
// 一覧と詳細だけ
Route::resource('items', ItemController::class)->only(['index', 'show']);

// 削除以外
Route::resource('items', ItemController::class)->except(['destroy']);
```

### コントローラも一括生成

```bash
# リソースコントローラ（7つのメソッドの雛形つき）を作る
php artisan make:controller ItemController --resource

# モデルとマイグレーションも同時に作る
php artisan make:controller ItemController --resource --model=Item
```

**`--resource` を付けると、`index` `create` `store` `show` `edit` `update` `destroy` の
7メソッドの雛形が入ったコントローラができます。**

---

## ✍️ 手を動かす⑤ ─ ルートのグループ化

**共通の設定をまとめられます。**

```php
// プレフィックス（URLの先頭を揃える）
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', ...);   // /admin/dashboard
    Route::get('/users', ...);       // /admin/users
});

// 名前のプレフィックス
Route::name('admin.')->group(function () {
    Route::get('/dashboard', ...)->name('dashboard');   // admin.dashboard
});

// ミドルウェア（ログイン必須など。5-11で詳しく）
Route::middleware('auth')->group(function () {
    Route::get('/mypage', ...);      // ログインしていないと弾かれる
    Route::resource('items', ItemController::class);
});

// まとめて
Route::prefix('admin')
    ->name('admin.')
    ->middleware('auth')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    });
```

> 💡 **`middleware('auth')` が、第3部で自作した `require_login()` に相当します。**
> グループで囲めば、中のすべてのルートがログイン必須になります。1つずつ書く必要がありません。

---

## ✍️ 手を動かす⑥ ─ 実践：フリマアプリのルート設計

フリマアプリのルートを設計します。`routes/web.php`（📋 完成形は `code/05-16/`）。

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ProfileController;

// トップページ = 商品一覧
Route::get('/', [ItemController::class, 'index'])->name('items.index');

// 商品詳細（誰でも見られる）
Route::get('/items/{item}', [ItemController::class, 'show'])
    ->whereNumber('item')
    ->name('items.show');

// ---------- ログインが必要な操作 ----------
Route::middleware('auth')->group(function () {

    // 出品（作成・登録・編集・更新・削除）
    Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
    Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
    Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');

    // 購入
    Route::post('/items/{item}/purchase', [PurchaseController::class, 'store'])
        ->name('purchases.store');

    // マイページ
    Route::get('/mypage', [ProfileController::class, 'show'])->name('mypage');
});
```

### ⚠️ ルートの順序に注意

```php
// ❌ この順序だと /items/create が動かない
Route::get('/items/{item}', [ItemController::class, 'show']);
Route::get('/items/create', [ItemController::class, 'create']);
//   /items/create にアクセスすると、{item} = "create" として show が呼ばれてしまう

// ✅ 具体的なルートを先に書く
Route::get('/items/create', [ItemController::class, 'create']);
Route::get('/items/{item}', [ItemController::class, 'show']);
```

> ⚠️ **ルートは「上から順にマッチ」します。**
> `/items/create` は `/items/{item}` にもマッチするので、**具体的なものを先に**書きます。
> `whereNumber('item')` を付けておけば、`create` は数字でないので回避できます。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| 404 Not Found | ルートが定義されていない | `php artisan route:list` で確認 |
| `/items/create` が詳細ページになる | ルートの順序 / 制約なし | 具体的なルートを先に、または `whereNumber` |
| `route('...')` でエラー | 名前付きルートがない | `->name()` を確認 |
| ルートを追加したのに反映されない | ルートキャッシュ | `php artisan route:clear` |
| POST でフォームが送れない | CSRF トークンがない | フォームに `@csrf`（5-4） |
| PUT/DELETE が動かない | フォームは GET/POST のみ | `@method('PUT')`（5-4） |
| `The GET method is not supported` | メソッドの不一致 | `route:list` でメソッドを確認 |

---

## 🤖 AIに聞いてみよう

### ① ルート設計をレビューさせる

```text
以下は、私が書いた Laravel のルート定義です。

（routes/web.php を貼る）

【このアプリの機能】
-
-

次の観点でレビューしてください。

1. RESTful な設計になっているか（HTTPメソッドとURLの対応）
2. ルートの順序に問題はないか（具体的なルートが後回しになっていないか）
3. 認証が必要なルートが middleware で守られているか
4. 名前付きルートの命名規則が一貫しているか
5. リソースルートでまとめられる箇所はないか
6. パラメータの制約（whereNumber など）が必要な箇所

修正後のコードは書かず、指摘だけをお願いします。
```

### ② REST の設計を学ぶ

```text
Laravel の RESTful なルート設計について教えてください。

以下の機能を、RESTful に設計するとどうなりますか。
HTTPメソッド・URL・コントローラのメソッド名を表にしてください。

1. 商品の一覧・詳細・出品・編集・削除
2. 商品へのコメント（追加・削除）
3. 商品のお気に入り登録・解除
4. ユーザーのフォロー・アンフォロー

「お気に入り」「フォロー」のような、CRUDに当てはめにくいものを
RESTfulに表現する考え方も教えてください。
（トグル操作をどう表現するか）
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）

- [ ] `/ping` にアクセスすると `pong` を返すルートを作る
- [ ] `/items/{id}` で、IDを表示するルートを作る（`whereNumber` 付き）
- [ ] `/items/abc` にアクセスして 404 になることを確認する
- [ ] `ItemController` を `--resource` 付きで作る
- [ ] `Route::resource('items', ...)` を定義する
- [ ] `php artisan route:list --name=items` で7つのルートを確認する

### 演習2（必須）

- [ ] `/admin` プレフィックスのグループを作る
- [ ] その中に `/admin/dashboard`（名前 `admin.dashboard`）を定義する
- [ ] `route('admin.dashboard')` が `/admin/dashboard` を返すことを確認する
- [ ] `middleware('auth')` でグループを囲む
- [ ] ログインしていない状態でアクセスすると、ログイン画面にリダイレクトされることを確認する
      （認証は 5-10 で作るので、いまはエラーになればOK）

### 演習3（挑戦）

フリマアプリの全ルートを設計してください。

- [ ] 商品：一覧（=トップ）・詳細・出品・編集・更新・削除
- [ ] 購入
- [ ] コメント：追加・削除
- [ ] お気に入り：登録・解除
- [ ] マイページ：出品した商品・購入した商品・お気に入り
- [ ] ログインが必要なものを `middleware('auth')` で囲む
- [ ] ルートの順序に注意する（`create` を `{item}` より先に）
- [ ] `php artisan route:list` で全体を確認する

> 💡 **お気に入りの登録・解除**は、`POST /items/{item}/favorite` と
> `DELETE /items/{item}/favorite` のように表現するのが RESTful です。
> AIに相談してみてください。

---

## ✅ 章末チェック

- [ ] HTTPメソッド（GET/POST/PUT/DELETE）と用途の対応を言える
- [ ] ルートパラメータ `{id}` を受け取れる
- [ ] `whereNumber` などの制約を付けられる
- [ ] 名前付きルートを使う理由を説明できる
- [ ] `route('...')` でURLを生成できる
- [ ] `Route::resource` が7つのルートを作ることを知っている
- [ ] ルートは上から順にマッチすることを知っている
- [ ] `middleware('auth')` でグループを守れる
- [ ] `php artisan route:list` を使える

---

**前 → [5-2 ディレクトリ構成とMVC](05-02-mvc.md)　｜　次 → [5-4 コントローラ](05-04-controller.md)**
