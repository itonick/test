# 2-1 JavaScriptを動かす・値と変数

> 🎯 **このレッスンのゴール**
> - JavaScriptを読み込む正しい方法を身につける
> - `console.log()` でデバッグできるようになる
> - 変数の3つの宣言（`const` / `let` / `var`）を使い分けられる
> - データ型の違いと、型の落とし穴を知る

所要 90分 / 難度 🟢
完成コード: [`code/02-01/`](../code/02-01/)

---

## 📖 JavaScript は「動き」の担当

第1部でHTMLとCSSを学びました。役割分担はこうです。

| 言語 | 役割 | たとえ |
| --- | --- | --- |
| HTML | 構造 | 骨組み |
| CSS | 見た目 | 服 |
| **JavaScript** | **動き・処理** | **筋肉と脳** |

JavaScript でできること。

- ボタンを押したら何かが起きる
- 入力内容をリアルタイムでチェックする
- サーバーからデータを取ってきて画面に表示する
- データをブラウザに保存する

**すべて「ブラウザの中」で動きます。** サーバー側の処理（PHP）は第3部です。

---

## ✍️ 手を動かす① ─ 読み込み方

### 準備

`htdocs/js-lesson/` フォルダを作り、以下の2ファイルを置いてください。

```
js-lesson/
├─ index.html
└─ js/
   └─ app.js
```

**index.html**（📋 コピペ可）

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JavaScript 練習</title>
</head>
<body>
  <h1>JavaScript 練習</h1>
  <p id="output">ここに結果が出ます</p>
  <button id="btn" type="button">クリック</button>

  <script src="js/app.js"></script>
</body>
</html>
```

**js/app.js**

```javascript
console.log("読み込まれました");
```

Live Server で開き、`F12` → **Console** タブを見てください。「読み込まれました」と出れば成功です。

> 🆘 **ここで詰まったら**（Consoleに何も出ない＝JSが動いていない）
> - **まず確認**：① `<script src="js/app.js">` の**パスが合っているか**（`js/` の付け忘れ・つづり）② `app.js` を保存したか ③ Consoleに赤いエラー（`Failed to load resource` など）が出ていないか ④ 開いているのは Live Server（`127.0.0.1:5500`）か
> - **切り分け**：`app.js` の代わりに `<script>console.log("test");</script>` を直接 `<body>` 末尾に書いて出るか試すと、「読み込みの問題」か「JSの問題」かが分かります
> - **直らなければ、AIにこう聞く**（HTMLとファイル構成を貼る）：
>   「JavaScriptが実行されず、Consoleに何も出ません。script の読み込みに問題がないか、確認手順つきで教えてください」

### `<script>` を置く場所

```html
<!-- ❌ head に置くと、HTMLより先に実行される -->
<head>
  <script src="js/app.js"></script>
</head>

<!-- ✅ 方法1：</body> の直前 -->
<body>
  ...
  <script src="js/app.js"></script>
</body>

<!-- ✅ 方法2：head に置いて defer を付ける -->
<head>
  <script src="js/app.js" defer></script>
</head>
```

> ⚠️ **`<head>` に `defer` なしで置くと、必ず事故ります。**
> HTMLがまだ読み込まれていない時点でJSが動くため、**要素を取得できません**。
> `Cannot read properties of null` という、第2部で最も多いエラーの原因がこれです。

| 書き方 | 実行タイミング |
| --- | --- |
| `<script src="...">`（head） | **すぐ実行**。HTMLの読み込みも止まる |
| `<script src="..." defer>` | HTMLを全部読んでから実行。**推奨** |
| `<script src="..." async>` | 読み込み完了次第すぐ実行（順序保証なし） |
| `</body>` の直前 | HTMLを読んだ後なので安全 |

**この教材では `defer` を使います。**

---

## ✍️ 手を動かす② ─ `console.log()` が最強のツール

```javascript
console.log("文字を出す");
console.log(123);
console.log("値は", 456, "です");   // カンマで複数出せる

console.table([{name: "太郎", age: 20}, {name: "花子", age: 22}]);  // 表で出る
console.error("エラー用（赤く出る）");
console.warn("警告用（黄色く出る）");
```

> 💡 **`console.log()` は「デバッグの命綱」です。**
> プログラムは「思った通り」ではなく「書いた通り」に動きます。
> **どこで、何の値になっているか**を確認する手段が `console.log()` です。
>
> **迷ったら出力する。** これを癖にしてください。

### 変数名と値をセットで出す小技

```javascript
const name = "太郎";
const age = 20;

