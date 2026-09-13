# 1-7 文字と色のデザイン基礎

> 🎯 **このレッスンのゴール**
> - 読みやすい文字組みの数値基準を持つ
> - 色の指定方法を使い分け、コントラスト比を確認できる
> - Webフォントを導入できる
> - カフェLPの文字と色を仕上げる

所要 90分 / 難度 🟢
完成コード: [`code/01-07/`](../code/01-07/)

---

## 📖 「なんかダサい」の正体は、だいたい3つ

デザインの才能がなくても、以下3つを数値で守るだけで**急に整って見えます**。

| 原因 | 対策の数値基準 |
| --- | --- |
| 行間が狭い | 本文の `line-height` は **1.7〜1.9** |
| 1行が長すぎる | 1行 **35〜45文字**（日本語）に収める |
| 文字サイズの差がない | 見出しと本文に**1.5倍以上**の差をつける |

---

## ✍️ 手を動かす① ─ 文字の基本

### font-size の単位

| 単位 | 意味 | 使いどころ |
| --- | --- | --- |
| `px` | 固定ピクセル | 迷ったらこれ。わかりやすい |
| `rem` | ルート（html）のfont-sizeの倍数 | **推奨**。ユーザーの設定を尊重できる |
| `em` | 親要素のfont-sizeの倍数 | 入れ子で倍々になるので注意 |
| `%` | 親の何% | em とほぼ同じ |

```css
html { font-size: 16px; }  /* ブラウザ初期値と同じ */

h1   { font-size: 2rem; }    /* 32px */
p    { font-size: 1rem; }    /* 16px */
small{ font-size: 0.875rem; }/* 14px */
```

> ⚠️ **`em` の落とし穴**
>
> ```css
> .parent { font-size: 1.2em; }  /* 親の1.2倍 */
> .child  { font-size: 1.2em; }  /* さらに1.2倍 = 1.44倍 */
> ```
>
> 入れ子になると**倍々に膨らみます**。`rem` は常にルート基準なので、この事故が起きません。

> 💡 **この教材では、わかりやすさを優先して `px` を基本に使い、必要な箇所で `rem` を使います。**
> 実務では `rem` 主体のプロジェクトが多いので、両方読めるようにしておいてください。

### line-height（行間）

```css
body { line-height: 1.8; }   /* 単位なしで書く */
h1   { line-height: 1.3; }   /* 見出しは詰め気味に */
```

> ⚠️ **`line-height` は単位なしで書きます。**
>
> ```css
> body { line-height: 24px; }  /* ❌ 子要素にも 24px が継承され、文字が大きい要素で詰まる */
> body { line-height: 1.5; }   /* ✅ 各要素の font-size の1.5倍になる */
> ```

**目安**

| 対象 | line-height |
| --- | --- |
| 日本語の本文 | 1.7〜1.9 |
| 見出し | 1.2〜1.4 |
| ボタンのラベル | 1 |

日本語は英語より行間を広めに取ります。漢字・ひらがなの密度が高いためです。

### font-family（フォント指定）

```css
body {
  font-family: "Hiragino Sans", "Noto Sans JP", system-ui, sans-serif;
}
```

**左から順に試され、その環境にあるフォントが使われます。** 最後は必ず総称名（`sans-serif` / `serif` / `monospace`）で締めます。

| 総称名 | 意味 | 印象 |
| --- | --- | --- |
| `sans-serif` | ゴシック体 | 現代的・読みやすい |
| `serif` | 明朝体 | 上品・伝統的 |
| `monospace` | 等幅 | コード・データ |

> 💡 **フォント名にスペースが含まれる場合は、クォートで囲みます。**
> `"Noto Sans JP"` はクォート必要、`sans-serif` は不要。

### 文字まわりのその他

```css
.title {
  font-weight: 700;          /* 太さ。400=標準, 700=太字 */
  letter-spacing: .08em;     /* 字間。日本語は .02〜.1em が目安 */
  text-align: center;        /* left / center / right / justify */
  text-decoration: underline;/* 下線 */
  text-wrap: balance;        /* 見出しの改行位置をきれいに（モダンブラウザ） */
}
```

