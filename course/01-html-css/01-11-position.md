# 1-11 position と重なり順

> ◎ **このレッスンのゴール**
> - `position` の5つの値を使い分けられる
> - `absolute` の「基準」がどこになるかを説明できる
> - `z-index` が効かない理由を、重ね合わせコンテキストから説明できる
> - カフェLPに固定ヘッダーとバッジを付ける

所要 90分 / 難度 🔴
完成コード: [`code/01-11/`](../code/01-11/)

---

## 📖 position は「通常の流れから外す」ための道具

HTMLの要素は、上から順に積まれていきます（**通常フロー**）。`position` は、この流れから要素を外して自由に配置する仕組みです。

| 値 | 通常フロー | 基準 | 使いどころ |
| --- | --- | --- | --- |
| `static` | **残る**（初期値） | — | 通常 |
| `relative` | **残る**（場所は取ったまま見た目だけずれる） | 自分の元の位置 | absolute の基準を作る |
| `absolute` | **外れる**（場所を取らない） | 最も近い `static` 以外の祖先 | バッジ、オーバーレイ |
| `fixed` | **外れる** | ビューポート（画面） | 固定ヘッダー、追従ボタン |
| `sticky` | **残る** | スクロール位置 | 追従する見出し・目次 |

---

## ✍️ 手を動かす① ─ relative と absolute はセット

**これが position の核心です。**

```css
.parent {
  position: relative;   /* ← 基準を作る。これがないと画面全体が基準になる */
}

.child {
  position: absolute;
  top: 12px;
  right: 12px;          /* 親の右上から12pxの位置 */
}
```

```
┌─ .parent（position: relative）─────┐
│                          ┌──────┐ │
│                          │.child│ │ ← 親の右上
│                          └──────┘ │
│                                    │
└────────────────────────────────────┘
```

### `absolute` の基準はどこか

**「祖先をたどって、最初に見つかった `position` が `static` 以外の要素」**が基準になります。

```html
<body>
  <div class="a">           <!-- position: static（初期値） -->
    <div class="b">         <!-- position: relative ← ここが基準 -->
      <div class="c">       <!-- position: static -->
        <span class="d"></span>   <!-- position: absolute -->
      </div>
    </div>
  </div>
</body>
```

`.d` の基準は `.b` です。`.c` は `static` なので飛ばされます。

> ⚠️ **`position: relative` を付け忘れると、要素が画面の隅に飛んでいきます。**
> 祖先に基準がないと、**ビューポート（正確には最初の包含ブロック）** が基準になるためです。
> 「absolute にしたら要素が消えた・変な場所に行った」の原因は99%これです。

### 位置の指定

```css
.child {
  position: absolute;
  top: 0;      /* 基準の上端から */
  right: 0;    /* 基準の右端から */
  bottom: 0;
  left: 0;
}
```

`top` と `bottom` の両方を `0` にすると、**基準の高さいっぱいに伸びます**。

```css
/* 基準を完全に覆う（オーバーレイの定番） */
.overlay {
  position: absolute;
  inset: 0;     /* top/right/bottom/left: 0 のショートハンド */
  background: rgb(0 0 0 / 40%);
}
```

> 💡 **`inset: 0` は覚えておくと便利です。** 4行が1行になります。

### absolute での中央寄せ

```css
.center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
```

`top: 50%` は「**要素の左上**が親の中央に来る」位置です。そこから**要素自身の半分だけ**戻す必要があるので、`translate(-50%, -50%)` を使います。

```
top/left: 50% だけ      transform を足すと
┌──────────────┐        ┌──────────────┐
│              │        │              │
│      ┌────┐  │        │   ┌────┐     │
│      │要素│  │   →    │   │要素│     │
│      └────┘  │        │   └────┘     │
└──────────────┘        └──────────────┘
 左上が中央              要素が中央
```

> 💡 Flexbox / Grid が使える場面では、そちらのほうが簡単です。**absolute での中央寄せは、他に手段がないときだけ**使ってください。

---

## ✍️ 手を動かす② ─ fixed と sticky

### `fixed`：画面に固定

```css
.floating-btn {
  position: fixed;
  right: 20px;
  bottom: 20px;
  z-index: 100;
}
```

**スクロールしても画面上の同じ位置**に留まります。基準は常にビューポートです。

> ⚠️ **`fixed` は祖先に `transform` / `filter` / `will-change` があると、基準がそちらに変わります。**
> 「固定したのに動いてしまう」場合は、祖先に `transform` が付いていないか確認してください。**これは非常に見つけにくいバグです。**

### `sticky`：スクロールで途中から固定

```css
.section-title {
  position: sticky;
  top: 0;
  background: #fff;
}
```

