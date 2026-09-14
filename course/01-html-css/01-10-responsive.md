# 1-10 レスポンシブ対応とメディアクエリ

> 🎯 **このレッスンのゴール**
> - モバイルファーストで書けるようになる
> - メディアクエリを最小限で済ませる設計ができる
> - `clamp()` で可変の文字サイズを書ける
> - カフェLPをスマホ〜PCまで崩れなくする

所要 150分 / 難度 🟡
完成コード: [`code/01-10/`](../code/01-10/)

---

## 📖 レスポンシブの前提：viewport

まず、これがないと何も始まりません。

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

| 指定 | 意味 |
| --- | --- |
| `width=device-width` | 表示幅を**端末の実際の幅**に合わせる |
| `initial-scale=1.0` | 初期倍率を等倍にする |

**これがないと、スマホは「PC用の980px幅のページ」を縮小表示します。** 文字が豆粒になるあの状態です。

> ⚠️ **`user-scalable=no` や `maximum-scale=1` を書かないでください。**
> ユーザーがピンチズームできなくなります。視力の弱い人にとって、これは致命的です。

---

## 📖 モバイルファーストで書く

CSSの書き方には2つの流派があります。

| 流派 | 書き方 | 使う条件 |
| --- | --- | --- |
| **モバイルファースト** | スマホ用を基本に書き、**大きい画面を上書き** | `min-width` |
| デスクトップファースト | PC用を基本に書き、**小さい画面を上書き** | `max-width` |

### なぜモバイルファーストなのか

1. **スマホのほうがレイアウトが単純**（1列）。単純なものから複雑に足すほうが書きやすい
2. スマホでの閲覧が多数派
3. スマホで不要なスタイルを読み込まずに済む

```css
/* ① まずスマホ用（メディアクエリなしで書く） */
.menu-list {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

/* ② タブレット以上で上書き */
@media (min-width: 768px) {
  .menu-list {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* ③ PC以上で上書き */
@media (min-width: 1024px) {
  .menu-list {
    grid-template-columns: repeat(3, 1fr);
  }
}
```

> ⚠️ **`min-width` と `max-width` を混ぜないでください。**
> 混ざると「どちらが優先されるか」が追えなくなり、デバッグ不能になります。**プロジェクト内では `min-width` で統一**してください。

---

## ✍️ 手を動かす① ─ メディアクエリの書き方

```css
@media (min-width: 768px) {
  /* 768px 以上のときだけ適用 */
}

@media (max-width: 767px) {
  /* 767px 以下のときだけ適用 */
}

@media (min-width: 768px) and (max-width: 1023px) {
  /* 768px 〜 1023px の間だけ */
}

@media print {
  /* 印刷時 */
}

@media (prefers-color-scheme: dark) {
  /* OSがダークモードのとき */
}

@media (prefers-reduced-motion: reduce) {
  /* ユーザーがアニメーション削減を設定しているとき */
}
```

### ブレークポイントの決め方

**「主要なスマホの幅」で決めるのは間違いです。** 端末は無限にあります。

**正解は「レイアウトが崩れた幅」で決めること。**

1. 開発者ツールのデバイスモードで、幅をゆっくり広げていく
2. 「ここで崩れる」「ここでスカスカになる」という幅を見つける
3. **その幅**をブレークポイントにする

とはいえ、目安は必要なので、この教材では以下を使います。

```css
/* この教材の標準ブレークポイント */
@media (min-width: 600px)  { /* 大きめのスマホ・小型タブレット */ }
@media (min-width: 768px)  { /* タブレット */ }
@media (min-width: 1024px) { /* PC */ }
```

> 💡 **ブレークポイントは少ないほど良い設計です。**
> 3つで足りるなら3つ。5つ以上必要になったら、そもそもの設計を見直すサインです。

> 🆘 **ここで詰まったら**（画面幅を変えてもレイアウトが変わらない）
> - **まず確認**：① HTMLの `<head>` に `<meta name="viewport" content="width=device-width, initial-scale=1.0">` があるか（無いとスマホで縮小表示になり、メディアクエリが効いて見えません）② ブラウザ幅ではなく**開発者ツールのデバイスモード**（F12 → スマホのアイコン）で確認しているか ③ `min-width` と `max-width` を取り違えていないか
> - **直らなければ、AIにこう聞く**（該当CSSと viewport の記述を貼る）：
>   「メディアクエリが効きません。ブレークポイントをまたいでも見た目が変わらない原因を、確認手順つきで教えてください」

---

## ✍️ 手を動かす② ─ メディアクエリを使わない技

**実は、多くの場面でメディアクエリは不要です。** こちらを先に検討してください。

### ① `repeat(auto-fit, minmax())`（1-9で学習済み）

