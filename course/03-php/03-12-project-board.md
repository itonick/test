# 3-12 【総合演習】掲示板を作る

> 🎯 **このレッスンのゴール**
> - 第3部で学んだすべてを1つのアプリにまとめる
> - **他人と共有できるWebアプリを完成させる**
> - セキュリティを自分でテストできるようになる

所要 300分（5日に分割推奨） / 難度 🔴
完成コード: [`code/03-12/`](../code/03-12/) ← **動作検証済み**

---

## 📸 完成イメージ

```
┌────────────────────────────────────────────┐
│  ひとこと掲示板              3件の投稿      │
├────────────────────────────────────────────┤
│  ┌──────────────────────────────────────┐  │
│  │ 投稿する                              │  │
│  │ お名前（省略可）                       │  │
│  │ [________________]                    │  │
│  │ 本文 [必須]                            │  │
│  │ [                              ]      │  │
│  │ [                              ]      │  │
│  │            [ 投稿する ]                │  │
│  └──────────────────────────────────────┘  │
│                                            │
│  [投稿を検索          ] [検索]             │
│                                            │
│  ┌──────────────────────────────────────┐  │
│  │ 山田太郎                      3分前   │  │
│  │ はじめまして。                        │  │
│  │ よろしくお願いします。          [削除] │  │
│  └──────────────────────────────────────┘  │
│  ┌──────────────────────────────────────┐  │
│  │ 名無しさん                    1時間前 │  │
│  │ テスト投稿です                        │  │
│  └──────────────────────────────────────┘  │
│                                            │
│         前へ  1  [2]  3  次へ              │
└────────────────────────────────────────────┘
```

**実装する機能**

| 機能 | 使う知識 |
| --- | --- |
| 投稿の一覧表示 | テンプレート（3-5） |
| 投稿の追加 | POST + PRG（3-6） |
| 入力検証 | バリデーション（3-7） |
| XSS対策 | エスケープ（3-7） |
| CSRF対策 | トークン（3-7） |
| **自分の投稿だけ削除** | セッション + 認可（3-8, 3-11） |
| キーワード検索 | GET（3-6） |
| ページ送り | 配列操作（3-3） |
| データの永続化 | ファイル操作 + 排他制御（3-9） |
| クラス設計 | OOP（3-10） |

---

## 📖 ディレクトリ構成

**セキュリティ上、最も重要な設計判断です。**

```
htdocs/board/
├─ public/                ← ★ ここだけを公開する
│   ├─ index.php
│   └─ css/
│       └─ style.css
├─ app/                   ← 公開しない
│   ├─ Models/
│   │   └─ Post.php
│   └─ Repositories/
│       └─ PostRepository.php
├─ lib/                   ← 公開しない
│   ├─ functions.php
│   ├─ session.php
│   └─ csrf.php
└─ storage/               ← ★ 絶対に公開しない
    └─ posts.json
```

> ⚠️ **`storage/posts.json` を公開ディレクトリに置くと、
> URLで直接アクセスして全データをダウンロードされます。**
>
> 3-11 の演習で見た「欠陥コード」の5番が、まさにこれでした。

### アクセスするURL

```
http://localhost/board/public/index.php
```

> 💡 **本番環境では、`public/` をドキュメントルートに設定します。**
> そうすると `http://example.com/` でアクセスでき、他のディレクトリは外部から見えません。
> Laravel（第5部）も、まったく同じ構成です。

### ローカルで `public/` だけを公開する方法

XAMPP を使わず、PHP の組み込みサーバーで試すこともできます。

```bash
cd htdocs/board
php -S localhost:8000 -t public
```

`http://localhost:8000/` でアクセスできます。**`public/` の外は配信されません。**

---

## 📖 進め方 ─ 5日に分割する

