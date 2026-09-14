# 0-5 VS Code を自分の道具にする

> 🎯 **このレッスンのゴール**
> - 日本語化と必須拡張機能の導入を終える
> - **全角スペースを見えるようにする**（エラー原因の最頻出を潰す）
> - 最低限のショートカットを手に入れる

所要 40分 / 難度 🟢

---

## なぜエディタの設定に時間を使うのか

初学者のエラーの多くは、**論理ミスではなくタイプミス**です。

- 閉じ括弧を忘れた
- 全角スペースが混ざった
- タグを閉じ忘れた
- ファイル名を間違えた

**これらはエディタの設定で、書いた瞬間に気づけます。** 30分の投資で、この先何十時間も節約できます。

---

## STEP 1: 日本語化

1. VS Code を起動
2. 左端の四角が4つ並んだアイコン（**拡張機能**）をクリック
   - ショートカット: `Ctrl + Shift + X` / `Cmd + Shift + X`
3. 検索欄に `Japanese Language Pack` と入力
4. 「Japanese Language Pack for Visual Studio Code」の **Install** をクリック
5. 右下に「Restart」ボタンが出るのでクリック

> ⚠️ 再起動しても英語のまま、という場合
> `Ctrl + Shift + P` → `Configure Display Language` → `日本語` を選択 → 再起動

---

## STEP 2: 必須拡張機能を入れる

拡張機能タブで、以下を検索してインストールしてください。

### 全員必須（4つ）

| 拡張機能名 | 何をしてくれるか |
| --- | --- |
| **Japanese Language Pack** | 日本語化（STEP1で導入済み） |
| **Live Server** | HTMLの変更を保存すると、ブラウザが自動でリロードされる |
| **Prettier - Code formatter** | インデントや改行を自動で整える |
| **zenkaku** | **全角スペースをハイライト表示する** |

### 各部に入ったら追加（今は不要）

| 拡張機能名 | いつ使うか |
| --- | --- |
| PHP Intelephense | 第3部（PHP）から |
| Laravel Blade Snippets | 第5部（Laravel）から |
| MySQL (by Weijan Chen) | 第4部（DB）から |
| GitLens | 第6部（Git）から |

---

## STEP 3: 設定を変える（ここが本題）

`Ctrl + Shift + P` （Mac は `Cmd + Shift + P`）でコマンドパレットを開き、

```
settings json
```

と打って、**「基本設定: ユーザー設定を開く (JSON)」** を選択します。

開いたファイルの `{ }` の中に、以下を貼り付けてください（すでに中身がある場合は、末尾にカンマを付けてから追記）。

```json
{
  "editor.renderWhitespace": "all",
  "editor.tabSize": 2,
  "editor.insertSpaces": true,
  "editor.formatOnSave": true,
  "editor.wordWrap": "on",
  "editor.bracketPairColorization.enabled": true,
  "editor.guides.bracketPairs": "active",
  "editor.minimap.enabled": false,
  "files.autoGuessEncoding": true,
  "files.insertFinalNewline": true,
  "files.trimTrailingWhitespace": true,
  "files.eol": "\n",
  "workbench.startupEditor": "none",
  "explorer.confirmDelete": true,
  "emmet.triggerExpansionOnTab": true
}
```

> 🆘 **ここで詰まったら**
> - **症状**：設定を貼ったら、あちこちに赤い波線が出て設定が効かない
> - **まず確認**：① `{ }` は最初と最後に1つずつだけか ② 各行の末尾の `,`（カンマ）が抜けていないか／最後の行の後ろに余計な `,` が付いていないか ③ もともと中身があった場合、二重に `{ }` を書いていないか
> - **直らなければ、AIにこう聞く**（自分の settings.json を丸ごと貼る）：
>   「VS Code の settings.json でエラーが出ています。どこが文法的におかしいか、初心者にもわかるように指摘してください。修正後の全文ではなく、"どの行をどう直すか" だけ教えてください」

### それぞれ何をしているのか

理解せずにコピペしないでください。以下が意味です。

| 設定 | 意味 | なぜ必要か |
| --- | --- | --- |
| `renderWhitespace: all` | 空白を「・」で表示 | **全角スペースが目で見える** |
| `tabSize: 2` / `insertSpaces` | インデントを半角スペース2個に統一 | 他人のコードと混ざったときの崩れを防ぐ |
| `formatOnSave` | 保存時に自動整形 | インデントで悩まなくてよくなる |
| `wordWrap: on` | 長い行を折り返す | 横スクロールしなくてよい |
| `bracketPairColorization` | 括弧を色分け | **括弧の対応ミスが目で見える** |
| `guides.bracketPairs` | 括弧の対応線を表示 | ネストの深さがわかる |
| `minimap: false` | 右端の縮小表示をオフ | 初学者には情報量が多すぎる |
| `insertFinalNewline` | ファイル末尾に改行を入れる | Git で余計な差分が出ない |
| `trimTrailingWhitespace` | 行末の空白を削除 | 見えないゴミを残さない |
| `files.eol: "\n"` | 改行コードを LF に統一 | Windows と Mac の混在トラブルを防ぐ |