```css
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 24px;
}
```

**メディアクエリ0個で、列数が自動変化します。**

### ② `flex-wrap: wrap` + `flex-basis`

```css
.row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}
.col {
  flex: 1 1 280px;   /* 280px 未満になるなら折り返す */
}
```

### ③ `clamp()` で可変サイズ

```css
font-size: clamp(最小値, 可変値, 最大値);
```

```css
h1 {
  font-size: clamp(28px, 5vw, 48px);
}
```

- 画面が狭いとき：**28px を下回らない**
- 画面が広いとき：**48px を超えない**
- その間：`5vw`（画面幅の5%）で滑らかに変化

**ブレークポイントごとに font-size を書く必要がなくなります。**

| 単位 | 意味 |
| --- | --- |
| `vw` | ビューポート幅の1% |
| `vh` | ビューポート高さの1% |
| `vmin` / `vmax` | vw と vh の小さい方 / 大きい方 |

余白にも使えます。

```css
.section {
  padding-block: clamp(48px, 8vw, 112px);
  padding-inline: clamp(16px, 4vw, 40px);
}
```

> 💡 **`clamp()` を覚えると、CSSの記述量が劇的に減ります。**
> 「スマホで48px、PCで112px」を、メディアクエリなしの1行で書けます。

### ④ `min()` / `max()`

```css
width: min(100%, 1040px);   /* 100% と 1040px の小さい方 = max-width と同じ */
padding-inline: max(16px, 4vw);  /* 最低16pxは確保 */
```

---

## ✍️ 手を動かす③ ─ 画像のレスポンシブ

### 基本

```css
img {
  max-width: 100%;
  height: auto;
  display: block;
}
```

これで、親要素より大きくなりません。**リセットCSSに入れた1行がこれです。**

### 画面幅で画像を切り替える

スマホに1600pxの画像を送るのは無駄です。`<picture>` を使います。

```html
<picture>
  <source media="(min-width: 768px)" srcset="images/hero-pc.jpg">
  <img src="images/hero-sp.jpg" alt="窓辺の席に差し込む木漏れ日" width="800" height="1000">
</picture>
```

- 768px 以上 → `hero-pc.jpg`
- それ未満 → `hero-sp.jpg`
- **`<img>` は必ず必要**（フォールバック兼、alt の置き場所）

> 💡 スマホ用に**縦長の画像**を用意すると、ぐっと「アプリらしく」なります。単に縮小するのとは印象が変わります。

---

## ✍️ 手を動かす④ ─ カフェLPをレスポンシブにする

### ① 共通の余白を可変にする

`style.css` のレイアウト共通部分を、以下に置き換えてください。

```css
/* ==========================================================
   4. レイアウト共通
   ========================================================== */

.site-header,
main > section,
.site-footer {
  max-width: 1040px;
  margin-inline: auto;
  padding-inline: clamp(16px, 4vw, 32px);
}

main > section {
  padding-block: clamp(48px, 8vw, 88px);
  border-bottom: 1px solid var(--color-border);
}

main > section:last-child {
  border-bottom: none;
}
```

### ② 文字サイズを可変にする

`:root` のタイプスケールを更新します。

```css
:root {
  /* ...色とフォントの定義はそのまま... */

  /* ---- 文字サイズ（可変） ---- */
  --fs-xs:  12px;
  --fs-sm:  14px;
  --fs-base:16px;
  --fs-lg:  clamp(17px, 1.6vw, 19px);
  --fs-xl:  clamp(20px, 2.4vw, 26px);
  --fs-2xl: clamp(24px, 3.2vw, 34px);
  --fs-3xl: clamp(28px, 5.2vw, 48px);
}
```

これで、**ヒーローの見出しが画面幅に応じて滑らかに変化**します。

### ③ ヘッダーをスマホ対応

```css
/* ==========================================================
   5. ヘッダー
   ========================================================== */

.site-header {
  display: flex;
  flex-direction: column;   /* スマホでは縦積み */
  align-items: center;
  gap: 10px;
  padding-block: 16px;
  border-bottom: 1px solid var(--color-border);
}

.global-nav ul {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px 20px;
}

@media (min-width: 768px) {
  .site-header {
    flex-direction: row;             /* PCでは横並び */
    justify-content: space-between;
    padding-block: 20px;
  }
  .global-nav ul {
    gap: 28px;
  }
}
```

### ④ メニューは Grid の auto-fit に任せる（メディアクエリ不要）

```css
.menu-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr));
  gap: clamp(28px, 4vw, 40px) clamp(20px, 3vw, 32px);
  margin-top: 40px;
}
```

