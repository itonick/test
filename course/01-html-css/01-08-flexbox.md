# 1-8 Flexbox で横並びを制する

> ◎ **このレッスンのゴール**
> - Flexbox の「親に指定するもの / 子に指定するもの」を区別できる
> - 主軸と交差軸の考え方を身につける
> - 縦横中央寄せを1秒で書けるようになる
> - カフェLPのヘッダーとメニューを横並びにする

所要 150分 / 難度 🟡
完成コード: [`code/01-08/`](../code/01-08/)

---

## 📸 完成イメージ

```
【Before】縦一列                【After】Flexbox
┌──────────────┐              ┌────────────────────────────┐
│ KOMOREBI     │              │ KOMOREBI  MENU ABOUT ACCESS│
│ MENU         │              ├────────────────────────────┤
│ ABOUT        │      →       │ ┌────┐ ┌────┐ ┌────┐      │
│ ACCESS       │              │ │写真│ │写真│ │写真│      │
├──────────────┤              │ │ドリ│ │ラテ│ │ケー│      │
│ ┌────┐       │              │ └────┘ └────┘ └────┘      │
│ │写真│       │              └────────────────────────────┘
│ └────┘       │
```

---

## 📖 Flexbox は「1方向に並べる」ための道具

かつて横並びは `float` で作っていましたが、回り込み解除など面倒な手当てが必要でした。**いまは Flexbox（と Grid）を使います。**

**Flexbox の鉄則**

> **親に `display: flex` を書くと、直下の子が横に並ぶ。**

```html
<ul class="menu-list">   ← 親（Flexコンテナ）
  <li>A</li>             ← 子（Flexアイテム）
  <li>B</li>             ← 子
  <li>C</li>             ← 子
</ul>
```

```css
.menu-list {
  display: flex;   /* ← 親に書く。子には書かない */
}
```

> ⚠️ **最頻出のミス：子に `display: flex` を書いてしまう。**
> 「横に並べたい要素」ではなく、**「それらを包んでいる親」**に書きます。
> 並ばないときは、まずこれを疑ってください。

---

## ✍️ 手を動かす① ─ 主軸と交差軸

Flexbox を理解する鍵は、**2つの軸**です。

```
flex-direction: row（初期値・横並び）

     主軸（main axis）→
   ┌─────────────────────────┐
 ↓ │  ┌───┐ ┌───┐ ┌───┐     │
交 │  │ A │ │ B │ │ C │     │
差 │  └───┘ └───┘ └───┘     │
軸 └─────────────────────────┘


flex-direction: column（縦並び）

     交差軸 →
   ┌──────────┐
 ↓ │ ┌───┐    │
主 │ │ A │    │
軸 │ └───┘    │
   │ ┌───┐    │
   │ │ B │    │
   │ └───┘    │
   └──────────┘
```

| プロパティ | 効く軸 | 覚え方 |
| --- | --- | --- |
| `justify-content` | **主軸**（並んでいる方向） | ジャスティファイ＝並び方向の配置 |
| `align-items` | **交差軸**（直角の方向） | アライン＝そろえる |

> 💡 **`flex-direction: column` にすると、この2つの意味が入れ替わります。**
> 「横並びのとき justify は横、縦並びのとき justify は縦」。ここが混乱の元です。
> **「justify は並んでいる方向」**とだけ覚えてください。

---

## ✍️ 手を動かす② ─ 親に指定するプロパティ

```css
.parent {
  display: flex;

  flex-direction: row;         /* row | row-reverse | column | column-reverse */
  justify-content: flex-start; /* 主軸方向の配置 */
  align-items: stretch;        /* 交差軸方向の配置 */
  flex-wrap: nowrap;           /* nowrap | wrap */
  gap: 16px;                   /* 子同士のすき間 */
}
```

### `justify-content`（主軸の配置）

