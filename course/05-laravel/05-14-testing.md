# 5-14 テストを書く（PHPUnit入門）

> 🎯 **このレッスンのゴール**
> - 「動作確認を手作業でやる」から「テストコードで自動確認する」へ進める
> - 機能テストで、URLを叩いてレスポンス・DB・認可を検証できる
> - 第0部から続けてきた「AIの出力を検証する」姿勢を、コードで仕組み化する

所要 180分 / 難度 🔴

---

## 📖 テストは「AIの出力を検証する」の最終形

この教材はずっと「AIが書いたコードを鵜呑みにせず、自分で確かめよう」と言ってきました。
その「確かめる」を、**毎回コードで自動実行できる**ようにするのがテストです。

- 手作業：毎回ブラウザで登録して、投稿して、削除して…（面倒で、抜ける）
- テスト：`php artisan test` 一発で、全部の確認を数秒で

しかも、**AIに機能を追加させた後**にテストを走らせれば、
「前は動いていた所を壊していないか」を自動で見張れます。これがAI時代のテストの価値です。

> 🧠 **テスト＝壊れていないことの証明**。
> あなたが書いても、AIが書いても、「テストが通る」という同じ土俵で品質を確認できます。

---

## 📖 2種類のテスト

Laravel のテストは、ざっくり2種類です。

| 種類 | 何を試す | 例 |
| --- | --- | --- |
| **Unit（単体）** | 小さな部品（1つのメソッド等） | `trim_ja()` が全角空白を正しく除去するか |
| **Feature（機能）** | URLを叩いた一連の動き | 「未ログインで投稿→ログイン画面に飛ぶ」 |

初学者はまず **Feature テスト**から始めるのがおすすめです。
「ユーザーがやること」をそのまま書けて、効果を実感しやすいからです。

---

## ✍️ 手を動かす① ─ 最初のテストを走らせる

Laravel には最初からテストが入っています。まず走らせてみましょう。

```bash
php artisan test
```

```
   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

  Tests:  1 passed
```

`✓` が並べばOK。この「緑」を増やしていくのがテストの仕事です。

---

## ✍️ 手を動かす② ─ テスト用DBの準備

テストで本番データを汚さないよう、**テスト専用のDB**を使います。
また、テストごとにDBをまっさらにする `RefreshDatabase` を使います。

```php
// tests/Feature/PostTest.php
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostTest extends TestCase
{
    use RefreshDatabase;   // 各テストの前にDBをリセット（5-6の migrate:fresh 相当）
    // ...
}
```

> 💡 手軽に始めるなら `phpunit.xml` でテスト用DBを SQLite の `:memory:` にすると速いです
> （第4部で使ったSQLiteがここでも活躍）。設定は環境に合わせて。

---

## ✍️ 手を動かす③ ─ 一覧ページのテスト

```php
public function test_一覧ページが表示できる(): void
{
    // 準備：投稿を1件作る（ファクトリ）
    $post = Post::factory()->create(['title' => 'テスト投稿']);

    // 実行：GET /posts
    $response = $this->get('/posts');

    // 検証
    $response->assertStatus(200);          // 200が返る
    $response->assertSee('テスト投稿');     // 画面にタイトルが出ている
}
```

- `$this->get('/posts')` … 実際にURLを叩く（第3部で curl でやった検証の自動版）
- `assertStatus(200)` … ステータスコードの確認
- `assertSee('...')` … レスポンスHTMLに文字列が含まれるか

> 💡 `Post::factory()` は「ダミーの投稿」を量産する仕組み（5-7で予告したファクトリ）。
> `php artisan make:factory PostFactory` で作り、列の初期値を定義しておきます。

---

## ✍️ 手を動かす④ ─ 「未ログインは弾かれる」テスト（5-10の検証）

5-10で「未ログインだと投稿作成に入れない」を実装しました。それをテストにします。

```php
public function test_未ログインだと投稿作成はログイン画面に飛ぶ(): void
{
    $response = $this->get('/posts/create');

    $response->assertRedirect('/login');   // /login にリダイレクトされる
}

public function test_ログイン済みなら投稿作成ページが開ける(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/posts/create');
    //                 ↑ このユーザーとしてログインした状態で叩く

    $response->assertStatus(200);
}
```

第3部・5-10で「手でログアウトして確認した」ことが、**コードで再現・自動化**できました。

---

## ✍️ 手を動かす⑤ ─ 投稿の保存とDB検証

```php
public function test_ログインユーザーは投稿できる(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/posts', [
        'title' => '新しい投稿',
        'body'  => '本文です',
    ]);

    $response->assertRedirect('/posts');           // 一覧にリダイレクト
    $this->assertDatabaseHas('posts', [            // DBに実際に入ったか
        'title'   => '新しい投稿',
        'user_id' => $user->id,                    // 投稿者が自分になっているか
    ]);
}
```

`assertDatabaseHas('posts', [...])` は「その条件の行がテーブルに存在するか」を確認します。
「なりすましされず、自分のuser_idで保存された」ことまで検証できる点に注目（5-10の学び）。

---

## ✍️ 手を動かす⑥ ─ 認可のテスト（5-11の検証）

「他人の投稿は消せない」を、テストで保証します。第3部で curl でやった検証の自動版です。

