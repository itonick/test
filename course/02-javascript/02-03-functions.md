# 2-3 関数とスコープ

> ◎ **このレッスンのゴール**
> - 関数の3つの書き方を使い分けられる
> - 引数・戻り値の設計ができる
> - スコープを理解し、変数がどこから見えるか説明できる
> - 「1つの関数は1つの仕事」の原則を実践できる

所要 120分 / 難度 🟡
完成コード: [`code/02-03/`](../code/02-03/)

---

## 📖 関数は「名前を付けた処理のかたまり」

```javascript
// 関数がない場合：同じ計算を3か所に書く
const total1 = Math.floor(1000 * 1.1);
const total2 = Math.floor(2500 * 1.1);
const total3 = Math.floor(800 * 1.1);

// 関数にする
function withTax(price) {
  return Math.floor(price * 1.1);
}

const total1 = withTax(1000);
const total2 = withTax(2500);
const total3 = withTax(800);
```

**関数にする3つのメリット**

| メリット | 具体的に |
| --- | --- |
| **再利用** | 同じ処理を何度も書かなくて済む |
| **修正が1か所** | 税率が12%になっても、関数の中を1行直すだけ |
| **名前が説明になる** | `withTax(1000)` は読めばわかる。`Math.floor(1000*1.1)` は考える必要がある |

> 💡 **「同じコードを2回書いたら、関数にするサイン」** です。
> 3回書いたら、確実に関数にしてください。

---

## ✍️ 手を動かす① ─ 3つの書き方

### ① 関数宣言

```javascript
function greet(name) {
  return `こんにちは、${name}さん`;
}

console.log(greet("太郎"));
```

**巻き上げ（ホイスティング）**があるため、**定義より前でも呼べます**。

```javascript
console.log(greet("太郎"));   // ✅ 動く

function greet(name) {
  return `こんにちは、${name}さん`;
}
```

### ② 関数式

```javascript
const greet = function(name) {
  return `こんにちは、${name}さん`;
};
```

**定義より前では呼べません。**

### ③ アロー関数（現代の主流）

```javascript
const greet = (name) => {
  return `こんにちは、${name}さん`;
};

// 1行で return するだけなら、{} と return を省略できる
const greet = (name) => `こんにちは、${name}さん`;

// 引数が1つなら、() も省略できる（この教材では付けます）
const greet = name => `こんにちは、${name}さん`;

// 引数が0個なら、() は必須
const now = () => new Date();

// 引数が複数
const add = (a, b) => a + b;

// オブジェクトを返すときは、() で囲む
const makeUser = (name) => ({ name: name, createdAt: new Date() });
```

> ⚠️ **オブジェクトを返すときの `()` を忘れがちです。**
>
> ```javascript
> const f = () => { name: "太郎" };    // ❌ {} が「関数の本体」と解釈される → undefined
> const f = () => ({ name: "太郎" });  // ✅
> ```

### どれを使うか

| 書き方 | 使う場面 |
| --- | --- |
| **アロー関数** | **基本これ**。コールバック（後述）では特に |
| 関数宣言 | ファイルの上部に定義をまとめたいとき |
| 関数式 | ほぼ使わない |

**この教材では、アロー関数を基本にします。** ただし、独立した処理単位には関数宣言も使います（巻き上げにより、呼び出し順を気にせず書けるため）。

---

## ✍️ 手を動かす② ─ 引数と戻り値

### デフォルト引数

```javascript
const greet = (name = "ゲスト") => `こんにちは、${name}さん`;

console.log(greet());        // こんにちは、ゲストさん
console.log(greet("太郎"));   // こんにちは、太郎さん
```

### 引数の数が合わないとき

```javascript
const add = (a, b) => a + b;

console.log(add(1));        // NaN（b は undefined）
console.log(add(1, 2, 3));  // 3（余分な引数は無視される）
```

> ⚠️ **JavaScript は引数の数をチェックしません。** 足りなくてもエラーになりません。
> `undefined` が混ざって `NaN` になる、という形で後からバグが顕在化します。
> **デフォルト引数を書く**か、関数の冒頭でチェックしてください。

> 🆘 **ここで詰まったら**（関数の結果が `undefined` になる）
> - **原因No.1**：関数の中で `return` を書き忘れている。`console.log` しているだけでは**呼び出し側に値は返りません**。「計算して返す」関数には必ず `return` を書く
> - **原因No.2**：引数の渡し忘れ（上の `NaN`）や、`return` の後ろで改行してしまい `return` だけが実行されている（JSは `return` 単独行を `return;` と解釈します）
> - **直らなければ、AIにこう聞く**（関数と呼び出し部分の両方を貼る）：
>   「この関数の戻り値が undefined になります。原因を、私のコードを見て指摘してください」