| Day | やること | 時間 |
| --- | --- | --- |
| **Day 1** | ディレクトリ構成・共通ライブラリ・一覧表示 | 60分 |
| **Day 2** | 投稿機能（検証 + CSRF + PRG） | 60分 |
| **Day 3** | 削除機能（**認可**） | 60分 |
| **Day 4** | 検索・ページ送り | 60分 |
| **Day 5** | セキュリティテスト・仕上げ | 60分 |

---

## ✍️ Day 1 ─ 土台を作る

### 共通ライブラリ

以下の3ファイルは、3-4〜3-8 で作ったものをまとめたものです（📋 コピペ可）。

- [`lib/functions.php`](../code/03-12/lib/functions.php) — `e()` `trim_ja()` `time_ago()` `redirect()` など
- [`lib/session.php`](../code/03-12/lib/session.php) — `start_secure_session()` とフラッシュメッセージ
- [`lib/csrf.php`](../code/03-12/lib/csrf.php) — CSRFトークン

> 🆘 **ここで詰まったら**（真っ白／`require` でエラー／変更が反映されない）
> - **チェック順**：① プロジェクトを **`htdocs` の中**に置き、`http://localhost/...` で開いているか（Live Serverでは動きません）② `require` のパスは `require __DIR__ . '/lib/functions.php';` のように **`__DIR__` 起点**にしているか（相対パスは実行場所でズレます）③ 真っ白なら、まず 3-1 で設定した**エラー表示ON**の状態で開き、画面のエラー文を読む
> - **詰まったら、まず該当機能のレッスンに戻る**：投稿は 3-6、XSS/CSRF は 3-7、ログインは 3-8。各レッスンの「🆘」を見直すのが近道です
> - **直らなければ、AIにこう聞く**（エラー全文とファイル構成、該当コードを貼る）：
>   「PHPの掲示板を作っています。○○というエラーが出ます。原因の候補を、確認手順つきで教えてください」

### `Post` クラス

[`app/Models/Post.php`](../code/03-12/app/Models/Post.php)

**設計のポイント**

```php
private function __construct(
    public readonly string  $id,
    public readonly string  $name,
    public readonly string  $body,
    public readonly int     $createdAt,
    public readonly ?string $editToken,
) {}
```

| 実装 | 理由 |
| --- | --- |
| `private __construct` | **必ず `create()` か `fromArray()` を通る** |
| `readonly` | 作成後に不正な状態へ変化できない |
| `create()` で検証 | **検証を通らないインスタンスが存在しない** |
| `editToken` | ログイン機能がないので、投稿時のトークンで所有者を判定する |

> 💡 **`editToken` の考え方**
> 掲示板にログイン機能はありませんが、「自分の投稿だけ削除できる」を実現したい。
> そこで、**投稿時にランダムなトークンを発行し、セッションに保存**します。
> 削除時に、そのトークンを持っているかで判定します。
>
> **他人はトークンを知らないので、IDを書き換えても削除できません。**

### `PostRepository` クラス

[`app/Repositories/PostRepository.php`](../code/03-12/app/Repositories/PostRepository.php)

**排他制御の実装に注目してください。**

```php
private function withLock(callable $mutator): void
{
    $handle = fopen($this->filePath, "c+");
    try {
        flock($handle, LOCK_EX);          // ← ロックを取る

        $rows = /* 読み込み */;
        $rows = $mutator($rows);          // ← 変更
        /* 書き込み */

    } finally {
        flock($handle, LOCK_UN);          // ← 必ず解放
        fclose($handle);
    }
}
```

> ⚠️ **「読み込み → 変更 → 書き込み」を、ロックしたまま行う**必要があります。
>
> ```
> ❌ file_get_contents() → 変更 → file_put_contents(LOCK_EX)
>    ↑ 読み込みと書き込みの「間」に、他のリクエストが書き込むと消える
>
> ✅ fopen → flock(LOCK_EX) → 読む → 変更 → 書く → flock(LOCK_UN)
> ```
>
> **これが競合状態（レースコンディション）の対策です。**
> `file_put_contents` に `LOCK_EX` を付けるだけでは不十分です。

