# 2-13 【総合演習】ToDoアプリを作る

> 🎯 **このレッスンのゴール**
> - 第2部で学んだすべてを1つのアプリにまとめる
> - **人に見せられるWebアプリを1つ完成させる**
> - 自分のコードをレビューし、改善できる

所要 300分（4日に分割推奨） / 難度 🔴
完成コード: [`code/02-13/`](../code/02-13/)

---

## 📸 完成イメージ

```
┌──────────────────────────────────────────┐
│  やること                          [☾]   │
├──────────────────────────────────────────┤
│  [やることを入力して Enter    ] [ 追加 ] │
│                                          │
│  (すべて) 未完了  完了      完了を削除   │
│  ┌────────────────────────────────────┐  │
│  │ ☐  牛乳を買う        4/13 10:05  × │  │
│  ├────────────────────────────────────┤  │
│  │ ☑  ~~提出書類を出す~~ 4/13 09:20  × │  │
│  ├────────────────────────────────────┤  │
│  │ ☐  [編集中の入力欄  ] 4/12 18:44  × │  │
│  └────────────────────────────────────┘  │
│                                          │
│  全 3 件 ／ 未完了 2 件 ／ 完了 1 件      │
└──────────────────────────────────────────┘
```

**実装する機能**

| 機能 | 使う知識 |
| --- | --- |
| タスクの追加 | フォーム、バリデーション（2-8） |
| 完了の切り替え | イベント委譲（2-7）、`map`（2-5） |
| 削除 | `filter`（2-5） |
| **インライン編集** | ダブルクリック、キーボード操作（2-7） |
| 絞り込み | 状態管理（2-6） |
| 完了を一括削除 | `filter`（2-5） |
| 保存・復元 | `localStorage`（2-11） |
| ダークモード | `localStorage`、CSS変数（2-11） |
| タブ間同期 | `storage` イベント（2-11） |

---

## 📖 進め方 ─ 4日に分割する

**一気にやらないでください。** 各Dayの終わりに、必ず動く状態にします。

| Day | やること | 時間 |
| --- | --- | --- |
| **Day 1** | HTML / CSS と、追加・表示 | 90分 |
| **Day 2** | 完了の切り替え・削除・集計 | 60分 |
| **Day 3** | 絞り込み・localStorage・一括削除 | 60分 |
| **Day 4** | インライン編集・ダークモード・仕上げ | 90分 |

> 💡 **Day ごとに Git でコミット**してください（第6部を先取り）。
> 壊したときに戻せます。まだ Git を学んでいなければ、日ごとにフォルダをコピーして残してください。

---

## ✍️ Day 1 ─ HTML / CSS と追加機能

### ファイル構成

```
htdocs/todo/
├─ index.html
├─ css/
│   └─ style.css
└─ js/
    └─ app.js
```

### HTML

完成コード [`code/02-13/index.html`](../code/02-13/index.html) を参照してください（📋 コピペ可）。

> 🆘 **ここで詰まったら**（作り始めたら、どこかで動かなくなった）
> - **鉄則①**：**まず `F12` → Console を開く。** JavaScript が絡む不具合は、9割ここに赤いエラーが出ています（行番号をクリックで該当箇所へ飛べます）
> - **鉄則②**：機能を**1つずつ**追加し、そのたびに動作確認する。「Day 2まで一気に書いたら動かない」より「1機能ごとに確認」のほうが、原因を切り分けやすい
> - **詰まったら、まずそのレッスンに戻る**：追加/削除は 2-6・2-7、保存は 2-11、というように該当レッスンの「🆘」と「つまずきポイント」を見直す
> - **それでもダメなら、AIにこう聞く**（エラー全文＋該当関数＋やりたいことをセットで／2-9で学んだ通り）：
>   「ToDoアプリの○○機能で、△△というエラーが出ます。原因の候補と、確認手順を教えてください（コードを貼る）」

**HTMLで押さえるポイント**

| 実装 | 理由 |
| --- | --- |
| `<form>` で囲む | Enter キーで送信できる |
| `autocomplete="off"` | 過去の入力候補が邪魔になる |
| `.visually-hidden` のラベル | 見た目は出さずに、読み上げには残す |
| `role="alert"` のエラー領域 | エラーが出たとき読み上げられる |
| `aria-live="polite"` の集計 | 件数の変化が読み上げられる |
| `<ul>` でリスト | 「3項目のリスト」と読み上げられる |
| `hidden` 属性の空状態 | JS から `el.hidden = true/false` で切り替え |

### CSS

完成コード [`code/02-13/css/style.css`](../code/02-13/css/style.css) を参照（📋 コピペ可）。

