# チートシート

書き方を忘れたときの早見表です。**暗記する必要はありません。** ここを見ればよい、と思ってください。

---

## HTML

### 基本の雛形

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ページタイトル</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <script src="js/app.js"></script>
</body>
</html>
```

### よく使うタグ

| タグ | 用途 |
| --- | --- |
| `<h1>`〜`<h6>` | 見出し。h1はページに1つ |
| `<p>` | 段落 |
| `<a href="">` | リンク |
| `<img src="" alt="">` | 画像。`alt` は必須 |
| `<ul>` `<ol>` `<li>` | リスト（順序なし / あり） |
| `<div>` | 意味のないまとまり |
| `<span>` | 意味のないインラインのまとまり |
| `<br>` | 改行 |
| `<table>` `<tr>` `<th>` `<td>` | 表 |

### セマンティックタグ

| タグ | 用途 |
| --- | --- |
| `<header>` | ページや区画の頭 |
| `<nav>` | ナビゲーション |
| `<main>` | 主要コンテンツ。1ページに1つ |
| `<section>` | 意味のある区画 |
| `<article>` | 独立して成立する記事 |
| `<aside>` | 補足・サイドバー |
| `<footer>` | ページや区画の末尾 |

### フォーム

```html
<form action="submit.php" method="post">
  <label for="name">名前</label>
  <input type="text" id="name" name="name" required>

  <label for="email">メール</label>
  <input type="email" id="email" name="email">

  <label for="msg">本文</label>
  <textarea id="msg" name="msg" rows="5"></textarea>

  <select name="category">
    <option value="1">選択肢1</option>
  </select>

  <input type="radio" name="gender" value="m" id="m">
  <input type="checkbox" name="agree" value="1" id="agree">

  <button type="submit">送信</button>
</form>
```

**input の type**: `text` `email` `password` `number` `tel` `url` `date` `file` `hidden` `radio` `checkbox` `submit`

---

## CSS

### セレクタ

```css
p          { }   /* 要素 */
.box       { }   /* クラス */
#header    { }   /* ID */
.a .b      { }   /* 子孫 */
.a > .b    { }   /* 直下の子 */
.a, .b     { }   /* 複数指定 */
a:hover    { }   /* マウスオーバー時 */
li:first-child { }
li:nth-child(2n) { }
input:focus { }
.box::before { content: ""; }
```

### よく使うプロパティ

```css
/* 文字 */
color: #333;
font-size: 16px;
font-weight: bold;
line-height: 1.6;
text-align: center;
text-decoration: none;

/* 背景 */
background-color: #fff;
background-image: url(img.png);
background-size: cover;

/* 余白・サイズ */
margin: 10px 20px;          /* 上下 左右 */
padding: 10px 20px 30px 40px; /* 上 右 下 左 */
width: 100%;
max-width: 1200px;
height: auto;

/* 枠 */
border: 1px solid #ccc;
border-radius: 8px;
box-shadow: 0 2px 8px rgba(0,0,0,.1);

/* 表示 */
display: block | inline | inline-block | flex | grid | none;
opacity: .5;
```

### 必ず入れる初期設定

```css
*, *::before, *::after {
  box-sizing: border-box;
}
body {
  margin: 0;
  font-family: system-ui, -apple-system, "Hiragino Sans", "Noto Sans JP", sans-serif;
  line-height: 1.6;
}
img {
  max-width: 100%;
  height: auto;
  display: block;
}
```

### Flexbox

```css
.parent {
  display: flex;
  flex-direction: row | column;
  justify-content: flex-start | center | space-between | space-around;  /* 主軸 */
  align-items: stretch | center | flex-start | flex-end;                /* 交差軸 */
  flex-wrap: wrap;
  gap: 16px;
}
.child {
  flex: 1;          /* 均等に伸びる */
  flex-shrink: 0;   /* 縮まない */
}
```

**覚え方**：`justify-content` は**並んでいる方向**、`align-items` は**それと直角の方向**。

### Grid

```css
.parent {
  display: grid;
  grid-template-columns: repeat(3, 1fr);   /* 3等分 */
  grid-template-columns: 200px 1fr;        /* サイドバー + 本体 */
  gap: 16px;
}
```

### レスポンシブ

```css
/* スマホ優先で書き、大きい画面を上書きしていく */
.container { width: 100%; }