通常は普通に流れ、**スクロールして指定位置に達すると固定**されます。親要素の範囲を出ると、また流れます。

> ⚠️ **`sticky` が効かない4大原因**
>
> 1. **`top` / `bottom` などを指定していない** → 必須です
> 2. **親要素に `overflow: hidden` / `auto` / `scroll` がある** → sticky が無効化されます
> 3. **親要素の高さが、sticky 要素と同じ** → 動く余地がない
> 4. **親が Flex/Grid コンテナで、`align-items: stretch` 以外** → 高さが足りない
>
> 特に **2番** が厄介です。祖先を1つずつ遡って `overflow` を確認してください。

---

## ✍️ 手を動かす③ ─ z-index と重ね合わせ

### 基本ルール

```css
.back  { position: relative; z-index: 1; }
.front { position: relative; z-index: 2; }   /* こちらが手前 */
```

> ⚠️ **`z-index` は `position: static` の要素には効きません。**
> `relative` / `absolute` / `fixed` / `sticky` のいずれかが必要です（Flex/Grid の子は例外的に効きます）。

### 「z-index: 9999 なのに前に出ない」問題

**重ね合わせコンテキスト（stacking context）** が原因です。

```html
<div class="parent-a">      <!-- z-index: 1 -->
  <div class="child">       <!-- z-index: 9999 -->
</div>
<div class="parent-b">      <!-- z-index: 2 -->
</div>
```

**`.child` は `.parent-b` より後ろになります。**

理由：`.child` の `z-index: 9999` は、**`.parent-a` の中でしか意味を持たない**からです。親同士の比較（1 vs 2）で `.parent-a` が負けたら、その中身も一緒に負けます。

> 💡 **例え話**：マンションの部屋番号のようなものです。
> 3階の999号室より、5階の101号室のほうが上です。部屋番号だけ大きくしても、階が下なら勝てません。

> 🆘 **ここで詰まったら**（`z-index` を大きくしても要素が前に出ない）
> - **まず確認**：① その要素に `position`（`relative`/`absolute`/`fixed`/`sticky`）が付いているか（`static` では `z-index` は効きません）② 上の「マンション」問題 ─ **勝てないのは親同士の比較で負けているから**。数字を上げるべきは、その要素ではなく**親**かもしれません
> - **開発者ツールで確認**：F12 で親をたどり、どこで `position` や `z-index`・`opacity`・`transform` が効いているか（これらは重ね合わせコンテキストを作ります）を見る
> - **直らなければ、AIにこう聞く**（該当部分のHTMLとCSSを貼る）：
>   「z-index を大きくしても前面に出ません。重ね合わせコンテキストの観点で、どの要素の指定を変えるべきか教えてください」

### 重ね合わせコンテキストを作るもの

以下があると、そこで新しい「階」が始まります。

- `position` + `z-index`（auto 以外）
- `opacity` が 1 未満
- `transform` / `filter` / `backdrop-filter`
- `will-change`
- `isolation: isolate`

> ⚠️ **`opacity: 0.99` を付けるだけで、重ね合わせコンテキストが生まれます。**
> 「なぜか z-index が効かない」ときは、祖先の `opacity` と `transform` を疑ってください。

### z-index の管理方針

```css
:root {
  --z-base: 1;
  --z-dropdown: 100;
  --z-header: 500;
  --z-overlay: 1000;
  --z-modal: 1100;
  --z-toast: 1200;
}
```

**値を変数で管理**すると、「9999 の上に 10000 を書く」という不毛な競争を防げます。

---

## ✍️ 手を動かす④ ─ カフェLPに適用する

### ① ヘッダーを追従させる

```css
/* ==========================================================
   5. ヘッダー
   ========================================================== */

.site-header {
  position: sticky;
  top: 0;
  z-index: var(--z-header);
  background-color: var(--color-bg);

  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding-block: 16px;
  border-bottom: 1px solid var(--color-border);
}
```

`:root` に z-index の変数を追加してください。

```css
:root {
  /* ...既存の定義... */

  /* ---- 重なり順 ---- */
  --z-header: 500;
  --z-overlay: 1000;
}
```

> ⚠️ **`background-color` を必ず指定してください。**
> 透明のまま固定すると、スクロール時に下のコンテンツが透けて重なります。

> ⚠️ **`max-width` + `margin-inline: auto` と `position: sticky` は相性が悪いです。**
> ヘッダー全体を画面幅いっぱいの背景にしたい場合は、外側に「幅いっぱいの帯」、内側に「中央寄せのコンテナ」の2重構造にします。演習3で扱います。

### ② ヒーローに文字を重ねる

`index.html` のヒーローを、以下の構造に変えます。