### 戻り値

```javascript
// return がないと undefined が返る
const f = () => {
  console.log("処理");
  // return なし
};
console.log(f());   // undefined

// return したらそこで関数は終了
const g = (n) => {
  if (n < 0) return "負の数";
  console.log("この行は n >= 0 のときだけ実行される");
  return "正の数";
};
```

### 複数の値を返したいとき

```javascript
// オブジェクトで返す（推奨。名前が付くのでわかりやすい）
const parseName = (fullName) => {
  const [last, first] = fullName.split(" ");
  return { last, first };
};

const { last, first } = parseName("山田 太郎");
console.log(last, first);   // 山田 太郎

// 配列で返す（順番に意味があるとき）
const minMax = (arr) => [Math.min(...arr), Math.max(...arr)];
const [min, max] = minMax([3, 1, 4, 1, 5]);
```

> 💡 `{ last, first }` は `{ last: last, first: first }` の省略形です。
> **キー名と変数名が同じなら省略できます**（ショートハンドプロパティ）。

---

## ✍️ 手を動かす③ ─ スコープ

**スコープ＝変数が見える範囲**です。

### ブロックスコープ

```javascript
{
  const inner = "中";
  console.log(inner);   // ✅ 見える
}
console.log(inner);     // ❌ ReferenceError: inner is not defined
```

`const` / `let` は **`{ }` の中でだけ有効**です。

```javascript
if (true) {
  const x = 1;
}
console.log(x);   // ❌ エラー

for (let i = 0; i < 3; i++) { }
console.log(i);   // ❌ エラー
```

### 関数スコープ

```javascript
const outer = () => {
  const a = 1;

  const inner = () => {
    const b = 2;
    console.log(a);   // ✅ 外側の変数は見える
  };

  inner();
  console.log(b);     // ❌ 内側の変数は見えない
};
```

**内側から外側は見える。外側から内側は見えない。**

```
┌─ グローバル ─────────────────┐
│  const g = "全体"            │
│  ┌─ 関数 outer ───────────┐  │
│  │  const a = 1           │  │  ← g が見える
│  │  ┌─ 関数 inner ─────┐  │  │
│  │  │  const b = 2     │  │  │  ← g, a が見える
│  │  └──────────────────┘  │  │
│  └────────────────────────┘  │
└──────────────────────────────┘
```

### グローバル変数は最小限に

```javascript
// ❌ どこからでも書き換えられる
let currentUser = null;
let cart = [];
let isLoading = false;

// ✅ 関数の中に閉じ込める、または1つのオブジェクトにまとめる
const state = {
  currentUser: null,
  cart: [],
  isLoading: false,
};
```

> 💡 **グローバル変数が増えると、「どこで書き換わったか」が追えなくなります。**
> 名前の衝突も起きます。**変数は、必要な範囲でいちばん狭いスコープに置く**のが原則です。

### `var` の問題（なぜ使わないか）

```javascript
// var はブロックスコープを無視する
if (true) {
  var x = 1;
}
console.log(x);   // 1 が出てしまう（漏れる）

// for ループでの典型的な事故
for (var i = 0; i < 3; i++) {
  setTimeout(() => console.log(i), 100);
}
// 3, 3, 3  ← 全部同じ値になる

for (let i = 0; i < 3; i++) {
  setTimeout(() => console.log(i), 100);
}
// 0, 1, 2  ← 期待通り
```

**`var` を使わない理由が、これで具体的にわかったはずです。**

---

## ✍️ 手を動かす④ ─ コールバック関数

**関数を、別の関数に「渡す」**ことができます。

```javascript
// 関数を引数として受け取る
const repeat = (times, callback) => {
  for (let i = 0; i < times; i++) {
    callback(i);
  }
};

repeat(3, (i) => console.log(`${i}回目`));
// 0回目
// 1回目
// 2回目
```

**これがJavaScriptの最重要概念の1つ**です。次のような場面で必ず出てきます。

```javascript
// 配列のメソッド（2-5）
[1, 2, 3].map((n) => n * 2);

// イベント（2-7）
button.addEventListener("click", () => console.log("押された"));

// タイマー
setTimeout(() => console.log("1秒後"), 1000);
```

