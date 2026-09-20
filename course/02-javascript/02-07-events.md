# 2-7 イベントで「動く」を作る

> ◎ **このレッスンのゴール**
> - `addEventListener` を正しく書ける
> - イベントオブジェクトから必要な情報を取り出せる
> - イベント委譲で、動的に追加した要素にも対応できる
> - モーダル・タブ・ハンバーガーメニューを実装する

所要 150分 / 難度 🟡
完成コード: [`code/02-07/`](../code/02-07/)

---

## ✍️ 手を動かす① ─ addEventListener の基本

```javascript
const btn = document.querySelector("#btn");

btn.addEventListener("click", () => {
  console.log("クリックされました");
});
```

```
要素.addEventListener("イベント名", 実行する関数);
```

### `()` を付けない（最頻出のミス）

```javascript
const handleClick = () => console.log("clicked");

btn.addEventListener("click", handleClick);     // ✅ 関数を渡す
btn.addEventListener("click", handleClick());   // ❌ 即実行され、戻り値(undefined)を渡す
```

> ⚠️ **`()` を付けると、その場で実行されてしまいます。**
> ページを開いた瞬間に1回だけ動いて、あとはクリックしても何も起きない——という症状になります。

> 🆘 **ここで詰まったら**（クリックしても反応しない）
> - **チェック順**：① `btn` は取れているか（`console.log(btn)` が `null` なら 2-6 の要素取得の問題）② `addEventListener` の第2引数に `()` を付けていないか（付けると即実行）③ **後から追加した要素**に効かないなら、親に付ける「イベント委譲」（この後の③）が必要 ④ リスナー自体が動いているか、関数の先頭に `console.log("clicked")` を入れて確認
> - **直らなければ、AIにこう聞く**（HTMLと該当JSを貼る）：
>   「ボタンをクリックしても関数が動きません。要素は取れているはずです。原因の候補を確認手順つきで教えてください」

### 引数を渡したいとき

```javascript
const deleteItem = (id) => console.log(`${id} を削除`);

btn.addEventListener("click", deleteItem);           // ❌ 引数を渡せない
btn.addEventListener("click", deleteItem(5));        // ❌ 即実行
btn.addEventListener("click", () => deleteItem(5));  // ✅ アロー関数で包む
```

### 主なイベント

| イベント名 | 発生タイミング | 主な対象 |
| --- | --- | --- |
| `click` | クリック（タップ含む） | ボタン、リンク、何でも |
| `submit` | フォーム送信 | `<form>` |
| `input` | **入力するたび** | `input` `textarea` |
| `change` | 入力を確定したとき（フォーカスが外れる、選択が変わる） | `input` `select` |
| `keydown` / `keyup` | キーを押した / 離した | document、input |
| `focus` / `blur` | フォーカスが入った / 外れた | フォーム部品 |
| `mouseenter` / `mouseleave` | マウスが乗った / 外れた | 何でも |
| `scroll` | スクロール | window、要素 |
| `resize` | ウィンドウサイズ変更 | window |
| `DOMContentLoaded` | HTMLの読み込み完了 | document |

### `input` と `change` の違い

```javascript
input.addEventListener("input",  (e) => console.log("input:", e.target.value));
input.addEventListener("change", (e) => console.log("change:", e.target.value));
```

「abc」と打つと：

```
input:  a
input:  ab
input:  abc
（フォーカスを外すと）
change: abc
```

| 使い分け | 使うイベント |
| --- | --- |
| 文字数カウント、リアルタイム検索、即時バリデーション | **`input`** |
| セレクトボックスの選択、チェックボックスの切り替え | **`change`** |
| 重い処理（API通信など） | `change`、または `input` + デバウンス（後述） |

---

## ✍️ 手を動かす② ─ イベントオブジェクト

コールバックの第1引数に、**イベントの情報**が渡されます。

```javascript
btn.addEventListener("click", (e) => {
  console.log(e.type);           // "click"
  console.log(e.target);         // 実際にクリックされた要素
  console.log(e.currentTarget);  // リスナーを付けた要素
  console.log(e.target.value);   // input なら入力値
  console.log(e.target.dataset.id);  // data-id 属性の値
});
```