```html
    <section class="hero">
      <div class="hero-media">
        <picture>
          <source media="(min-width: 768px)" srcset="images/hero.jpg">
          <img src="images/hero-sp.jpg"
               alt="窓辺の席に差し込む木漏れ日とコーヒーカップ"
               width="800" height="1000">
        </picture>
        <div class="hero-copy">
          <h1>木漏れ日の下で、一杯を。</h1>
          <p>住宅街の小さな自家焙煎コーヒースタンド。</p>
          <a href="#menu" class="btn btn-on-image">メニューを見る</a>
        </div>
      </div>
    </section>
```

```css
/* ==========================================================
   6. ヒーロー
   ========================================================== */

.hero {
  padding-block: 0;
  border-bottom: none;
}

.hero-media {
  position: relative;   /* ← absolute の基準を作る */
}

.hero-media img {
  width: 100%;
  aspect-ratio: 4 / 5;
  object-fit: cover;
}

/* 画像を暗くして、文字を読みやすくする */
.hero-media::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgb(0 0 0 / 10%), rgb(0 0 0 / 55%));
}

.hero-copy {
  position: absolute;
  inset: auto 0 0 0;         /* 下端に配置 */
  z-index: 1;                /* ::after より手前に */
  padding: clamp(20px, 5vw, 48px);
  color: #fff;
  text-align: center;
}

.hero-copy h1 {
  font-size: var(--fs-3xl);
  color: #fff;
  margin-bottom: 12px;
}

.hero-copy p {
  color: rgb(255 255 255 / 88%);
  margin-inline: auto;
  max-width: 30em;
}

.btn-on-image {
  margin-top: 24px;
  color: #fff;
  border-color: rgb(255 255 255 / 70%);
}

.btn-on-image:hover {
  background-color: #fff;
  color: var(--color-text);
}

@media (min-width: 768px) {
  .hero-media img {
    aspect-ratio: 16 / 7;
  }
}
```

**ここで使った技術**

| 技術 | 役割 |
| --- | --- |
| `position: relative` + `absolute` | 画像の上に文字を重ねる |
| `::after` + `inset: 0` | 画像全体を覆う暗いレイヤー |
| `linear-gradient` | 下にいくほど暗くする（文字の可読性を確保） |
| `z-index: 1` | 文字を暗いレイヤーより手前に |

> 💡 **画像の上に文字を置くときは、必ず暗くする（または明るくする）レイヤーを挟んでください。**
> 写真によっては文字が全く読めなくなります。コントラスト比の基準（4.5:1）は、画像の上でも同じです。

### ③ メニューに「おすすめ」バッジを付ける

```html
        <li class="menu-item">
          <div class="menu-thumb">
            <img src="images/menu-drip.jpg" alt="ドリッパーから注がれるコーヒー"
                 width="800" height="800" loading="lazy">
            <span class="badge">おすすめ</span>
          </div>
          <h3>ハンドドリップ</h3>
          ...
```

```css
.menu-thumb {
  position: relative;
  margin-bottom: 16px;
}

.menu-thumb img {
  aspect-ratio: 1 / 1;
  object-fit: cover;
}

.badge {
  position: absolute;
  top: 10px;
  left: 10px;
  padding: 4px 12px;
  font-size: var(--fs-xs);
  letter-spacing: .08em;
  color: #fff;
  background-color: var(--color-accent);
  border-radius: 2px;
}
```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| absolute にしたら変な場所に飛んだ | 親に `position: relative` がない | 基準にしたい親に `relative` を付ける |
| absolute の要素が消えた | 親に高さがない（子が全部 absolute） | 親に `min-height` を付ける |
| sticky が効かない | `top` の指定がない | `top: 0` などを必ず書く |
| sticky が効かない② | 祖先に `overflow: hidden` がある | 祖先を1つずつ遡って確認 |
| fixed が動いてしまう | 祖先に `transform` がある | 祖先の transform を外すか、構造を変える |
| z-index が効かない | `position: static` のまま | `relative` などを付ける |
| z-index: 9999 でも前に出ない | 親が別の重ね合わせコンテキスト | 親の z-index を上げる |
| 固定ヘッダーで内容が隠れる | ヘッダーの高さ分の余白がない | `scroll-margin-top` か `padding-top` |

### 固定ヘッダーとページ内リンクの問題

`position: sticky` のヘッダーがあると、`#menu` にジャンプしたとき**見出しがヘッダーの下に隠れます**。

```css
/* ジャンプ先に余白を確保する */
main > section {
  scroll-margin-top: 80px;   /* ヘッダーの高さ + α */
}

/* スクロールを滑らかに */
html {
  scroll-behavior: smooth;
}

@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
}
```