### `()` を付けるかどうか

```javascript
const sayHi = () => console.log("Hi");

button.addEventListener("click", sayHi);     // ✅ 関数そのものを渡す
button.addEventListener("click", sayHi());   // ❌ 即座に実行され、戻り値(undefined)を渡す
```

> ⚠️ **これは第2部で最頻出のミスです。**
> `sayHi` は「関数」、`sayHi()` は「関数を実行した結果」。**渡すのは関数そのもの**です。
>
> **たとえ話**：レシピを渡すのか、作った料理を渡すのか、の違いです。
> イベントリスナーには「後で作ってね」とレシピを渡します。

### 引数を渡したいとき

```javascript
const greet = (name) => console.log(`Hi, ${name}`);

button.addEventListener("click", greet);           // ❌ 引数が渡せない
button.addEventListener("click", greet("太郎"));    // ❌ 即実行される
button.addEventListener("click", () => greet("太郎")); // ✅ アロー関数で包む
```

---

## ✍️ 手を動かす⑤ ─ 1つの関数は1つの仕事

```javascript
// ❌ 何でもやっている関数
function processOrder(order) {
  // バリデーション
  if (!order.name) return "名前がありません";
  if (!order.email) return "メールがありません";
  // 金額計算
  let total = 0;
  for (const item of order.items) {
    total += item.price * item.quantity;
  }
  total = Math.floor(total * 1.1);
  // 画面に表示
  document.querySelector("#total").textContent = `${total}円`;
  // 保存
  localStorage.setItem("order", JSON.stringify(order));
}
```

この関数は**4つの仕事**をしています。問題点：

- テストしにくい（全部まとめてしか試せない）
- 再利用できない（金額計算だけ使いたくてもできない）
- 変更の影響が読めない（表示を変えたいのに保存が壊れるかも）

```javascript
// ✅ 仕事ごとに分ける
const validateOrder = (order) => {
  if (!order.name) return "名前を入力してください";
  if (!order.email) return "メールアドレスを入力してください";
  return null;   // エラーなし
};

const calcSubtotal = (items) =>
  items.reduce((sum, item) => sum + item.price * item.quantity, 0);

const withTax = (price) => Math.floor(price * 1.1);

const renderTotal = (total) => {
  document.querySelector("#total").textContent = `${total.toLocaleString()}円`;
};

const saveOrder = (order) => {
  localStorage.setItem("order", JSON.stringify(order));
};

// 呼び出し側は「手順書」になる
const processOrder = (order) => {
  const error = validateOrder(order);
  if (error) return error;

  const total = withTax(calcSubtotal(order.items));
  renderTotal(total);
  saveOrder(order);
  return null;
};
```

> 💡 **関数名に「と」が入ったら、分割のサイン**です。
> 「バリデーション**と**計算**と**表示をする関数」は、3つに分けられます。

### 関数の適切な長さ

**画面に収まる長さ（20行程度）**を目安にしてください。スクロールしないと全体が見えない関数は、分割を検討します。

---

## ✍️ 手を動かす⑥ ─ 実践：料金計算モジュール

`js/app.js` に書いてください。

> 🖊 **手で打ってください。** 完成形は `code/02-03/js/app.js`（📋 コピペ可）。

```javascript
// ==========================================================
// KOMOREBI COFFEE 料金計算
// ==========================================================

const TAX_RATE = 0.1;
const MEMBER_DISCOUNT = 0.1;
const FREE_DELIVERY_THRESHOLD = 3000;
const DELIVERY_FEE = 400;

/**
 * 商品の小計を計算する
 * @param {Array<{price: number, quantity: number}>} items
 * @returns {number} 小計（税抜き）
 */
const calcSubtotal = (items) => {
  let sum = 0;
  for (const item of items) {
    sum += item.price * item.quantity;
  }
  return sum;
};

/**
 * 会員割引を適用する
 */
const applyMemberDiscount = (amount, isMember) => {
  if (!isMember) return amount;
  return amount * (1 - MEMBER_DISCOUNT);
};

/**
 * 配送料を求める
 */
const calcDeliveryFee = (subtotal) => {
  if (subtotal >= FREE_DELIVERY_THRESHOLD) return 0;
  return DELIVERY_FEE;
};

/**
 * 税込み金額にする
 */
const withTax = (amount) => Math.floor(amount * (1 + TAX_RATE));

/**
 * 注文の合計を計算する（メインの処理）
 */
const calcOrderTotal = (items, isMember = false) => {
  const subtotal = calcSubtotal(items);
  const discounted = applyMemberDiscount(subtotal, isMember);
  const delivery = calcDeliveryFee(discounted);
  const total = withTax(discounted + delivery);

  return {
    subtotal: Math.floor(subtotal),
    discount: Math.floor(subtotal - discounted),
    delivery,
    total,
  };
};

// ==========================================================
// 動作確認
// ==========================================================

const cart = [
  { name: "ハンドドリップ", price: 600, quantity: 2 },
  { name: "カフェラテ",     price: 600, quantity: 1 },
  { name: "本日のケーキ",   price: 550, quantity: 2 },
];

console.log("一般:", calcOrderTotal(cart, false));
console.log("会員:", calcOrderTotal(cart, true));
console.table(cart);
```