**CSSで押さえるポイント**

- `:root` と `:root[data-theme="dark"]` の**2セットの変数**でテーマを切り替える
- 入力欄の `font-size: 16px`（iOS のズーム防止）
- `:focus-visible` を必ず残す
- `overflow-wrap: anywhere` で長いテキストを折り返す

### JavaScript：追加機能まで

> 🖊 **ここは自分で書いてください。** 2-12 で作った骨組みを埋めていきます。

```javascript
/* ---------- ユーティリティ ---------- */

const escapeHtml = (str) =>
  String(str)
    .replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;").replaceAll("'", "&#39;");

const createId = () =>
  crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2)}`;

/* ---------- 状態 ---------- */

let todos = [];
let filter = "all";

/* ---------- DOM ---------- */

const el = {
  form:  document.querySelector("#addForm"),
  input: document.querySelector("#newTodo"),
  formError: document.querySelector("#formError"),
  list:  document.querySelector("#todoList"),
  empty: document.querySelector("#emptyState"),
};

/* ---------- データ操作 ---------- */

const addTodo = (text) => {
  todos = [...todos, { id: createId(), text, done: false, createdAt: Date.now() }];
};

/* ---------- 描画 ---------- */

const todoHtml = (todo) => `
  <li class="todo" data-id="${todo.id}">
    <input type="checkbox" class="todo-check" ${todo.done ? "checked" : ""}>
    <span class="todo-text">${escapeHtml(todo.text)}</span>
    <button type="button" class="todo-delete" aria-label="削除">×</button>
  </li>
`;

const render = () => {
  el.list.innerHTML = todos.map(todoHtml).join("");
  el.empty.hidden = todos.length > 0;
  el.empty.textContent = "まだタスクがありません。";
};

/* ---------- イベント ---------- */

el.form.addEventListener("submit", (e) => {
  e.preventDefault();
  const text = el.input.value.trim();
  if (text === "") {
    el.formError.textContent = "タスクの内容を入力してください";
    return;
  }
  addTodo(text);
  el.input.value = "";
  el.formError.textContent = "";
  render();
  el.input.focus();
});

render();
```

**Day 1 のチェック**
- [ ] 入力して Enter を押すと、タスクが追加される
- [ ] 空のまま送信するとエラーが出る
- [ ] タスクが0件のとき「まだタスクがありません」が出る
- [ ] `<img src=x onerror=alert(1)>` と入力しても、**文字として表示される**（XSS対策の確認）
- [ ] 追加後、入力欄にフォーカスが戻る

> ⚠️ **XSSの確認は必ずやってください。** アラートが出たら、`escapeHtml` を通していません。

---

## ✍️ Day 2 ─ 完了・削除・集計

### イベント委譲で実装する

```javascript
/* チェックボックス（change イベント） */
el.list.addEventListener("change", (e) => {
  const check = e.target.closest(".todo-check");
  if (!check) return;
  const id = check.closest(".todo").dataset.id;
  toggleTodo(id);
  render();
});

/* 削除（click イベント） */
el.list.addEventListener("click", (e) => {
  if (!e.target.closest(".todo-delete")) return;
  const id = e.target.closest(".todo").dataset.id;
  deleteTodo(id);
  render();
});
```

```javascript
const toggleTodo = (id) => {
  todos = todos.map((t) => (t.id === id ? { ...t, done: !t.done } : t));
};

const deleteTodo = (id) => {
  todos = todos.filter((t) => t.id !== id);
};
```

> ⚠️ **`el.list` に直接リスナーを付けている**点を確認してください。
> `.todo-delete` 1つずつに付けると、`render()` で消えてしまいます。

### 完了のスタイル

```javascript
const todoHtml = (todo) => `
  <li class="todo ${todo.done ? "is-done" : ""}" data-id="${todo.id}">
  ...
`;
```

```css
.todo.is-done .todo-text {
  color: var(--text-weak);
  text-decoration: line-through;
}
```

### 集計

```javascript
const total = todos.length;
const doneCount = todos.filter((t) => t.done).length;
el.counter.textContent =
  total === 0 ? "" : `全 ${total} 件 ／ 未完了 ${total - doneCount} 件 ／ 完了 ${doneCount} 件`;
```

**Day 2 のチェック**
- [ ] チェックを付けると打ち消し線が付く
- [ ] × を押すと削除される
- [ ] **追加したタスクにも、チェックと削除が効く**（イベント委譲の確認）
- [ ] 集計が正しく更新される

---

## ✍️ Day 3 ─ 絞り込み・保存・一括削除

### 絞り込み

```javascript
const getVisibleTodos = () => {
  if (filter === "active") return todos.filter((t) => !t.done);
  if (filter === "done")   return todos.filter((t) => t.done);
  return todos;
};