console.log({ name, age });   // { name: "太郎", age: 20 } と出る
```

`{ }` で囲むと、**変数名も一緒に**表示されます。複数の値を確認するときに便利です。

---

## ✍️ 手を動かす③ ─ 変数

### 3つの宣言方法

```javascript
const name = "太郎";   // 再代入できない
let age = 20;          // 再代入できる
var old = "使わない";  // 古い書き方
```

| 宣言 | 再代入 | 再宣言 | スコープ | 使うか |
| --- | --- | --- | --- | --- |
| **`const`** | ❌ | ❌ | ブロック `{}` | **基本これ** |
| **`let`** | ✅ | ❌ | ブロック `{}` | 変わる値のみ |
| `var` | ✅ | ✅ | 関数 | **使わない** |

### 使い分けのルール

> **まず `const` で書く。再代入が必要になったら `let` に変える。`var` は使わない。**

```javascript
const TAX_RATE = 0.1;    // 変わらない値
const user = "太郎";      // 変わらない値

let count = 0;           // 増えていく値
count = count + 1;       // OK

const price = 100;
price = 200;             // ❌ TypeError: Assignment to constant variable.
```

> 💡 **なぜ `const` を優先するのか**
> 「この変数は途中で変わらない」と保証されるので、**コードを読むときに追いかけなくて済みます**。
> 変数が50個あるコードで、どれが変わるか全部追うのは大変です。`const` なら考えなくてよくなります。

### `const` でも中身は変えられる（重要）

```javascript
const arr = [1, 2, 3];
arr.push(4);        // ✅ OK！中身の変更は可能
console.log(arr);   // [1, 2, 3, 4]

arr = [5, 6];       // ❌ NG！箱ごとの入れ替えは不可
```

**`const` は「箱を入れ替えられない」であって、「中身を変えられない」ではありません。**

配列やオブジェクトは、`const` で宣言しても中身を追加・変更できます。ここは初学者が必ず混乱する箇所です。

### 変数名のルール

```javascript
// ✅ 良い名前
const userName = "太郎";
const totalPrice = 1200;
const isLoggedIn = true;
const itemList = [];

// ❌ 悪い名前
const a = "太郎";        // 何の値かわからない
const data = 1200;       // 抽象的すぎる
const user_name = "太郎"; // JSでは camelCase が慣習
const 名前 = "太郎";      // 動くが、実務では使わない
```

**命名の慣習**

| 対象 | 書き方 | 例 |
| --- | --- | --- |
| 変数・関数 | camelCase | `userName` `getTotalPrice` |
| 定数（変わらない設定値） | UPPER_SNAKE | `TAX_RATE` `MAX_COUNT` |
| クラス | PascalCase | `UserAccount` |
| 真偽値 | `is` / `has` / `can` で始める | `isActive` `hasError` |

> 💡 **命名はコードの品質を決めます。**
> 良い名前が付いていれば、コメントがなくても読めます。迷ったら、`AIプロンプト集 C-4`（命名相談）を使ってください。

---

## ✍️ 手を動かす④ ─ データ型

### 基本の6つ

```javascript
// 1. 文字列（string）
const name = "太郎";
const greeting = 'こんにちは';
const message = `${name}さん、${greeting}`;  // テンプレートリテラル

// 2. 数値（number）
const age = 20;
const price = 1980.5;

// 3. 真偽値（boolean）
const isActive = true;
const isDeleted = false;

// 4. 未定義（undefined）— 値が入っていない
let x;
console.log(x);   // undefined

// 5. 空（null）— 意図的に「なし」を入れた
const found = null;

// 6. 配列・オブジェクト（object）
const list = [1, 2, 3];
const user = { name: "太郎", age: 20 };
```

### 型を確認する

```javascript
console.log(typeof "太郎");    // "string"
console.log(typeof 20);        // "number"
console.log(typeof true);      // "boolean"
console.log(typeof undefined); // "undefined"
console.log(typeof null);      // "object" ← JavaScript の有名なバグ
console.log(typeof [1,2]);     // "object"
console.log(typeof {a:1});     // "object"
```

> ⚠️ **`typeof null` が `"object"` になるのは、JavaScript の歴史的なバグ**です。修正すると既存サイトが壊れるため、直されていません。豆知識として覚えておいてください。

### テンプレートリテラル（バッククォート）

```javascript
const name = "太郎";
const age = 20;

// ❌ 昔の書き方（読みにくい）
const msg1 = name + "さんは" + age + "歳です";

// ✅ テンプレートリテラル
const msg2 = `${name}さんは${age}歳です`;

// 改行もそのまま書ける
const html = `
  <div class="card">
    <h3>${name}</h3>
    <p>${age}歳</p>
  </div>
`;
```

**バッククォート（`` ` ``）は、キーボードの `Shift + @` で入力できます**（日本語キーボードの場合）。