> 💡 **`minmax(min(100%, 240px), 1fr)` の `min(100%, 240px)` に注目してください。**
> 単に `minmax(240px, 1fr)` と書くと、**画面幅が240px未満のとき横スクロールが発生します**。
> `min(100%, 240px)` にすると「240px、ただし親幅を超えない」となり、極小画面でも崩れません。
> **これは実務で必ず踏むバグです。** いま覚えてください。

### ⑤ ヒーローの画像をスマホで縦長に

`index.html` のヒーロー画像を `<picture>` に変えます。

```html
      <picture>
        <source media="(min-width: 768px)" srcset="images/hero.jpg">
        <img src="images/hero-sp.jpg"
             alt="窓辺の席に差し込む木漏れ日とコーヒーカップ"
             width="800" height="1000">
      </picture>
```

CSSも調整します。

```css
.hero picture,
.hero img {
  width: 100%;
}

.hero img {
  aspect-ratio: 4 / 5;    /* スマホは縦長 */
  object-fit: cover;
  margin-bottom: clamp(20px, 4vw, 32px);
}

@media (min-width: 768px) {
  .hero img {
    aspect-ratio: 16 / 7;  /* PCは横長 */
  }
}
```

> 💡 `hero-sp.jpg` を用意していない場合は、`<picture>` を使わず `<img>` のままでも構いません。CSSの `aspect-ratio` だけでも印象は変わります。

### ⑥ フォームの調整

```css
.contact-form {
  display: flex;
  flex-direction: column;
  gap: clamp(18px, 3vw, 24px);
  max-width: 560px;
  margin-top: 32px;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  padding: 12px 14px;
  font-size: 16px;   /* ← 重要。下記参照 */
  border: 1px solid var(--color-border);
  border-radius: 2px;
  background: var(--color-surface);
}
```

> ⚠️ **iOS Safari では、入力欄の `font-size` が16px未満だと、タップ時に画面が勝手にズームします。**
> これは仕様で、無効化する方法はありません（`user-scalable=no` は使ってはいけません）。
> **入力欄の font-size は必ず 16px 以上**にしてください。実務で必ずハマる罠です。

---

## ✍️ 手を動かす⑤ ─ 確認の仕方

### 開発者ツールのデバイスモード

1. `F12` で開発者ツールを開く
2. 左上の**スマホとタブレットのアイコン**をクリック（`Ctrl + Shift + M`）
3. 上部のドロップダウンで端末を選ぶ、または幅を直接入力

**必ず確認する幅**

| 幅 | 想定 |
| --- | --- |
| **320px** | 最小クラスのスマホ（iPhone SE 相当） |
| 375px | 標準的なスマホ |
| 768px | タブレット縦 |
| 1024px | タブレット横・小型ノート |
| 1440px | デスクトップ |

> 💡 **320px で崩れなければ、ほぼ大丈夫です。** ここを基準にしてください。

### 横スクロールが出ていないか確認する

レスポンシブで最も多い不具合が「**横スクロールが出る**」です。

原因の特定方法：

```css
/* 一時的にこれを入れて、はみ出している要素を探す */
* { outline: 1px solid red; }
```

すべての要素に赤い枠が付くので、**画面外にはみ出しているものが見つかります**。

**よくある原因**

| 原因 | 対処 |
| --- | --- |
| 固定幅（`width: 400px`）の要素 | `max-width` に変える |
| 長いURLや英単語が折り返さない | `overflow-wrap: break-word;` |
| 画像が親より大きい | `max-width: 100%` |
| `100vw` を使っている | `vw` はスクロールバー幅を含む。`100%` にする |
| `minmax(240px, 1fr)` | `minmax(min(100%, 240px), 1fr)` にする |
| 負の margin | 値を見直す |

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| スマホで文字が極小 | viewport の meta がない | `<meta name="viewport" ...>` を追加 |
| メディアクエリが効かない | `min-width` と `max-width` が混在して打ち消し合っている | どちらかに統一 |
| メディアクエリが効かない② | 書く順序が逆 | `min-width` は**小さい順**に書く |
| iOSで入力時にズームする | 入力欄の font-size が16px未満 | 16px以上にする |
| 横スクロールが出る | はみ出し要素がある | `* { outline: 1px solid red }` で特定 |
| `100vh` がスマホで変 | アドレスバーの分がずれる | `100dvh` を使う（モダンブラウザ） |
| PCで見ると余白がスカスカ | `max-width` を設定していない | コンテナに `max-width` |

### メディアクエリの順序

```css
/* ✅ 正しい：小さい順 */
.box { font-size: 16px; }
@media (min-width: 768px)  { .box { font-size: 18px; } }
@media (min-width: 1024px) { .box { font-size: 20px; } }

/* ❌ 間違い：大きい順に書くと、768px以上でも常に18pxで上書きされる */
@media (min-width: 1024px) { .box { font-size: 20px; } }
@media (min-width: 768px)  { .box { font-size: 18px; } }
```