### `target` と `currentTarget` の違い

```html
<button id="btn">
  <span>押して</span>   ← ここをクリックすると
</button>
```

| プロパティ | 値 |
| --- | --- |
| `e.target` | `<span>`（**実際にクリックされたもの**） |
| `e.currentTarget` | `<button>`（**リスナーを付けたもの**） |

> ⚠️ ボタンの中にアイコンやテキストの要素があると、`e.target` はその子要素になります。
> **ボタン自体を取りたいなら `e.currentTarget`**、または `e.target.closest("button")` を使ってください。

### `preventDefault()` — デフォルト動作を止める

```javascript
// フォーム送信でページがリロードされるのを防ぐ
form.addEventListener("submit", (e) => {
  e.preventDefault();
  console.log("送信処理をJSで行う");
});

// リンクの遷移を止める
link.addEventListener("click", (e) => {
  e.preventDefault();
});
```

> 💡 **`submit` イベントで `e.preventDefault()` を書かないと、ページがリロードされて処理が消えます。**
> 「送信ボタンを押すと画面が一瞬光って何も起きない」の原因はこれです。

### `stopPropagation()` — 伝播を止める

イベントは、**子から親へ伝わっていきます**（バブリング）。

```html
<div id="outer">
  <div id="inner">クリック</div>
</div>
```

```javascript
outer.addEventListener("click", () => console.log("outer"));
inner.addEventListener("click", () => console.log("inner"));

// inner をクリックすると
// inner
// outer   ← 親にも伝わる
```

```javascript
inner.addEventListener("click", (e) => {
  e.stopPropagation();   // ここで止まる
  console.log("inner");
});
```

> 💡 **モーダルの「背景をクリックしたら閉じる」を実装するとき**に、この理解が必要になります。
> ただし、`stopPropagation()` は多用しないでください。他の機能を壊すことがあります。
> 多くの場合、`if (e.target === e.currentTarget)` で判定するほうが安全です。

---

## ✍️ 手を動かす③ ─ イベント委譲（重要）

### 問題：後から追加した要素には効かない

```javascript
// ページ読み込み時に存在する .btn にだけリスナーが付く
document.querySelectorAll(".btn").forEach((btn) => {
  btn.addEventListener("click", handleClick);
});

// 後から追加した要素には効かない
list.innerHTML += '<button class="btn">新しいボタン</button>';   // ← 反応しない
```

これは「データを更新して描き直す」設計と**相性が最悪**です。描き直すたびにリスナーを付け直す必要があります。

### 解決：親にリスナーを1つ付ける

```javascript
// 親（ずっと存在する要素）にリスナーを付ける
list.addEventListener("click", (e) => {
  const btn = e.target.closest(".btn");
  if (!btn) return;              // .btn 以外がクリックされたら何もしない

  const id = btn.dataset.id;
  console.log(`${id} がクリックされました`);
});
```

**これが「イベント委譲（イベントデリゲーション）」です。**

| メリット | 説明 |
| --- | --- |
| **後から追加した要素にも効く** | 親にリスナーがあるので |
| **リスナーが1つで済む** | 100個のボタンでもリスナーは1つ |
| **描き直しても付け直し不要** | 親は消えないので |

### `closest()` を覚える

```javascript
e.target.closest(".btn")
```

「クリックされた要素**から親をたどって**、最初に見つかった `.btn`」を返します。見つからなければ `null`。

ボタンの中にアイコンがあっても正しく動きます。

```html
<button class="btn" data-id="1">
  <svg>...</svg>   ← ここをクリックしても
  <span>削除</span>
</button>
```

> 💡 **イベント委譲は、DOM操作の必須テクニックです。**
> ToDoアプリ（2-13）でも、これがないと成立しません。

---

## ✍️ 手を動かす④ ─ 実装1：ハンバーガーメニュー

第1部で保留にしていた、アクセシブルなハンバーガーメニューを作ります。

**HTML**（📋 コピペ可）

