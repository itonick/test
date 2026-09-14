# 5-16 【総合演習】フリマアプリを作る

> 🎯 **このレッスンのゴール**
> - 第5部で学んだ全部を使い、動くフリマアプリを1本作り切る
> - 認証・認可・リレーション・アップロード・トランザクション・テストを統合する
> - ポートフォリオに載せられる「自分の作品」を手に入れる

所要 600分（数日かけてOK）/ 難度 🔴 ／ 🏁 成果物：**フリマアプリ**

---

## 📖 これは「写経」ではなく「組み立て」

このレッスンには、動くコードを**ほぼ全部**載せています。でも、目的は
「コピペして完成させること」ではありません。**なぜそのコードなのかを、各部の学びで説明できること**です。

各コードブロックの後に「🔍 ここで効いている学び」を付けています。手を動かしながら、
「あ、これは第4部のトランザクションだ」「これは5-11のポリシーだ」とつなげてください。

> ⚠️ 実行にはLaravel環境（5-1で構築）＋Breeze（5-10で導入）が必要です。
> まだの人は先にそちらを済ませてください。

---

## 🧭 作るもの（MVP）

5-15でAИと詰めた設計を、そのまま実装します。

- ユーザー登録・ログイン（Breeze）
- 商品の出品（画像・タイトル・説明・価格）
- 商品一覧・詳細・キーワード検索・ページネーション
- 購入（購入済みは他の人は買えない＝**二重販売を防ぐ**）
- マイページ（自分の出品・購入履歴）
- 出品の編集・削除は**本人だけ**

---

## STEP 1 ─ テーブル設計（マイグレーション）

> 🆘 **総合演習で詰まったときの動き方**（最初に読んでください）
> - **一気に作らない**：STEPを1つ進めるごとに `php artisan serve` で動作確認する。「STEP5まで書いてから確認」より、原因の切り分けが圧倒的に楽です
> - **エラーが出たら、該当機能のレッスンに戻る**：マイグレーション→5-6、Eloquent→5-7、リレーション/N+1→5-8、認可→5-11、アップロード→5-12。各レッスンの「🆘」が効きます
> - **AIには「1機能ずつ・設計から」**：「フリマアプリ全部書いて」ではなく「この購入処理だけ、まず方針を説明して、そのあと短いコードを」と頼む（5-15の型）
> - **最後の確認用に完成コードあり**：各STEPのコードは、自分で書いて詰まってから照合すると力が付きます

`users` は Breeze 標準を使います。`products` と `orders` を作ります。

```bash
php artisan make:model Product -m
php artisan make:model Order -m
```

`database/migrations/xxxx_create_products_table.php`：

```php
public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 出品者
        $table->string('title', 100);
        $table->text('description');
        $table->unsignedInteger('price');            // 価格（0以上のみ）
        $table->string('image_path')->nullable();
        $table->string('status')->default('on_sale'); // on_sale | sold
        $table->timestamps();

        $table->index('status');   // 「販売中だけ一覧」で使うのでインデックス
    });
}
```

`xxxx_create_orders_table.php`：

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();     // 購入者
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();  // 買った商品
        $table->unsignedInteger('price');   // 購入時点の価格を記録（後で価格改定されても履歴は不変）
        $table->timestamps();
    });
}
```

```bash
php artisan migrate
```

> 🔍 **ここで効いている学び**
> - 外部キー・`cascadeOnDelete`（第4部・5-6）
> - `unsignedInteger` で「価格は0以上」を型で表現（第4部「型を意識」）
> - `status` にインデックス（第4部「検索する列にインデックス」）
> - `orders.price` に購入時価格を持つ＝**履歴は事実を残す**（正規化の実務判断）

---

## STEP 2 ─ モデルとリレーション

`app/Models/Product.php`：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'price', 'image_path'];
    // ↑ user_id / status はフォームから受け取らない（なりすまし・不正操作防止）

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        // 1商品につき購入は1件（MVP）。売れていなければ null
        return $this->belongsTo(Order::class, 'id', 'product_id');
    }

    // 販売中だけに絞るスコープ
    public function scopeOnSale($query)
    {
        return $query->where('status', 'on_sale');
    }

    public function isSold(): bool
    {
        return $this->status === 'sold';
    }
}
```

