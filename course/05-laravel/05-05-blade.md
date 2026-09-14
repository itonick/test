# 5-5 Bladeテンプレート

> 🎯 **このレッスンのゴール**
> - Blade の構文で、安全に画面を組み立てられる
> - レイアウトの継承・コンポーネントで重複をなくせる
> - 第3部の `partials/` が Blade でどう進化するかを理解する

所要 150分 / 難度 🟡

---

## 📖 Blade は「安全な PHP テンプレート」

第3部で、あなたは `<?= e($x) ?>` と `<?php foreach ...: ?>` でテンプレートを書きました。
Blade は、それを**短く・安全に**した記法です。

| 第3部（素のPHP） | Blade |
| --- | --- |
| `<?= e($name) ?>` | `{{ $name }}` |
| `<?php if ($a): ?>...<?php endif; ?>` | `@if ($a) ... @endif` |
| `<?php foreach ($items as $i): ?>` | `@foreach ($items as $i)` |
| `require 'header.php'` | `@include('header')` / レイアウト継承 |
| `csrf_field()` | `@csrf` |

**Blade ファイルは `.blade.php` という拡張子**にします。

---

## ✍️ 手を動かす① ─ 出力

```blade
{{-- 自動エスケープ（★ 基本これ） --}}
<p>{{ $name }}</p>

{{-- エスケープしない（★ 危険。信頼できるHTMLのみ） --}}
<div>{!! $trustedHtml !!}</div>

{{-- デフォルト値 --}}
<p>{{ $name ?? 'ゲスト' }}</p>

{{-- コメント（HTMLに出力されない） --}}
{{-- これはコメント --}}
```

> 🆘 **ここで詰まったら**（`{{ $name }}` が画面にそのまま文字で出る／`Undefined variable`）
> - **`{{ $name }}` がそのまま表示される**：拡張子が **`.blade.php`** になっていない（`.php` や `.html` だと Blade として処理されません）
> - **`Undefined variable $name`**：コントローラの `view('hello', ['name' => ...])` で**その変数を渡していない**、またはキー名のつづり違い。任意表示なら `{{ $name ?? '' }}`
> - **変更が反映されない**：`php artisan view:clear`（ビューのキャッシュ）を試す
> - **直らなければ、AIにこう聞く**（コントローラの view 呼び出しと Blade を貼る）：
>   「Blade で変数が表示されません（またはそのまま文字で出ます）。原因を確認手順つきで教えてください」

### ⚠️ `{{ }}` と `{!! !!}` の違い

**これは第2部・第3部で繰り返し学んだ XSS の話です。**

```blade
{{-- ユーザーが name に <script>alert(1)</script> を入れた場合 --}}

{{ $name }}     {{-- → &lt;script&gt;... 文字として表示（安全） --}}
{!! $name !!}   {{-- → <script>が実行される（XSS！） --}}
```

| 記法 | エスケープ | 使う場面 |
| --- | --- | --- |
| **`{{ $x }}`** | **する** | **ほぼ全部これ** |
| `{!! $x !!}` | しない | Markdownを変換したHTMLなど、**信頼できるものだけ** |

> ⚠️ **`{!! !!}` にユーザー入力を渡してはいけません。**
> 第2部（2-6）で `innerHTML`、第3部（3-7）で `e()` を通さない出力が XSS になると学びました。
> Blade の `{!! !!}` は、まさに「エスケープしない出力」です。**原則 `{{ }}`。**

> 💡 **`{{ }}` は自動でエスケープする**ので、第3部のように毎回 `e()` を書く必要がありません。
> **「うっかりエスケープを忘れる」事故が、Blade では起きにくい**設計です。

---

## ✍️ 手を動かす② ─ 制御構文

```blade
{{-- 条件分岐 --}}
@if ($item->stock > 0)
    <span class="in-stock">在庫あり</span>
@elseif ($item->stock === 0)
    <span class="sold-out">売り切れ</span>
@else
    <span>不明</span>
@endif

{{-- 否定 --}}
@unless ($user)
    <a href="{{ route('login') }}">ログイン</a>
@endunless

{{-- 値があるか（isset） --}}
@isset($item->description)
    <p>{{ $item->description }}</p>
@endisset

{{-- 空でないか --}}
@empty($items)
    <p>商品がありません</p>
@endempty

{{-- 繰り返し --}}
@foreach ($items as $item)
    <li>{{ $item->name }}</li>
@endforeach

{{-- 0件対応つきの繰り返し（★ 便利） --}}
@forelse ($items as $item)
    <li>{{ $item->name }}</li>
@empty
    <p class="empty">商品がありません</p>
@endforelse

{{-- for / while --}}
@for ($i = 0; $i < 5; $i++)
    <span>{{ $i }}</span>
@endfor
```