```html
<header class="site-header">
  <div class="header-inner">
    <p class="logo">KOMOREBI COFFEE</p>

    <button type="button" class="nav-toggle" id="navToggle"
            aria-expanded="false" aria-controls="globalNav">
      <span class="nav-toggle-bar"></span>
      <span class="visually-hidden">メニューを開く</span>
    </button>

    <nav class="global-nav" id="globalNav" aria-label="メインナビゲーション">
      <ul>
        <li><a href="#menu">MENU</a></li>
        <li><a href="#about">ABOUT</a></li>
        <li><a href="#access">ACCESS</a></li>
        <li><a href="#contact">CONTACT</a></li>
      </ul>
    </nav>
  </div>
</header>
```

**CSS**

```css
/* スクリーンリーダー用（画面には出ないが読み上げられる） */
.visually-hidden {
  position: absolute;
  width: 1px; height: 1px;
  padding: 0; margin: -1px;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
}

.nav-toggle {
  width: 44px; height: 44px;     /* タップしやすい最小サイズ */
  border: none;
  background: none;
  cursor: pointer;
  position: relative;
}

/* 3本線をひとつの要素と疑似要素で作る */
.nav-toggle-bar,
.nav-toggle-bar::before,
.nav-toggle-bar::after {
  position: absolute;
  left: 11px;
  width: 22px; height: 1.5px;
  background: currentColor;
  transition: transform .25s, opacity .25s;
}
.nav-toggle-bar        { top: 21px; }
.nav-toggle-bar::before{ content: ""; top: -7px; left: 0; }
.nav-toggle-bar::after { content: ""; top:  7px; left: 0; }

/* 開いているとき × に変形 */
.nav-toggle[aria-expanded="true"] .nav-toggle-bar         { background: transparent; }
.nav-toggle[aria-expanded="true"] .nav-toggle-bar::before { transform: translateY(7px) rotate(45deg); }
.nav-toggle[aria-expanded="true"] .nav-toggle-bar::after  { transform: translateY(-7px) rotate(-45deg); }

/* ナビ本体：スマホでは隠す */
.global-nav {
  display: none;
  width: 100%;
}
.global-nav.is-open {
  display: block;
}

@media (min-width: 768px) {
  .nav-toggle { display: none; }
  .global-nav { display: block; width: auto; }
}
```

**JavaScript**

```javascript
const navToggle = document.querySelector("#navToggle");
const globalNav = document.querySelector("#globalNav");

if (navToggle && globalNav) {
  const setNavOpen = (isOpen) => {
    navToggle.setAttribute("aria-expanded", String(isOpen));
    globalNav.classList.toggle("is-open", isOpen);
    navToggle.querySelector(".visually-hidden").textContent =
      isOpen ? "メニューを閉じる" : "メニューを開く";
  };

  navToggle.addEventListener("click", () => {
    const isOpen = navToggle.getAttribute("aria-expanded") === "true";
    setNavOpen(!isOpen);
  });

  // ナビ内のリンクを押したら閉じる
  globalNav.addEventListener("click", (e) => {
    if (e.target.closest("a")) setNavOpen(false);
  });

  // Esc キーで閉じる
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setNavOpen(false);
  });
}
```

**アクセシビリティのポイント**

| 実装 | 理由 |
| --- | --- |
| `<button>` を使う | `<div>` だとキーボードで押せない |
| `aria-expanded` | スクリーンリーダーに開閉状態を伝える |
| `aria-controls` | どの要素を制御しているか伝える |
| `.visually-hidden` でラベル | アイコンだけだと何のボタンかわからない |
| Esc キーで閉じる | キーボードユーザーの標準的な期待 |
| 44×44px 以上 | 指でタップできる最小サイズ |

> 💡 **`aria-expanded` を CSS のセレクタに使っている**点に注目してください。
> JSは状態（属性）を変えるだけ、見た目はCSSが決める。**この分離が保守しやすいコードを生みます。**

---

## ✍️ 手を動かす⑤ ─ 実装2：タブUI