@media (min-width: 768px) {
  .container { max-width: 720px; }
}
@media (min-width: 1024px) {
  .container { max-width: 960px; }
}
```

### 中央寄せ早見表

| やりたいこと | 書き方 |
| --- | --- |
| ブロック要素を横中央 | `margin: 0 auto;` + `width` 指定 |
| テキストを横中央 | `text-align: center;` |
| 縦横中央（Flex） | 親に `display:flex; justify-content:center; align-items:center;` |
| 縦横中央（Grid） | 親に `display:grid; place-items:center;` |

---

## JavaScript

### 変数・型

```javascript
let x = 1;          // 再代入できる
const y = 2;        // 再代入できない（基本はこちら）

typeof x            // "number"

// テンプレートリテラル
const name = "太郎";
console.log(`こんにちは、${name}さん`);
```

### 条件分岐

```javascript
if (a === b) {
} else if (a > b) {
} else {
}

const result = (a > b) ? "大" : "小";   // 三項演算子

switch (val) {
  case 1: break;
  default: break;
}
```

> ⚠️ 比較は必ず `===`（型も比較）を使う。`==` は型を勝手に変換するため事故のもと。

### 繰り返し

```javascript
for (let i = 0; i < 5; i++) { }

for (const item of array) { }        // 配列
for (const key in object) { }        // オブジェクト

while (条件) { }

array.forEach((item, index) => { });
```

### 配列

```javascript
const arr = [1, 2, 3];

arr.length
arr.push(4);           // 末尾に追加
arr.pop();             // 末尾を削除
arr.shift();           // 先頭を削除
arr.unshift(0);        // 先頭に追加
arr.includes(2);       // 含むか
arr.indexOf(2);        // 位置
arr.join("-");         // 文字列化
arr.slice(1, 3);       // 切り出し（元を変えない）
arr.splice(1, 1);      // 削除（元を変える）

// よく使う3つ
arr.map(n => n * 2);              // 各要素を変換 → 新しい配列
arr.filter(n => n > 1);           // 条件に合うものだけ → 新しい配列
arr.reduce((sum, n) => sum + n, 0); // 1つの値にまとめる

arr.find(n => n > 1);             // 最初に見つかった要素
arr.sort((a, b) => a - b);        // 昇順
```

### オブジェクト

```javascript
const user = { name: "太郎", age: 20 };

user.name
user["name"]
user.email = "a@b.com";   // 追加
delete user.age;

Object.keys(user);        // キーの配列
Object.values(user);      // 値の配列

// 分割代入
const { name, age } = user;
```

### 関数

```javascript
function add(a, b) { return a + b; }

const add = (a, b) => a + b;         // アロー関数（1行なら return 省略可）
const add = (a, b) => { return a + b; };

const greet = (name = "ゲスト") => `こんにちは${name}`;   // デフォルト引数
```

### DOM操作

```javascript
// 取得
document.querySelector('.box');       // 最初の1つ
document.querySelectorAll('.box');    // 全部（NodeList）
document.getElementById('id');

// 変更
el.textContent = "text";     // テキスト（安全）
el.innerHTML = "<b>a</b>";   // HTML（XSSに注意）
el.value                     // input の値
el.classList.add('active');
el.classList.remove('active');
el.classList.toggle('active');
el.style.color = 'red';
el.setAttribute('src', 'a.png');

// 作成・追加・削除
const p = document.createElement('p');
p.textContent = "new";
parent.appendChild(p);
el.remove();
```

### イベント

```javascript
btn.addEventListener('click', (e) => {
  e.preventDefault();     // デフォルト動作を止める
  console.log(e.target);  // イベントが起きた要素
});
```

**主なイベント**: `click` `submit` `input` `change` `keydown` `mouseover` `load` `DOMContentLoaded`

### 非同期・fetch

```javascript
async function getData() {
  try {
    const res = await fetch('https://api.example.com/data');
    if (!res.ok) throw new Error(`HTTPエラー: ${res.status}`);
    const data = await res.json();
    console.log(data);
  } catch (err) {
    console.error(err);
  }
}
```

### localStorage

```javascript
localStorage.setItem('key', JSON.stringify(obj));
const obj = JSON.parse(localStorage.getItem('key'));
localStorage.removeItem('key');
localStorage.clear();
```

---

## PHP

### 基本

```php
<?php
// 末尾の ?> は書かない（余計な出力を防ぐため）