> 💡 **`@forelse` は、第3部で毎回書いた「0件のときの表示」を言語機能にしたものです。**
>
> ```blade
> @forelse ($items as $item)
>     ... 商品を表示 ...
> @empty
>     まだ商品がありません   {{-- 0件のとき、自動でこちら --}}
> @endforelse
> ```
>
> 「一覧が0件のときの表示を忘れる」事故を防げます。

### ループ変数 `$loop`

```blade
@foreach ($items as $item)
    {{ $loop->iteration }}   {{-- 1から始まる番号 --}}
    {{ $loop->index }}       {{-- 0から始まる番号 --}}
    @if ($loop->first) 最初 @endif
    @if ($loop->last) 最後 @endif
    {{ $loop->count }}       {{-- 総数 --}}
@endforeach
```

---

## ✍️ 手を動かす③ ─ よく使うディレクティブ

```blade
{{-- CSRF トークン（フォームに必須） --}}
@csrf

{{-- HTTPメソッドの偽装 --}}
@method('PUT')

{{-- ルートのURL --}}
<a href="{{ route('items.show', $item->id) }}">詳細</a>

{{-- 静的ファイルのURL（public/ からの相対） --}}
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<img src="{{ asset('images/logo.png') }}">

{{-- 認証状態 --}}
@auth
    <p>{{ auth()->user()->name }} さん</p>
@endauth
@guest
    <a href="{{ route('login') }}">ログイン</a>
@endguest

{{-- バリデーションエラー（5-9で詳しく） --}}
@error('name')
    <p class="error">{{ $message }}</p>
@enderror

{{-- 直前の入力値（エラー時の再表示） --}}
<input type="text" name="name" value="{{ old('name') }}">

{{-- 条件で class を付ける --}}
<div @class(['card', 'sold-out' => $item->stock === 0])>
```

> 💡 **`old('name')` が、第3部で自作した「エラー時に入力値を保持する」仕組み（`set_old`）に相当します。**
> Laravel はバリデーションエラーで戻ったとき、自動で入力値を保持します。`old()` で取り出せます。

> 💡 **`@auth` / `@guest` が、第3部の `is_logged_in()` による表示分岐に相当します。**

---

## ✍️ 手を動かす④ ─ レイアウトの継承

**第3部で `header.php` / `footer.php` を毎ページ `require` したのが、Blade では洗練されます。**

### レイアウトを定義する

`resources/views/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'フリマアプリ')</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

  <header class="site-header">
    <a href="{{ route('items.index') }}" class="logo">フリマ</a>
    <nav>
      @auth
        <a href="{{ route('items.create') }}">出品する</a>
        <a href="{{ route('mypage') }}">マイページ</a>
      @else
        <a href="{{ route('login') }}">ログイン</a>
      @endauth
    </nav>
  </header>

  {{-- フラッシュメッセージ（全ページ共通） --}}
  @if (session('success'))
    <p class="alert alert-success" role="status">{{ session('success') }}</p>
  @endif
  @if (session('error'))
    <p class="alert alert-error" role="alert">{{ session('error') }}</p>
  @endif

  <main class="container">
    @yield('content')   {{-- ← ここに各ページの中身が入る --}}
  </main>

  <footer class="site-footer">
    <p>&copy; {{ date('Y') }} フリマアプリ</p>
  </footer>

</body>
</html>
```

### レイアウトを使う

`resources/views/items/index.blade.php`

```blade
@extends('layouts.app')

@section('title', '商品一覧')

@section('content')
    <h1>商品一覧</h1>

    @forelse ($items as $item)
        <div class="item-card">
            <h2>{{ $item->name }}</h2>
            <p>{{ number_format($item->price) }}円</p>
        </div>
    @empty
        <p class="empty">まだ商品がありません。</p>
    @endforelse
@endsection
```

**仕組み**

| ディレクティブ | 役割 |
| --- | --- |
| `@extends('layouts.app')` | このレイアウトを使う |
| `@section('title', '...')` | `@yield('title')` の中身を埋める |
| `@section('content') ... @endsection` | `@yield('content')` の中身を埋める |
| `@yield('content')` | レイアウト側の「穴」 |

```
layouts/app.blade.php（枠）        items/index.blade.php（中身）
┌───────────────────────┐
│ ヘッダー              │
│ フラッシュメッセージ  │
│ ┌───────────────────┐ │        @section('content')
│ │ @yield('content') │◄├────────    <h1>商品一覧</h1>
│ └───────────────────┘ │            @forelse ...
│ フッター              │        @endsection
└───────────────────────┘
```