```html
<div class="tabs">
  <div class="tab-list" role="tablist">
    <button type="button" role="tab" class="tab is-active"
            data-tab="coffee" aria-selected="true">コーヒー</button>
    <button type="button" role="tab" class="tab"
            data-tab="food" aria-selected="false">フード</button>
    <button type="button" role="tab" class="tab"
            data-tab="tea" aria-selected="false">お茶</button>
  </div>

  <div class="tab-panel is-active" data-panel="coffee">コーヒーの内容</div>
  <div class="tab-panel" data-panel="food">フードの内容</div>
  <div class="tab-panel" data-panel="tea">お茶の内容</div>
</div>
```

```css
.tab-list { display: flex; gap: 4px; border-bottom: 1px solid #ddd; }
.tab {
  padding: 10px 20px;
  border: none;
  background: none;
  cursor: pointer;
  border-bottom: 2px solid transparent;
}
.tab.is-active { border-bottom-color: currentColor; font-weight: 700; }
.tab-panel { display: none; padding: 20px 0; }
.tab-panel.is-active { display: block; }
```

```javascript
const tabList = document.querySelector(".tab-list");

if (tabList) {
  tabList.addEventListener("click", (e) => {
    const tab = e.target.closest(".tab");
    if (!tab) return;

    const name = tab.dataset.tab;

    // すべてのタブとパネルの状態を更新する（全部書き直す発想）
    document.querySelectorAll(".tab").forEach((t) => {
      const active = t.dataset.tab === name;
      t.classList.toggle("is-active", active);
      t.setAttribute("aria-selected", String(active));
    });

    document.querySelectorAll(".tab-panel").forEach((p) => {
      p.classList.toggle("is-active", p.dataset.panel === name);
    });
  });
}
```

> 💡 **「クリックされたものを active にして、他を外す」ではなく、「全部の状態を計算し直す」**と書いています。
> こちらのほうがバグりません。「他を外す」を書き忘れる事故が起きないからです。

---

## ✍️ 手を動かす⑥ ─ 実装3：モーダル

```html
<button type="button" id="openModal">詳細を見る</button>

<div class="modal" id="modal" hidden>
  <div class="modal-overlay" data-close></div>
  <div class="modal-body" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <h2 id="modalTitle">ハンドドリップ</h2>
    <p>その日の豆から、お好みの一杯を選べます。</p>
    <button type="button" class="modal-close" data-close aria-label="閉じる">×</button>
  </div>
</div>
```

```css
.modal { position: fixed; inset: 0; z-index: 1000; display: grid; place-items: center; }
.modal-overlay { position: absolute; inset: 0; background: rgb(0 0 0 / 50%); }
.modal-body {
  position: relative;
  background: #fff;
  padding: 32px;
  max-width: 480px;
  width: calc(100% - 32px);
  border-radius: 4px;
}
.modal-close { position: absolute; top: 8px; right: 8px; width: 44px; height: 44px;
               border: none; background: none; font-size: 24px; cursor: pointer; }
body.modal-open { overflow: hidden; }   /* 背後のスクロールを止める */
```

```javascript
const modal = document.querySelector("#modal");
const openBtn = document.querySelector("#openModal");
let lastFocused = null;

const openModal = () => {
  lastFocused = document.activeElement;   // 開く前のフォーカス位置を覚えておく
  modal.hidden = false;
  document.body.classList.add("modal-open");
  modal.querySelector(".modal-close").focus();   // モーダル内にフォーカスを移す
};

const closeModal = () => {
  modal.hidden = true;
  document.body.classList.remove("modal-open");
  lastFocused?.focus();   // 元の位置にフォーカスを戻す
};

openBtn?.addEventListener("click", openModal);

// 閉じるボタンと背景（data-close が付いた要素）で閉じる
modal?.addEventListener("click", (e) => {
  if (e.target.closest("[data-close]")) closeModal();
});

// Esc キーで閉じる
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && !modal.hidden) closeModal();
});
```

**アクセシビリティのポイント**

