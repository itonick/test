# 5-9 フォームリクエストとバリデーション

> 🎯 **このレッスンのゴール**
> - 入力チェックを Laravel の仕組みで宣言的に書ける
> - フォームリクエストにルールを切り出して、コントローラをスッキリさせる
> - 第3部で自作したバリデーション・エラー表示・入力保持が、どう仕組み化されるか理解する

所要 150分 / 難度 🟡

---

## 📖 バリデーションは「宣言」で書く

第3部で、あなたはこんなコードを自分で書きました。

```php
$errors = [];
if ($title === '')            { $errors['title'] = 'タイトルは必須です'; }
if (mb_strlen($title) > 100)  { $errors['title'] = '100文字以内で'; }
if ($body === '')             { $errors['body']  = '本文は必須です'; }
// ...エラーがあれば入力を保持して再表示...
```

Laravel なら、「どんなルールか」を**宣言するだけ**です。

```php
$request->validate([
    'title' => ['required', 'max:100'],
    'body'  => ['required'],
]);
```

- チェックNG → 自動で**前のページに戻り**、**エラーメッセージ**と**入力値**を持たせてくれる
- チェックOK → 検証済みの配列が返る

第3部で手書きした「if の山」「エラー配列」「old() 相当の入力保持」が、**全部込み**です。

---

## ✍️ 手を動かす① ─ コントローラで validate する

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => ['required', 'max:100'],
        'body'  => ['required', 'min:1'],
    ]);

    Post::create($validated);   // 検証済みデータだけを保存

    return redirect()->route('posts.index')->with('status', '投稿しました');
}
```

`$validated` には**ルールを書いた項目だけ**が入ります。
だから余計なキー（`is_admin` など）が紛れ込まず、5-7で学んだ mass assignment 対策とも噛み合います。

### Blade 側（エラー表示・入力保持）

第3部で手書きした部分が、専用構文になります。

```blade
<form method="POST" action="/posts">
    @csrf

    <input type="text" name="title" value="{{ old('title') }}">
    @error('title')
        <p class="error">{{ $message }}</p>
    @enderror

    <textarea name="body">{{ old('body') }}</textarea>
    @error('body')
        <p class="error">{{ $message }}</p>
    @enderror

    <button>投稿</button>
</form>
```

- `old('title')` … 検証で戻ってきたとき、**さっき打った値**を復元（第3部で自作したのと同じ発想）
- `@error('title') ... @enderror` … その項目にエラーがあるときだけ表示

> 💡 `@csrf` を忘れると 419 エラー。第3部で自作したCSRFトークンが、Laravelでは `@csrf` 一発です。

---

## ✍️ 手を動かす② ─ よく使うバリデーションルール

```php
$request->validate([
    'title'    => ['required', 'string', 'max:100'],
    'body'     => ['required', 'string'],
    'email'    => ['required', 'email'],
    'price'    => ['required', 'integer', 'min:0'],
    'age'      => ['nullable', 'integer', 'between:0,150'],  // 任意
    'password' => ['required', 'confirmed', 'min:8'],        // password_confirmation と一致
    'category' => ['required', 'in:tech,life,news'],         // この中のどれか
    'email2'   => ['required', 'email', 'unique:users,email'],// usersに重複が無いこと
    'image'    => ['nullable', 'image', 'max:2048'],         // 画像・2MBまで（5-12で使う）
]);
```

| ルール | 意味 |
| --- | --- |
| `required` | 必須 |
| `nullable` | 空でもOK（他ルールをスキップ） |
| `max:100` / `min:8` | 文字列なら文字数、数値なら値の上下限 |
| `email` | メール形式 |
| `unique:users,email` | usersテーブルのemail列に重複が無い |
| `confirmed` | `xxx_confirmation` と一致 |
| `in:a,b,c` | 列挙のどれか |

> ⚠️ **`max` の意味は型で変わる**
> `string` なら文字数、`integer` なら値、`file` ならKB。第4部で学んだ「型を意識する」がここでも大事。

---

## ✍️ 手を動かす③ ─ フォームリクエストに切り出す

ルールが増えると、コントローラが太ります。専用クラス **フォームリクエスト** に引っ越します。

```bash
php artisan make:request StorePostRequest
```

`app/Http/Requests/StorePostRequest.php`：

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    // このリクエストを送っていい人か？（認可。今は全員OK）
    public function authorize(): bool
    {
        return true;
    }

    // 検証ルール
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'body'  => ['required', 'string'],
        ];
    }

    // エラーメッセージを日本語に（任意）
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max'      => 'タイトルは100文字以内で入力してください',
            'body.required'  => '本文を入力してください',
        ];
    }
}
```