> 💡 **`scroll-margin-top` は、この問題のための専用プロパティです。** 知らないと、`padding-top` と `margin-top` で無理やり調整することになります。

---

## 🤖 AIに聞いてみよう

### ① z-index が効かない原因を切り分けさせる

```text
CSSで z-index を指定しましたが、要素が期待通りの重なり順になりません。

【HTML】
（構造を貼る）
【CSS】
（該当部分を貼る）
【期待する重なり順】
【実際の重なり順】

1. 重ね合わせコンテキストの観点から、原因を分析してください
2. どの要素が重ね合わせコンテキストを作っているか指摘してください
3. 開発者ツールで確認する方法を教えてください

すぐに修正コードを出すのではなく、私が原因を理解できるよう説明してください。
```

### ② position の選択を検証させる

```text
以下のUIを実装したいです。それぞれ position の どの値を使うべきか、
理由つきで教えてください。また、position を使わずに実現できる場合は
その方法も教えてください。

1. スクロールしても常に画面右下に表示される「トップへ戻る」ボタン
2. カード画像の右上に表示される「NEW」バッジ
3. 記事を読み進めても、章タイトルが画面上部に留まる
4. モーダルダイアログの背景を暗くするオーバーレイ
5. ヘッダーのナビにマウスを乗せると出るドロップダウン
6. 画像の上に重ねるキャプション

「position を使わない方法があるならそちらを優先すべき」という前提で
アドバイスしてください。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

「**トップへ戻る**」ボタンを追加してください。

- [ ] `</main>` の直後に `<a href="#top" class="to-top" aria-label="ページ上部へ戻る">↑</a>` を追加
- [ ] `position: fixed` で画面右下に固定する
- [ ] 円形（`border-radius: 50%`）、48×48px にする
- [ ] `z-index` を変数で管理する
- [ ] `<body>` の直後に `<span id="top"></span>` を置くか、`<header>` に `id="top"` を付ける

### 演習2（必須）

以下のコードで、`.badge` が**画面の左上**に表示されてしまいます。原因と対処を答えてください。

```html
<div class="card">
  <div class="thumb">
    <img src="a.jpg" alt="">
    <span class="badge">NEW</span>
  </div>
  <h3>タイトル</h3>
</div>
```

```css
.card { padding: 16px; }
.thumb { margin-bottom: 12px; }
.badge { position: absolute; top: 8px; left: 8px; }
```

<details>
<summary>答えを見る</summary>

**原因**：`.badge` の祖先に `position: static` 以外の要素が1つもないため、基準がビューポートになっている。

**対処**：バッジを重ねたい親（`.thumb`）に `position: relative;` を付ける。

```css
.thumb { position: relative; margin-bottom: 12px; }
```

**ポイント**：`absolute` を書いたら、**必ずセットで「どれを基準にするか」を決めて `relative` を付ける**。この2つは常にペアです。

</details>

### 演習3（挑戦）

ヘッダーを、**画面幅いっぱいの背景 + 中央寄せの中身**という2重構造に作り替えてください。

```
┌────────────────────────────────────────────┐ ← 背景は画面幅いっぱい
│      ┌──────────────────────────┐          │
│      │ LOGO        MENU ABOUT   │          │ ← 中身は1040pxで中央
│      └──────────────────────────┘          │
└────────────────────────────────────────────┘
```

- [ ] `<header class="site-header">` の中に `<div class="header-inner">` を追加する
- [ ] `.site-header` に `position: sticky; top: 0;` と背景色・下線を持たせる（`max-width` は付けない）
- [ ] `.header-inner` に `max-width: 1040px; margin-inline: auto;` を持たせる
- [ ] スクロールしたとき、背景が画面幅いっぱいに追従することを確認する

> 💡 これは実務のサイトでほぼ必ず使う構造です。「外枠＝背景と固定、内枠＝幅制限」と覚えてください。

---

## ✅ 章末チェック

- [ ] `position` の5つの値と、それぞれの基準を言える
- [ ] `absolute` の基準が「最も近い static 以外の祖先」だと説明できる
- [ ] `relative` と `absolute` がセットである理由を言える
- [ ] `inset: 0` の意味を知っている
- [ ] `sticky` が効かない原因を3つ言える
- [ ] `z-index` が `static` には効かないことを知っている
- [ ] 重ね合わせコンテキストを作るプロパティを3つ言える
- [ ] `scroll-margin-top` の用途を説明できる
- [ ] カフェLPに固定ヘッダーとヒーローの文字重ねが入った

---

**前 → [1-10 レスポンシブ対応とメディアクエリ](01-10-responsive.md)　｜　次 → [1-12 開発者ツールでCSSをデバッグする](01-12-devtools.md)**