### 一覧表示だけ作る

`public/index.php` の、**GET部分と表示部分だけ**を先に作ってください。

```php
<?php
declare(strict_types=1);

require_once __DIR__ . "/../lib/session.php";
start_secure_session();
require_once __DIR__ . "/../lib/functions.php";
require_once __DIR__ . "/../lib/csrf.php";
require_once __DIR__ . "/../app/Models/Post.php";
require_once __DIR__ . "/../app/Repositories/PostRepository.php";

use App\Models\Post;
use App\Repositories\PostRepository;

send_security_headers();

$repository = new PostRepository(__DIR__ . "/../storage/posts.json");
$posts = $repository->all();
?>
<!-- 表示部分 -->
```

**Day 1 のチェック**
- [ ] ディレクトリ構成ができた
- [ ] 空の状態で「まだ投稿がありません」が表示される
- [ ] `storage/posts.json` に手でデータを書くと、一覧に表示される
- [ ] `public/` の外のファイルに、URLで直接アクセスできないことを確認

---

## ✍️ Day 2 ─ 投稿機能

### POST処理を追加する

```php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_post_with_csrf();          // ← CSRF検証（失敗なら419で終了）

    $action = $_POST["action"] ?? "";

    if ($action === "create") {
        try {
            $post = Post::create($_POST["name"] ?? "", $_POST["body"] ?? "");
            $repository->save($post);

            // 自分の投稿として記録（削除に使う）
            $_SESSION["my_posts"][$post->id] = $post->editToken;

            set_flash("success", "投稿しました。");

        } catch (InvalidArgumentException $e) {
            // 検証エラー：入力値を保持して戻す
            set_errors(["body" => $e->getMessage()]);
            set_old(["name" => $_POST["name"] ?? "", "body" => $_POST["body"] ?? ""]);

        } catch (Throwable $e) {
            // 想定外のエラー：詳細はログに、画面には一般的なメッセージ
            error_log("投稿の保存に失敗: " . $e->getMessage());
            set_flash("error", "投稿の保存に失敗しました。時間をおいてお試しください。");
        }

        redirect("index.php");         // ← PRGパターン
    }
}
```

### 例外の使い分けに注目

| 例外 | 意味 | ユーザーへの表示 |
| --- | --- | --- |
| `InvalidArgumentException` | 入力が不正（想定内） | **具体的なメッセージ**（「本文を入力してください」） |
| `Throwable`（その他） | 想定外の障害 | **一般的なメッセージ** + 詳細はログへ |

> ⚠️ **想定外のエラーの詳細を画面に出してはいけません。**
> ファイルパスや内部構造が漏れ、攻撃の手がかりになります。
> **ログには詳細を、画面には一般的なメッセージを。**

**Day 2 のチェック**
- [ ] 投稿できる
- [ ] 本文が空だとエラーになり、**入力値が保持される**
- [ ] 1000文字を超えるとエラーになる
- [ ] 名前を空にすると「名無しさん」になる
- [ ] **リロードしても二重投稿されない**（PRG）
- [ ] `<script>alert(1)</script>` を投稿しても、文字として表示される

---

## ✍️ Day 3 ─ 削除機能（最重要）

**ここが、このレッスンで最も重要な部分です。**

```php
if ($action === "delete") {
    $id = (string) ($_POST["id"] ?? "");
    $post = $repository->find($id);

    // ① 存在確認
    if ($post === null) {
        set_flash("error", "投稿が見つかりませんでした。");
        redirect("index.php");
    }

    // ② ★ 認可チェック
    $my_token = $_SESSION["my_posts"][$id] ?? null;

    if (!$post->canDeleteWith($my_token)) {
        http_response_code(403);
        set_flash("error", "この投稿を削除する権限がありません。");
        redirect("index.php");
    }

    // ③ 削除
    $repository->delete($id);
    unset($_SESSION["my_posts"][$id]);
    set_flash("success", "投稿を削除しました。");
    redirect("index.php");
}
```

