# 2-8 フォームバリデーション実装

> 🎯 **このレッスンのゴール**
> - リアルタイムバリデーションを実装できる
> - エラーメッセージをアクセシブルに表示できる
> - クライアント側の検証だけでは不十分な理由を説明できる

所要 120分 / 難度 🟡
完成コード: [`code/02-08/`](../code/02-08/)

---

## 📖 バリデーションは3層で行う

| 層 | 目的 | 突破されるか |
| --- | --- | --- |
| ① **HTML属性**（`required` など） | 手軽な入力支援 | **簡単に突破される** |
| ② **JavaScript** | 親切なUX（即時フィードバック） | **簡単に突破される** |
| ③ **サーバー（PHP）** | **本当の検証** | **突破されない** |

> ⚠️ **①と②は「親切機能」であって、セキュリティ対策ではありません。**
>
> 開発者ツールで `required` を消す、JavaScriptを無効にする、curl で直接POSTする——
> どれも簡単にできます。**サーバー側の検証（第3部 3-7）が本命**です。
>
> **鉄則：クライアントから来るデータは、すべて信用しない。**

このレッスンでは②を実装します。目的は**セキュリティではなくUX**です。

---

## 📖 良いバリデーションUXの原則

| 原則 | 理由 |
| --- | --- |
| **入力中はエラーを出さない** | 打ちかけの「a」に「メール形式が不正」と出るのは不快 |
| **フォーカスが外れたら検証する**（`blur`） | 入力し終わったタイミング |
| **一度エラーになった項目は、入力中も再検証する** | 直したらすぐ消えてほしい |
| **エラーは項目のすぐ下に出す** | 上部にまとめると、どれが問題か探すことになる |
| **何が問題で、どう直すかを書く** | 「不正な値です」では直せない |
| **送信時は全項目を検証し、最初のエラーにフォーカス** | どこを直すかすぐわかる |

### 悪いエラーメッセージ / 良いエラーメッセージ

| ❌ 悪い | ✅ 良い |
| --- | --- |
| エラーが発生しました | メールアドレスに「@」が含まれていません |
| 不正な値です | 電話番号は数字とハイフンで入力してください |
| 必須項目です | お名前を入力してください |
| 文字数オーバー | お名前は50文字以内で入力してください（現在52文字） |

---

## ✍️ 手を動かす① ─ HTMLを準備する

`htdocs/js-lesson/form.html`（📋 コピペ可）

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>フォームバリデーション</title>
  <link rel="stylesheet" href="css/form.css">
</head>
<body>
  <main>
    <h1>ご予約フォーム</h1>

    <form id="reserveForm" novalidate>

      <div class="field">
        <label for="name">お名前 <span class="req">必須</span></label>
        <input type="text" id="name" name="name" maxlength="50"
               autocomplete="name" aria-describedby="err-name">
        <p class="error" id="err-name" role="alert"></p>
      </div>

      <div class="field">
        <label for="email">メールアドレス <span class="req">必須</span></label>
        <input type="email" id="email" name="email"
               autocomplete="email" aria-describedby="err-email">
        <p class="error" id="err-email" role="alert"></p>
      </div>

      <div class="field">
        <label for="tel">電話番号</label>
        <input type="tel" id="tel" name="tel" placeholder="03-1234-5678"
               autocomplete="tel" aria-describedby="err-tel">
        <p class="error" id="err-tel" role="alert"></p>
      </div>

      <div class="field">
        <label for="guests">ご来店人数 <span class="req">必須</span></label>
        <input type="number" id="guests" name="guests" min="1" max="8" value="2"
               aria-describedby="err-guests">
        <p class="error" id="err-guests" role="alert"></p>
      </div>

      <div class="field">
        <label for="message">ご要望
          <span class="counter"><span id="msgCount">0</span> / 500</span>
        </label>
        <textarea id="message" name="message" rows="5" maxlength="500"
                  aria-describedby="err-message"></textarea>
        <p class="error" id="err-message" role="alert"></p>
      </div>

      <div class="field field-check">
        <input type="checkbox" id="agree" name="agree" value="1"
               aria-describedby="err-agree">
        <label for="agree">プライバシーポリシーに同意する <span class="req">必須</span></label>
        <p class="error" id="err-agree" role="alert"></p>
      </div>

      <button type="submit" class="btn">送信する</button>

      <p class="form-status" id="formStatus" role="status"></p>
    </form>
  </main>

  <script src="js/form.js" defer></script>