`app/Models/Order.php`：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'product_id', 'price'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

`app/Models/User.php` にリレーションを追記：

```php
public function products()   // 出品した商品
{
    return $this->hasMany(Product::class);
}

public function orders()     // 購入履歴
{
    return $this->hasMany(Order::class);
}
```

> 🔍 **ここで効いている学び**
> - `$fillable` に `user_id`/`status` を**入れない**（5-7 mass assignment 対策）
> - `belongsTo` / `hasMany` の向き（5-8）
> - `scopeOnSale` で「販売中だけ」を再利用可能に（Eloquentスコープ）

---

## STEP 3 ─ ルート設計

`routes/web.php`：

```php
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\MyPageController;

// 誰でも見れる
Route::get('/', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// ログイン必須
Route::middleware('auth')->group(function () {
    // 出品
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // 購入
    Route::post('/products/{product}/purchase', [PurchaseController::class, 'store'])->name('products.purchase');

    // マイページ
    Route::get('/mypage', [MyPageController::class, 'index'])->name('mypage');
});
```

> ⚠️ **順序に注意**：`/products/{product}` より `/products/create` を**先**に書かないと、
> `create` が `{product}` に食われます（`create` という名前の商品を探しに行く）。
> Laravelはルートを上から評価するので、具体的なパスを先に。

> 🔍 **ここで効いている学び**：`auth` ミドルウェアでの保護（5-10）、ルーティング（5-3）

---

## STEP 4 ─ 出品（フォームリクエスト＋アップロード）

`app/Http/Requests/StoreProductRequest.php`：

```bash
php artisan make:request StoreProductRequest
```

```php
public function authorize(): bool
{
    return true;   // ログイン済みは誰でも出品可（auth ミドルウェアで手前を守っている）
}

public function rules(): array
{
    return [
        'title'       => ['required', 'string', 'max:100'],
        'description' => ['required', 'string', 'max:2000'],
        'price'       => ['required', 'integer', 'min:0', 'max:99999999'],
        'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    ];
}

public function messages(): array
{
    return [
        'title.required' => '商品名を入力してください',
        'price.integer'  => '価格は数値で入力してください',
        'price.min'      => '価格は0以上で入力してください',
        'image.image'    => '画像ファイルを選んでください',
        'image.max'      => '画像は2MB以内にしてください',
    ];
}
```

`app/Http/Controllers/ProductController.php`：

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');

        $products = Product::with('user')
            ->onSale()                          // 販売中だけ（STEP2のスコープ）
            ->when($keyword, function ($query, $keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();                // 検索を保持したままページ送り（5-13）

        return view('products.index', compact('products', 'keyword'));
    }

    public function show(Product $product)
    {
        $product->load('user');                 // N+1対策
        return view('products.show', compact('product'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        // ログインユーザーに紐づけて作成（user_id はフォームから受け取らない）
        $request->user()->products()->create($data);

        return redirect()->route('products.index')->with('status', '出品しました');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);   // 本人だけ（5-11）
        return view('products.edit', compact('product'));
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path); // 旧画像を消す
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('products.show', $product)->with('status', '更新しました');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return redirect()->route('products.index')->with('status', '削除しました');
    }
}
```

> 🔍 **ここで効いている学び**
> - フォームリクエスト（5-9）／画像バリデーション・保存・旧画像削除（5-12）
> - `with('user')`・`load('user')` で N+1 対策（5-8）
> - `onSale()` スコープ・`when()` 検索・`withQueryString()`（5-7, 5-13）
> - `authorize()` で本人チェック（5-11）／`->products()->create()` でなりすまし防止（5-10）

---

## STEP 5 ─ 購入（トランザクションで二重販売を防ぐ）

ここが本アプリの心臓部です。第4部の**トランザクションとロック**の出番。

`app/Http/Controllers/PurchaseController.php`：

```bash
php artisan make:controller PurchaseController
```

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(Request $request, Product $product)
    {
        // 自分の出品は買えない
        if ($product->user_id === $request->user()->id) {
            return back()->with('error', '自分の商品は購入できません');
        }

        try {
            DB::transaction(function () use ($product, $request) {
                // 行ロックを取って、最新の状態を取り直す（同時購入対策）
                $locked = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== 'on_sale') {
                    // すでに誰かが買っていた
                    throw new \RuntimeException('sold');
                }

                // 状態を更新し、注文を作る（この2つは「全部成功か全部失敗か」）
                $locked->update(['status' => 'sold']);

                $request->user()->orders()->create([
                    'product_id' => $locked->id,
                    'price'      => $locked->price,   // 購入時点の価格を記録
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'この商品はすでに売り切れです');
        }

        return redirect()->route('mypage')->with('status', '購入しました');
    }
}
```