### 認可チェックの3要素

| # | 確認すること | これがないと |
| --- | --- | --- |
| ① | そのデータが存在するか | 存在しないIDでエラーになる |
| ② | **操作する権限があるか** | **他人のデータを操作される（IDOR）** |
| ③ | 操作が業務的に妥当か | （例：締切を過ぎた投稿は削除不可、など） |

> ⚠️ **②を書き忘れるのが、実際のWebサービスで最も多い脆弱性の1つです。**
> 3-11 で「AIが見落とす」と書いた、まさにその箇所です。

### 削除ボタンは POST + フォーム

```php
<?php if (isset($my_posts[$post->id])): ?>
  <form method="post" class="delete-form"
        onsubmit="return confirm('この投稿を削除します。よろしいですか？');">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="<?= e($post->id) ?>">
    <button type="submit" class="link-btn danger">削除</button>
  </form>
<?php endif; ?>
```

> ⚠️ **`<a href="?delete=1">` にしてはいけません。**
> `<img src="...?delete=1">` を貼られるだけで、投稿が消されます。

> 💡 **`isset($my_posts[$post->id])` で、自分の投稿だけにボタンを表示**しています。
> ただし、**これは「見た目」の制御であって、セキュリティではありません。**
> ボタンを表示していなくても、直接POSTすれば実行できます。
> **サーバー側の②のチェックが本命**です。

**Day 3 のチェック**
- [ ] 自分の投稿に削除ボタンが出る
- [ ] 他人の投稿（別ブラウザで投稿したもの）にはボタンが出ない
- [ ] **削除できる**
- [ ] **別のブラウザ（シークレットウィンドウ）から、IDを指定して削除を試みる → 拒否される**

---

## ✍️ Day 4 ─ 検索とページ送り

### 検索（GET）

```php
$keyword = trim_ja($_GET["q"] ?? "");

if ($keyword !== "") {
    $found = $repository->search($keyword);
    // ...
}
```

`PostRepository::search()` は `mb_stripos()` で部分一致検索します（日本語対応・大文字小文字を区別しない）。

### ページ送り

```php
$page = max(1, (int) ($_GET["page"] ?? 1));
$result = $repository->paginate($page, PER_PAGE);
```

**リンクの組み立てには `http_build_query()` を使います。**

```php
$query = static function (int $p) use ($keyword): string {
    $params = ["page" => $p];
    if ($keyword !== "") {
        $params["q"] = $keyword;
    }
    return "index.php?" . http_build_query($params);
};
```

> 💡 **`http_build_query()` は、値を自動でURLエンコードします。**
> 手で `"?page={$p}&q={$keyword}"` と繋ぐと、日本語や `&` が含まれるときに壊れます。

**Day 4 のチェック**
- [ ] キーワードで検索できる（日本語も）
- [ ] 検索後もキーワードが入力欄に残る
- [ ] 11件以上投稿すると、2ページ目が出る
- [ ] **検索したままページ送りしても、検索条件が維持される**
- [ ] URLをコピーして別のタブで開くと、同じ結果になる
- [ ] `?page=999` を指定しても壊れない

---

## ✍️ Day 5 ─ セキュリティテスト

**ここまでで動くようになりました。次は「壊せないか」を確認します。**

### 自動テスト（curl）

完成コードは、以下のテストで**動作を検証済み**です。同じテストを自分でも実行してください。

```bash
# サーバーを起動（プロジェクトのルートで）
php -S 127.0.0.1:8000 -t public
```

別のターミナルで：