</body>
</html>
```

### HTMLのポイント

| 属性 | 役割 |
| --- | --- |
| `novalidate` | **ブラウザ標準の検証を無効化**し、JSで制御する |
| `aria-describedby` | エラーメッセージ要素と入力欄を紐付ける |
| `role="alert"` | 内容が変わったとき、スクリーンリーダーが読み上げる |
| `role="status"` | 同上（`alert` より控えめ） |
| `autocomplete` | ブラウザの自動入力を効かせる（UXに効く） |

> 💡 **`novalidate` を付ける理由**
> ブラウザ標準のエラー表示はデザインを変えられず、日本語も不自然なことがあります。
> JSで制御するなら、標準の検証は止めてしまうほうが一貫します。
>
> ただし、**`type="email"` や `min` / `max` は残しておいてください**。スマホのキーボードや、JS無効時の最低限の保険になります。

**CSS**（`css/form.css`、📋 コピペ可）

```css
* , *::before, *::after { box-sizing: border-box; }

body {
  font-family: system-ui, "Hiragino Sans", sans-serif;
  line-height: 1.8;
  margin: 0;
  padding: 24px;
  background: #fafaf8;
  color: #22201d;
}

main { max-width: 560px; margin-inline: auto; }

form { display: flex; flex-direction: column; gap: 20px; margin-top: 24px; }

.field { display: flex; flex-direction: column; gap: 6px; }

label { font-size: 14px; font-weight: 500; }

.req {
  font-size: 11px;
  color: #fff;
  background: #b1452b;
  padding: 2px 6px;
  border-radius: 2px;
  margin-left: 4px;
}