> 💡 **文字列の連結は、これから常にテンプレートリテラルを使ってください。**
> DOM操作（2-6）でHTMLを組み立てるときに、圧倒的に読みやすくなります。

---

## ✍️ 手を動かす⑤ ─ 演算子と型の落とし穴

### 算術演算子

```javascript
console.log(7 + 3);   // 10
console.log(7 - 3);   // 4
console.log(7 * 3);   // 21
console.log(7 / 3);   // 2.333...
console.log(7 % 3);   // 1  （余り）
console.log(7 ** 3);  // 343（べき乗）
```

### 比較演算子 ─ `==` を使ってはいけない

```javascript
console.log(1 == "1");    // true  ← 型を勝手に変換してしまう
console.log(1 === "1");   // false ← 型も比較する（正しい）

console.log(0 == false);      // true  😱
console.log("" == false);     // true  😱
console.log(null == undefined); // true 😱

console.log(0 === false);     // false ✅
console.log("" === false);    // false ✅
```

> ⚠️ **比較は必ず `===` と `!==` を使ってください。**
> `==` は型を勝手に変換するため、意図しない結果になります。
> **`==` を使ってよい場面はありません。** 迷わず `===` です。

### 数値と文字列の落とし穴

```javascript
console.log("5" + 3);   // "53"  ← 文字列の連結になる
console.log("5" - 3);   // 2     ← 引き算は数値に変換される
console.log("5" * 3);   // 15
```

**`+` だけが「連結」の意味を持つため、特別扱い**されます。

これは実務で本当に起きます。フォームから受け取った値は**すべて文字列**だからです。

```javascript
const price = "1000";   // input から取得した値は文字列
const tax = "100";

console.log(price + tax);              // "1000100" 😱
console.log(Number(price) + Number(tax)); // 1100 ✅
```

### 文字列 → 数値の変換

```javascript
Number("123")      // 123
Number("12.5")     // 12.5
Number("abc")      // NaN（Not a Number）
Number("")         // 0
parseInt("123px")  // 123（数字部分だけ取り出す）
parseFloat("12.5em") // 12.5
+"123"             // 123（短縮形。単項プラス）
```

### `NaN` に注意

```javascript
const n = Number("abc");
console.log(n);              // NaN
console.log(n === NaN);      // false ← NaN は自分自身とも等しくない！
console.log(Number.isNaN(n));// true  ← これで判定する
```

> ⚠️ **`NaN` は、自分自身と比較しても `false` になります。**
> 判定には `Number.isNaN()` を使ってください。

### 論理演算子

```javascript
const a = true, b = false;

console.log(a && b);   // false（かつ）
console.log(a || b);   // true （または）
console.log(!a);       // false（否定）
```

### 便利な演算子

```javascript
// null合体演算子（??）：左が null / undefined なら右を使う
const name = inputName ?? "ゲスト";

// オプショナルチェーン（?.）：途中が null でもエラーにならない
const city = user?.address?.city;   // user や address が無くても落ちない

// 論理OR（||）：左が「偽っぽい値」なら右を使う
const count = inputCount || 0;
```

> ⚠️ **`??` と `||` の違い**
>
> ```javascript
> const a = 0 ?? 10;   // 0  （0 は null でも undefined でもない）
> const b = 0 || 10;   // 10 （0 は「偽っぽい値」なので右が使われる）
> ```
>
> **数値の 0 や空文字を有効な値として扱いたいときは `??`** を使ってください。
> 「入力が0なのに10になってしまう」というバグは、これが原因です。

### 「偽っぽい値」（falsy）の一覧

以下は `if` で `false` として扱われます。**これだけ**です。

```javascript
false
0
-0
0n        // BigInt の 0
""        // 空文字
null
undefined
NaN
```

**空の配列 `[]` と空のオブジェクト `{}` は `true` です。** これは間違えやすいので注意してください。

```javascript
if ([]) { console.log("実行される"); }   // 空配列は truthy
if ({}) { console.log("実行される"); }   // 空オブジェクトも truthy
```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `Cannot read properties of null` | script が head にあり、HTMLより先に実行された | `defer` を付ける |
| 数値の足し算が文字列連結になる | 片方が文字列 | `Number()` で変換 |
| `if` の判定がおかしい | `==` を使っている | `===` に変える |
| 0 が入力されたのに初期値になる | `||` を使っている | `??` に変える |
| `Assignment to constant variable` | `const` に再代入した | `let` にするか、代入をやめる |
| console.log が出ない | Console タブを見ていない / フィルタが掛かっている | F12 → Console |
| `is not defined` | 変数名のスペルミス / 定義より前で使った | 定義位置とつづりを確認 |