// render() の中で使う
const visible = getVisibleTodos();
el.list.innerHTML = visible.map(todoHtml).join("");
```

```javascript
el.filters.addEventListener("click", (e) => {
  const chip = e.target.closest(".chip");
  if (!chip) return;
  filter = chip.dataset.filter;
  render();
});
```

**ボタンの状態も `render()` の中で更新します。**

```javascript
el.filters.querySelectorAll(".chip").forEach((chip) => {
  const active = chip.dataset.filter === filter;
  chip.classList.toggle("is-active", active);
  chip.setAttribute("aria-pressed", String(active));
});
```

> 💡 **「押されたボタンだけ active にする」ではなく「全ボタンの状態を計算し直す」**。
> 2-7 で学んだ考え方です。「他を外す」を書き忘れる事故が起きません。

### 空メッセージを絞り込みに合わせる

```javascript
const EMPTY_MESSAGE = {
  all:    "まだタスクがありません。上の入力欄から追加してください。",
  active: "未完了のタスクはありません。おつかれさまでした。",
  done:   "完了したタスクはまだありません。",
};

el.empty.textContent = EMPTY_MESSAGE[filter];
```

> 💡 **「該当なし」の文言を状況に合わせる**だけで、アプリの完成度が大きく上がります。

### localStorage

2-11 の `storage` ラッパーをそのまま使います（📋 完成コードからコピペ可）。

```javascript
const STORAGE_KEY = "todos-v1";

let todos = storage.get(STORAGE_KEY, []);   // ← 初期化時に読み込む

const save = () => storage.set(STORAGE_KEY, todos);
```

**すべてのデータ操作関数の末尾で `save()` を呼びます。**

```javascript
const addTodo = (text) => {
  todos = [...todos, { ... }];
  save();          // ← 忘れない
};
```

> ⚠️ **保存漏れが最も多いバグです。** 削除や編集で `save()` を忘れると、
> リロードしたときに消したはずのタスクが復活します。**必ず各操作でリロードして確認してください。**

> 💡 キー名に `-v1` を付けているのは、**将来データ構造を変えたときに古いデータと衝突しないため**です。
> 実務でよく使う工夫です。

### 完了を一括削除

```javascript
el.clearDone.addEventListener("click", () => {
  const doneCount = todos.filter((t) => t.done).length;
  if (doneCount === 0) return;
  if (!confirm(`完了した ${doneCount} 件を削除します。よろしいですか？`)) return;

  todos = todos.filter((t) => !t.done);
  save();
  render();
});
```

**ボタンの活性も `render()` で制御します。**

```javascript
el.clearDone.disabled = doneCount === 0;
```

**Day 3 のチェック**
- [ ] 絞り込みが正しく動く
- [ ] 絞り込みボタンの見た目が切り替わる
- [ ] リロードしてもタスクが残る
- [ ] **削除してリロードしても復活しない**（保存漏れの確認）
- [ ] 完了0件のとき「完了を削除」が押せない
- [ ] 開発者ツールの Application タブで、保存されたJSONを確認した

---

## ✍️ Day 4 ─ 編集・ダークモード・仕上げ

### インライン編集（いちばん難しい）

**課題**：`render()` で `innerHTML` を書き換えると、編集中の `<input>` が消えてしまいます。

**解決**：**「編集中かどうか」も状態として持つ**。

```javascript
let editingId = null;   // 編集中のタスクID

const todoHtml = (todo) => {
  const isEditing = todo.id === editingId;
  const safeText = escapeHtml(todo.text);

  const body = isEditing
    ? `<input type="text" class="todo-edit" value="${safeText}" maxlength="120">`
    : `<span class="todo-text" tabindex="0" role="button">${safeText}</span>`;

  return `<li class="todo ..." data-id="${todo.id}">
            <input type="checkbox" class="todo-check" ...>
            ${body}
            ...
          </li>`;
};
```

**`render()` の最後で、編集中なら input にフォーカスを移します。**

```javascript
if (editingId) {
  const input = el.list.querySelector(".todo-edit");
  if (input) {
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);   // カーソルを末尾へ
  }
}
```

### 編集の開始と確定

```javascript
/* ダブルクリックで開始 */
el.list.addEventListener("dblclick", (e) => {
  const text = e.target.closest(".todo-text");
  if (!text) return;
  editingId = text.closest(".todo").dataset.id;
  render();
});