**この設計のポイント**

| 工夫 | 理由 |
| --- | --- |
| 定数を上部にまとめた | 税率変更が1行で済む |
| 関数を1つ1つ小さくした | 単体で動作確認できる |
| `calcOrderTotal` が手順書になっている | 読めば流れがわかる |
| 戻り値をオブジェクトにした | 内訳も返せる |
| JSDoc コメント（`/** */`）を付けた | VS Code が型を推論してくれる |

> 💡 **JSDoc コメントを書くと、VS Code で補完が効きます。** 関数名にマウスを乗せると説明が出ます。
> 型のない JavaScript でも、これでかなり安全に書けます。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `is not defined` | スコープの外から呼んでいる / 定義前に使った | スコープと定義位置を確認 |
| コールバックが即実行される | `()` を付けて渡している | `func` を渡す（`func()` ではなく） |
| 戻り値が `undefined` | `return` を書き忘れた | `return` を追加 |
| アロー関数でオブジェクトを返せない | `{}` が本体と解釈された | `() => ({ ... })` と囲む |
| 引数が足りずに `NaN` | 引数のチェックがない | デフォルト引数を書く |
| `setTimeout` の中で値が全部同じ | `var` を使っている | `let` にする |
| 関数が長すぎて読めない | 複数の仕事をしている | 仕事ごとに分割する |

---

## 🤖 AIに聞いてみよう

### ① 関数の分割方針を相談する

```text
以下は、私が書いた JavaScript の関数です。
1つの関数が長くなってしまい、分割すべきか悩んでいます。

（関数のコードを貼る）

1. この関数は、いくつの「仕事」をしていますか？
2. どう分割すべきか、方針を教えてください
3. 分割後の各関数に、どんな名前を付けるとよいですか（候補を3つずつ）
4. 分割しないほうがよいケースもありますか？

分割後のコードは書かないでください。方針と命名だけ教えてください。
私が自分で書きます。
```

### ② スコープの理解を試す

```text
JavaScript のスコープについて理解を確認したいです。

以下の観点を含む、コードを読んで出力を予想する問題を6問作ってください。

- ブロックスコープ（const / let）
- var との違い
- 関数スコープと入れ子
- for ループ内での let と var の違い
- クロージャの基本

各問題は「このコードの出力は？」の形式で、まだ答えは書かないでください。
私が回答したら採点し、間違えた場合は「なぜそうなるか」を説明してください。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

以下の関数を、**アロー関数**に書き換えてください。可能なら1行にしてください。

```javascript
function double(n) {
  return n * 2;
}

function isEven(n) {
  return n % 2 === 0;
}

function makeGreeting(name, time) {
  if (time < 12) {
    return "おはよう、" + name;
  } else {
    return "こんにちは、" + name;
  }
}

function createUser(name) {
  return { name: name, createdAt: new Date() };
}
```

<details>
<summary>答えを見る</summary>

```javascript
const double = (n) => n * 2;

const isEven = (n) => n % 2 === 0;

const makeGreeting = (name, time) =>
  time < 12 ? `おはよう、${name}` : `こんにちは、${name}`;

const createUser = (name) => ({ name, createdAt: new Date() });
```

**ポイント**
- `isEven` は `n % 2 === 0` がすでに真偽値を返すので、`if` は不要
- `makeGreeting` は三項演算子で1行に。テンプレートリテラルも使う
- `createUser` はオブジェクトを返すので `()` で囲む。`name: name` は `name` に省略できる

</details>

### 演習2（必須）

以下のコードの出力を予想してから、実行して確認してください。

```javascript
const x = "グローバル";