```bash
# ① CSRFトークンなしでPOST → 419 が返るはず
curl -s -o /dev/null -w "status=%{http_code}\n" \
  -X POST -d "action=create&body=test" http://127.0.0.1:8000/index.php

# ② 正常な投稿（トークンを取得してから）
TOKEN=$(curl -s -c /tmp/c.txt http://127.0.0.1:8000/index.php \
  | grep -o '[a-f0-9]\{64\}' | head -1)

curl -s -o /dev/null -X POST -b /tmp/c.txt -c /tmp/c.txt \
  --data-urlencode "_csrf_token=$TOKEN" \
  --data-urlencode "action=create" \
  --data-urlencode 'name=<script>alert(1)</script>' \
  --data-urlencode 'body=テスト投稿' \
  http://127.0.0.1:8000/index.php

# ③ 出力がエスケープされているか確認
curl -s -b /tmp/c.txt http://127.0.0.1:8000/index.php | grep 'post-name'
#   → &lt;script&gt;alert(1)&lt;/script&gt; となっていれば正しい

# ④ 別セッションから削除を試みる → 拒否されるはず
POST_ID=$(php -r '$d=json_decode(file_get_contents("storage/posts.json"),true); echo $d[0]["id"];')
T2=$(curl -s -c /tmp/c2.txt http://127.0.0.1:8000/index.php | grep -o '[a-f0-9]\{64\}' | head -1)

curl -s -o /dev/null -X POST -b /tmp/c2.txt -c /tmp/c2.txt \
  --data-urlencode "_csrf_token=$T2" -d "action=delete" -d "id=$POST_ID" \
  http://127.0.0.1:8000/index.php

curl -s -b /tmp/c2.txt http://127.0.0.1:8000/index.php | grep '権限がありません'
#   → 出力されれば、認可チェックが機能している
```

> 💡 **「ブラウザからは弾かれるが、直接POSTすると通る」というバグは非常に多いです。**
> curl でのテストは、実務でも必ず行います。

### 手動チェックリスト

**XSS**
- [ ] 名前に `<script>alert(1)</script>` → 文字として表示される
- [ ] 本文に `<img src=x onerror=alert(1)>` → 文字として表示される
- [ ] 検索欄に `"><script>alert(1)</script>` → 属性から抜け出せない

**CSRF**
- [ ] トークンなしでPOST → 419
- [ ] 別のセッションのトークンを使う → 419

**認可（IDOR）**
- [ ] シークレットウィンドウから、他人の投稿IDを指定して削除 → 403
- [ ] `$_SESSION["my_posts"]` を消してから削除を試みる → 403

**情報漏洩**
- [ ] `http://localhost/board/storage/posts.json` にアクセス → **404 になるべき**
      （なる場合は構成が正しい。**見えたら即座に構成を直す**）
- [ ] `http://localhost/board/lib/functions.php` → 404
- [ ] わざとエラーを起こして、ファイルパスが画面に出ないか確認

**入力検証**
- [ ] 空の本文 → エラー
- [ ] 全角スペースだけの本文 → エラー
- [ ] 1001文字の本文 → エラー
- [ ] 名前だけ31文字 → エラー
- [ ] 絵文字を含む投稿 → 正しく表示・保存される

**競合状態**
- [ ] 2つのタブで同時に投稿ボタンを押す → **両方保存される**（片方が消えない）

**その他**
- [ ] リロードで二重投稿されない
- [ ] 320px 幅で崩れない
- [ ] Tab キーだけで全操作できる
- [ ] スクリーンリーダーで、フラッシュメッセージが読み上げられる

---

## 🤖 AIに聞いてみよう

### ① 総合セキュリティレビュー

3-11 の「手を動かす①」のプロンプトを使い、**完成したコードを全ファイル貼って**レビューさせてください。

- [ ] 指摘を優先度順に整理する
- [ ] 🔴 から順に、**自分で**修正する
- [ ] 2周目のレビューを行う
- [ ] AIが**指摘できなかった**項目を記録する

### ② 第4部への橋渡しを聞く