> 🔍 **ここで効いている学び（第4部の集大成）**
> - `DB::transaction()` … 「status更新」と「orders作成」を**まとめて成功/失敗**（原子性）
> - `lockForUpdate()` … 2人が同時に購入ボタンを押しても、**片方が待たされ**、
>   後の人は「もう sold」で弾かれる → **二重販売を防ぐ**
> - `orders.price` に購入時価格を保存 … 後で出品者が値段を変えても履歴は不変

> 🧠 **なぜ単純な `if ($product->status === 'on_sale')` ではダメか**
> チェックした瞬間と更新する瞬間の**すき間**に、別の人の購入が割り込むと二重販売になります
> （レースコンディション）。ロックとトランザクションで、この「すき間」を塞ぎます。

> 🆘 **ここで詰まったら**（購入処理でエラー／二重販売が防げない）
> - **ロックが効かない（両方売れる）**：`lockForUpdate()` は**トランザクションの中**でだけ効く。`DB::transaction()` で囲めているか確認。SQLiteはテーブルロックのため厳密な検証はMySQLで
> - **`RuntimeException('sold')` で毎回落ちる**：`throw` を `catch` できているか、`status` の初期値が `'on_sale'` になっているか
> - **購入されても在庫が変わらない**：`$locked->update(['status' => 'sold'])` が走る前に `return` していないか、`status` が `$fillable` に入っているか
> - **二重販売が防げているかテストで確認**：STEP9のテストを走らせ、「売り切れの商品は購入できない」が緑になるかを見る
> - **直らなければ、AIにこう聞く**（PurchaseController全体を貼る）：
>   「Laravelの購入処理で、トランザクションとロックによる二重販売防止が意図どおり動きません。コードのどこが原因か教えてください」

---

## STEP 6 ─ ポリシー（本人だけ編集・削除）

```bash
php artisan make:policy ProductPolicy --model=Product
```

```php
public function update(User $user, Product $product): bool
{
    // 本人 かつ まだ売れていない商品だけ編集可
    return $user->id === $product->user_id && $product->status === 'on_sale';
}

public function delete(User $user, Product $product): bool
{
    return $user->id === $product->user_id;
}
```

> 🔍 **ここで効いている学び**：認可の集約（5-11）。
> 「売れた商品は編集できない」というビジネスルールもここに集約できる。

---

## STEP 7 ─ マイページ

`app/Http/Controllers/MyPageController.php`：

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MyPageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $listings  = $user->products()->latest()->get();               // 出品一覧
        $purchases = $user->orders()->with('product')->latest()->get(); // 購入履歴（N+1対策）

        return view('mypage', compact('listings', 'purchases'));
    }
}
```

> 🔍 `with('product')` で購入履歴のN+1を防止（5-8）。

---

## STEP 8 ─ ビュー（Blade・抜粋）

`resources/views/products/index.blade.php`（一覧）：

```blade
<x-app-layout>
    <div class="container">
        @if (session('status'))
            <p class="flash">{{ session('status') }}</p>
        @endif

        <form method="GET" action="{{ route('products.index') }}" class="search">
            <input type="text" name="keyword" value="{{ $keyword }}" placeholder="商品を検索">
            <button>検索</button>
        </form>

        <div class="grid">
            @forelse ($products as $product)
                <a href="{{ route('products.show', $product) }}" class="card">
                    @if ($product->image_path)
                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->title }}">
                    @endif
                    <h3>{{ $product->title }}</h3>
                    <p class="price">¥{{ number_format($product->price) }}</p>
                    <p class="seller">出品者: {{ $product->user->name }}</p>
                </a>
            @empty
                <p>商品がありません。</p>
            @endforelse
        </div>

        {{ $products->links() }}
    </div>