const outer = () => {
  const x = "外側";

  const inner = () => {
    console.log(x);
  };

  inner();
};

outer();
console.log(x);

// ---

for (var i = 0; i < 3; i++) {
  setTimeout(() => console.log("var:", i), 0);
}
for (let j = 0; j < 3; j++) {
  setTimeout(() => console.log("let:", j), 0);
}
```

<details>
<summary>答えを見る</summary>

```
外側
グローバル
var: 3
var: 3
var: 3
let: 0
let: 1
let: 2
```

**解説**
- `inner` の中に `x` がないので、外側の `outer` の `x` を探しに行く → `"外側"`
- グローバルの `x` は上書きされていない → `"グローバル"`
- `var` は関数スコープなので、ループ全体で**1つの `i`** を共有する。setTimeout が動くころには `i` は 3 になっている
- `let` はブロックスコープなので、**各周回ごとに新しい `j`** が作られる

</details>

### 演習3（挑戦）

**バリデーション関数群**を作ってください。

```javascript
// 以下の関数を実装してください。すべて「エラーメッセージ or null」を返します。

const validateRequired = (value, label) => {
  // 空文字・null・undefined ならエラー
};

const validateMaxLength = (value, max, label) => {
  // max 文字を超えたらエラー
};

const validateEmail = (value) => {
  // @ を含み、@ の後に . があるか（簡易チェック）
};

const validateNumberRange = (value, min, max, label) => {
  // 数値に変換できない、または範囲外ならエラー
};

// これらをまとめて実行する関数
const validateForm = (form) => {
  // form = { name: "太郎", email: "a@b.com", guests: "3" }
  // エラーメッセージの配列を返す（エラーがなければ空配列）
};

// テスト
console.log(validateForm({ name: "",      email: "abc",     guests: "0" }));
console.log(validateForm({ name: "太郎",  email: "a@b.com", guests: "3" }));
```

<details>
<summary>答えを見る</summary>

```javascript
const validateRequired = (value, label) => {
  if (value === null || value === undefined || String(value).trim() === "") {
    return `${label}を入力してください`;
  }
  return null;
};

const validateMaxLength = (value, max, label) => {
  if (String(value).length > max) {
    return `${label}は${max}文字以内で入力してください`;
  }
  return null;
};

const validateEmail = (value) => {
  const at = value.indexOf("@");
  if (at < 1) return "メールアドレスの形式が正しくありません";
  if (value.indexOf(".", at) < at + 2) return "メールアドレスの形式が正しくありません";
  return null;
};

const validateNumberRange = (value, min, max, label) => {
  const n = Number(value);
  if (Number.isNaN(n)) return `${label}は数値で入力してください`;
  if (n < min || n > max) return `${label}は${min}〜${max}の範囲で入力してください`;
  return null;
};

const validateForm = (form) => {
  const errors = [
    validateRequired(form.name, "お名前"),
    validateMaxLength(form.name, 50, "お名前"),
    validateRequired(form.email, "メールアドレス"),
    validateEmail(form.email),
    validateNumberRange(form.guests, 1, 8, "人数"),
  ];
  // null（エラーなし）を取り除く
  return errors.filter((e) => e !== null);
};
```

**設計のポイント**
- 各バリデーション関数は**1つのことだけ**をチェックする
- 戻り値を「エラーメッセージ or null」で統一すると、まとめやすい
- `validateForm` は、チェック項目を並べるだけの**手順書**になる
- 新しいチェックを足すのが簡単（配列に1行足すだけ）

`filter` は次のレッスン（2-5）で学びます。いまは「null を取り除く」と理解すればOKです。

</details>

---

## ✅ 章末チェック

- [ ] 関数にする3つのメリットを言える
- [ ] アロー関数の省略記法を書ける
- [ ] アロー関数でオブジェクトを返すときの注意点を知っている
- [ ] スコープの「内から外は見える、外から内は見えない」を説明できる
- [ ] `var` を使わない理由を、`for` + `setTimeout` の例で説明できる
- [ ] コールバック関数に `()` を付けてはいけない理由を説明できる
- [ ] 「1つの関数は1つの仕事」の原則を実践できる
- [ ] 関数名に「と」が入ったら分割のサイン、と知っている

---

**前 → [2-2 条件分岐と繰り返し](02-02-control-flow.md)　｜　次 → [2-4 配列とオブジェクト](02-04-array-object.md)**