> 💡 **`text-wrap: balance` は見出しに効きます。**
> 2行になった見出しの1行目だけが長い、という不格好な折り返しを自動で均等にしてくれます。**見出しには付けておく価値があります。**

---

## ✍️ 手を動かす② ─ 色の指定

### 4つの書き方

```css
color: #8a5a3b;                    /* 16進数（最も一般的） */
color: #85a;                       /* 3桁省略形（#8855aa と同じ） */
color: rgb(138 90 59);             /* RGB */
color: rgb(138 90 59 / 50%);       /* 透明度つき */
color: hsl(25 40% 39%);            /* 色相・彩度・明度 */
```

### `hsl` を知っておくと便利

```css
hsl(色相 彩度 明度)
     0-360  0-100%  0-100%
```

**同系色のバリエーションを作るのが圧倒的に楽です。**

```css
:root {
  --accent:       hsl(25 40% 39%);   /* ベース */
  --accent-light: hsl(25 40% 55%);   /* 明度だけ上げる */
  --accent-dark:  hsl(25 40% 25%);   /* 明度だけ下げる */
  --accent-pale:  hsl(25 40% 94%);   /* 背景用の淡い色 */
}
```

16進数だと、明るくした色を自分で計算する必要があります。**hsl なら明度の数字を変えるだけ**です。

### 配色の作り方（初学者向けの安全策）

**色は3つまで。** これを守るだけで破綻しません。

| 役割 | 割合 | 例（カフェLP） |
| --- | --- | --- |
| **ベースカラー**（背景） | 70% | `#fdfcfa`（生成り） |
| **メインカラー**（文字） | 25% | `#2b2723`（濃茶） |
| **アクセントカラー**（強調） | 5% | `#8a5a3b`（茶） |

> ⚠️ **アクセントカラーを使いすぎないでください。**
> 「目立たせたい」が積み重なると、何も目立たなくなります。アクセントは**ページ全体の5%程度**が目安です。

### コントラスト比を必ず確認する

**文字色と背景色のコントラストが足りないと、読めません。**

| 対象 | 必要なコントラスト比（WCAG AA） |
| --- | --- |
| 通常の文字（18px未満） | **4.5:1 以上** |
| 大きい文字（18px以上の太字 / 24px以上） | **3:1 以上** |

**確認方法**

1. Chrome の開発者ツールで要素を選択
2. Styles パネルの `color` の色見本をクリック
3. カラーピッカーに **Contrast ratio** が表示される
4. ✅ が付いていればOK、⚠ なら不足

> ⚠️ **薄いグレーの文字は、初学者がやりがちな失敗の代表です。**
> `#999` を白背景に置くとコントラスト比は 2.85:1 で、**基準を満たしません**。
> おしゃれに見えても、高齢者や視力の弱い人には読めません。`#6b6259` 程度までにしてください。

---

## ✍️ 手を動かす③ ─ Webフォントを入れる

デバイスに入っていないフォントも、Webから読み込めば使えます。

### Google Fonts の使い方