</x-app-layout>
```

`resources/views/products/show.blade.php`（詳細・購入ボタン）：

```blade
<x-app-layout>
    <div class="container">
        @if (session('error'))
            <p class="flash error">{{ session('error') }}</p>
        @endif

        @if ($product->image_path)
            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->title }}">
        @endif

        <h1>{{ $product->title }}</h1>
        <p class="price">¥{{ number_format($product->price) }}</p>
        <p>{{ $product->description }}</p>
        <p class="seller">出品者: {{ $product->user->name }}</p>

        @if ($product->isSold())
            <p class="sold-label">SOLD OUT</p>
        @else
            @auth
                @if ($product->user_id !== auth()->id())
                    <form method="POST" action="{{ route('products.purchase', $product) }}">
                        @csrf
                        <button class="buy">購入する</button>
                    </form>
                @endif

                @can('update', $product)
                    <a href="{{ route('products.edit', $product) }}">編集</a>
                @endcan
                @can('delete', $product)
                    <form method="POST" action="{{ route('products.destroy', $product) }}"
                          onsubmit="return confirm('削除しますか？');">
                        @csrf
                        @method('DELETE')
                        <button>削除</button>
                    </form>
                @endcan
            @else
                <a href="{{ route('login') }}">購入するにはログイン</a>
            @endauth
        @endif
    </div>
</x-app-layout>
```

`resources/views/products/create.blade.php`（出品フォーム）：

```blade
<x-app-layout>
    <div class="container">
        <h1>商品を出品</h1>
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf

            <label>商品名
                <input type="text" name="title" value="{{ old('title') }}">
            </label>
            @error('title') <p class="error">{{ $message }}</p> @enderror

            <label>説明
                <textarea name="description">{{ old('description') }}</textarea>
            </label>
            @error('description') <p class="error">{{ $message }}</p> @enderror

            <label>価格（円）
                <input type="number" name="price" value="{{ old('price') }}" min="0">
            </label>
            @error('price') <p class="error">{{ $message }}</p> @enderror

            <label>画像
                <input type="file" name="image" accept="image/*">
            </label>
            @error('image') <p class="error">{{ $message }}</p> @enderror

            <button>出品する</button>
        </form>
    </div>