```
flex-start      [A][B][C]・・・・・・・
center          ・・・[A][B][C]・・・
flex-end        ・・・・・・・[A][B][C]
space-between   [A]・・・[B]・・・[C]     ← 両端に寄せて等間隔
space-around    ・[A]・・[B]・・[C]・     ← 各要素の周りに等しい余白
space-evenly    ・・[A]・・[B]・・[C]・・ ← すき間が全部同じ
```

**最もよく使うのは `space-between`**（ロゴを左、ナビを右）と `center` です。

### `align-items`（交差軸の配置）

```
stretch（初期値）    flex-start        center           flex-end
┌───┬───┬───┐      ┌───┬───┬───┐    ┌───┬───┬───┐   ┌───┬───┬───┐
│ A │ B │ C │      │ A │ B │ C │    │   │   │   │   │   │   │   │
│   │   │   │      └───┴───┤   │    │ A │ B │ C │   │   │ B │   │
│   │   │   │              └───┘    │   │   │   │   │ A │   │ C │
└───┴───┴───┘                       └───┴───┴───┘   └───┴───┴───┘
高さが揃う          上揃え           中央揃え         下揃え
```

### `gap`（すき間）

```css
gap: 16px;          /* 縦横とも16px */
gap: 24px 16px;     /* 行間24px 列間16px */
```

> 💡 **`gap` は最高の発明です。**
> かつては `margin-right` を付けて最後の要素だけ打ち消す、という面倒な処理が必要でした。
> `gap` なら**要素間だけ**に余白が入り、両端には入りません。マージンの相殺も起きません。**余白は gap で作る**を基本にしてください。

### `flex-wrap`（折り返し）

```css
flex-wrap: nowrap;  /* 初期値。折り返さず、縮んでしまう */
flex-wrap: wrap;    /* 入りきらなければ次の行へ */
```

> ⚠️ **初期値は `nowrap` です。**
> 3つのカードを横並びにしたとき、スマホ幅では**折り返さずに潰れます**。
> レスポンシブを考えるなら、`flex-wrap: wrap` をセットで書く癖をつけてください。

---

## ✍️ 手を動かす③ ─ 子に指定するプロパティ

```css
.child {
  flex-grow: 0;    /* 余ったスペースを分け合う比率。初期値0（伸びない） */
  flex-shrink: 1;  /* 足りないときに縮む比率。初期値1（縮む） */
  flex-basis: auto;/* 基準の幅 */

  flex: 1;         /* 上の3つのショートハンド（= 1 1 0%） */
  align-self: center; /* この子だけ交差軸の配置を変える */
  order: 2;        /* 並び順を変える（HTMLの順序は変えずに） */
}
```

### よく使う `flex` の値

| 書き方 | 意味 |
| --- | --- |
| `flex: 1` | **余白を均等に分け合って伸びる**（最頻出） |
| `flex: 0 0 auto` | 伸びも縮みもしない（中身の幅を保つ） |
| `flex: 0 0 280px` | 常に280px固定 |
| `flex: 2` / `flex: 1` | 2:1 の比率で幅を取る |

**典型例：サイドバー固定・本文可変**

```css
.layout { display: flex; gap: 32px; }
.sidebar { flex: 0 0 240px; }  /* 240px 固定 */
.main    { flex: 1; }          /* 残り全部 */
```

> ⚠️ **`flex-shrink` の初期値は 1 です。**
> つまり、何も指定しないと**子は縮みます**。「画像が潰れる」の原因はこれです。
> 潰したくない要素には `flex-shrink: 0;` を付けてください。

---

## ✍️ 手を動かす④ ─ 縦横中央寄せ

CSSで長年の難問だった「縦横中央」が、Flexboxで3行になりました。

```css
.center {
  display: flex;
  justify-content: center;  /* 横中央 */
  align-items: center;      /* 縦中央 */
  min-height: 400px;        /* 高さがないと中央にならない */
}
```

> ⚠️ **親に高さがないと縦中央になりません。**
> 高さが中身ぴったりなら、「中央」も中身の位置と同じだからです。`min-height` や `height` を指定してください。

> 💡 Grid ならもっと短く書けます（レッスン1-9）。
>
> ```css
> .center { display: grid; place-items: center; min-height: 400px; }
> ```