> 💡 **ヘッダー・フッター・フラッシュメッセージを、1か所（レイアウト）に書くだけ**で、
> 全ページに反映されます。第3部で毎ページ `require` していた手間が消えます。

---

## ✍️ 手を動かす⑤ ─ コンポーネント

**繰り返し使う部品**は、コンポーネントにします。第3部の `partials/menu-card.php` の進化形です。

### コンポーネントを作る

```bash
php artisan make:component ItemCard
```

これで2つのファイルができます。

`app/View/Components/ItemCard.php`（ロジック）

```php
<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ItemCard extends Component
{
    public function __construct(public $item) {}   // 受け取る値

    public function render()
    {
        return view('components.item-card');
    }
}
```

`resources/views/components/item-card.blade.php`（見た目）

```blade
<div class="item-card {{ $item->stock === 0 ? 'sold-out' : '' }}">
    <a href="{{ route('items.show', $item->id) }}">
        <h3>{{ $item->name }}</h3>
        <p class="price">{{ number_format($item->price) }}円</p>
        @if ($item->stock === 0)
            <span class="badge">売り切れ</span>
        @endif
    </a>
</div>
```

### 使う

```blade
@foreach ($items as $item)
    <x-item-card :item="$item" />
@endforeach
```

| 記法 | 意味 |
| --- | --- |
| `<x-item-card>` | `ItemCard` コンポーネントを呼ぶ |
| `:item="$item"` | `$item` を渡す（`:` は「変数を渡す」印） |
| `item="固定文字列"` | 文字列を渡す（`:` なし） |

> 💡 **`:item`（コロンあり）と `item`（コロンなし）の違い**
> - `:item="$item"` → PHPの変数 `$item` を渡す
> - `item="ノートPC"` → 文字列 "ノートPC" を渡す
>
> 第2部のReact風の書き方に似ています。

### 匿名コンポーネント（もっと簡単）

**ロジックが不要なら、Blade ファイルだけで作れます。**

`resources/views/components/alert.blade.php`

```blade
@props(['type' => 'info', 'message'])

<div class="alert alert-{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">
    {{ $message }}
</div>
```

```blade
{{-- 使う --}}
<x-alert type="success" message="保存しました" />
<x-alert type="error" :message="$errorMessage" />
```

---

## ✍️ 手を動かす⑥ ─ 部分的な include とスタック

### `@include`

```blade
{{-- 単純に別ファイルを埋め込む --}}
@include('partials.pagination', ['paginator' => $items])
```

### `@push` / `@stack`（ページ固有のCSS/JS）

```blade
{{-- レイアウト側 --}}
<head>
    ...
    @stack('styles')
</head>
<body>
    ...
    @stack('scripts')
</body>
```

```blade
{{-- 各ページ側 --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/items.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/items.js') }}"></script>
@endpush
```

> 💡 **「この画面だけで使うCSS/JS」を、その画面のBladeから追加できます。**
> レイアウトを汚さずに済みます。

---

## ✍️ 手を動かす⑦ ─ ビューを整理する原則

```
resources/views/
├─ layouts/
│   └─ app.blade.php          共通レイアウト
├─ components/
│   ├─ item-card.blade.php    再利用する部品
│   └─ alert.blade.php
├─ partials/
│   └─ pagination.blade.php   include する断片
└─ items/
    ├─ index.blade.php        一覧
    ├─ show.blade.php         詳細
    ├─ create.blade.php       出品フォーム
    └─ edit.blade.php         編集フォーム
```

**リソース名（`items`）ごとにディレクトリを作り、アクション名（`index`, `show`）で分ける。**
コントローラの `view('items.index')` と、素直に対応します。

### ⚠️ Blade にロジックを書きすぎない

```blade
{{-- ❌ Blade で複雑な計算をしている --}}
@php
    $total = 0;
    foreach ($items as $item) {
        $total += $item->price * $item->quantity * (1 + 0.1);
    }
@endphp
<p>合計: {{ number_format($total) }}</p>
```

```php
// ✅ 計算はコントローラかモデルで行い、結果だけ渡す
// コントローラ
$total = $cart->totalWithTax();
return view('cart', compact('total'));
```

```blade
{{-- ビューは表示だけ --}}
<p>合計: {{ number_format($total) }}</p>
```