---

## 🤖 AIに聞いてみよう

### ① 型変換の挙動を体系的に理解する

```text
JavaScript の暗黙の型変換について理解したいです。

以下の式が、それぞれ何になるか、そして「なぜそうなるか」を教えてください。

1. "5" + 3
2. "5" - 3
3. 1 == "1"
4. 0 == false
5. [] == false
6. null == undefined
7. NaN === NaN
8. 0 || "default"
9. 0 ?? "default"

そのうえで、「こういう事故を防ぐために、初学者が守るべきルール」を
3つにまとめてください。
```

### ② 命名をレビューさせる

```text
以下は、私が書いた JavaScript の変数宣言です。

（コードを貼る）

命名の観点でレビューしてください。

1. 何を表しているかわかりにくい名前
2. 慣習（camelCase、is/has で始める等）に合っていない名前
3. const にすべきなのに let になっているもの

それぞれ、より良い名前の候補を3つずつ挙げてください。
コード全体を書き直す必要はありません。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

`js/app.js` に以下を書いて、Console で結果を確認してください。

```javascript
// 1. 自分の名前と年齢を変数に入れる（適切な const / let を選ぶ）

// 2. テンプレートリテラルで「〇〇さんは〇〇歳です」と出力する

// 3. 来年の年齢を計算して出力する

// 4. 名前の文字数を出力する（ヒント: .length）

// 5. 年齢が20歳以上かどうかを true / false で出力する
```

### 演習2（必須）

以下の出力結果を**予想してから**、実行して確認してください。

```javascript
console.log("10" + 5);
console.log("10" - 5);
console.log(10 + 5 + "個");
console.log("個" + 10 + 5);
console.log(true + 1);
console.log([] + []);
console.log(Number("") === 0);
console.log(Number.isNaN(Number("abc")));
```

<details>
<summary>答えを見る（予想してから開いてください）</summary>

```
"105"     ← + は連結を優先
5         ← - は数値変換
"15個"    ← 左から順に。10+5=15、それに"個"を連結
"個105"   ← 左から順に。"個"+10="個10"、それに5を連結
2         ← true は 1 に変換される
""        ← 空配列は空文字に変換される
true      ← Number("") は 0
true      ← "abc" は数値にできないので NaN
```

**3番目と4番目の違いが重要です。** JavaScript は**左から順に**評価します。
「なぜ結果が違うのか」を説明できるようになってください。

</details>

### 演習3（挑戦）

以下の要件を満たすコードを書いてください。

```javascript
// フォームから受け取ったつもりの値（すべて文字列）
const priceStr = "1200";
const quantityStr = "3";
const discountStr = "";   // 未入力

// 要件:
// 1. 合計金額（price × quantity）を計算する
// 2. 割引額を引く（未入力なら 0 として扱う）
// 3. 消費税10%を加える
// 4. 「合計: 3,960円」の形式で出力する（3桁区切り）

// ヒント: toLocaleString() を調べてください
```

<details>
<summary>答えを見る</summary>

```javascript
const priceStr = "1200";
const quantityStr = "3";
const discountStr = "";

const price = Number(priceStr);
const quantity = Number(quantityStr);
// 空文字は Number() で 0 になるが、明示的に書くと意図が伝わる
const discount = discountStr === "" ? 0 : Number(discountStr);

const subtotal = price * quantity - discount;
const total = Math.round(subtotal * 1.1);

console.log(`合計: ${total.toLocaleString()}円`);  // 合計: 3,960円
```

**ポイント**
- 文字列のまま計算すると `"1200" * "3"` は動く（`*` は数値変換される）が、
  `+` を使う瞬間に破綻する。**受け取ったら即 `Number()` する**のが安全
- 消費税の計算では、`Math.round()` で端数処理をする
- `toLocaleString()` で 3桁区切りになる

</details>

---

## ✅ 章末チェック

- [ ] `<script>` に `defer` を付ける理由を説明できる
- [ ] `console.log()` の重要性を理解した
- [ ] `const` を優先し、`var` を使わない理由を言える
- [ ] `const` の配列に `push` できる理由を説明できる
- [ ] `==` ではなく `===` を使う理由を言える
- [ ] `"5" + 3` と `"5" - 3` の違いを説明できる
- [ ] `??` と `||` の違いを説明できる
- [ ] falsy な値を6つ以上言える
- [ ] `NaN` の判定に `Number.isNaN()` を使うことを知っている

---

**次のレッスン → [2-2 条件分岐と繰り返し](02-02-control-flow.md)**