.counter { float: right; font-size: 12px; color: #777; font-weight: 400; }

input, textarea {
  width: 100%;
  padding: 11px 13px;
  font: inherit;
  font-size: 16px;              /* iOS のズーム防止 */
  border: 1px solid #d8d3ca;
  border-radius: 3px;
  background: #fff;
}

input:focus, textarea:focus {
  outline: 2px solid #8a5a3b;
  outline-offset: 1px;
  border-color: transparent;
}

/* エラー状態 */
.field.is-error input,
.field.is-error textarea {
  border-color: #b1452b;
  background: #fdf6f4;
}

.error {
  margin: 0;
  font-size: 13px;
  color: #b1452b;
  min-height: 0;
}
.error:empty { display: none; }

/* 成功状態 */
.field.is-valid input,
.field.is-valid textarea {
  border-color: #4b8a4b;
}

.field-check { flex-direction: row; flex-wrap: wrap; align-items: center; gap: 8px; }
.field-check input { width: 18px; height: 18px; flex-shrink: 0; }
.field-check .error { flex-basis: 100%; }

.btn {
  align-self: flex-start;
  padding: 13px 34px;
  font: inherit;
  font-size: 15px;
  color: #fff;
  background: #8a5a3b;
  border: none;
  border-radius: 3px;
  cursor: pointer;
}
.btn:hover { background: #6f4830; }
.btn:disabled { opacity: .5; cursor: not-allowed; }

.form-status { font-size: 14px; }
.form-status.is-success { color: #2f6b2f; }
.form-status.is-error { color: #b1452b; }
```

---

## ✍️ 手を動かす② ─ バリデーションルールを作る

`js/form.js`（📋 完成形は `code/02-08/js/form.js`）

```javascript
// ==========================================================
// バリデーションルール
// 各関数は「エラーメッセージ（文字列）」か「null（OK）」を返す
// ==========================================================

const rules = {
  name: (value) => {
    const v = value.trim();
    if (v === "") return "お名前を入力してください";
    if (v.length > 50) return `お名前は50文字以内で入力してください（現在${v.length}文字）`;
    return null;
  },

  email: (value) => {
    const v = value.trim();
    if (v === "") return "メールアドレスを入力してください";
    if (!v.includes("@")) return "メールアドレスに「@」が含まれていません";
    const [local, domain] = v.split("@");
    if (!local || !domain) return "メールアドレスの形式が正しくありません";
    if (!domain.includes(".")) return "メールアドレスのドメイン部分が正しくありません";
    if (v.length > 254) return "メールアドレスが長すぎます";
    return null;
  },

  tel: (value) => {
    const v = value.trim();
    if (v === "") return null;                       // 任意項目
    if (!/^[0-9-]+$/.test(v)) return "電話番号は数字とハイフンで入力してください";
    const digits = v.replaceAll("-", "");
    if (digits.length < 10 || digits.length > 11) {
      return "электронный電話番号は10桁または11桁で入力してください";
    }
    return null;
  },

  guests: (value) => {
    const v = value.trim();
    if (v === "") return "ご来店人数を入力してください";
    const n = Number(v);
    if (!Number.isInteger(n)) return "人数は整数で入力してください";
    if (n < 1 || n > 8) return "人数は1〜8名で入力してください";
    return null;
  },

  message: (value) => {
    if (value.length > 500) return `ご要望は500文字以内で入力してください（現在${value.length}文字）`;
    return null;
  },

  agree: (value, el) => {
    if (!el.checked) return "プライバシーポリシーへの同意が必要です";
    return null;
  },
};
```

> ⚠️ 上の `tel` のメッセージに、わざと壊れた文字列（`электронный`）を混ぜてあります。
> **これは「AIやコピペのコードをそのまま信じない」練習です。** 気づいたら削除してください。
> 気づかずに進んだ人は、実際の画面で確認したときに気づくはずです。**必ず動かして確認する**習慣をつけてください。

### 正規表現について

```javascript
/^[0-9-]+$/.test(v)
```

| 記号 | 意味 |
| --- | --- |
| `/.../ ` | 正規表現リテラル |
| `^` | 文字列の先頭 |
| `$` | 文字列の末尾 |
| `[0-9-]` | 0〜9 とハイフンのいずれか |
| `+` | 1回以上の繰り返し |
| `.test(str)` | マッチするか（true / false） |

> 💡 **メールアドレスの完全な正規表現は書かないでください。**
> RFC準拠の正規表現は数千文字になり、それでも完璧ではありません。
> **「@ があり、その後に . がある」程度の簡易チェック + 実際に確認メールを送る**のが現実的な方法です。

---

## ✍️ 手を動かす③ ─ 検証と表示のロジック

```javascript
// ==========================================================
// DOM要素
// ==========================================================

const form = document.querySelector("#reserveForm");
const status = document.querySelector("#formStatus");
const msgCount = document.querySelector("#msgCount");

// 検証済みフラグ（一度エラーになった項目を覚えておく）
const touched = new Set();

// ==========================================================
// エラー表示
// ==========================================================

/** 1つの項目を検証して、結果を画面に反映する */
const validateField = (name) => {
  const el = form.elements[name];
  const rule = rules[name];
  if (!el || !rule) return true;

  const message = rule(el.value, el);
  const field = el.closest(".field");
  const errorEl = document.querySelector(`#err-${name}`);

  if (message) {
    field.classList.add("is-error");
    field.classList.remove("is-valid");
    errorEl.textContent = message;
    el.setAttribute("aria-invalid", "true");
    return false;
  }

  field.classList.remove("is-error");
  field.classList.add("is-valid");
  errorEl.textContent = "";
  el.removeAttribute("aria-invalid");
  return true;
};

/** すべての項目を検証する */
const validateAll = () => {
  let firstInvalid = null;

  for (const name of Object.keys(rules)) {
    const ok = validateField(name);
    if (!ok && !firstInvalid) {
      firstInvalid = form.elements[name];
    }
  }

  return firstInvalid;   // null なら全部OK
};
```

> 💡 **`form.elements["name"]` で、`name` 属性から要素を取得できます。**
> `document.querySelector("#name")` より、フォームの中に限定されるので安全です。

---

## ✍️ 手を動かす④ ─ イベントを繋ぐ

```javascript
// ==========================================================
// イベント
// ==========================================================

// ① フォーカスが外れたら検証する（初回はここで検証）
form.addEventListener("blur", (e) => {
  const name = e.target.name;
  if (!rules[name]) return;
  touched.add(name);
  validateField(name);
}, true);   // ← 第3引数 true が重要（後述）

// ② 一度検証した項目は、入力中も再検証する（直したらすぐ消える）
form.addEventListener("input", (e) => {
  const name = e.target.name;
  if (!rules[name]) return;
  if (touched.has(name)) validateField(name);
});

// ③ チェックボックスは change で
form.addEventListener("change", (e) => {
  if (e.target.type !== "checkbox") return;
  const name = e.target.name;
  if (!rules[name]) return;
  touched.add(name);
  validateField(name);
});

// ④ 文字数カウンター
form.elements.message.addEventListener("input", (e) => {
  msgCount.textContent = e.target.value.length;
});

// ⑤ 送信
form.addEventListener("submit", (e) => {
  e.preventDefault();

  // すべて touched にしてから全項目検証
  Object.keys(rules).forEach((n) => touched.add(n));
  const firstInvalid = validateAll();

  if (firstInvalid) {
    status.textContent = "入力内容にエラーがあります。ご確認ください。";
    status.className = "form-status is-error";
    firstInvalid.focus();          // 最初のエラー項目にフォーカス
    return;
  }

  // 送信処理（いまは Console に出すだけ。第3部でサーバーに送ります）
  const data = Object.fromEntries(new FormData(form));
  console.log("送信データ:", data);

  status.textContent = "送信しました。ありがとうございました。";
  status.className = "form-status is-success";
  form.reset();

  // 見た目もリセット
  document.querySelectorAll(".field").forEach((f) => {
    f.classList.remove("is-error", "is-valid");
  });
  document.querySelectorAll(".error").forEach((el) => (el.textContent = ""));
  touched.clear();
  msgCount.textContent = "0";
});
```

### `blur` に第3引数 `true` が必要な理由

```javascript
form.addEventListener("blur", handler, true);
```

**`blur` と `focus` は、バブリングしません。** 子要素で発生しても、親に伝わりません。

第3引数を `true` にすると **キャプチャフェーズ**（親→子の順に降りていく段階）で捕まえられるため、親でまとめて処理できます。

```
キャプチャフェーズ（親→子）  ← ここで捕まえる
        ↓
     ターゲット
        ↓
バブリングフェーズ（子→親）  ← 通常はここ。blur は伝わらない
```

> 💡 代替として `focusout` イベントを使う方法もあります。こちらはバブリングします。
>
> ```javascript
> form.addEventListener("focusout", handler);   // 第3引数不要
> ```

### `FormData` と `Object.fromEntries`

```javascript
const data = Object.fromEntries(new FormData(form));
console.log(data);
// { name: "山田太郎", email: "a@b.com", tel: "", guests: "2", message: "", agree: "1" }
```

**フォームの全入力値を、一発でオブジェクトにできます。** 手動で1つずつ取る必要はありません。

> ⚠️ **チェックが外れているチェックボックスは、キー自体が含まれません。** `data.agree` は `undefined` になります。
> また、**すべての値が文字列**です。数値として使うときは `Number()` してください。

> 🆘 **ここで詰まったら**（送信すると画面が一瞬光ってリロードされ、JSの処理が動かない）
> - **原因**：`submit` イベントで **`e.preventDefault()` を書き忘れている**。フォームは既定でページを再送信（リロード）するため、その前にJSが中断されます
> - **対処**：`submit` ハンドラの**先頭**で `e.preventDefault();` を必ず呼ぶ（上のコード⑤参照）
> - **直らなければ、AIにこう聞く**（submitハンドラを貼る）：
>   「フォーム送信でページがリロードされてしまい、JSのバリデーションが動きません。原因を教えてください」

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| ブラウザ標準のエラーが出る | `novalidate` がない | `<form novalidate>` |
| `blur` で検証されない | `blur` はバブリングしない | 第3引数 `true`、または `focusout` |
| チェックボックスの値が取れない | `value` は常に固定値 | `el.checked` を見る |
| 送信でページがリロードされる | `e.preventDefault()` がない | 必ず書く |
| 数値の比較がおかしい | 値が文字列 | `Number()` で変換 |
| エラーが読み上げられない | `role="alert"` がない | エラー要素に付ける |
| 入力中にエラーが出て不快 | `input` で常に検証している | 初回は `blur`、以降は `input` |
| エラーが空でも余白が残る | 空要素にも高さがある | `.error:empty { display: none; }` |

---

## 🤖 AIに聞いてみよう

### ① バリデーション仕様をレビューさせる

```text
以下は、私が実装したフォームバリデーションのルールです。

（rules オブジェクトを貼る）

次の観点でレビューしてください。

1. 検証が漏れているケース（全角スペースだけの入力、絵文字、極端に長い入力など）
2. エラーメッセージが「何が問題で、どう直すか」を伝えられているか
3. 日本語のフォームとして考慮すべき点（全角数字、半角カナなど）
4. 過剰な検証になっている箇所（ユーザーを不必要に弾いていないか）

修正後のコードは書かず、指摘だけをお願いします。
```

> 💡 「過剰な検証」の観点は重要です。
> たとえば、メールアドレスの厳しすぎる正規表現は、**実在する正しいアドレスを弾いてしまいます**。

### ② テストケースを網羅させる

```text
以下のバリデーション関数について、テストすべき入力値を網羅的に挙げてください。

（関数を貼る）

観点:
- 正常系
- 境界値（ちょうど上限、ちょうど下限、その±1）
- 異常系（空、空白のみ、全角空白のみ、null、undefined、極端に長い、特殊文字、絵文字）
- 日本語特有のケース（全角数字、半角カナ、サロゲートペア）

「入力」と「期待する結果」の表形式でお願いします。
私が実際に入力して確認します。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

上のコードを完成させ、以下を確認してください。

- [ ] `tel` のルールに混ざっていた壊れた文字列を見つけて直した
- [ ] 名前を空のままフォーカスを外すと、エラーが出る
- [ ] エラーが出た後、文字を入力するとエラーが消える
- [ ] 何も入力せず送信すると、最初のエラー項目にフォーカスが移る
- [ ] 文字数カウンターが動く
- [ ] すべて正しく入力して送信すると、Console にデータが出る
- [ ] Tab キーだけで最後まで操作でき、フォーカスが常に見える

### 演習2（必須）

以下の入力に対して、現在のルールがどう判定するか確認してください。**問題があれば直してください。**

| 入力欄 | 値 | 期待 | 実際 |
| --- | --- | --- | --- |
| name | `"   "`（半角スペースのみ） | エラー | |
| name | `"　　"`（全角スペースのみ） | エラー | |
| email | `"a@b"` | エラー | |
| email | `"@example.com"` | エラー | |
| email | `"a@@b.com"` | エラー | |
| guests | `"2.5"` | エラー | |
| guests | `"０"`（全角ゼロ） | エラー | |
| guests | `"-1"` | エラー | |

<details>
<summary>ヒント</summary>

- `trim()` は**半角スペースだけでなく全角スペースも除去します**（実は大丈夫）
- `"a@@b.com".split("@")` は `["a", "", "b.com"]` になります。`domain` が空文字になるので弾けます
- `Number("０")` は `0` になります（**全角数字も変換される**）。範囲チェックで弾けます
- `Number("2.5")` は `2.5`。`Number.isInteger()` で弾けます

**実際に入力して、期待通りか確認してください。** 頭で考えるだけでなく、動かすことが重要です。

</details>

### 演習3（挑戦）

以下の機能を追加してください。

- [ ] **パスワード欄**を追加し、以下を検証する
      - 8文字以上
      - 英字と数字を両方含む
      - よくあるパスワード（`password`、`12345678` など）を弾く
- [ ] **パスワード確認欄**を追加し、一致するか検証する
- [ ] パスワードの**強度メーター**を表示する（弱い / 普通 / 強い）
- [ ] 全項目が有効になるまで、**送信ボタンを `disabled`** にする

> ⚠️ **送信ボタンの `disabled` は、実はUX上の議論があります。**
> 「なぜ押せないのかわからない」という声もあるためです。
> 実装したうえで、**押せるようにしてエラーを出す方式と、どちらが良いか自分で考えてみてください。**

---

## ✅ 章末チェック

- [ ] クライアント側の検証だけでは不十分な理由を説明できる
- [ ] `novalidate` を付ける理由を言える
- [ ] 「入力中はエラーを出さない、一度エラーになったら即時検証」の理由を説明できる
- [ ] `blur` がバブリングしないことと、その対処を知っている
- [ ] `FormData` + `Object.fromEntries` でフォームの値を取れる
- [ ] チェックボックスは `checked` で判定することを知っている
- [ ] `role="alert"` の役割を説明できる
- [ ] 良いエラーメッセージの条件を言える
- [ ] メールの完全な正規表現を書くべきでない理由を言える

---

**前 → [2-7 イベントで「動く」を作る](02-07-events.md)　｜　次 → [2-9 エラーの読み方とデバッグ手順](02-09-debugging.md)**
