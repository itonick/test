# 1-6 ボックスモデルを完全に理解する

> 🎯 **このレッスンのゴール**
> - すべての要素が「4層の箱」であることを理解する
> - `box-sizing: border-box` が何を解決しているか説明できる
> - マージンの相殺を知り、余白がズレる原因を自分で特定できる
> - カフェLPに余白とセクションのレイアウトを入れる

所要 120分 / 難度 🟡
完成コード: [`code/01-06/`](../code/01-06/)

---

## 📖 すべての要素は「4層の箱」

CSSのレイアウトで悩む原因の8割は、ここの理解不足です。**ここだけは丁寧にやってください。**

```
┌─ margin（外側の余白・透明） ────────────────┐
│                                              │
│  ┌─ border（枠線） ─────────────────────┐   │
│  │                                       │   │
│  │  ┌─ padding（内側の余白） ────────┐  │   │
│  │  │                                 │  │   │
│  │  │  ┌─ content（中身） ────────┐  │  │   │
│  │  │  │  テキストや画像          │  │  │   │
│  │  │  └──────────────────────────┘  │  │   │
│  │  │                                 │  │   │
│  │  └─────────────────────────────────┘  │   │
│  │                                       │   │
│  └───────────────────────────────────────┘   │
│                                              │
└──────────────────────────────────────────────┘
```

| 層 | 役割 | 背景色は付くか |
| --- | --- | --- |
| **content** | 中身（テキスト・画像） | 付く |
| **padding** | 枠線の**内側**の余白 | **付く** |
| **border** | 枠線 | — |
| **margin** | 枠線の**外側**の余白 | **付かない（透明）** |

> 💡 **padding と margin の使い分け**
> - 背景色や枠線を**中身から離したい** → `padding`
> - **他の要素との距離**を空けたい → `margin`
>
> 迷ったら「背景色を付けたときに、その余白も色が付いてほしいか？」で判断します。

---

## ✍️ 手を動かす① ─ `box-sizing` の衝撃

### デフォルトの挙動（content-box）

```css
.box {
  width: 200px;
  padding: 20px;
  border: 5px solid black;
}
```

この箱の**実際の横幅**は何pxでしょうか。

```
content   200px
padding   20px × 2 = 40px
border     5px × 2 = 10px
──────────────────────────
合計      250px
```

**200px と書いたのに、250px になります。** これがCSSの初期設定（`content-box`）です。

### `border-box` にすると

```css
* {
  box-sizing: border-box;
}
```

```
合計      200px  ← 指定通り
うち border 10px
うち padding 40px
残りが content 150px
```

**`width` が「見た目の幅」になります。** 直感通りです。

### なぜ全要素に指定するのか

```css
*,
*::before,
*::after {
  box-sizing: border-box;
}
```

前回書いたこの3行は、**「レイアウト計算を人間の直感に合わせる」ための魔法**です。

> 💡 実務では、ほぼ100%のプロジェクトでこれが最初に書かれています。
> 「なぜこれを書くのか」を説明できると、それだけで初学者を抜けています。

### 実際に確かめる