/* キーボードでも開始できるようにする（Enter / Space） */
el.list.addEventListener("keydown", (e) => {
  const text = e.target.closest(".todo-text");
  if (text && (e.key === "Enter" || e.key === " ")) {
    e.preventDefault();
    editingId = text.closest(".todo").dataset.id;
    render();
    return;
  }

  const input = e.target.closest(".todo-edit");
  if (!input) return;

  if (e.key === "Enter") { e.preventDefault(); commitEdit(input); }
  if (e.key === "Escape") { e.preventDefault(); editingId = null; render(); }
});

/* フォーカスが外れたら確定 */
el.list.addEventListener("focusout", (e) => {
  const input = e.target.closest(".todo-edit");
  if (input) commitEdit(input);
});

const commitEdit = (input) => {
  if (!editingId) return;      // ← 二重実行を防ぐガード
  const id = editingId;
  const text = input.value.trim();

  editingId = null;            // ← 先に null にする

  if (text !== "") updateTodo(id, text);
  render();
};
```

> ⚠️ **`if (!editingId) return;` のガードが重要です。**
> Enter で確定 → `render()` で input が消える → 環境によっては `focusout` も発火する、
> という二重実行が起こりえます。先に `editingId = null` にしておけば、2回目は何もしません。
>
> **「同じ処理が2回走るかもしれない」と考えて、防御的に書く**のは実務の基本です。

> 💡 **`tabindex="0"` と `role="button"`** を `.todo-text` に付けることで、
> マウスがなくてもキーボードだけで編集を開始できます。**これがないと、キーボードユーザーは編集できません。**

### ダークモード

2-11 で作ったものをそのまま使います（📋 完成コード参照）。

### タブ間同期

```javascript
window.addEventListener("storage", (e) => {
  if (e.key !== STORAGE_KEY) return;
  todos = storage.get(STORAGE_KEY, []);
  editingId = null;
  render();
});
```

2つのタブで開いて、片方で追加すると、**もう片方にも反映されます。**

**Day 4 のチェック**
- [ ] ダブルクリックで編集モードになる
- [ ] Enter で確定、Esc でキャンセル
- [ ] フォーカスを外しても確定する
- [ ] 空にして確定すると、元のテキストのまま（削除されない）
- [ ] 編集してリロードしても、内容が残る
- [ ] キーボードだけで編集できる（Tab → Enter）
- [ ] ダークモードが記憶される
- [ ] 2つのタブで同期する

---

## ✍️ 仕上げ ─ 自己レビュー

### 動作テスト

**正常系**
- [ ] 追加・完了・削除・編集・絞り込み・一括削除がすべて動く
- [ ] リロードしても状態が残る

**異常系（ここが重要）**
- [ ] 空のまま追加 → エラーが出る
- [ ] 半角スペースだけで追加 → エラーが出る
- [ ] 全角スペースだけで追加 → エラーが出る
- [ ] 120文字を超える入力 → エラーが出る
- [ ] `<script>alert(1)</script>` を入力 → **文字として表示される**
- [ ] 絵文字を入力 → 正しく表示される
- [ ] 同じ内容を2回追加 → 重複エラーが出る（実装した場合）
- [ ] 0件のとき、各絞り込みで適切なメッセージが出る
- [ ] シークレットモードで開く → **クラッシュせず、警告が出る**

**アクセシビリティ**
- [ ] Tab だけで全機能を操作できる
- [ ] フォーカスが常に見える
- [ ] スクリーンリーダーで、各ボタンが何のボタンかわかる
- [ ] 件数の変化が読み上げられる（`aria-live`）

**レスポンシブ**
- [ ] 320px で崩れない
- [ ] 長いテキストが折り返される
- [ ] スマホで入力欄をタップしてもズームしない

**コード品質**
- [ ] `var` と `==` を使っていない
- [ ] `innerHTML` にエスケープしていない値を渡していない
- [ ] 元の配列を破壊していない（`push` ではなく `[...todos, x]`）
- [ ] イベント委譲を使っている
- [ ] `localStorage` が `try...catch` で包まれている
- [ ] 1つの関数が1つの仕事をしている

### Lighthouse

- [ ] Accessibility が **95点以上**
- [ ] Best Practices が **90点以上**

---

## 🤖 AIに聞いてみよう

### ① 完成品を総合レビューさせる

```text
以下は、JavaScript を1ヶ月学習した初学者が作った ToDo アプリです。

【HTML】
（貼る）
【CSS】
（貼る）
【JavaScript】
（貼る）

厳しめにレビューしてください。