```php
public function test_他人の投稿は削除できない(): void
{
    $owner  = User::factory()->create();
    $other  = User::factory()->create();
    $post   = Post::factory()->for($owner)->create();   // owner の投稿

    // other としてログインして、owner の投稿を削除しようとする
    $response = $this->actingAs($other)->delete("/posts/{$post->id}");

    $response->assertStatus(403);                        // 403で拒否
    $this->assertDatabaseHas('posts', ['id' => $post->id]); // まだ消えていない
}

public function test_自分の投稿は削除できる(): void
{
    $owner = User::factory()->create();
    $post  = Post::factory()->for($owner)->create();

    $response = $this->actingAs($owner)->delete("/posts/{$post->id}");

    $response->assertRedirect('/posts');
    $this->assertDatabaseMissing('posts', ['id' => $post->id]); // 消えている
}
```

第3部で手作業でやった「cross-session delete → 403 / owner delete → success」が、
**そっくりそのままテストコード**になりました。これが自動化の威力です。

---

## ✍️ 手を動かす⑦ ─ 単体テスト（マルチバイトの罠を思い出す）

第3部で痛い目を見た「全角空白のトリミング」も、テストで守れます。

```php
// tests/Unit/TrimJaTest.php
public function test_全角スペースも前後から除去できる(): void
{
    // 全角スペース＋ラテ＋全角スペース
    $result = trim_ja('　ラテを飲みたい　');

    $this->assertSame('ラテを飲みたい', $result);   // 壊れず正しく除去
}
```

もし将来、誰か（AIを含む）が `trim_ja` を「素の `trim()`」に書き換えたら、
このテストが**赤く落ちて**教えてくれます。第3部で発見したあのバグの再発を、テストが防ぎます。

> 🧠 **一度踏んだバグは、テストにして二度と踏まない**。これがプロの習慣です。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| テストで本番データが消えた | 本番DBを見ていた | テスト用DB／`RefreshDatabase` を使う |
| 毎回結果が変わる | 前のテストのデータが残っている | `RefreshDatabase` でリセット |
| `factory()` が無いと言われる | ファクトリ未作成 | `make:factory`／モデルに `HasFactory` |
| `assertSee` が通らない | エスケープで実体参照になっている | `assertSee('...', false)` など確認 |
| ログイン前提のテストが403/302 | `actingAs()` を忘れている | `->actingAs($user)` を付ける |

---

## 🤖 AIに聞いてみよう

テストは、AIに「書かせる」のも「検算に使う」のも両方できます。

### 使えるプロンプト例

```
Laravel の Feature テストを書いてください。
- 未ログインで /posts/create にアクセスすると /login にリダイレクトされる
- ログインユーザーは投稿でき、posts テーブルに user_id 付きで保存される
- 他人の投稿を削除しようとすると 403 で、DBから消えない
RefreshDatabase とファクトリを使ってください。
```

### 🔍 AIの答えを疑うチェックリスト

- [ ] `RefreshDatabase` を使い、**本番DBを汚さない**設計になっているか
- [ ] 認可テストで「消せてはいけないケース」を**ちゃんと落として（403）**いるか
- [ ] `assertDatabaseHas` / `assertDatabaseMissing` で**DBの状態**まで見ているか（画面だけで満足していないか）
- [ ] `actingAs()` の付け忘れで、意図せず未ログイン扱いになっていないか
- [ ] 「成功パターン」だけでなく「失敗すべきパターン」も書いているか

> 🧠 **テストは「通ればいい」ものではない**。
> 「本来落ちるべきケースがちゃんと落ちるか」まで書けて、初めて機能を守れます。
> AIは成功パターンを書きがちなので、失敗パターンはあなたが足しましょう。

---

## 🔧 やってみよう（演習）

1. `php artisan test` で最初のテストが通ることを確認
2. `PostFactory` / `UserFactory` を用意（`make:factory`）
3. 一覧ページの表示テストを書く（200 と `assertSee`）
4. 「未ログイン→/login」「ログイン→200」の2本を書く
5. 「自分の投稿は削除できる／他人のは403」の2本を書く（5-11の検証）
6. `trim_ja` の全角空白テスト（Unit）を書く
7. わざとコントローラの `authorize()` を消して、5 のテストが**赤くなる**ことを確認 → 戻す

> ✅ 手順7が体験のキモ。「テストが壊れを検知してくれる」を自分の目で見ると、
> テストを書く意味が腹落ちします。

---

## ✅ 章末チェック

1. Feature テストと Unit テストの違いは？ 初学者はどちらから始めるとよいか。
2. `RefreshDatabase` は何のためにあるか。無いと何が起きるか。
3. 第3部で手作業でやった「他人の投稿は消せない」検証を、どのアサーションで自動化できるか。
4. 「テストが通る」だけでは不十分な理由を、認可テストを例に説明せよ。

> これで実装の技術はひと通り完成です。次回は総合演習の前に、
> **AIにLaravelアプリの設計を相談する**やり方を、これまでの検証姿勢とともに整理します。

---

[⬅️ 5-13 ページネーション・検索](05-13-pagination-search.md) ｜ [➡️ 5-15 🤖 AIにLaravelの設計を相談する](05-15-ai-with-laravel.md)