`box-test.html` を作って試してください（📋 コピペ可）。

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>box-sizing の比較</title>
  <style>
    body { font-family: sans-serif; padding: 20px; }

    .box {
      width: 200px;
      padding: 20px;
      border: 5px solid #333;
      margin-bottom: 20px;
      background: #ffe9c9;
    }

    .content-box { box-sizing: content-box; }  /* 初期値 */
    .border-box  { box-sizing: border-box; }

    /* 200px の目盛り */
    .ruler {
      width: 200px;
      height: 8px;
      background: repeating-linear-gradient(90deg, #c00 0 10px, #fff 10px 20px);
      margin-bottom: 8px;
    }
  </style>
</head>
<body>
  <div class="ruler"></div>
  <p>↑ これが 200px</p>

  <div class="box content-box">content-box（初期値）— 実際は250px</div>
  <div class="box border-box">border-box — 実際も200px</div>
</body>
</html>
```

赤白の目盛りと見比べてください。**content-box のほうがはみ出している**のがわかります。

---

## ✍️ 手を動かす② ─ 余白の書き方

### ショートハンド

```css
/* 4方向すべて */
margin: 20px;

/* 上下 / 左右 */
margin: 20px 40px;

/* 上 / 左右 / 下 */
margin: 20px 40px 30px;

/* 上 / 右 / 下 / 左（時計回り） */
margin: 10px 20px 30px 40px;
```

> 💡 **覚え方**：4つ書くときは**時計回り**（上→右→下→左）。
> 2つのときは「たて・よこ」。3つのときは「上・よこ・下」。

### 個別指定

```css
margin-top: 20px;
margin-right: 40px;
margin-bottom: 30px;
margin-left: 40px;
```

### 論理プロパティ（モダンな書き方）

```css
margin-block: 20px;        /* 上下 */
margin-inline: auto;       /* 左右 */
padding-block: 40px 60px;  /* 上40px 下60px */
```

> 💡 縦書きや右から左に読む言語にも対応できる書き方です。この教材では、**上下だけ・左右だけ**を指定したいときに `margin-block` / `margin-inline` を使います。読みやすいためです。

### 横中央寄せの定番

```css
.container {
  max-width: 1000px;
  margin-inline: auto;   /* 左右の margin を auto にすると中央に寄る */
}
```

> ⚠️ **`margin: auto` で中央寄せできるのは「横方向」だけです。** 縦は寄りません。
> 縦中央は Flexbox か Grid を使います（次のレッスン）。

---

## ✍️ 手を動かす③ ─ マージンの相殺（最重要）

**上下に隣り合う margin は、大きいほうだけが適用されます。** これを「マージンの相殺」と呼びます。

```html
<p class="a">上の段落</p>
<p class="b">下の段落</p>
```

```css
.a { margin-bottom: 30px; }
.b { margin-top: 20px; }
```

**間隔は 30 + 20 = 50px …ではなく、30px です。**

```
┌──────────┐
│ 上の段落 │
└──────────┘
    ↕ 30px（20pxは飲み込まれる）
┌──────────┐
│ 下の段落 │
└──────────┘
```

> 🆘 **ここで詰まったら**（余白が思ったとおりにならない／要素が横にはみ出す）
> - **まず確認**：① `* { box-sizing: border-box; }` を入れたか（入れないと `width` に `padding`・`border` が加算されて、はみ出します）② 上下の余白が「合計にならない」のは**マージンの相殺**（正常な仕様）。片方に寄せると混乱しないので、**余白は下方向だけ `margin-bottom` に統一**すると安定します
> - **開発者ツールで確認**：F12 で要素を選ぶと、右下に**ボックスモデルの図**（青=内容 / 緑=padding / オレンジ=margin）が出る。実際の数値がここで見えます
> - **直らなければ、AIにこう聞く**（HTMLとCSSを貼る）：
>   「この要素の余白が意図どおりになりません。box-sizing とマージンの相殺の観点で、原因と直し方を教えてください」

### 親子でも起きる

```html
<div class="parent">
  <p class="child">テキスト</p>
</div>
```

```css
.parent { background: #eee; }
.child  { margin-top: 40px; }
```

**子の margin-top が、親の外側に飛び出します。** 親の中に40pxの余白ができるのではなく、親ごと40px下がります。

### 相殺が起きない条件

親に以下のいずれかがあると、相殺しません。

- `padding-top` がある
- `border-top` がある
- `display: flex` または `display: grid`
- `overflow: hidden` など

> 💡 **実務での対処**
> 現代のCSSでは、**Flexbox / Grid の `gap` を使うのが最も確実**です。gap では相殺が起きません。
>
> ```css
> .list {
>   display: flex;
>   flex-direction: column;
>   gap: 20px;   /* 必ず20px。相殺しない */
> }
> ```
>
> 次のレッスン（1-8）で本格的に扱います。

> ⚠️ **左右のマージンは相殺しません。** 相殺は上下だけの現象です。

---

## ✍️ 手を動かす④ ─ 表示形式（display）

| 値 | 特徴 | 代表的なタグ |
| --- | --- | --- |
| `block` | 横幅いっぱい。**前後で改行される**。width/height が効く | `div` `p` `h1` `section` |
| `inline` | 中身の幅だけ。**改行されない**。**width/height が効かない** | `span` `a` `strong` |
| `inline-block` | 横に並ぶが、width/height が効く | `button` `input` |
| `flex` / `grid` | レイアウト用（1-8、1-9で扱う） | — |
| `none` | 表示されなくなる（場所も取らない） | — |

> ⚠️ **`inline` 要素には `width` `height` `margin-top` `margin-bottom` が効きません。**
> 「`<a>` に高さを指定したのに効かない」の原因はこれです。`display: inline-block` か `block` にしてください。

### `<img>` の下にできる謎の隙間

```html
<div style="background: pink;">
  <img src="a.jpg" alt="">
</div>
```

画像の下に 3〜4px の隙間ができます。これは `<img>` が `inline` 要素で、**文字のベースラインの下（アルファベットの g や y が伸びる領域）が確保されている**ためです。

```css
img {
  display: block;   /* これで解決 */
}
```

前回のリセットCSSに `img { display: block; }` を入れたのは、この事故を防ぐためです。

---

## ✍️ 手を動かす⑤ ─ カフェLPに余白を入れる

`style.css` の末尾に追加してください。

> 🖊 **手で打ってください。** 完成形は `code/01-06/css/style.css`（📋 コピペ可）。

```css
/* ==========================================================
   4. レイアウト共通
   ========================================================== */

/* 中央寄せのコンテナ */
.site-header,
main > section,
.site-footer {
  max-width: 1040px;
  margin-inline: auto;
  padding-inline: 24px;
}

/* セクションの上下余白 */
main > section {
  padding-block: 72px;
  border-bottom: 1px solid var(--color-border);
}

main > section:last-child {
  border-bottom: none;
}


/* ==========================================================
   5. ヘッダー
   ========================================================== */

.site-header {
  padding-block: 20px;
  border-bottom: 1px solid var(--color-border);
}

.logo {
  font-family: var(--font-en);
  font-weight: 700;
  font-size: 18px;
  letter-spacing: .12em;
  margin-bottom: 8px;
}

.global-nav li {
  margin-bottom: 4px;
}

.global-nav a {
  font-size: 14px;
  letter-spacing: .08em;
}


/* ==========================================================
   6. セクション見出し
   ========================================================== */

main > section > h2 {
  font-family: var(--font-en);
  font-size: 14px;
  letter-spacing: .2em;
  color: var(--color-accent);
  margin-bottom: 16px;
}


/* ==========================================================
   7. メニュー
   ========================================================== */

.menu-item {
  padding-block: 28px;
  border-bottom: 1px dashed var(--color-border);
}

.menu-item:last-child {
  border-bottom: none;
}

.menu-item img {
  margin-bottom: 16px;
}

.menu-item h3 {
  margin-bottom: 4px;
}

.price {
  color: var(--color-accent);
  font-weight: 700;
  margin-bottom: 8px;
}


/* ==========================================================
   8. アクセス情報（dl）
   ========================================================== */

.info dt {
  font-size: 13px;
  color: var(--color-accent);
  margin-top: 16px;
}

.info dt:first-child {
  margin-top: 0;
}


/* ==========================================================
   9. フッター
   ========================================================== */

.site-footer {
  padding-block: 40px;
  font-size: 13px;
  text-align: center;
  border-top: 1px solid var(--color-border);
}
```

保存して確認してください。**セクションごとに余白と区切り線が入り、ぐっと「サイトらしく」なります。**

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| 幅を指定したのに、はみ出す | `box-sizing` が content-box | `* { box-sizing: border-box; }` |
| 余白が思ったより狭い | マージンの相殺 | 片側だけに margin を付けるか、gap を使う |
| 親の中に余白ができず、親ごと下がる | 親子間のマージン相殺 | 親に `padding-top` を付けるか、子の margin を親の padding に置き換える |
| `<a>` に高さが効かない | inline 要素 | `display: inline-block` |
| 画像の下に隙間ができる | img が inline 要素 | `img { display: block; }` |
| 中央寄せできない | `width` を指定していない | `margin-inline: auto` は `max-width` とセット |
| 縦中央にならない | `margin: auto` は横だけ | Flexbox / Grid を使う（1-8） |

### 余白のデバッグ方法

**開発者ツールの右下に、ボックスモデルの図が出ます。**

```
┌── margin ──────────────┐
│  ┌─ border ─────────┐  │
│  │ ┌─ padding ────┐ │  │
│  │ │   200 × 48   │ │  │
│  │ └──────────────┘ │  │
│  └──────────────────┘  │
└────────────────────────┘
```

数値をクリックすると**その場で編集できます**。余白で悩んだら、まずここを見てください。

---

## 🤖 AIに聞いてみよう

### ① 余白の設計方針を相談する

```text
CSSの余白設計について相談です。

私はいま、セクションごとに padding-block で余白を付けています。
しかし、要素ごとに margin-bottom を付ける方法もあると知りました。

1. 「margin で余白を付ける流派」と「padding で付ける流派」の違いを教えてください
2. それぞれのメリット・デメリット
3. 初学者はどちらを基本にすべきですか？理由も
4. 現代のCSS（Flexbox / Grid の gap）を使う場合、考え方はどう変わりますか？

コードは最小限で、考え方を中心に説明してください。
```

### ② マージン相殺を実験で理解する

```text
CSSのマージンの相殺を、実際に手を動かして理解したいです。

相殺が「起きるケース」と「起きないケース」を比較できる
HTMLファイルを1つ作ってください。

条件:
- 1ファイルで完結（CSSは <style> にインラインで）
- 相殺が起きる例と起きない例を、画面上で並べて比較できる
- どちらが何pxになっているか、画面上でわかるようにする
- 各ケースに、なぜそうなるかのコメントをCSS内に書く
```

> 💡 これは「AIにコードを書かせる」使い方ですが、**学習用の実験環境を作らせる**のは良い使い方です。
> ただし、返ってきたコードは必ず**1行ずつ読んで**から実行してください。

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

以下の箱の**実際の横幅**を計算してください。

```css
/* ① box-sizing の指定なし（初期値） */
.a { width: 300px; padding: 15px; border: 2px solid; margin: 10px; }

/* ② border-box */
.b { box-sizing: border-box; width: 300px; padding: 15px; border: 2px solid; margin: 10px; }
```

1. `.a` の**見た目の幅**（border まで）は？
2. `.a` が**占める幅**（margin を含む）は？
3. `.b` の**見た目の幅**は？
4. `.b` の **content の幅**は？

<details>
<summary>答えを見る</summary>

1. `300 + 15×2 + 2×2 = 334px`
2. `334 + 10×2 = 354px`
3. `300px`（指定通り）
4. `300 - 15×2 - 2×2 = 266px`

</details>

### 演習2（必須）

`.hero` を、以下のように整えてください。

- [ ] 上下の余白を 96px にする
- [ ] `h1` の下に 16px の余白を付ける
- [ ] `.btn` を `display: inline-block` にし、`padding: 14px 32px`、枠線をアクセントカラーにする
- [ ] `.btn` の上に 32px の余白を付ける

### 演習3（挑戦）

以下のHTMLで、**灰色の親ボックスの中に 40px の余白**を作りたいのに、親ごと下がってしまいます。原因を特定し、**2通りの方法**で直してください。

```html
<div class="parent">
  <p class="child">テキスト</p>
</div>
```

```css
.parent { background: #eee; }
.child  { margin-top: 40px; }
```

<details>
<summary>答えを見る</summary>

**原因**：親子間のマージン相殺。子の `margin-top` が親の外に飛び出している。

**方法1：子の margin を親の padding に置き換える**

```css
.parent { background: #eee; padding-top: 40px; }
.child  { margin-top: 0; }
```

**方法2：親に相殺を止めるプロパティを付ける**

```css
.parent { background: #eee; display: flow-root; }
.child  { margin-top: 40px; }
```

（`display: flex` や `overflow: hidden` でも止まりますが、`flow-root` が副作用の少ない専用の指定です）

</details>

---

## ✅ 章末チェック

- [ ] margin / border / padding / content の順序を図で描ける
- [ ] `content-box` と `border-box` の計算の違いを説明できる
- [ ] `box-sizing: border-box` を全要素に指定する理由を言える
- [ ] マージンの相殺がどんなときに起きるか説明できる
- [ ] `inline` 要素に width が効かないことを知っている
- [ ] 画像の下の隙間の原因と対処を言える
- [ ] 開発者ツールでボックスモデルの図を見つけられる
- [ ] カフェLPに余白が入った

---

**前 → [1-5 CSSの書き方とセレクタ](01-05-css-basics.md)　｜　次 → [1-7 文字と色のデザイン基礎](01-07-typography-color.md)**