```text
以下は、JSONファイルにデータを保存する掲示板アプリです。

（PostRepository.php を貼る）

これをデータベース（MySQL）に移行することを考えています。

1. このクラスのうち、書き換えが必要な部分はどこですか
2. 呼び出し側（index.php）は変更が必要ですか
3. ファイル保存とデータベースで、根本的に変わる点は何ですか
   （検索、ページ送り、排他制御、トランザクション）
4. いまの設計で「移行しやすくなっている」点、
   逆に「移行の障害になる」点を教えてください

コードは書かないでください。設計の観点で説明してください。
```

> 💡 **この質問への答えが、リポジトリパターンの価値そのものです。**

---

## 🔧 発展課題

### 課題A：機能を足す

- [ ] 返信機能（親投稿へのぶら下がり）
- [ ] 投稿の編集（`editToken` で認可）
- [ ] 画像の添付（3-9 のアップロード処理を使う）
- [ ] 「いいね」ボタン（1人1回、セッションで管理）
- [ ] NGワードフィルタ
- [ ] 連続投稿の制限（60秒以内の再投稿を拒否）

### 課題B：運用を考える

- [ ] 投稿数が1万件になったときの問題を考え、対策を書き出す
      （ヒント：毎回JSON全体を読み込んでいる）
- [ ] `storage/` のバックアップ方法を考える
- [ ] 荒らし対策として何が必要か、リストアップする

> 💡 **「1万件で破綻する」ことに自分で気づけるかが重要です。**
> これが、第4部（データベース）を学ぶ動機になります。

### 課題C：ログイン機能を統合する

3-8 で作ったログイン機能を、掲示板に統合してください。

- [ ] ログインしないと投稿できないようにする
- [ ] 投稿に `user_id` を持たせる
- [ ] 削除の認可を `editToken` から `user_id` の一致に変える
- [ ] マイページで、自分の投稿一覧を表示する

---

## ✅ 第3部 修了チェック

### 知識

- [ ] PHPコードがブラウザに届かない理由を説明できる
- [ ] `"0"` が falsy であることと、その対処を知っている
- [ ] `mb_` 系関数を使うべき場面を言える
- [ ] `declare(strict_types=1)` を書いている
- [ ] すべての出力に `e()` を通している
- [ ] HTML / URL / JS でエスケープ方法が違うことを知っている
- [ ] PRG パターンを実装できる
- [ ] CSRF の仕組みと対策を説明できる
- [ ] `password_hash` / `password_verify` を使える
- [ ] `session_regenerate_id(true)` の必要性を説明できる
- [ ] ファイルアップロードの7つの対策を言える
- [ ] クラスで「不正な状態を作れなくする」設計ができる
- [ ] **認可（IDOR）のチェックを自分で書ける**

### 成果物

- [ ] **掲示板アプリが完成した**
- [ ] curl でのセキュリティテストを通した
- [ ] 手動チェックリストをすべて確認した
- [ ] AIレビューを2周した
- [ ] `storage/` が公開されていないことを確認した

---

## 🎉 第3部 修了

おつかれさまでした。あなたはいま、**サーバーで動くWebアプリを、一人で作れる人**になりました。

第1部・第2部で作ったものとの決定的な違いは、**他の人とデータを共有できる**ことです。
掲示板は、誰が投稿しても、全員が見られます。

ただし、いまの掲示板には限界があります。

| 限界 | 理由 |
| --- | --- |
| **投稿が増えると遅くなる** | 毎回JSON全体を読み込んでいる |
| **検索が遅い** | 全件を順に調べている |
| **複雑な条件で絞り込めない** | 「4月の投稿で、山田さんのもの」を効率よく取れない |
| **同時アクセスに弱い** | ファイルロックで待たせている |
| **データが壊れやすい** | 途中で処理が止まると、JSONが不完全になる |

**これらを解決するのが、データベースです。**

第4部では MySQL を学び、この掲示板をデータベース版に作り替えます。
そして第5部で Laravel を使い、本格的なアプリケーションを作ります。

**次のレッスン → [4-1 データベースが必要な理由](../04-database/04-01-why-database.md)**

---

**前 → [3-11 AIにコードレビューさせる](03-11-ai-review.md)**