| 実装 | 理由 |
| --- | --- |
| `hidden` 属性で開閉 | `display: none` と同じだが、意味が明確 |
| `role="dialog"` `aria-modal="true"` | スクリーンリーダーにモーダルだと伝える |
| 開いたらモーダル内にフォーカス | キーボードユーザーが操作できる |
| 閉じたら元の位置にフォーカス | どこにいたか失わない |
| `body` のスクロールを止める | 背後が動くと混乱する |
| Esc で閉じる | 標準的な期待 |

> 💡 **`hidden` プロパティで開閉するのが最も簡単**です。`modal.hidden = true/false` だけで済みます。

---

## ✍️ 手を動かす⑦ ─ デバウンス（重い処理を間引く）

`input` イベントは打鍵ごとに発火します。毎回検索すると重すぎます。

```javascript
/** 指定時間、呼ばれなくなったら実行する */
const debounce = (fn, delay = 300) => {
  let timerId;
  return (...args) => {
    clearTimeout(timerId);
    timerId = setTimeout(() => fn(...args), delay);
  };
};

// 使い方
const search = (keyword) => {
  console.log("検索:", keyword);
};

const debouncedSearch = debounce(search, 300);

searchInput.addEventListener("input", (e) => {
  debouncedSearch(e.target.value);
});
```

**「abc」と打っても、最後の入力から300ms後に1回だけ実行**されます。

> 💡 `scroll` や `resize` イベントも、同様に間引かないとパフォーマンスに影響します。
> **この `debounce` 関数はコピペで使い回せます。** スニペット集に保存してください。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| ページを開いた瞬間に1回だけ動く | `addEventListener("click", f())` と書いた | `()` を外す |
| クリックしても何も起きない | 要素が取得できていない | `console.log(el)` で確認 |
| 送信するとページがリロードされる | `e.preventDefault()` がない | submit で必ず書く |
| 後から追加した要素が反応しない | 直接リスナーを付けている | イベント委譲にする |
| ボタン内のアイコンを押すと動かない | `e.target` が子要素 | `e.target.closest("button")` |
| 親の処理まで動いてしまう | イベントのバブリング | `stopPropagation()` か `e.target === e.currentTarget` |
| 検索が重い | `input` で毎回処理 | デバウンスする |
| モーダルを開くと背後がスクロールする | body のスクロールを止めていない | `body { overflow: hidden }` |
| キーボードで操作できない | `<div>` をボタンにしている | `<button>` を使う |

---

## 🤖 AIに聞いてみよう

### ① アクセシビリティをレビューさせる

```text
以下は、私が実装したモーダルダイアログです。

【HTML】
（貼る）
【CSS】
（貼る）
【JavaScript】
（貼る）

アクセシビリティの観点で、厳しくレビューしてください。

1. キーボードだけで操作できるか（Tab, Enter, Esc）
2. スクリーンリーダーで正しく認識されるか
3. フォーカス管理（開いたとき、閉じたとき、モーダル内での移動）に問題はないか
4. WAI-ARIA の使い方が正しいか
5. 実装が漏れている一般的な要件はないか

修正後のコードは書かず、指摘だけをお願いします。優先度も付けてください。
```

> 💡 **「フォーカストラップ」**（モーダル内でTabがループする）は、この実装では未対応です。
> AIに聞くと指摘されるはずです。それが正しいレビューです。

### ② イベント委譲の理解を試す

```text
JavaScript のイベント委譲について、理解を確認したいです。

以下の観点を含む問題を5問作ってください。

- なぜ委譲が必要か（後から追加した要素の問題）
- e.target と e.currentTarget の違い
- closest() の役割
- バブリングの順序
- 委譲が使えないイベント（あれば）

コードを読んで出力や挙動を予想する形式にしてください。
まだ答えは書かないでください。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

`dom.html` に以下を追加してください。

- [ ] カテゴリの絞り込みボタン（すべて / コーヒー / フード / お茶）を作る
- [ ] クリックすると `filter.category` を変えて `render()` する
- [ ] 選択中のボタンに `is-active` クラスを付ける
- [ ] 「売り切れを隠す」チェックボックスを作り、`change` で `filter.hideSoldOut` を切り替える
- [ ] **イベント委譲**を使う（ボタン1つずつにリスナーを付けない）

### 演習2（必須）

以下のコードの問題点を、それぞれ指摘して直してください。

```javascript
// ①
btn.addEventListener("click", showModal());