同じ詳細度なら**後に書いたほうが勝つ**ため、順序が意味を持ちます。

---

## 🤖 AIに聞いてみよう

### ① 横スクロールの原因を切り分けさせる

```text
レスポンシブ対応をしていますが、スマホ幅で横スクロールが発生します。

【画面幅】375px
【HTML】
（該当部分を貼る）
【CSS】
（該当部分を貼る）

1. 横スクロールが出る原因として考えられるものを、可能性の高い順に5つ挙げてください
2. それぞれについて、開発者ツールでどう確認すればよいか教えてください
3. すぐに修正コードを出すのではなく、私が原因を特定できる手順を示してください
```

### ② clamp() の値を設計させる

```text
CSSの clamp() を使って、レスポンシブな文字サイズと余白を設計したいです。

【条件】
- 見出しh1: スマホ(375px)で28px、PC(1440px)で48px にしたい
- セクションの上下余白: スマホで48px、PCで112px にしたい

1. それぞれの clamp() の値を計算して教えてください
2. 計算の考え方（vw の値をどう求めるか）も説明してください
3. clamp() を使うべきでないケースがあれば教えてください
```

> 💡 計算方法を教わったら、**次からは自分で計算**してください。仕組みがわかれば暗算でも近い値が出せます。

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

以下を実装してください。

- [ ] `.about` を、768px以上で2カラムになるようにする（見出しは全幅）
- [ ] `.info`（アクセス情報）を、600px未満では1カラム（ラベルの下に値）にする
- [ ] `.site-footer` の上下余白を `clamp(32px, 6vw, 56px)` にする
- [ ] 320px 幅で横スクロールが出ないことを確認する

### 演習2（必須）

以下のCSSの問題点を、それぞれ指摘してください。

```css
/* ① */
.container { width: 1200px; margin: 0 auto; }

/* ② */
@media (min-width: 1024px) { .box { font-size: 20px; } }
@media (min-width: 768px)  { .box { font-size: 18px; } }

/* ③ */
input { font-size: 14px; }

/* ④ */
.hero { width: 100vw; }

/* ⑤ */
.cards { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
```

<details>
<summary>答えを見る</summary>

① **固定幅なので、1200px未満の画面で横スクロールが出る。** `max-width: 1200px` にする。

② **順序が逆。** 同詳細度では後勝ちなので、768px以上のとき常に18pxになり、1024px以上の20pxが効かない。`min-width` は小さい順に書く。

③ **iOS Safari で、タップ時に画面がズームする。** 入力欄の font-size は16px以上にする。

④ **`100vw` はスクロールバーの幅を含む**ため、縦スクロールバーがあると横に数px はみ出す。`width: 100%` にする。

⑤ **画面幅320pxのとき、320px + padding ではみ出す。** `minmax(min(100%, 320px), 1fr)` にする。

</details>

### 演習3（挑戦）

**ハンバーガーメニュー**をCSSだけで作ってください（JavaScript不使用）。

- [ ] 768px未満では、ナビを隠し、☰ ボタンを表示する
- [ ] チェックボックス（`<input type="checkbox">`）と `:checked` 疑似クラスを使って開閉する
- [ ] 768px以上では、通常の横並びナビに戻す
- [ ] キーボード（Tab と Enter）でも操作できることを確認する

> 💡 ヒント：`input:checked ~ nav { display: block; }` のように、**兄弟セレクタ（`~`）** を使います。
> チェックボックス自体は `position: absolute; opacity: 0;` で隠し、`<label>` をボタンとして見せます。
>
> 調べるキーワード：`チェックボックスハック CSS`
>
> ⚠️ ただし実務では、アクセシビリティの観点から**JavaScriptで実装するのが望ましい**とされています（`aria-expanded` を切り替えるため）。第2部で作り直します。

---

## ✅ 章末チェック

- [ ] viewport の meta タグの役割を説明できる
- [ ] モバイルファーストで書く理由を3つ言える
- [ ] `min-width` と `max-width` を混ぜてはいけない理由を言える
- [ ] メディアクエリを小さい順に書く理由を説明できる
- [ ] `clamp()` の3つの引数の意味を言える
- [ ] `minmax(min(100%, 240px), 1fr)` を使う理由を説明できる
- [ ] 入力欄の font-size を16px以上にする理由を言える
- [ ] 横スクロールの原因を特定する方法を知っている
- [ ] カフェLPが320px〜1440pxで崩れない

---

**前 → [1-9 CSS Grid でページ全体を組む](01-09-grid.md)　｜　次 → [1-11 position と重なり順](01-11-position.md)**