1. https://fonts.google.com/ を開く
2. 使いたいフォントを選ぶ（例：`Noto Sans JP`）
3. ウェイト（太さ）を選ぶ → **必要な分だけ**
4. 右側の `<link>` タグをコピー
5. HTMLの `<head>` に貼る（`<link rel="stylesheet" href="css/style.css">` **より前**に）

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&family=Marcellus&display=swap">
```

```css
:root {
  --font-base: "Noto Sans JP", "Hiragino Sans", sans-serif;
  --font-en:   "Marcellus", serif;
}
```

> ⚠️ **日本語Webフォントは重いです。**
> 日本語は文字数が多いため、1ウェイトで 1〜3MB になることがあります。
> **必要なウェイトだけ**を選んでください。400 と 700 の2つで十分です。5つ選ぶと、それだけでページが数MB重くなります。

> 💡 `display=swap` を付けると、フォント読み込み中は代替フォントで表示されます。付けないと、**読み込み完了まで文字が見えません**（FOIT）。必ず付けてください。

---

## ✍️ 手を動かす④ ─ カフェLPの文字と色を仕上げる

### HTMLに Webフォントを追加

`index.html` の `<head>` の `<link rel="stylesheet" href="css/style.css">` の**直前**に追加してください。

```html
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&family=Marcellus&display=swap">
```

### CSSを更新

`style.css` の `:root` を、以下に置き換えてください。

```css
:root {
  /* ---- 色（hsl で管理する） ---- */
  --color-bg:         hsl(35 33% 98%);
  --color-surface:    hsl(0 0% 100%);
  --color-text:       hsl(25 12% 15%);
  --color-text-light: hsl(25 8%  42%);
  --color-accent:     hsl(25 40% 39%);
  --color-accent-dark:hsl(25 40% 28%);
  --color-accent-pale:hsl(25 40% 95%);
  --color-border:     hsl(30 20% 88%);

  /* ---- フォント ---- */
  --font-base: "Noto Sans JP", "Hiragino Sans", system-ui, sans-serif;
  --font-en:   "Marcellus", "Times New Roman", serif;

  /* ---- 文字サイズ（タイプスケール） ---- */
  --fs-xs:  13px;
  --fs-sm:  14px;
  --fs-base:16px;
  --fs-lg:  18px;
  --fs-xl:  24px;
  --fs-2xl: 32px;
  --fs-3xl: 44px;
}
```

続いて、ベーススタイルを更新します。

```css
body {
  font-family: var(--font-base);
  font-size: var(--fs-base);
  line-height: 1.9;
  color: var(--color-text);
  background-color: var(--color-bg);
  -webkit-font-smoothing: antialiased;
}

h1, h2, h3 {
  line-height: 1.4;
  font-weight: 700;
  text-wrap: balance;
}

/* ヒーローの見出しだけ英字フォントの雰囲気に寄せる */
.hero h1 {
  font-size: var(--fs-3xl);
  letter-spacing: .04em;
  margin-bottom: 16px;
}

.hero p {
  font-size: var(--fs-lg);
}

/* セクション見出しは英字・小さく・字間を広く */
main > section > h2 {
  font-family: var(--font-en);
  font-size: var(--fs-sm);
  font-weight: 400;
  letter-spacing: .28em;
  color: var(--color-accent);
  margin-bottom: 20px;
}

.menu-item h3 {
  font-size: var(--fs-lg);
  margin-bottom: 2px;
}

.price {
  font-family: var(--font-en);
  font-size: var(--fs-lg);
  color: var(--color-accent);
  margin-bottom: 8px;
}

.logo {
  font-family: var(--font-en);
  font-size: var(--fs-lg);
  letter-spacing: .18em;
}

/* 本文の1行を長くしすぎない */
.about p,
.hero p {
  max-width: 34em;
}

/* ボタン */
.btn {
  display: inline-block;
  padding: 14px 36px;
  font-size: var(--fs-sm);
  letter-spacing: .1em;
  color: var(--color-accent);
  border: 1px solid var(--color-accent);
  transition: background-color .2s, color .2s;
}

.btn:hover {
  background-color: var(--color-accent);
  color: #fff;
}

@media (prefers-reduced-motion: reduce) {
  .btn { transition: none; }
}
```

保存して確認してください。**フォントと配色が入り、一気にデザインらしくなります。**

### `max-width: 34em` の意味

`em` は「その要素の文字サイズの倍数」です。`34em` = 文字34個分の幅。

> 💡 **日本語は 35〜45文字、英語は 60〜75文字**が、目が疲れずに読める1行の長さと言われています。
> 幅の広いディスプレイでは、本文が1行100文字を超えて非常に読みにくくなります。`max-width` で止めてください。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| Webフォントが反映されない | `<link>` が style.css より後にある / フォント名のつづり違い | 順番と、Google Fonts が示す正確な名前を確認 |
| 文字が一瞬見えない | `display=swap` がない | URLの末尾に `&display=swap` |
| ページが重い | 日本語フォントのウェイトを選びすぎ | 400 と 700 だけにする |
| 入れ子で文字が巨大化 | `em` を使っている | `rem` か `px` にする |
| 文字が読みにくい | コントラスト不足 | 開発者ツールで比率を確認（4.5:1以上） |
| 行間が詰まって見える | `line-height` に単位を付けている | 単位なしで書く |
| 見出しの折り返しが不格好 | — | `text-wrap: balance` を付ける |

---

## 🤖 AIに聞いてみよう

### ① 配色を提案させ、コントラストを検証させる

```text
架空のカフェのWebサイトを作っています。