$name = "太郎";
$age = 20;
const TAX = 1.1;

echo "こんにちは、{$name}さん";     // ダブルクォートは変数展開される
echo 'こんにちは、$name さん';       // シングルクォートは展開されない
echo "合計: " . $price . "円";      // 連結は .
```

### 条件分岐・繰り返し

```php
if ($a === $b) {
} elseif ($a > $b) {
} else {
}

$r = ($a > $b) ? "大" : "小";
$name = $_POST['name'] ?? '';    // null合体演算子

for ($i = 0; $i < 5; $i++) { }
foreach ($array as $value) { }
foreach ($array as $key => $value) { }
while ($条件) { }
```

### 配列

```php
$arr = [1, 2, 3];
$assoc = ['name' => '太郎', 'age' => 20];

count($arr);
$arr[] = 4;                 // 追加
array_push($arr, 5);
in_array(2, $arr);          // 含むか
array_keys($assoc);
array_values($assoc);
implode(",", $arr);         // 配列 → 文字列
explode(",", $str);         // 文字列 → 配列
array_map(fn($n) => $n * 2, $arr);
array_filter($arr, fn($n) => $n > 1);
sort($arr);
```

### よく使う関数

```php
strlen($s);            // バイト数
mb_strlen($s);         // 文字数（日本語はこちら）
mb_substr($s, 0, 5);   // 切り出し
str_replace("a", "b", $s);
trim($s);              // 前後の空白除去
strtolower($s);
number_format(1000);   // "1,000"
date('Y-m-d H:i:s');
htmlspecialchars($s, ENT_QUOTES, 'UTF-8');   // XSS対策（必須）
```

### HTMLとの混在

```php
<?php foreach ($items as $item): ?>
  <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
<?php endforeach; ?>
```

> `<?=` は `<?php echo` の省略形。

### フォーム受け取り

```php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    $errors = [];
    if ($name === '') {
        $errors[] = '名前を入力してください';
    }
    if (mb_strlen($name) > 50) {
        $errors[] = '名前は50文字以内で入力してください';
    }
}
```

### セッション

```php
<?php
session_start();              // 必ず出力より前に

$_SESSION['user_id'] = 1;
$id = $_SESSION['user_id'] ?? null;

unset($_SESSION['user_id']);
session_destroy();
```

### PDO（データベース接続）

```php
<?php
$dsn = 'mysql:host=localhost;dbname=testdb;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// 取得
$stmt = $pdo->prepare('SELECT * FROM users WHERE age > ?');
$stmt->execute([20]);
$users = $stmt->fetchAll();

// 追加（名前付きプレースホルダ）
$stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
$stmt->execute(['name' => $name, 'email' => $email]);
$id = $pdo->lastInsertId();
```

> ⚠️ **SQL文に変数を直接埋め込まない。** 必ずプレースホルダを使う（SQLインジェクション対策）。

---

## SQL

```sql
-- 取得
SELECT * FROM users;
SELECT name, email FROM users WHERE age >= 20;
SELECT * FROM users WHERE name LIKE '%田%';
SELECT * FROM users WHERE age BETWEEN 20 AND 30;
SELECT * FROM users WHERE id IN (1, 2, 3);
SELECT * FROM users WHERE deleted_at IS NULL;
SELECT * FROM users ORDER BY created_at DESC LIMIT 10 OFFSET 20;

-- 集計
SELECT COUNT(*) FROM users;
SELECT category, COUNT(*) FROM items GROUP BY category HAVING COUNT(*) > 5;
SELECT AVG(price), MAX(price), MIN(price), SUM(price) FROM items;

-- 結合
SELECT u.name, p.title
FROM users u
INNER JOIN posts p ON u.id = p.user_id;      -- 両方にあるものだけ

SELECT u.name, p.title
FROM users u
LEFT JOIN posts p ON u.id = p.user_id;       -- 左は全部残す