---

## ✍️ 手を動かす⑤ ─ カフェLPを横並びにする

### ① ヘッダー：ロゴを左、ナビを右

`style.css` のヘッダー部分を、以下に置き換えてください。

> 🖊 **手で打ってください。** 完成形は `code/01-08/css/style.css`（📋 コピペ可）。

```css
/* ==========================================================
   5. ヘッダー
   ========================================================== */

.site-header {
  display: flex;
  justify-content: space-between;  /* ロゴを左端、ナビを右端へ */
  align-items: center;             /* 高さを中央で揃える */
  flex-wrap: wrap;
  gap: 12px 24px;
  padding-block: 20px;
  border-bottom: 1px solid var(--color-border);
}

.logo {
  font-family: var(--font-en);
  font-size: var(--fs-lg);
  letter-spacing: .18em;
  margin: 0;
}

.global-nav ul {
  display: flex;
  gap: 24px;
}

.global-nav a {
  font-size: var(--fs-sm);
  letter-spacing: .1em;
  padding-block: 4px;
  border-bottom: 1px solid transparent;
  transition: border-color .2s;
}

.global-nav a:hover {
  border-bottom-color: var(--color-accent);
}
```

保存して確認してください。**ロゴが左、ナビが右に並びます。**

> 🆘 **ここで詰まったら**（横並びにならない ─ Flexbox最頻出）
> - **まず確認**：`display: flex` を付けたのは**並べたい要素の「親」**か？（子に付けても並びません）。ここでは `.site-header`（親）に付け、その直下の子が横に並びます
> - **開発者ツールで確認**：F12 → 対象要素を選び、右の Styles に `display: flex` が**打ち消し線なし**で効いているか。子が縦に落ちるなら幅が大きすぎて折り返している可能性（`flex-wrap` と子の `width` を見直す）
> - **直らなければ、AIにこう聞く**（HTMLの該当部分とCSSを貼る）：
>   「Flexboxで横並びにしたいのに縦に並びます。どの要素に何を指定すべきか、私のコードのどこが原因か教えてください」

### ② メニュー：カードを3列に

```css
/* ==========================================================
   7. メニュー
   ========================================================== */

.menu-list {
  display: flex;
  flex-wrap: wrap;        /* 狭い画面では折り返す */
  gap: 32px;
  margin-top: 40px;
}

.menu-item {
  flex: 1 1 240px;        /* 最低240px、余れば均等に伸びる */
  padding: 0;
  border-bottom: none;
}

.menu-item img {
  aspect-ratio: 1 / 1;    /* 正方形に切り抜く */
  object-fit: cover;      /* 縦横比を保ったまま埋める */
  margin-bottom: 16px;
}

.menu-item h3 {
  font-size: var(--fs-lg);
  margin-bottom: 2px;
}
```

### `object-fit` を覚える

画像の縦横比がバラバラでも、**カードの見た目を揃えられます**。

```css
img {
  aspect-ratio: 1 / 1;   /* 表示領域を正方形にする */
  object-fit: cover;     /* はみ出す部分を切り取って埋める */
}
```

| 値 | 挙動 |
| --- | --- |
| `cover` | **枠を埋める**。はみ出しは切り取られる（写真向き） |
| `contain` | **全体が収まる**。余白ができる（ロゴ向き） |
| `fill` | 引き伸ばして埋める（**歪むので基本使わない**） |

> 💡 これを知らないと、「写真のサイズがバラバラでレイアウトが崩れる」問題に延々と悩まされます。
> **`aspect-ratio` + `object-fit: cover` はセットで暗記**してください。

### ③ ヒーロー：中央寄せ

```css
/* ==========================================================
   6. ヒーロー
   ========================================================== */

.hero {
  display: flex;
  flex-direction: column;
  align-items: center;      /* 交差軸（横）方向の中央 */
  text-align: center;
  gap: 16px;
  padding-block: 96px;
}

.hero img {
  width: 100%;
  aspect-ratio: 16 / 7;
  object-fit: cover;
  margin-bottom: 24px;
}

.hero h1 {
  font-size: var(--fs-3xl);
  margin: 0;
}

.hero .btn {
  margin-top: 16px;
}
```