> 💡 **`@php` を使いたくなったら、それはロジックがビューに漏れているサインです。**
> ビューは「表示するだけ」。計算はコントローラかモデルへ。
> 第3部で「ロジックと表示を上下に分ける」と学んだ原則が、ここでも成り立ちます。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `{{ }}` がそのまま表示される | 拡張子が `.blade.php` でない | 拡張子を確認 |
| XSS が発生する | `{!! !!}` にユーザー入力 | `{{ }}` を使う |
| `View not found` | ファイル名・場所の間違い | ドットとディレクトリの対応を確認 |
| レイアウトが反映されない | `@extends` / `@section` の書き忘れ | 両方書く |
| `@yield` が空 | `@section` 名の不一致 | 名前を合わせる |
| CSSが読み込まれない | パスの間違い | `asset('css/style.css')` |
| `<x-component>` が動かない | コンポーネント名の不一致 | ケバブケースで参照（`ItemCard` → `x-item-card`） |
| ビューのキャッシュが古い | コンパイル済みビュー | `php artisan view:clear` |

---

## 🤖 AIに聞いてみよう

### ① 第3部のテンプレートを Blade に変換させる（考え方）

```text
素の PHP で書いたテンプレートを、Laravel の Blade に書き換えたいです。

【素のPHP】
（partials/header.php や一覧の foreach を貼る）

これを Blade で書くとどうなるか、以下を教えてください。

1. <?= e($x) ?> → Blade での書き方
2. header.php / footer.php の require → レイアウト継承への変換
3. 繰り返し使うカード部分 → コンポーネント化すべきか
4. 0件のときの表示 → @forelse の活用

いきなり完成形を出すのではなく、対応関係を説明してから、
最後に変換例を示してください。
```

### ② Blade のロジック漏れをレビューさせる

```text
以下は、私が書いた Blade テンプレートです。

（Blade を貼る）

次の観点でレビューしてください。

1. @php で複雑なロジックを書いている箇所（コントローラ/モデルに移すべき）
2. {!! !!} でユーザー入力を出力している箇所（XSSの危険）
3. 同じ部分の繰り返し（コンポーネント化できる箇所）
4. レイアウトに切り出せる共通部分
5. アクセシビリティ（alt、label、role など）

修正後のコードは書かず、指摘だけをお願いします。
```

---

## 🔧 やってみよう（演習）

### 演習1（必須）

- [ ] `resources/views/layouts/app.blade.php` を作る（ヘッダー・フッター・フラッシュ表示）
- [ ] `items/index.blade.php` で `@extends('layouts.app')` を使う
- [ ] 仮の商品配列を `@forelse` で一覧表示する
- [ ] 0件のとき「まだ商品がありません」と出ることを確認する（配列を空にして試す）
- [ ] `{{ }}` に `<script>` を含む文字列を渡し、エスケープされることを確認する

### 演習2（必須）

- [ ] `<x-item-card>` コンポーネントを作る（`make:component`）
- [ ] 一覧で `@foreach` + `<x-item-card :item="$item" />` を使う
- [ ] 匿名コンポーネント `<x-alert>` を作る
- [ ] フラッシュメッセージを `<x-alert>` で表示するように書き換える
- [ ] `$loop->iteration` で、商品に連番を付ける

### 演習3（挑戦）

第1部で作ったカフェLPを、Blade で作り直してください。

- [ ] `layouts/app.blade.php` にヘッダー・フッターを切り出す
- [ ] メニューカードを `<x-menu-item>` コンポーネントにする
- [ ] メニューデータを配列でコントローラから渡し、`@foreach` で表示する
- [ ] `@php` を1つも使わずに書けるか挑戦する

> 💡 **第1部で「ヘッダーをコピペするのは無駄」と感じ、第3部で `require` で解決し、
> ここで Blade のレイアウト継承にたどり着きました。**
> 同じ問題を3段階で解決してきたことを、振り返ってみてください。

---

## ✅ 章末チェック

- [ ] `{{ }}` が自動エスケープすることを知っている
- [ ] `{!! !!}` にユーザー入力を渡してはいけない理由を言える
- [ ] `@if` / `@foreach` / `@forelse` を使える
- [ ] `@forelse` が「0件対応」に便利な理由を説明できる
- [ ] `@csrf` / `@method` / `@error` / `old()` の役割を言える
- [ ] `@extends` / `@section` / `@yield` でレイアウトを継承できる
- [ ] コンポーネント（`<x-...>`）を作って使える
- [ ] `:item`（コロンあり）と `item`（なし）の違いを説明できる
- [ ] Blade に複雑なロジックを書かない理由を言える

---

**前 → [5-4 コントローラ](05-04-controller.md)　｜　次 → [5-6 マイグレーションでテーブルを作る](05-06-migration.md)**