</x-app-layout>
```

> 🔍 **ここで効いている学び**
> - `@csrf`・`@error`・`old()`（5-9）／`enctype`・`asset('storage/...')`（5-12）
> - `@auth`・`@can`（5-10, 5-11）／`@forelse`（5-5）
> - `number_format` で価格表示（第2部の細かな配慮）
> - **売れた商品には購入ボタンを出さない**＝UXの認可（本命の防御はSTEP5のロック）

---

## STEP 9 ─ テストで守る（品質の証明）

`tests/Feature/PurchaseTest.php`：

```php
<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインユーザーは商品を購入できる(): void
    {
        $seller  = User::factory()->create();
        $buyer   = User::factory()->create();
        $product = Product::factory()->for($seller)->create(['status' => 'on_sale']);

        $response = $this->actingAs($buyer)->post(route('products.purchase', $product));

        $response->assertRedirect(route('mypage'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'sold']);
        $this->assertDatabaseHas('orders', ['product_id' => $product->id, 'user_id' => $buyer->id]);
    }

    public function test_売り切れの商品は購入できない(): void
    {
        $buyer   = User::factory()->create();
        $product = Product::factory()->create(['status' => 'sold']);

        $response = $this->actingAs($buyer)->post(route('products.purchase', $product));

        // orders は増えない
        $this->assertDatabaseMissing('orders', ['product_id' => $product->id]);
    }

    public function test_自分の商品は購入できない(): void
    {
        $seller  = User::factory()->create();
        $product = Product::factory()->for($seller)->create(['status' => 'on_sale']);

        $this->actingAs($seller)->post(route('products.purchase', $product));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'on_sale']);
        $this->assertDatabaseMissing('orders', ['product_id' => $product->id]);
    }

    public function test_他人の商品は編集できない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->for($owner)->create();

        $response = $this->actingAs($other)->get(route('products.edit', $product));

        $response->assertStatus(403);
    }
}
```

`database/factories/ProductFactory.php`：

```php
public function definition(): array
{
    return [
        'user_id'     => User::factory(),
        'title'       => fake()->words(3, true),
        'description' => fake()->sentence(),
        'price'       => fake()->numberBetween(100, 50000),
        'image_path'  => null,
        'status'      => 'on_sale',
    ];
}
```

```bash
php artisan test
```

> 🔍 **ここで効いている学び**：Feature テスト・`RefreshDatabase`・`actingAs`・
> `assertDatabaseHas/Missing`（5-14）。第3部で手作業でやった検証が、全部自動化された。

---

## STEP 10 ─ 動作確認（受け入れテスト）

最後に、人間の目でも確認します。テストと手動、両方やるのがプロ。

- [ ] 未ログインで出品ページ → `/login` に飛ぶ
- [ ] 登録・ログインできる
- [ ] 出品できる（画像つき／画像なし両方）
- [ ] 一覧に販売中の商品だけ出る、検索が効く、ページ送りで検索が保持される
- [ ] 詳細で購入できる → 商品が SOLD になり、他の人には買えない
- [ ] 自分の出品には購入ボタンが出ない、編集・削除は本人だけ
- [ ] マイページに出品・購入履歴が出る
- [ ] `.php` ファイルを画像欄にアップロード → 弾かれる

---

## 🤖 AIを使って仕上げる（この演習での使い方）

- **設計相談**：5-15でやったように、着手前にAIと設計を詰める
- **たたき台生成**：Bladeの見た目やCSSはAIに大量に出させてOK（見た目は事故が少ない）
- **レビュー依頼**：「このPurchaseControllerに二重販売の穴はない？」とAIに**自分のコードを審査**させる
- **テスト追加**：「このコントローラに対する異常系テストを追加して」

### 🔍 AIに頼るときも外さないチェック

- [ ] 購入処理から**トランザクション/ロックを外していないか**（AIは簡略化しがち）
- [ ] `$fillable` に `user_id`/`status` を**足していないか**（なりすまし穴）
- [ ] 認可（`authorize`/policy）を**省略していないか**
- [ ] N+1（`with`）が**消えていないか**
- [ ] 生成されたコードで `php artisan test` が**通るか**

---

## ✅ 完成したら（次の第6部へ）

おめでとうございます。あなたは**認証・認可・DB設計・トランザクション・
ファイル処理・テスト**を統合した、実用的なWebアプリを作り切りました。

このフリマアプリは、そのまま**ポートフォリオの主役**になります。
次の第6部では、これを

- Git で公開し、
- README で「何を・なぜ・どう作ったか」を語り、
- （任意で）サーバに公開して「動くURL」にする

ところまで持っていきます。**作った物を、伝わる形にする**のが最後の仕上げです。

---

## 🔧 発展課題（できたら挑戦）

1. 商品にカテゴリを付ける（多対多 or 単一カテゴリ）＝5-8の応用
2. 「いいね」機能（多対多＋N+1に注意）
3. 購入確認ページを1枚挟む（誤購入防止）
4. 出品者へのレビュー機能
5. 画像を複数枚に対応（`product_images` テーブル）

どれも「今の設計にどう足すか」をまず**AIと設計相談**してから着手してください。

---

## ✅ 章末チェック

1. 購入処理でトランザクションとロックを使う理由を、「二重販売」という言葉を使って説明せよ。
2. `Product` の `$fillable` に `user_id` と `status` を入れないのはなぜか。
3. 「売れた商品は購入ボタンを出さない」だけでは不十分な理由は？ 本命の防御はどこか。
4. このアプリで、第3部・第4部・第5部の学びがそれぞれどこに使われているか、1つずつ挙げよ。

---

[⬅️ 5-15 🤖 AIにLaravelの設計を相談する](05-15-ai-with-laravel.md) ｜ [➡️ 第6部 ポートフォリオと公開](../06-portfolio/06-01-git.md)