> ⚠️ **`formatOnSave` が効かない場合**
> 「既定のフォーマッタ」が未設定です。`Ctrl + Shift + P` → `Format Document With...` → `Configure Default Formatter...` → `Prettier` を選択してください。

---

## STEP 4: 全角スペースが見えることを確認する

新しいファイル `zenkaku-test.php` を作り、以下を打ってください。**`echo` の前は全角スペース**で打ちます（日本語入力ONでスペースキー）。

```php
<?php
　echo "test";
```

zenkaku 拡張が効いていれば、全角スペースの部分に**色付きの背景**が表示されます。

```php
<?php
▓echo "test";     ← ここが色付きで見えればOK
```

見えない場合は zenkaku 拡張が入っているか確認してください。

> 💡 このファイルはブラウザで開くとエラーになります。**それが正解です。** どんなエラーが出るか見てから削除してください。

---

## STEP 5: 覚えるショートカット（10個だけ）

全部覚える必要はありません。**まずこの10個**です。使いながら覚わります。

| 操作 | Windows | Mac |
| --- | --- | --- |
| 保存 | `Ctrl + S` | `Cmd + S` |
| 元に戻す | `Ctrl + Z` | `Cmd + Z` |
| やり直し | `Ctrl + Shift + Z` | `Cmd + Shift + Z` |
| 検索 | `Ctrl + F` | `Cmd + F` |
| 置換 | `Ctrl + H` | `Cmd + Option + F` |
| 行を複製 | `Shift + Alt + ↓` | `Shift + Option + ↓` |
| 行を移動 | `Alt + ↑ / ↓` | `Option + ↑ / ↓` |
| 行をコメントアウト | `Ctrl + /` | `Cmd + /` |
| ターミナルを開く | `Ctrl + Shift + @` | `Control + Shift + @` |
| コマンドパレット | `Ctrl + Shift + P` | `Cmd + Shift + P` |

> 💡 **「行を複製」と「行をコメントアウト」は、今日から使ってください。** この2つだけで作業速度が体感2倍になります。

---

## STEP 6: Emmet を知る（HTMLが一瞬で書ける）

VS Code には **Emmet** という省略記法が最初から入っています。

`.html` ファイルで `!` と打って `Tab` キーを押してみてください。

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>

</body>
</html>
```

これが一瞬で出ます。他にもよく使うものを挙げておきます。

| 入力 | `Tab` を押すと |
| --- | --- |
| `!` | HTML の雛形 |
| `div.container` | `<div class="container"></div>` |
| `ul>li*3` | `<ul>` の中に `<li>` が3つ |
| `p.text{こんにちは}` | `<p class="text">こんにちは</p>` |

第1部で本格的に使います。今は「そういう機能がある」だけ覚えてください。

---

## 🤖 AIに聞いてみよう

エディタ設定も AI に相談できます。

```text
VS Code の settings.json に以下の設定を入れました。
初学者がWeb開発（HTML/CSS/JavaScript/PHP）を学ぶ用途として、
追加したほうがよい設定と、その理由を3つ教えてください。
また、初学者には不要・むしろ混乱する設定があれば指摘してください。

（ここに自分の settings.json を貼る）
```

> ⚠️ **AIの提案を無条件で入れないでください。**
> 「なぜ必要か」を説明させて、納得したものだけ入れます。意味のわからない設定が積み上がると、後でトラブルの原因を切り分けられなくなります。
> これは AI との付き合い方の基本原則です。次のレッスンで詳しく扱います。

---

## 🔧 やってみよう

1. `htdocs/lesson` に `index.html` を作る
2. `!` + `Tab` で雛形を出す
3. `<title>` を「はじめてのページ」に変える
4. `<body>` の中に `h1{こんにちは}` + `Tab` で見出しを作る
5. 右クリック →「Open with Live Server」でブラウザに表示する
6. `h1` の文字を変えて保存 → **ブラウザが自動で更新されることを確認**

> 💡 **Live Server は HTML/CSS/JS 専用です。** PHPファイルには使えません（`http://127.0.0.1:5500/` で開くため、PHPが実行されない）。
> PHPを表示するときは必ず `http://localhost/...` を使ってください。**ここは混同する人が非常に多いです。**

| ファイル種別 | 開き方 |
| --- | --- |
| `.html` / `.css` / `.js` のみ | Live Server（`127.0.0.1:5500`）でOK |
| `.php` を含む | **必ず** `localhost`（XAMPP/MAMP）で開く |

---

## ✅ 章末チェック

- [ ] VS Code が日本語になった
- [ ] 拡張機能4つを入れた
- [ ] `settings.json` を設定した
- [ ] **全角スペースが色付きで見えることを確認した**
- [ ] 括弧が色分けされることを確認した
- [ ] `Ctrl + /`（コメントアウト）を使ってみた
- [ ] Emmet の `!` + `Tab` を試した
- [ ] Live Server と localhost の使い分けを理解した

---

**次のレッスン → [0-6 AIを開発の相棒にする — 基本編](00-06-ai-basics.md)**