// ②
form.addEventListener("submit", () => {
  saveData();
});

// ③
document.querySelectorAll(".delete-btn").forEach((btn) => {
  btn.addEventListener("click", () => deleteItem(btn.dataset.id));
});
// この後、render() で innerHTML を書き換えている

// ④
searchInput.addEventListener("input", (e) => {
  fetchSearchResults(e.target.value);   // 毎打鍵でAPI通信
});

// ⑤
<div class="btn" onclick="doSomething()">クリック</div>
```

<details>
<summary>答えを見る</summary>

① **`()` が付いている** → ページ読み込み時に即実行される
```javascript
btn.addEventListener("click", showModal);
```

② **`e.preventDefault()` がない** → ページがリロードされ、処理が中断される
```javascript
form.addEventListener("submit", (e) => {
  e.preventDefault();
  saveData();
});
```

③ **`render()` で DOM を作り直すとリスナーが消える** → イベント委譲にする
```javascript
list.addEventListener("click", (e) => {
  const btn = e.target.closest(".delete-btn");
  if (!btn) return;
  deleteItem(btn.dataset.id);
});
```

④ **毎打鍵で通信が飛ぶ** → デバウンスする
```javascript
const debouncedFetch = debounce(fetchSearchResults, 300);
searchInput.addEventListener("input", (e) => debouncedFetch(e.target.value));
```

⑤ **`<div>` はキーボードで押せない。`onclick` 属性はHTMLとJSが混ざる**
```html
<button type="button" class="btn" id="doBtn">クリック</button>
```
```javascript
document.querySelector("#doBtn").addEventListener("click", doSomething);
```

</details>

### 演習3（挑戦）

**モーダルにフォーカストラップを実装**してください。

現在の実装では、モーダルを開いた状態で Tab を押し続けると、**背後のページの要素にフォーカスが移ってしまいます**。

- [ ] モーダル内の「フォーカス可能な要素」を取得する
      （ヒント：`'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])'`）
- [ ] 最後の要素で Tab を押したら、最初の要素に戻す
- [ ] 最初の要素で Shift+Tab を押したら、最後の要素に移す
- [ ] `keydown` イベントで `e.key === "Tab"` を検知する

<details>
<summary>ヒントをもっと見る</summary>

```javascript
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

modal.addEventListener("keydown", (e) => {
  if (e.key !== "Tab") return;

  const focusables = [...modal.querySelectorAll(FOCUSABLE)];
  if (focusables.length === 0) return;

  const first = focusables[0];
  const last = focusables.at(-1);

  if (e.shiftKey && document.activeElement === first) {
    e.preventDefault();
    last.focus();
  } else if (!e.shiftKey && document.activeElement === last) {
    e.preventDefault();
    first.focus();
  }
});
```

> 💡 実務では `<dialog>` 要素を使うと、これらが**ブラウザ標準で実装済み**です。
> `dialog.showModal()` を調べてみてください。ただし、仕組みを知っておく価値はあります。

</details>

---

## ✅ 章末チェック

- [ ] `addEventListener` に `()` を付けてはいけない理由を言える
- [ ] `input` と `change` の違いを説明できる
- [ ] `e.target` と `e.currentTarget` の違いを説明できる
- [ ] `e.preventDefault()` が必要な場面を言える
- [ ] イベント委譲がなぜ必要か説明できる
- [ ] `closest()` の役割を説明できる
- [ ] ハンバーガーメニューを `aria-expanded` 付きで実装できた
- [ ] モーダルのフォーカス管理の必要性を理解した
- [ ] デバウンスの仕組みを説明できる

---

**前 → [2-6 DOM操作で画面を書き換える](02-06-dom.md)　｜　次 → [2-8 フォームバリデーション実装](02-08-validation.md)**