> 💡 `flex-direction: column` にしたので、`align-items: center` は**横方向**の中央寄せになります。軸が入れ替わる例です。

### ④ フォームの整列

```css
/* ==========================================================
   10. フォーム
   ========================================================== */

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
  max-width: 560px;
  margin-top: 32px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  border: none;   /* fieldset の枠線を消す */
  padding: 0;
  margin: 0;
}

.field > label,
.field legend {
  font-size: var(--fs-sm);
  font-weight: 500;
}

.field input,
.field select,
.field textarea {
  padding: 12px 14px;
  border: 1px solid var(--color-border);
  border-radius: 2px;
  background: var(--color-surface);
}

.field input:focus,
.field select:focus,
.field textarea:focus {
  outline: 2px solid var(--color-accent);
  outline-offset: 1px;
  border-color: transparent;
}

/* ラジオボタンの行 */
.radio-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 24px;
  align-items: center;
}

.radio-group input { width: auto; }

.radio-group label {
  font-size: var(--fs-sm);
  color: var(--color-text-light);
}

/* チェックボックスの行だけ横並び */
.field-check {
  flex-direction: row;
  align-items: center;
  gap: 10px;
}

.field-check input {
  width: 18px;
  height: 18px;
  flex-shrink: 0;   /* 潰さない */
}

.field-check label {
  font-size: var(--fs-sm);
  font-weight: 400;
}

.btn-submit {
  align-self: flex-start;   /* ボタンだけ左寄せ（幅いっぱいにしない） */
  cursor: pointer;
  background: none;
}

.req {
  color: var(--color-accent);
  font-size: var(--fs-xs);
}
```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| 横に並ばない | 子に `display: flex` を書いている | **親**に書く |
| 横に並ばない（親には書いた） | 子が直下にいない（間に別の要素がある） | HTMLの構造を確認。Flexは**直下の子だけ**に効く |
| 画像が潰れる | `flex-shrink` の初期値が1 | `flex-shrink: 0` を付ける |
| スマホで潰れる | `flex-wrap: nowrap`（初期値） | `flex-wrap: wrap` を付ける |
| 縦中央にならない | 親に高さがない | `min-height` を指定 |
| 高さがバラバラ | `align-items` が `stretch` でない | 初期値の `stretch` に戻す |
| gap が効かない | 古いブラウザ | 現代のブラウザなら効く。効かないなら別原因 |
| `justify-content` が縦に効く | `flex-direction: column` になっている | 主軸が縦になっている |

### デバッグの決定打

**Chrome の開発者ツールで、`display: flex` の要素の横に `flex` バッジが出ます。**

1. Elements タブで親要素を選択
2. HTMLの横の `flex` バッジをクリック
3. **主軸・交差軸の方向、gap、各アイテムの領域が画面上に可視化される**

Flexboxで悩んだら、必ずこれを表示してください。頭で考えるより速く解決します。

---

## 🤖 AIに聞いてみよう

### ① レイアウトの実現方法を相談する

```text
CSSで以下のレイアウトを作りたいです。

【作りたいもの】
- ヘッダー内で、左にロゴ、中央にナビ、右にお問い合わせボタン
- 画面が狭くなったら、ナビは折り返して2行目に
- ロゴは絶対に縮まない

【いま書いているCSS】
（貼る）

1. Flexbox でこれを実現する方法を、考え方から教えてください
2. Grid で作る場合との違いも教えてください
3. どちらを選ぶべきか、理由つきで

完成コードは最後に1つだけ示し、まず考え方を説明してください。
```

### ② 主軸・交差軸の理解を試す