コントローラは、`Request` の代わりにこのクラスを**型指定するだけ**：

```php
use App\Http\Requests\StorePostRequest;

public function store(StorePostRequest $request)
{
    // ここに来た時点で検証は通過済み
    Post::create($request->validated());

    return redirect()->route('posts.index')->with('status', '投稿しました');
}
```

コントローラから if の山が消え、**「検証ルール」と「保存処理」の関心が分離**されました。

> 🧠 **`authorize()` が地味に重要**
> ここで `false` を返すと 403。「誰がこの操作をしてよいか」を検証と同じ場所で扱えます。
> 本格的な認可は 5-11（ポリシー）でやりますが、入り口はここにもあると覚えておいてください。

---

## ✍️ 手を動かす④ ─ 検証NGのときの流れを理解する

`StorePostRequest` を型指定したコントローラで、わざと空のフォームを送るとどうなるか。

1. Laravel が `rules()` で検証 → NG
2. **`store()` は呼ばれない**（コントローラに入る前に止まる）
3. 自動で**直前のフォームページにリダイレクト**
4. エラーメッセージが `$errors` に、入力値が `old()` に入った状態で再表示

第3部では、この「戻して・エラー出して・入力を残す」を全部手で書きました。
フォームリクエストは、その定番処理を**フレームワークに任せる**ものです。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| 419 Page Expired | `@csrf` 忘れ | フォームに `@csrf` |
| 検証が効かず素通り | コントローラで `validate()` を呼んでいない／型指定が `Request` のまま | `validate()` するか FormRequest を型指定 |
| 403 Forbidden | FormRequest の `authorize()` が `false` | 今は `return true;`（認可は5-11） |
| エラーは出るが入力が消える | `value="{{ old('name') }}"` を書いていない | `old()` で復元 |
| `@error` が出ない | name属性とルールのキーがズレている | フォームの `name` とルールのキーを一致させる |

---

## 🤖 AIに聞いてみよう

### 使えるプロンプト例

```
Laravel のフォームリクエストを作ってください。
- ユーザー登録フォーム: name(必須,50), email(必須,メール形式,usersで重複不可),
  password(必須,8文字以上,確認用と一致)
- 日本語のエラーメッセージも
対応するBladeのフォーム（エラー表示・入力保持つき）も示してください。
```

### 🔍 AIの答えを疑うチェックリスト

- [ ] `unique:users,email` のように**重複チェックの対象テーブル・列**が正しいか
- [ ] `password` に `confirmed` を付けたら、フォームに `password_confirmation` があるか
- [ ] Blade に `@csrf` があるか（無いと419）
- [ ] `old()` で入力保持しているか（第3部の学びが反映されているか）
- [ ] `authorize()` を安易に `true` 固定にしていないか（認可が必要な操作かを考える）
- [ ] `nullable` を付けるべき任意項目に、`required` が付いていないか

> 🧠 AIはルールをたくさん並べてくれますが、**「そのアプリに本当に必要なルールか」**は
> あなたが決めること。過剰なルールはユーザーを困らせ、緩いルールは事故を招きます。

---

## 🔧 やってみよう（演習）

第3部の掲示板の投稿フォームを、Laravel流に作り直します。

1. `StorePostRequest` を作り、`title`(必須・最大100) と `body`(必須) のルールを書く
2. 日本語メッセージを `messages()` に定義
3. コントローラの `store` の引数を `StorePostRequest` にする
4. Blade フォームに `@csrf` / `@error` / `old()` を入れる
5. 空で送信 → エラー表示され、入力が保持され、`store()` が呼ばれないことを確認
6. 正しく入力 → 保存され、一覧に `with('status', ...)` のメッセージが出ることを確認

---

## ✅ 章末チェック

1. `$request->validate([...])` が検証に失敗すると、ユーザーには何が起きるか（3つ）。
2. フォームリクエストに切り出すと、コントローラ側は何が嬉しいか。
3. `@csrf` / `@error` / `old()` は、それぞれ第3部で自作した何に対応するか。
4. `nullable` と `required` を取り違えると、どんな不具合になるか。

> 次回はいよいよ **認証（ログイン機能）**。Laravel Breeze を入れて、
> 第3部で自作したセッション・`password_hash` が、どう置き換わるかを見ます。

---

[⬅️ 5-8 リレーション](05-08-relations.md) ｜ [➡️ 5-10 認証機能（Breeze）](05-10-auth.md)