【店のイメージ】
- 住宅街の小さな自家焙煎コーヒースタンド
- 落ち着いた、木と光の雰囲気
- ターゲットは30〜50代

この店に合う配色を、以下の形式で3案提案してください。

各案について:
- ベースカラー（背景）/ メインカラー（文字）/ アクセントカラー
- それぞれ hsl() 形式と16進数の両方
- 文字色と背景色のコントラスト比（WCAG AAを満たすか明記）
- その配色が与える印象

また、私が自分でコントラスト比を確認する方法も教えてください。
```

> ⚠️ AIが答えたコントラスト比は**必ず自分で検算**してください。計算を間違えることがあります。
> 検証サイト：https://webaim.org/resources/contrastchecker/

### ② 自分のタイポグラフィをレビューさせる

```text
以下は、私が書いたCSSのタイポグラフィ設定です。

（該当部分を貼る）

次の観点でレビューしてください。

1. 日本語サイトとして、行間・字間・文字サイズは適切か
2. 見出しと本文のコントラスト（サイズ差）は十分か
3. タイプスケール（文字サイズの段階）の設計に問題はないか
4. アクセシビリティ上の問題

修正後のコードは書かず、指摘だけをお願いします。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

以下を実装してください。

- [ ] `.site-footer` の文字色を `--color-text-light` にする
- [ ] `.info dt`（住所・営業時間などのラベル）を英字フォント・12px・字間 `.15em` にする
- [ ] `.menu-item p`（説明文）の `max-width` を `28em` にする
- [ ] `.req`（必須マーク）の色をアクセントカラーにし、12px にする

### 演習2（必須）

以下のコントラスト比を、開発者ツールで確認してください。

| 文字色 | 背景色 | 比率 | AA基準（4.5:1）を満たすか |
| --- | --- | --- | --- |
| `#999999` | `#ffffff` | | |
| `#767676` | `#ffffff` | | |
| `#6b6259` | `#fdfcfa` | | |
| `#8a5a3b` | `#ffffff` | | |

<details>
<summary>答えを見る</summary>

| 文字色 | 背景色 | 比率 | 判定 |
| --- | --- | --- | --- |
| `#999999` | `#ffffff` | 約 2.85:1 | ❌ 不足 |
| `#767676` | `#ffffff` | 約 4.54:1 | ✅ ぎりぎり合格 |
| `#6b6259` | `#fdfcfa` | 約 6.5:1 | ✅ 合格 |
| `#8a5a3b` | `#ffffff` | 約 6.2:1 | ✅ 合格 |

**`#767676` が、白背景で AA を満たす最も薄いグレー**として知られています。これより薄くしたくなったら、それは基準を割っています。

</details>

### 演習3（挑戦）

`hsl()` を使って、アクセントカラーの**5段階のトーン**を作ってください。

- [ ] `--accent-50`（最も淡い・背景用）
- [ ] `--accent-100`
- [ ] `--accent-500`（ベース）
- [ ] `--accent-700`
- [ ] `--accent-900`（最も濃い）

**色相と彩度は固定し、明度だけを変える**のがコツです。作ったら、ボタンの hover 色などに使ってみてください。

---

## ✅ 章末チェック

- [ ] 本文の line-height の目安（1.7〜1.9）を言える
- [ ] `line-height` を単位なしで書く理由を説明できる
- [ ] `em` と `rem` の違いを説明できる
- [ ] 色は3つまで、アクセントは5%程度、という原則を知っている
- [ ] コントラスト比の基準（4.5:1）と確認方法を言える
- [ ] Webフォントで `display=swap` が必要な理由を言える
- [ ] 1行の最適な文字数（日本語35〜45文字）を知っている
- [ ] カフェLPの文字と色が仕上がった

---

**前 → [1-6 ボックスモデルを完全に理解する](01-06-box-model.md)　｜　次 → [1-8 Flexbox で横並びを制する](01-08-flexbox.md)**