-- 変更
INSERT INTO users (name, email) VALUES ('太郎', 'a@b.com');
UPDATE users SET name = '次郎' WHERE id = 1;
DELETE FROM users WHERE id = 1;

-- テーブル作成
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(50) NOT NULL,
  email      VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> ⚠️ `UPDATE` と `DELETE` で **`WHERE` を書き忘れると全件が対象**になります。
> 実行前に、同じ `WHERE` で `SELECT` して確認する癖をつけてください。

---

## Laravel

### Artisan コマンド

```bash
php artisan serve                      # 開発サーバー起動
php artisan make:model Post -mcr       # モデル+マイグレーション+コントローラ
php artisan make:controller PostController --resource
php artisan make:migration create_posts_table
php artisan make:request StorePostRequest
php artisan migrate                    # マイグレーション実行
php artisan migrate:fresh --seed       # 全削除して作り直し + シード
php artisan route:list                 # ルート一覧
php artisan tinker                     # 対話コンソール
php artisan config:clear               # 設定キャッシュ削除
php artisan storage:link               # publicへのシンボリックリンク
```

### ルーティング

```php
// routes/web.php
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::post('/posts', [PostController::class, 'store']);
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::resource('posts', PostController::class);
Route::middleware('auth')->group(function () {
    Route::get('/mypage', [UserController::class, 'mypage']);
});
```

### コントローラ

```php
public function index()
{
    $posts = Post::latest()->paginate(10);
    return view('posts.index', compact('posts'));
}

public function store(StorePostRequest $request)
{
    Post::create($request->validated());
    return redirect()->route('posts.index')->with('success', '登録しました');
}
```

### Eloquent

```php
Post::all();
Post::find(1);
Post::findOrFail(1);
Post::where('status', 'published')->get();
Post::where('title', 'like', "%{$keyword}%")->orderBy('created_at', 'desc')->paginate(10);
Post::with('user')->get();        // N+1問題の回避

Post::create(['title' => $title]);
$post->update(['title' => $title]);
$post->delete();

// リレーション
public function user()  { return $this->belongsTo(User::class); }
public function posts() { return $this->hasMany(Post::class); }
public function tags()  { return $this->belongsToMany(Tag::class); }
```

### Blade

```blade
{{ $post->title }}              {{-- エスケープあり（基本こちら） --}}
{!! $post->body !!}             {{-- エスケープなし（危険） --}}

@if ($a) ... @elseif ($b) ... @else ... @endif
@foreach ($posts as $post) ... @endforeach
@forelse ($posts as $post) ... @empty 投稿がありません @endforelse

@extends('layouts.app')
@section('content') ... @endsection
@include('partials.header')

@csrf
@method('PUT')
@auth ... @endauth
@guest ... @endguest

{{ route('posts.show', $post->id) }}
{{ asset('css/style.css') }}
{{ old('title') }}
@error('title') <p>{{ $message }}</p> @enderror
```

### バリデーション

```php
$request->validate([
    'title' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'age'   => 'nullable|integer|min:0|max:120',
    'image' => 'nullable|image|mimes:jpeg,png|max:2048',
]);
```

---

## Git

```bash
git init
git clone <URL>

git status
git add .
git add ファイル名
git commit -m "メッセージ"

git push -u origin main
git pull origin main

git branch                     # 一覧
git switch -c feature/xxx      # 作成して移動
git switch main                # 移動
git merge feature/xxx

git log --oneline
git diff
git restore ファイル名          # 変更を取り消す
git reset --soft HEAD^         # 直前のコミットを取り消す（変更は残る）
```

### コミットメッセージの型

```
feat: ログイン機能を追加
fix: 検索結果が0件のときのエラーを修正
docs: READMEに環境構築手順を追加
style: インデントを修正
refactor: 商品一覧の取得処理を整理
test: 投稿機能のテストを追加
chore: 不要なファイルを削除
```

---

## 公式ドキュメント

| 技術 | URL |
| --- | --- |
| HTML / CSS / JS | https://developer.mozilla.org/ja/ |
| PHP | https://www.php.net/manual/ja/ |
| Laravel | https://laravel.com/docs |
| MySQL | https://dev.mysql.com/doc/refman/8.0/ja/ |
| Git | https://git-scm.com/book/ja/v2 |