```text
Flexbox の justify-content と align-items の使い分けを、
理解できているか確認したいです。

以下の条件で、それぞれ「どのプロパティに何の値を指定すべきか」を
問う練習問題を6問作ってください。
flex-direction が row のケースと column のケースを混ぜてください。

まだ答えは書かないでください。私が回答したら採点し、
間違えた場合は「なぜ間違えたか」の考え方のズレを指摘してください。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

`.info`（アクセス情報の `<dl>`）を、**ラベルと値が横並び**になるようにしてください。

```
住所      東京都〇〇区〇〇 1-2-3
営業時間  8:00 - 18:00
定休日    水曜日
```

- [ ] `dt` の幅を 96px に固定する（縮まないようにする）
- [ ] `dt` と `dd` を横に並べる
- [ ] 行と行の間に 12px の余白を空ける

> 💡 ヒント：`<dl>` の中で `dt` と `dd` を横に並べるには、`display: flex` と `flex-wrap: wrap` を組み合わせるか、`display: grid` を使います。今回は Flexbox で挑戦してください。

<details>
<summary>ヒントをもっと見る</summary>

`dl` を `display: flex; flex-wrap: wrap;` にし、`dt` を `flex: 0 0 96px;`、`dd` を `flex: 1 1 200px;` にすると、2つで1行になり、次のペアが折り返します。

</details>

### 演習2（必須）

以下のFlexboxが**意図通りに動かない理由**を、それぞれ答えてください。

```html
<!-- ① -->
<div class="wrap">
  <div class="box" style="display: flex;">A</div>
  <div class="box" style="display: flex;">B</div>
</div>

<!-- ② -->
<div class="wrap2">
  <div><img src="icon.png" alt=""></div>
  <p>長いテキスト...</p>
</div>
```
```css
.wrap2 { display: flex; }
/* アイコンが潰れてしまう */

/* ③ */
.center { display: flex; justify-content: center; align-items: center; }
/* 縦中央にならない */

/* ④ */
.cards { display: flex; gap: 20px; }
.card { width: 300px; }
/* スマホで横にはみ出す */
```

<details>
<summary>答えを見る</summary>

① **子に `display: flex` を書いている。** 横に並べたいなら親 `.wrap` に書く。子に書くと「その子の中身」が Flex になるだけ。

② **`flex-shrink` の初期値が 1 なので、画像の入った div が縮んでいる。** `div { flex-shrink: 0; }` を付ける。

③ **親に高さがない。** `min-height: 400px;` などを指定する。高さが中身ぴったりだと、中央も中身の位置と同じになる。

④ **`flex-wrap` が初期値の `nowrap` で、`width: 300px` 固定。** `flex-wrap: wrap;` を親に、`.card` を `flex: 1 1 300px;` にすると折り返す。

</details>

### 演習3（挑戦）

フッターを、以下のレイアウトに作り替えてください。

```
┌──────────────────────────────────────────┐
│ KOMOREBI COFFEE          MENU  ABOUT     │
│ 東京都〇〇区〇〇         ACCESS CONTACT  │
│                                          │
│              © 2026 KOMOREBI COFFEE      │
└──────────────────────────────────────────┘
```

- [ ] 上段を Flexbox で左右に分ける（`space-between`）
- [ ] 右側のリンクも Flexbox で横並びにし、折り返し可能にする
- [ ] 下段のコピーライトは中央寄せ、上に区切り線
- [ ] スマホ幅（400px）で崩れないことを確認する

---

## ✅ 章末チェック

- [ ] `display: flex` は親に書く、と説明できる
- [ ] 主軸と交差軸の違いを図で描ける
- [ ] `justify-content` と `align-items` の使い分けを言える
- [ ] `flex: 1` の意味を説明できる
- [ ] `flex-shrink` の初期値が1であることを知っている
- [ ] `flex-wrap: wrap` を書く理由を言える
- [ ] `aspect-ratio` + `object-fit: cover` の役割を説明できる
- [ ] 開発者ツールの flex バッジを使える
- [ ] カフェLPが横並びレイアウトになった

---

**前 → [1-7 文字と色のデザイン基礎](01-07-typography-color.md)　｜　次 → [1-9 CSS Grid でページ全体を組む](01-09-grid.md)**