1. セキュリティ（特に XSS）
2. バグや、特定の条件で壊れる箇所
3. アクセシビリティ
4. 保守性（命名、関数の分割、重複）
5. パフォーマンス
6. 「初学者っぽさ」が出ている箇所と、その改善方針

各指摘に優先度（高/中/低）を付けてください。
修正後のコードは書かないでください。私が自分で直します。
```

> 💡 **指摘を受けたら、優先度「高」から自分で直し、もう一度レビューさせる。**
> このループを**2周**してください。それが第2部の総まとめです。

### ② エッジケースを洗い出させる

```text
以下は、私が作った ToDo アプリの仕様です。

（機能一覧を貼る）

このアプリで、私がテストし忘れていそうなエッジケースを
20個挙げてください。

観点:
- 入力値の異常（空、極端に長い、特殊文字、絵文字、改行）
- 状態の組み合わせ（編集中に削除、絞り込み中に追加 など）
- ストレージ（容量オーバー、壊れたデータ、シークレットモード）
- 複数タブ
- キーボード操作

各ケースについて「操作手順」と「期待する結果」を書いてください。
```

> ⚠️ **「編集中に、その項目を削除する」**は、実際に問題が起きるケースです。
> 完成コードでも完全には解決していません。**演習3で扱います。**

---

## 🔧 発展課題

### 課題A：機能を足す

- [ ] タスクの並び替え（ドラッグ&ドロップ、または上下ボタン）
- [ ] 期限日の設定と、期限切れの強調表示
- [ ] 優先度（高・中・低）とその絞り込み
- [ ] タスクの検索（デバウンス付き）
- [ ] Undo（元に戻す）— 削除の取り消し
- [ ] JSONでエクスポート / インポート

### 課題B：既知の不具合を直す

完成コードには、**意図的に残した不具合**があります。

> **編集中のタスクの「×」ボタンを押すと、削除されないことがある。**

**原因**：`mousedown` → `focusout`（`commitEdit` → `render()`）→ DOM が入れ替わる → `click` が発火しない。

**ヒント**
- `mousedown` の段階で削除対象を記録しておく
- または、削除ボタンに `onmousedown="event.preventDefault()"` 相当の処理を入れてフォーカス移動を防ぐ

自分で調査して直してください。**これは実務でよく遭遇するタイプのバグです。**

### 課題C：見た目を自分のものにする

- [ ] 配色を自分好みに変える（`:root` の変数だけ）
- [ ] フォントを Google Fonts に変える
- [ ] 完了時にアニメーションを付ける（`prefers-reduced-motion` 対応も忘れずに）
- [ ] アイコンを絵文字から SVG に変える

### 課題D：公開する

第6部を先取りして、GitHub Pages で公開してみてください。

**JavaScript だけで動くアプリなので、そのままアップロードすれば動きます。**

---

## ✅ 第2部 修了チェック

### 知識

- [ ] `const` / `let` を使い分け、`var` を使わない
- [ ] `===` を使い、`??` と `||` の違いを説明できる
- [ ] 早期リターンでネストを浅くできる
- [ ] アロー関数とコールバックを理解している
- [ ] `map` / `filter` / `reduce` を使い分けられる
- [ ] 参照渡しの事故を避けられる
- [ ] XSS を理解し、`escapeHtml` を使える
- [ ] イベント委譲を実装できる
- [ ] `async` / `await` で通信を書ける
- [ ] `localStorage` を安全に扱える
- [ ] エラーを読んで、切り分けで原因を特定できる
- [ ] AIの出力をレビューできる

### 成果物

- [ ] **ToDoアプリが完成した**
- [ ] 異常系のテストをすべて通した
- [ ] Lighthouse の Accessibility が 95点以上
- [ ] AIレビューを2周した
- [ ] スクリーンショットを保存した

---

## 🎉 第2部 修了

おつかれさまでした。ここまでで、あなたは**ブラウザの中で動くアプリを、一人で作れる人**になりました。

ただし、いま作ったアプリには決定的な限界があります。

- **他の人とデータを共有できない**
- **別の端末から見られない**
- **ブラウザのデータを消したら全部消える**

これを解決するには、**サーバー**が必要です。

第3部からは PHP を学び、**サーバー側で動くプログラム**を書きます。
掲示板を作り、第4部でデータベースに繋ぎ、第5部で Laravel を使って本格的なアプリを作ります。

**次のレッスン → [3-1 サーバーサイドとは何か / PHPを動かす](../03-php/03-01-php-intro.md)**

---

**前 → [2-12 AIとペアプロする実践フロー](02-12-ai-pairpro.md)**
